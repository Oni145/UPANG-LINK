package com.phinma.upang.ui.requests

import android.util.Log
import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.phinma.upang.data.model.Request
import com.phinma.upang.data.model.RequirementNote
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
    
    // Add LiveData for requirement notes
    private val _requirementNotes = MutableLiveData<List<RequirementNote>>()
    val requirementNotes: LiveData<List<RequirementNote>> = _requirementNotes

    // Add LiveData for request_requirement_notes table contents
    private val _requestRequirementNotes = MutableLiveData<List<RequirementNote>>()
    val requestRequirementNotes: LiveData<List<RequirementNote>> = _requestRequirementNotes

    init {
        getRequest(requestId)
        getRequirementNotes(requestId)
        getRequestRequirementNotes(requestId)
    }

    fun getRequest(requestId: String) {
        viewModelScope.launch {
            try {
                _loading.value = true
                _errorMessage.value = null

                // Validate tracking number format
                if (!requestId.matches(Regex("REQ-\\d{8}-\\d{4}")) && !requestId.matches(Regex("REQ-\\d{4}-\\d{3}"))) {
                    _errorMessage.value = "Invalid tracking number format. Expected format: REQ-YYYYMMDD-XXXX or REQ-YYYY-XXX"
                    _loading.value = false
                    return@launch
                }

                // Log the request ID for debugging
                Log.d("RequestDetailsVM", "Getting request with ID: $requestId")

                repository.getRequest(requestId)
                    .onSuccess { request ->
                        _request.value = request
                        Log.d("RequestDetailsVM", "Successfully loaded request: ${request.id}")
                    }
                    .onFailure { error ->
                        Log.e("RequestDetailsVM", "Failed to load request: ${error.message}")
                        _errorMessage.value = error.message ?: "Failed to load request"
                    }
            } catch (e: Exception) {
                Log.e("RequestDetailsVM", "Exception loading request", e)
                _errorMessage.value = e.message ?: "Failed to load request"
            } finally {
                _loading.value = false
            }
        }
    }
    
    // Add function to get requirement notes
    fun getRequirementNotes(requestId: String) {
        viewModelScope.launch {
            try {
                Log.d("RequestDetailsVM", "Getting notes for request ID: $requestId")
                
                repository.getRequestNotes(requestId)
                    .onSuccess { notes ->
                        _requirementNotes.value = notes
                        Log.d("RequestDetailsVM", "Successfully loaded ${notes.size} notes")
                    }
                    .onFailure { error ->
                        Log.e("RequestDetailsVM", "Failed to load notes: ${error.message}")
                        // Don't set error message to avoid confusing the user
                        // Just log the error and use an empty list
                        _requirementNotes.value = emptyList()
                    }
            } catch (e: Exception) {
                Log.e("RequestDetailsVM", "Exception loading notes", e)
                _requirementNotes.value = emptyList()
            }
        }
    }

    // Add function to get notes from request_requirement_notes table
    fun getRequestRequirementNotes(requestId: String) {
        viewModelScope.launch {
            try {
                Log.d("RequestDetailsVM", "Getting requirement notes for request ID: $requestId")
                
                repository.getRequestRequirementNotes(requestId)
                    .onSuccess { notes ->
                        _requestRequirementNotes.value = notes
                        Log.d("RequestDetailsVM", "Successfully loaded ${notes.size} requirement notes")
                    }
                    .onFailure { error ->
                        Log.e("RequestDetailsVM", "Failed to load requirement notes: ${error.message}")
                        // Don't set error message to avoid confusing the user
                        // Just log the error and use an empty list
                        _requestRequirementNotes.value = emptyList()
                    }
            } catch (e: Exception) {
                Log.e("RequestDetailsVM", "Exception loading requirement notes", e)
                _requestRequirementNotes.value = emptyList()
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