package com.phinma.upang.ui.profile

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.launch
import javax.inject.Inject
import com.phinma.upang.data.api.AuthApi
import com.phinma.upang.data.model.UserProfile
import com.phinma.upang.data.local.SessionManager
import com.phinma.upang.data.repository.AuthRepository

@HiltViewModel
class ProfileViewModel @Inject constructor(
    private val authRepository: AuthRepository,
    private val sessionManager: SessionManager
) : ViewModel() {

    private val _profileState = MutableLiveData<ProfileState>()
    val profileState: LiveData<ProfileState> = _profileState

    init {
        loadProfile()
    }

    private fun loadProfile() {
        viewModelScope.launch {
            _profileState.value = ProfileState.Loading
            try {
                // Get user from SessionManager
                val user = sessionManager.getUser()
                if (user != null) {
                    _profileState.value = ProfileState.Success(user)
                } else {
                    // If user is not in SessionManager, try to fetch from API
                    val response = authRepository.getProfile()
                    response.onSuccess { profile ->
                        _profileState.value = ProfileState.Success(profile)
                    }.onFailure { error ->
                        _profileState.value = ProfileState.Error(error.message ?: "Failed to load profile")
                    }
                }
            } catch (e: Exception) {
                _profileState.value = ProfileState.Error(e.message ?: "Failed to load profile")
            }
        }
    }

    fun logout() {
        viewModelScope.launch {
            try {
                val result = authRepository.logout()
                result.onSuccess {
                    sessionManager.clearSession()
                }.onFailure { error ->
                    _profileState.value = ProfileState.Error(error.message ?: "Failed to logout")
                }
            } catch (e: Exception) {
                _profileState.value = ProfileState.Error(e.message ?: "Failed to logout")
            }
        }
    }

    sealed class ProfileState {
        object Loading : ProfileState()
        data class Success(val user: UserProfile) : ProfileState()
        data class Error(val message: String) : ProfileState()
    }

    data class User(
        val firstName: String,
        val lastName: String,
        val email: String
    )
} 