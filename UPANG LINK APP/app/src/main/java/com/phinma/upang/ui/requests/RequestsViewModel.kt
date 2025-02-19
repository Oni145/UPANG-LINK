package com.phinma.upang.ui.requests

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.phinma.upang.data.model.*
import com.phinma.upang.data.repository.RequestRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.launch
import java.io.File
import javax.inject.Inject

@HiltViewModel
class RequestsViewModel @Inject constructor(
    private val repository: RequestRepository
) : ViewModel() {

    private val _requests = MutableLiveData<List<Request>>()
    val requests: LiveData<List<Request>> = _requests

    private val _requestTypes = MutableLiveData<List<RequestType>>()
    val requestTypes: LiveData<List<RequestType>> = _requestTypes

    private val _selectedRequest = MutableLiveData<Request>()
    val selectedRequest: LiveData<Request> = _selectedRequest

    private val _statistics = MutableLiveData<RequestStatistics>()
    val statistics: LiveData<RequestStatistics> = _statistics

    private val _uiState = MutableLiveData<RequestUiState>()
    val uiState: LiveData<RequestUiState> = _uiState

    private val _isLoading = MutableLiveData<Boolean>()
    val isLoading: LiveData<Boolean> = _isLoading

    private val _error = MutableLiveData<String?>()
    val error: LiveData<String?> = _error

    init {
        loadRequests()
        loadRequestTypes()
        loadStatistics()
    }

    fun loadRequests(filter: RequestFilter? = null) {
        viewModelScope.launch {
            try {
                _isLoading.value = true
                _error.value = null
                
                repository.getRequests(filter).fold(
                    onSuccess = { requests ->
                        _requests.value = requests
                    },
                    onFailure = { e ->
                        _error.value = e.message ?: "Failed to load requests"
                    }
                )
            } catch (e: Exception) {
                _error.value = e.message ?: "Failed to load requests"
            } finally {
                _isLoading.value = false
            }
        }
    }

    fun loadRequestTypes() {
        viewModelScope.launch {
            repository.getRequestTypes()
                .onSuccess { types ->
                    _requestTypes.value = types
                }
                .onFailure { error ->
                    _uiState.value = RequestUiState.Error(error.message ?: "Failed to load request types")
                }
        }
    }

    fun loadRequestDetails(id: String) {
        viewModelScope.launch {
            _uiState.value = RequestUiState.Loading
            repository.getRequest(id)
                .onSuccess { request ->
                    _selectedRequest.value = request
                    _uiState.value = RequestUiState.Success
                }
                .onFailure { error ->
                    _uiState.value = RequestUiState.Error(error.message ?: "Failed to load request details")
                }
        }
    }

    fun createRequest(typeId: Int, purpose: String, files: List<File>) {
        viewModelScope.launch {
            _uiState.value = RequestUiState.Loading
            repository.createRequest(typeId, purpose, files)
                .onSuccess { response ->
                    _selectedRequest.value = response.request
                    _uiState.value = RequestUiState.RequestCreated(response)
                    loadRequests() // Refresh the list
                }
                .onFailure { error ->
                    _uiState.value = RequestUiState.Error(error.message ?: "Failed to create request")
                }
        }
    }

    fun uploadRequirement(requestId: String, requirementId: String, file: File) {
        viewModelScope.launch {
            _uiState.value = RequestUiState.Loading
            repository.uploadRequirement(requestId, requirementId, file)
                .onSuccess {
                    loadRequestDetails(requestId) // Refresh request details
                    _uiState.value = RequestUiState.Success
                }
                .onFailure { error ->
                    _uiState.value = RequestUiState.Error(error.message ?: "Failed to upload requirement")
                }
        }
    }

    fun deleteRequirement(requestId: String, requirementId: String) {
        viewModelScope.launch {
            _uiState.value = RequestUiState.Loading
            repository.deleteRequirement(requestId, requirementId)
                .onSuccess {
                    loadRequestDetails(requestId) // Refresh request details
                    _uiState.value = RequestUiState.Success
                }
                .onFailure { error ->
                    _uiState.value = RequestUiState.Error(error.message ?: "Failed to delete requirement")
                }
        }
    }

    fun cancelRequest(requestId: String) {
        viewModelScope.launch {
            try {
                _isLoading.value = true
                _error.value = null

                repository.cancelRequest(requestId).fold(
                    onSuccess = {
                        loadRequests() // Refresh the list after cancellation
                    },
                    onFailure = { e ->
                        _error.value = e.message ?: "Failed to cancel request"
                        _isLoading.value = false
                    }
                )
            } catch (e: Exception) {
                _error.value = e.message ?: "Failed to cancel request"
                _isLoading.value = false
            }
        }
    }

    private fun loadStatistics() {
        viewModelScope.launch {
            repository.getRequestStatistics()
                .onSuccess { stats ->
                    _statistics.value = stats
                }
                .onFailure { error ->
                    _uiState.value = RequestUiState.Error(error.message ?: "Failed to load statistics")
                }
        }
    }

    sealed class RequestUiState {
        object Loading : RequestUiState()
        object Success : RequestUiState()
        data class Error(val message: String) : RequestUiState()
        data class RequestCreated(val response: RequestCreateResponse) : RequestUiState()
    }
} 