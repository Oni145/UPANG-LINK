package com.phinma.upang.ui.requests

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.phinma.upang.data.model.Request
import com.phinma.upang.data.repository.RequestRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.launch
import java.io.File
import javax.inject.Inject

@HiltViewModel
class RequestDetailsViewModel @Inject constructor(
    private val repository: RequestRepository,
    savedStateHandle: SavedStateHandle
) : ViewModel() {

    private val requestId: String = checkNotNull(savedStateHandle["requestId"])

    private val _request = MutableLiveData<Request>()
    val request: LiveData<Request> = _request

    private val _loading = MutableLiveData<Boolean>()
    val loading: LiveData<Boolean> = _loading

    private val _errorMessage = MutableLiveData<String?>()
    val errorMessage: LiveData<String?> = _errorMessage

    init {
        getRequest(requestId)
    }

    fun getRequest(requestId: String) {
        viewModelScope.launch {
            try {
                _loading.value = true
                _errorMessage.value = null

                repository.getRequest(requestId)
                    .onSuccess { request ->
                        _request.value = request
                    }
                    .onFailure { error ->
                        _errorMessage.value = error.message ?: "Failed to load request"
                    }
            } catch (e: Exception) {
                _errorMessage.value = e.message ?: "Failed to load request"
            } finally {
                _loading.value = false
            }
        }
    }

    fun uploadRequirement(requestId: String, requirementId: String, file: File) {
        viewModelScope.launch {
            try {
                _loading.value = true
                _errorMessage.value = null

                repository.uploadRequirement(requestId, requirementId, file)
                    .onSuccess {
                        getRequest(requestId) // Refresh request details
                    }
                    .onFailure { error ->
                        _errorMessage.value = error.message ?: "Failed to upload requirement"
                    }
            } catch (e: Exception) {
                _errorMessage.value = e.message ?: "Failed to upload requirement"
            } finally {
                _loading.value = false
            }
        }
    }

    fun deleteRequirement(requestId: String, requirementId: String) {
        viewModelScope.launch {
            try {
                _loading.value = true
                _errorMessage.value = null

                repository.deleteRequirement(requestId, requirementId)
                    .onSuccess {
                        getRequest(requestId) // Refresh request details
                    }
                    .onFailure { error ->
                        _errorMessage.value = error.message ?: "Failed to delete requirement"
                    }
            } catch (e: Exception) {
                _errorMessage.value = e.message ?: "Failed to delete requirement"
            } finally {
                _loading.value = false
            }
        }
    }

    fun cancelRequest(requestId: String) {
        viewModelScope.launch {
            try {
                _loading.value = true
                _errorMessage.value = null

                repository.cancelRequest(requestId)
                    .onSuccess {
                        getRequest(requestId) // Refresh request details
                    }
                    .onFailure { error ->
                        _errorMessage.value = error.message ?: "Failed to cancel request"
                    }
            } catch (e: Exception) {
                _errorMessage.value = e.message ?: "Failed to cancel request"
            } finally {
                _loading.value = false
            }
        }
    }
} 