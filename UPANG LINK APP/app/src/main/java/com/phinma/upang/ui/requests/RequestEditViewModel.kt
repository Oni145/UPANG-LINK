package com.phinma.upang.ui.requests

import android.util.Log
import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.phinma.upang.data.model.RequestDetails
import com.phinma.upang.data.model.RequestUpdateData
import com.phinma.upang.data.model.RequirementUpdateItem
import com.phinma.upang.data.repository.RequestRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class RequestEditViewModel @Inject constructor(
    private val repository: RequestRepository,
    savedStateHandle: SavedStateHandle
) : ViewModel() {

    companion object {
        private const val TAG = "RequestEditViewModel"
    }

    private val requestId: String = checkNotNull(savedStateHandle["requestId"])

    private val _requestDetails = MutableLiveData<RequestDetails>()
    val requestDetails: LiveData<RequestDetails> = _requestDetails

    private val _loading = MutableLiveData<Boolean>()
    val loading: LiveData<Boolean> = _loading

    private val _errorMessage = MutableLiveData<String?>()
    val errorMessage: LiveData<String?> = _errorMessage

    private val _saveSuccess = MutableLiveData<Boolean>()
    val saveSuccess: LiveData<Boolean> = _saveSuccess

    init {
        loadRequestDetails()
    }

    fun loadRequestDetails() {
        viewModelScope.launch {
            try {
                _loading.value = true
                _errorMessage.value = null

                val result = repository.getRequestDetails(requestId)
                if (result.isSuccess) {
                    _requestDetails.value = result.getOrNull()
                } else {
                    _errorMessage.value = result.exceptionOrNull()?.message ?: "Failed to load request details"
                }
            } catch (e: Exception) {
                _errorMessage.value = e.message ?: "Failed to load request details"
            } finally {
                _loading.value = false
            }
        }
    }

    fun saveRequestDetails(purpose: String, requirements: List<RequirementUpdateItem>) {
        viewModelScope.launch {
            try {
                _loading.value = true
                _errorMessage.value = null

                val updateData = RequestUpdateData(
                    purpose = purpose,
                    requirements = requirements
                )

                val result = repository.updateRequestDetails(requestId, updateData)
                if (result.isSuccess) {
                    _saveSuccess.value = true
                } else {
                    _errorMessage.value = result.exceptionOrNull()?.message ?: "Failed to save request details"
                    _saveSuccess.value = false
                }
            } catch (e: Exception) {
                Log.e(TAG, "Error saving request details", e)
                _errorMessage.value = e.message ?: "Failed to save request details"
                _saveSuccess.value = false
            } finally {
                _loading.value = false
            }
        }
    }
} 