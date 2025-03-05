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
class CreateRequestViewModel @Inject constructor(
    private val repository: RequestRepository
) : ViewModel() {

    private val _requestTypes = MutableLiveData<List<RequestType>>()
    val requestTypes: LiveData<List<RequestType>> = _requestTypes

    private val _selectedType = MutableLiveData<RequestType>()
    val selectedType: LiveData<RequestType> = _selectedType

    private val _requirements = MutableLiveData<List<Requirement>>()
    val requirements: LiveData<List<Requirement>> = _requirements

    private val _uiState = MutableLiveData<CreateRequestUiState>()
    val uiState: LiveData<CreateRequestUiState> = _uiState

    private val files = mutableMapOf<String, File>()

    init {
        loadRequestTypes()
    }

    private fun loadRequestTypes() {
        viewModelScope.launch {
            _uiState.value = CreateRequestUiState.Loading
            repository.getRequestTypes()
                .onSuccess { types ->
                    _requestTypes.value = types
                    _uiState.value = CreateRequestUiState.Success
                }
                .onFailure { error ->
                    _uiState.value = CreateRequestUiState.Error(error.message ?: "Failed to load request types")
                }
        }
    }

    fun selectRequestType(type: RequestType) {
        _selectedType.value = type
        viewModelScope.launch {
            repository.getRequestRequirements(type.type_id)
                .onSuccess { requirements ->
                    _requirements.value = requirements
                }
                .onFailure { error ->
                    _uiState.value = CreateRequestUiState.Error(error.message ?: "Failed to load requirements")
                }
        }
    }

    fun addFile(requirement: RequirementItem, file: File) {
        files[requirement.id] = file
    }

    fun removeFile(requirement: RequirementItem) {
        files.remove(requirement.id)?.delete()
    }

    fun createRequest(purpose: String) {
        viewModelScope.launch {
            val type = _selectedType.value ?: run {
                _uiState.value = CreateRequestUiState.Error("Please select a request type")
                return@launch
            }

            if (purpose.isBlank()) {
                _uiState.value = CreateRequestUiState.Error("Please enter a purpose")
                return@launch
            }

            // Validate required files
            val missingRequirements = _requirements.value?.filter { requirement ->
                requirement.isRequired && !files.containsKey(requirement.id)
            } ?: emptyList()

            if (missingRequirements.isNotEmpty()) {
                val message = "Please upload the following required files:\n" +
                    missingRequirements.joinToString("\n") { "- ${it.name}" }
                _uiState.value = CreateRequestUiState.Error(message)
                return@launch
            }

            _uiState.value = CreateRequestUiState.Loading
            repository.createRequest(type.type_id, purpose, files.values.toList())
                .onSuccess { response ->
                    _uiState.value = CreateRequestUiState.RequestCreated(response.request)
                    // Clear files after successful creation
                    files.values.forEach { it.delete() }
                    files.clear()
                }
                .onFailure { error ->
                    _uiState.value = CreateRequestUiState.Error(error.message ?: "Failed to create request")
                }
        }
    }

    sealed class CreateRequestUiState {
        object Loading : CreateRequestUiState()
        object Success : CreateRequestUiState()
        data class Error(val message: String) : CreateRequestUiState()
        data class RequestCreated(val request: Request) : CreateRequestUiState()
    }
} 