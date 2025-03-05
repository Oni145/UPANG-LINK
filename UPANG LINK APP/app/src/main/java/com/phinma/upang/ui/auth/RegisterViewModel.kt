package com.phinma.upang.ui.auth

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.phinma.upang.data.model.RegisterResponse
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
        if (!validateInput(firstName, lastName, email, password, confirmPassword)) {
            return
        }

        _registerState.value = RegisterState.Loading

        viewModelScope.launch {
            authRepository.register(
                firstName = firstName,
                lastName = lastName,
                email = email,
                password = password
            ).onSuccess { response ->
                _registerState.value = RegisterState.Success(response)
            }.onFailure { error ->
                _registerState.value = RegisterState.Error(error.message ?: "Registration failed")
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
        data class Success(val response: RegisterResponse) : RegisterState()
        data class Error(val message: String) : RegisterState()
    }
} 