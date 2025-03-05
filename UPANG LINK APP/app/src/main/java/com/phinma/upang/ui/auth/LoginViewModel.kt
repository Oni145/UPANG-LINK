package com.phinma.upang.ui.auth

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.phinma.upang.data.model.LoginResponse
import com.phinma.upang.data.repository.AuthRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class LoginViewModel @Inject constructor(
    private val authRepository: AuthRepository
) : ViewModel() {

    private val _loginState = MutableLiveData<LoginState>()
    val loginState: LiveData<LoginState> = _loginState

    fun login(email: String, password: String) {
        if (!validateInput(email, password)) return

        _loginState.value = LoginState.Loading

        viewModelScope.launch {
            authRepository.login(email, password)
                .onSuccess { response ->
                    _loginState.value = LoginState.Success(response)
                }
                .onFailure { error ->
                    _loginState.value = LoginState.Error(error.message ?: "Login failed")
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
        data class Success(val response: LoginResponse) : LoginState()
        data class Error(val message: String) : LoginState()
    }
} 