package com.phinma.upang.ui.auth

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.phinma.upang.data.repository.AuthRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class ForgotPasswordViewModel @Inject constructor(
    private val authRepository: AuthRepository,
    private val savedStateHandle: SavedStateHandle
) : ViewModel() {

    private val _forgotPasswordState = MutableLiveData<ForgotPasswordState>()
    val forgotPasswordState: LiveData<ForgotPasswordState> = _forgotPasswordState

    private val _email = MutableLiveData<String>()
    val email: LiveData<String> = _email

    var lastEmail: String
        get() = _email.value ?: savedStateHandle.get<String>(KEY_EMAIL) ?: ""
        private set(value) {
            _email.value = value
            savedStateHandle.set(KEY_EMAIL, value)
        }

    fun resetPassword(email: String) {
        if (!validateInput(email)) return

        lastEmail = email
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

    fun clearState() {
        _forgotPasswordState.value = null
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

    companion object {
        private const val KEY_EMAIL = "email"
    }
} 