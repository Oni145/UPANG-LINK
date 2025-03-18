package com.phinma.upang.ui.auth

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.phinma.upang.data.repository.AuthRepository
import com.phinma.upang.data.SessionManager
import com.phinma.upang.data.model.LoginResponse
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class LoginViewModel @Inject constructor(
    private val authRepository: AuthRepository,
    private val sessionManager: SessionManager
) : ViewModel() {

    private val _loginState = MutableLiveData<LoginState>()
    val loginState: LiveData<LoginState> = _loginState

    fun login(email: String, password: String) {
        viewModelScope.launch {
            _loginState.value = LoginState.Loading
            try {
                authRepository.login(email, password).fold(
                    onSuccess = { response ->
                        if (response.status == "success") {
                            response.data?.let { loginData ->
                                sessionManager.saveAuthToken(loginData.token, loginData.expires_at)
                                sessionManager.saveUserDetails(
                                    userId = loginData.user.user_id,
                                    email = loginData.user.email,
                                    firstName = loginData.user.first_name,
                                    lastName = loginData.user.last_name,
                                    role = loginData.user.role,
                                    emailVerified = loginData.user.email_verified
                                )
                                _loginState.value = LoginState.Success(loginData)
                            } ?: run {
                                _loginState.value = LoginState.Error("Invalid response from server")
                            }
                        } else {
                            _loginState.value = LoginState.Error(response.message ?: "Login failed")
                        }
                    },
                    onFailure = { error ->
                        _loginState.value = LoginState.Error(error.message ?: "Login failed")
                    }
                )
            } catch (e: Exception) {
                _loginState.value = LoginState.Error(e.message ?: "An unexpected error occurred")
            }
        }
    }

    private fun validateInput(email: String, password: String): Boolean {
        if (email.isBlank() || password.isBlank()) {
            _loginState.value = LoginState.Error("Email and password cannot be empty")
            return false
        }
        if (!android.util.Patterns.EMAIL_ADDRESS.matcher(email).matches()) {
            _loginState.value = LoginState.Error("Invalid email format")
            return false
        }
        if (password.length < 8) {
            _loginState.value = LoginState.Error("Password must be at least 8 characters")
            return false
        }
        return true
    }

    sealed class LoginState {
        object Loading : LoginState()
        data class Success(val data: LoginResponse) : LoginState()
        data class Error(val message: String) : LoginState()
    }
} 