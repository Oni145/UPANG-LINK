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
class RegisterViewModel @Inject constructor(
    private val authRepository: AuthRepository
) : ViewModel() {

    private val _registerState = MutableLiveData<RegisterState>()
    val registerState: LiveData<RegisterState> = _registerState

    fun register(
        firstName: String,
        lastName: String,
        email: String,
        password: String,
        confirmPassword: String
    ) {
        if (password != confirmPassword) {
            _registerState.value = RegisterState.Error("Passwords do not match")
            return
        }

        viewModelScope.launch {
            _registerState.value = RegisterState.Loading
            try {
                authRepository.register(
                    firstName = firstName,
                    lastName = lastName,
                    email = email,
                    password = password
                ).fold(
                    onSuccess = { response ->
                        if (response.status == "success") {
                            _registerState.value = RegisterState.Success
                        } else {
                            _registerState.value = RegisterState.Error(response.message ?: "Registration failed")
                        }
                    },
                    onFailure = { error ->
                        _registerState.value = RegisterState.Error(error.message ?: "Registration failed")
                    }
                )
            } catch (e: Exception) {
                _registerState.value = RegisterState.Error(e.message ?: "An unexpected error occurred")
            }
        }
    }

    private fun validateInput(
        firstName: String,
        lastName: String,
        email: String,
        password: String,
        confirmPassword: String
    ): Boolean {
        if (firstName.isBlank() || lastName.isBlank() || 
            email.isBlank() || password.isBlank() || confirmPassword.isBlank()
        ) {
            _registerState.value = RegisterState.Error("All fields are required")
            return false
        }

        if (!android.util.Patterns.EMAIL_ADDRESS.matcher(email).matches()) {
            _registerState.value = RegisterState.Error("Invalid email format")
            return false
        }

        if (password.length < 8) {
            _registerState.value = RegisterState.Error("Password must be at least 8 characters")
            return false
        }

        if (password != confirmPassword) {
            _registerState.value = RegisterState.Error("Passwords do not match")
            return false
        }

        return true
    }

    sealed class RegisterState {
        object Loading : RegisterState()
        object Success : RegisterState()
        data class Error(val message: String) : RegisterState()
    }
} 