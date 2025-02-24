package com.phinma.upang.ui.auth

import android.content.Context
import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.phinma.upang.data.repository.AuthRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.launch
import javax.inject.Inject
import dagger.hilt.android.qualifiers.ApplicationContext

@HiltViewModel
class AuthViewModel @Inject constructor(
    private val authRepository: AuthRepository,
    @ApplicationContext private val context: Context
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
                    onSuccess = { 
                        // Save token to SharedPreferences
                        saveToken(it.token)
                        _loginState.value = AuthState.Success 
                    },
                    onFailure = { error -> 
                        _loginState.value = AuthState.Error(error.message ?: "An unexpected error occurred")
                    }
                )
            } catch (e: Exception) {
                _loginState.value = AuthState.Error(e.message ?: "An unexpected error occurred")
            }
        }
    }

    private fun saveToken(token: String) {
        val sharedPreferences = context.getSharedPreferences("app_prefs", Context.MODE_PRIVATE)
        sharedPreferences.edit().putString("user_token", token).apply()
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
                val result = authRepository.register(
                    firstName = firstName,
                    lastName = lastName,
                    email = email,
                    password = password
                )
                result.fold(
                    onSuccess = { _registerState.value = AuthState.Success },
                    onFailure = { _registerState.value = AuthState.Error(it.message ?: "An error occurred") }
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

    fun resendVerificationEmail(email: String) {
        viewModelScope.launch {
            _resendEmailState.value = AuthState.Loading
            try {
                val result = authRepository.resendVerification(email)
                result.fold(
                    onSuccess = { _resendEmailState.value = AuthState.Success },
                    onFailure = { _resendEmailState.value = AuthState.Error(it.message ?: "Failed to resend email") }
                )
            } catch (e: Exception) {
                _resendEmailState.value = AuthState.Error(e.message ?: "Failed to resend email")
            }
        }
    }

    fun logout() {
        val sharedPreferences = context.getSharedPreferences("app_prefs", Context.MODE_PRIVATE)
        sharedPreferences.edit().remove("user_token").apply()
    }

    sealed class AuthState {
        object Loading : AuthState()
        object Success : AuthState()
        data class Error(val message: String) : AuthState()
    }
} 