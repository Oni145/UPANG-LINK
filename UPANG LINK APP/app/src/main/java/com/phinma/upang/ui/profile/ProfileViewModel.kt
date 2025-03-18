package com.phinma.upang.ui.profile

import android.util.Log
import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.phinma.upang.data.model.UserProfile
import com.phinma.upang.data.repository.AuthRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class ProfileViewModel @Inject constructor(
    private val authRepository: AuthRepository
) : ViewModel() {

    private val _profileState = MutableLiveData<ProfileState>()
    val profileState: LiveData<ProfileState> = _profileState

    private val _changePasswordState = MutableLiveData<ChangePasswordState?>()
    val changePasswordState: LiveData<ChangePasswordState?> = _changePasswordState
    
    private val _studentDetailsState = MutableLiveData<StudentDetailsState>()
    val studentDetailsState: LiveData<StudentDetailsState> = _studentDetailsState
    
    private val _updateStudentDetailsState = MutableLiveData<UpdateStudentDetailsState?>()
    val updateStudentDetailsState: LiveData<UpdateStudentDetailsState?> = _updateStudentDetailsState

    init {
        fetchUserProfile()
        fetchStudentDetails()
    }

    private fun fetchUserProfile() {
        viewModelScope.launch {
            _profileState.value = ProfileState.Loading
            try {
                val user = authRepository.getCurrentUser()
                if (user != null) {
                    _profileState.value = ProfileState.Success(user)
                } else {
                    _profileState.value = ProfileState.Error("Failed to load profile")
                }
            } catch (e: Exception) {
                _profileState.value = ProfileState.Error(e.message ?: "Failed to load profile")
            }
        }
    }
    
    private fun fetchStudentDetails() {
        viewModelScope.launch {
            _studentDetailsState.value = StudentDetailsState.Loading
            try {
                val result = authRepository.getStudentDetails()
                if (result.isSuccess) {
                    val response = result.getOrNull()
                    if (response?.status == "success" && response.data != null) {
                        _studentDetailsState.value = StudentDetailsState.Success(response.data)
                    } else {
                        _studentDetailsState.value = StudentDetailsState.Error(
                            response?.message ?: "Failed to load student details"
                        )
                        Log.e("ProfileViewModel", "Error loading student details: ${response?.message}")
                    }
                } else {
                    val errorMessage = result.exceptionOrNull()?.message ?: "Failed to load student details"
                    _studentDetailsState.value = StudentDetailsState.Error(errorMessage)
                    Log.e("ProfileViewModel", "Failed to load student details", result.exceptionOrNull())
                }
            } catch (e: Exception) {
                Log.e("ProfileViewModel", "Exception loading student details", e)
                _studentDetailsState.value = StudentDetailsState.Error("An unexpected error occurred: ${e.message}")
            }
        }
    }
    
    fun updateStudentDetails(
        studentNumber: String,
        birthdate: String,
        emergencyContact: String,
        course: String,
        currentYear: String
    ) {
        viewModelScope.launch {
            _updateStudentDetailsState.value = UpdateStudentDetailsState.Loading
            try {
                val result = authRepository.updateStudentDetails(
                    studentNumber, birthdate, emergencyContact, course, currentYear
                )
                if (result.isSuccess) {
                    val response = result.getOrNull()
                    if (response?.status == "success") {
                        _updateStudentDetailsState.value = UpdateStudentDetailsState.Success
                        // Refresh student details
                        fetchStudentDetails()
                    } else {
                        _updateStudentDetailsState.value = UpdateStudentDetailsState.Error(
                            response?.message ?: "Failed to update student details"
                        )
                    }
                } else {
                    _updateStudentDetailsState.value = UpdateStudentDetailsState.Error(
                        result.exceptionOrNull()?.message ?: "Failed to update student details"
                    )
                }
            } catch (e: Exception) {
                _updateStudentDetailsState.value = UpdateStudentDetailsState.Error(e.message ?: "Failed to update student details")
            }
        }
    }

    fun changePassword(currentPassword: String, newPassword: String, confirmPassword: String) {
        viewModelScope.launch {
            _changePasswordState.value = ChangePasswordState.Loading
            try {
                val result = authRepository.changePassword(currentPassword, newPassword, confirmPassword)
                if (result.isSuccess) {
                    _changePasswordState.value = ChangePasswordState.Success
                } else {
                    _changePasswordState.value = ChangePasswordState.Error(
                        result.exceptionOrNull()?.message ?: "Failed to change password"
                    )
                }
            } catch (e: Exception) {
                _changePasswordState.value = ChangePasswordState.Error(e.message ?: "Failed to change password")
            }
        }
    }

    fun logout() {
        viewModelScope.launch {
            authRepository.logout()
        }
    }

    fun resetUpdateStudentDetailsState() {
        _updateStudentDetailsState.value = null
    }

    sealed class ProfileState {
        object Loading : ProfileState()
        data class Success(val user: UserProfile) : ProfileState()
        data class Error(val message: String) : ProfileState()
    }

    sealed class ChangePasswordState {
        object Loading : ChangePasswordState()
        object Success : ChangePasswordState()
        data class Error(val message: String) : ChangePasswordState()
    }
    
    sealed class StudentDetailsState {
        object Loading : StudentDetailsState()
        data class Success(val user: UserProfile) : StudentDetailsState()
        data class Error(val message: String) : StudentDetailsState()
    }
    
    sealed class UpdateStudentDetailsState {
        object Loading : UpdateStudentDetailsState()
        object Success : UpdateStudentDetailsState()
        data class Error(val message: String) : UpdateStudentDetailsState()
    }
} 