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
class AuthViewModel @Inject constructor(
    private val authRepository: AuthRepository
) : ViewModel() {

    private val _loginState = MutableLiveData<AuthState>()
    val loginState: LiveData<AuthState> = _loginState

    fun clearLoginState() {
        _loginState.value = null
    }

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
                val result = authRepository.login(email, password)
                result.fold(
                    onSuccess = { _loginState.value = AuthState.Success },
                    onFailure = { _loginState.value = AuthState.Error("Email or password is incorrect") }
                )
            } catch (e: Exception) {
                _loginState.value = AuthState.Error("Email or password is incorrect")
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
        if (password != confirmPassword) {
            _registerState.value = AuthState.Error("Passwords do not match")
            return
        }

        viewModelScope.launch {
            _registerState.value = AuthState.Loading
            try {
                val result = authRepository.register(
                    studentNumber = studentNumber,
                    firstName = firstName,
                    lastName = lastName,
                    email = email,
                    course = "BSIT", // TODO: Add course selection
                    yearLevel = 1, // TODO: Add year level selection
                    block = "A", // TODO: Add block selection
                    password = password
                )
                result.fold(
                    onSuccess = { _registerState.value = AuthState.Success },
                    onFailure = { _registerState.value = AuthState.Error(it.message ?: "Registration failed") }
                )
            } catch (e: Exception) {
                _registerState.value = AuthState.Error(e.message ?: "Registration failed")
            }
        }
    }

    fun resetPassword(email: String) {
        viewModelScope.launch {
            _resetPasswordState.value = AuthState.Loading
            try {
                val result = authRepository.forgotPassword(email)
                result.fold(
                    onSuccess = { _resetPasswordState.value = AuthState.Success },
                    onFailure = { _resetPasswordState.value = AuthState.Error(it.message ?: "Password reset failed") }
                )
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