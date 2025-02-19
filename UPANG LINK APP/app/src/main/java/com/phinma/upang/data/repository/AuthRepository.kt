package com.phinma.upang.data.repository

import com.phinma.upang.data.api.AuthApi
import com.phinma.upang.data.local.SessionManager
import com.phinma.upang.data.model.*
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AuthRepository @Inject constructor(
    private val api: AuthApi,
    private val sessionManager: SessionManager
) {
    suspend fun login(email: String, password: String): Result<LoginResponse> {
        return try {
            val request = LoginRequest(email, password)
            val response = api.login(request)
            
            response.data?.let { loginResponse ->
                // Save auth token and user profile
                sessionManager.saveAuthToken(loginResponse.token)
                sessionManager.saveUser(loginResponse.user)
                Result.success(loginResponse)
            } ?: Result.failure(Exception(response.message))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun register(
        studentNumber: String,
        firstName: String,
        lastName: String,
        email: String,
        course: String,
        yearLevel: Int,
        block: String,
        password: String
    ): Result<RegisterResponse> {
        return try {
            val request = RegisterRequest(
                studentNumber = studentNumber,
                firstName = firstName,
                lastName = lastName,
                email = email,
                course = course,
                yearLevel = yearLevel,
                block = block,
                password = password,
                admissionYear = studentNumber.substring(0, 4) // Extract year from student number
            )
            val response = api.register(request)
            response.data?.let {
                Result.success(it)
            } ?: Result.failure(Exception(response.message))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun verifyEmail(token: String): Result<Unit> {
        return try {
            val response = api.verifyEmail(mapOf("token" to token))
            if (response.status == "success") {
                Result.success(Unit)
            } else {
                Result.failure(Exception(response.message))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun forgotPassword(email: String): Result<Unit> {
        return try {
            val response = api.forgotPassword(mapOf("email" to email))
            if (response.status == "success") {
                Result.success(Unit)
            } else {
                Result.failure(Exception(response.message))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun resetPassword(token: String, newPassword: String): Result<Unit> {
        return try {
            val response = api.resetPassword(mapOf(
                "token" to token,
                "password" to newPassword
            ))
            if (response.status == "success") {
                Result.success(Unit)
            } else {
                Result.failure(Exception(response.message))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun resendVerification(email: String): Result<Unit> {
        return try {
            val response = api.resendVerification(mapOf("email" to email))
            if (response.status == "success") {
                Result.success(Unit)
            } else {
                Result.failure(Exception(response.message))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getProfile(): Result<UserProfile> {
        return try {
            val response = api.getProfile()
            response.data?.let {
                Result.success(it)
            } ?: Result.failure(Exception(response.message))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun updateProfile(request: UpdateProfileRequest): Result<UserProfile> {
        return try {
            val response = api.updateProfile(request)
            response.data?.let {
                sessionManager.saveUser(it)
                Result.success(it)
            } ?: Result.failure(Exception(response.message))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun changePassword(
        currentPassword: String,
        newPassword: String,
        confirmPassword: String
    ): Result<Unit> {
        return try {
            val request = ChangePasswordRequest(
                currentPassword = currentPassword,
                newPassword = newPassword,
                confirmPassword = confirmPassword
            )
            val response = api.changePassword(request)
            if (response.status == "success") {
                Result.success(Unit)
            } else {
                Result.failure(Exception(response.message))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun logout(): Result<Unit> {
        return try {
            val response = api.logout()
            sessionManager.clearSession()
            if (response.status == "success") {
                Result.success(Unit)
            } else {
                Result.failure(Exception(response.message))
            }
        } catch (e: Exception) {
            sessionManager.clearSession() // Clear session even if API call fails
            Result.failure(e)
        }
    }

    fun isLoggedIn(): Boolean = sessionManager.getAuthToken() != null

    fun getCurrentUser(): UserProfile? = sessionManager.getUser()
} 