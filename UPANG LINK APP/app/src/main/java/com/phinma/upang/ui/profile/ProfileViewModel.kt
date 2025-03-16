package com.phinma.upang.ui.profile

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

    init {
        fetchUserProfile()
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
} 