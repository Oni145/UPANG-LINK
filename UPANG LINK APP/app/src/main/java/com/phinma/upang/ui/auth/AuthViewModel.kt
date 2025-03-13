package com.phinma.upang.ui.auth

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.phinma.upang.data.repository.AuthRepository
import com.phinma.upang.data.SessionManager
import com.phinma.upang.data.model.*
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class AuthViewModel @Inject constructor(
    private val authRepository: AuthRepository,
    private val sessionManager: SessionManager
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
                authRepository.login(email, password).fold(
                    onSuccess = { apiResponse ->
                        if (apiResponse.status == "success") {
                            apiResponse.data?.let { loginData ->
                                sessionManager.saveAuthToken(loginData.token, loginData.expires_at)
                                sessionManager.saveUserDetails(
                                    userId = loginData.user.user_id,
                                    email = loginData.user.email,
                                    firstName = loginData.user.first_name,
                                    lastName = loginData.user.last_name,
                                    role = loginData.user.role,
                                    emailVerified = loginData.user.email_verified
                                )
                                _loginState.value = AuthState.Success
                            } ?: run {
                                _loginState.value = AuthState.Error("Invalid response from server")
                            }
                        } else {
                            _loginState.value = AuthState.Error(apiResponse.message ?: "Login failed")
                        }
                    },
                    onFailure = { error ->
                        _loginState.value = AuthState.Error(error.message ?: "Login failed")
                    }
                )
            } catch (e: Exception) {
                _loginState.value = AuthState.Error(e.message ?: "An unexpected error occurred")
            }
        }
    }

    fun register(
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
                authRepository.register(
                    firstName = firstName,
                    lastName = lastName,
                    email = email,
                    password = password
                ).fold(
                    onSuccess = { apiResponse ->
                        if (apiResponse.status == "success") {
                            _registerState.value = AuthState.Success
                        } else {
                            _registerState.value = AuthState.Error(apiResponse.message ?: "Registration failed")
                        }
                    },
                    onFailure = { error ->
                        _registerState.value = AuthState.Error(error.message ?: "Registration failed")
                    }
                )
            } catch (e: Exception) {
                _registerState.value = AuthState.Error(e.message ?: "An error occurred")
            }
        }
    }

    fun resetPassword(email: String) {
        viewModelScope.launch {
            _resetPasswordState.value = AuthState.Loading
            try {
                authRepository.forgotPassword(email).fold(
                    onSuccess = { apiResponse ->
                        if (apiResponse.status == "success") {
                            _resetPasswordState.value = AuthState.Success
                        } else {
                            _resetPasswordState.value = AuthState.Error(apiResponse.message ?: "Password reset failed")
                        }
                    },
                    onFailure = { error ->
                        _resetPasswordState.value = AuthState.Error(error.message ?: "Password reset failed")
                    }
                )
            } catch (e: Exception) {
                _resetPasswordState.value = AuthState.Error(e.message ?: "Password reset failed")
            }
        }
    }

    fun resendVerificationEmail(email: String) {
        viewModelScope.launch {
            _resendEmailState.value = AuthState.Loading
            try {
                authRepository.resendVerification(email).fold(
                    onSuccess = { apiResponse ->
                        if (apiResponse.status == "success") {
                            _resendEmailState.value = AuthState.Success
                        } else {
                            _resendEmailState.value = AuthState.Error(apiResponse.message ?: "Failed to resend email")
                        }
                    },
                    onFailure = { error ->
                        _resendEmailState.value = AuthState.Error(error.message ?: "Failed to resend email")
                    }
                )
            } catch (e: Exception) {
                _resendEmailState.value = AuthState.Error(e.message ?: "Failed to resend email")
            }
        }
    }

    fun logout() {
        sessionManager.clearSession()
    }

    sealed class AuthState {
        object Loading : AuthState()
        object Success : AuthState()
        data class Error(val message: String) : AuthState()
    }
} 