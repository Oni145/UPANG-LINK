package com.phinma.upang.ui.auth

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.phinma.upang.data.repository.AuthRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class ForgotPasswordViewModel @Inject constructor(
    private val authRepository: AuthRepository
) : ViewModel() {

    private val _forgotPasswordState = MutableLiveData<ForgotPasswordState>()
    val forgotPasswordState: LiveData<ForgotPasswordState> = _forgotPasswordState

    fun resetPassword(email: String) {
        if (!validateInput(email)) return

        _forgotPasswordState.value = ForgotPasswordState.Loading

        viewModelScope.launch {
            authRepository.forgotPassword(email)
                .onSuccess {
                    _forgotPasswordState.value = ForgotPasswordState.Success(
                        "Password reset instructions have been sent to your email"
                    )
                }
                .onFailure { error ->
                    _forgotPasswordState.value = ForgotPasswordState.Error(
                        error.message ?: "Failed to send reset instructions"
                    )
                }
        }
    }

    private fun validateInput(email: String): Boolean {
        if (email.isBlank()) {
            _forgotPasswordState.value = ForgotPasswordState.Error("Email cannot be empty")
            return false
        }

        if (!android.util.Patterns.EMAIL_ADDRESS.matcher(email).matches()) {
            _forgotPasswordState.value = ForgotPasswordState.Error("Invalid email format")
            return false
        }

        return true
    }

    sealed class ForgotPasswordState {
        object Loading : ForgotPasswordState()
        data class Success(val message: String) : ForgotPasswordState()
        data class Error(val message: String) : ForgotPasswordState()
    }
} 