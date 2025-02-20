package com.phinma.upang.ui.auth

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class AuthViewModel @Inject constructor() : ViewModel() {

    private val _loginState = MutableLiveData<AuthState>()
    val loginState: LiveData<AuthState> = _loginState

    private val _registerState = MutableLiveData<AuthState>()
    val registerState: LiveData<AuthState> = _registerState

    private val _resetPasswordState = MutableLiveData<AuthState>()
    val resetPasswordState: LiveData<AuthState> = _resetPasswordState

    private val _resendEmailState = MutableLiveData<AuthState>()
    val resendEmailState: LiveData<AuthState> = _resendEmailState

    fun login(email: String, password: String) {
        viewModelScope.launch {
            _loginState.value = AuthState.Loading
            try {
                // TODO: Implement actual login logic
                delay(1000) // Simulate network delay
                _loginState.value = AuthState.Success
            } catch (e: Exception) {
                _loginState.value = AuthState.Error(e.message ?: "Login failed")
            }
        }
    }

    fun register(
        studentNumber: String,
        firstName: String,
        lastName: String,
        email: String,
        password: String,
        confirmPassword: String
    ) {
        viewModelScope.launch {
            _registerState.value = AuthState.Loading
            try {
                // TODO: Implement actual registration logic
                delay(1000) // Simulate network delay
                _registerState.value = AuthState.Success
            } catch (e: Exception) {
                _registerState.value = AuthState.Error(e.message ?: "Registration failed")
            }
        }
    }

    fun resetPassword(email: String) {
        viewModelScope.launch {
            _resetPasswordState.value = AuthState.Loading
            try {
                // TODO: Implement actual password reset logic
                delay(1000) // Simulate network delay
                _resetPasswordState.value = AuthState.Success
            } catch (e: Exception) {
                _resetPasswordState.value = AuthState.Error(e.message ?: "Password reset failed")
            }
        }
    }

    fun resendVerificationEmail() {
        viewModelScope.launch {
            _resendEmailState.value = AuthState.Loading
            try {
                // TODO: Implement actual resend verification email logic
                delay(1000) // Simulate network delay
                _resendEmailState.value = AuthState.Success
            } catch (e: Exception) {
                _resendEmailState.value = AuthState.Error(e.message ?: "Failed to resend verification email")
            }
        }
    }

    sealed class AuthState {
        object Loading : AuthState()
        object Success : AuthState()
        data class Error(val message: String) : AuthState()
    }
} 