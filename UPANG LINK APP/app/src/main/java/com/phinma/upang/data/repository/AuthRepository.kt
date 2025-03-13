package com.phinma.upang.data.repository

import com.google.gson.Gson
import com.phinma.upang.data.api.AuthApi
import com.phinma.upang.data.local.SessionManager
import com.phinma.upang.data.model.*
import retrofit2.HttpException
import javax.inject.Inject
import javax.inject.Singleton
import android.util.Log

@Singleton
class AuthRepository @Inject constructor(
    private val api: AuthApi,
    private val sessionManager: SessionManager
) {
    private val gson = Gson()

    private fun parseErrorResponse(throwable: Throwable): String {
        return when (throwable) {
            is HttpException -> {
                try {
                    val errorBody = throwable.response()?.errorBody()?.string()
                    val errorResponse = gson.fromJson(errorBody, ApiErrorResponse::class.java)
                    errorResponse.message
                } catch (e: Exception) {
                    "An unexpected error occurred"
                }
            }
            else -> throwable.message ?: "An unexpected error occurred"
        }
    }

    suspend fun login(email: String, password: String): Result<ApiResponse<LoginResponse>> {
        return try {
            val request = LoginRequest(email, password)
            val response = api.login(request)
            Result.success(response)
        } catch (e: Exception) {
            Result.failure(Exception(parseErrorResponse(e)))
        }
    }

    suspend fun register(
        firstName: String,
        lastName: String,
        email: String,
        password: String
    ): Result<ApiResponse<Unit>> {
        return try {
            val request = RegisterRequest(
                email = email,
                password = password,
                first_name = firstName,
                last_name = lastName
            )
            val response = api.register(request)
            Result.success(response)
        } catch (e: Exception) {
            Result.failure(Exception(parseErrorResponse(e)))
        }
    }

    suspend fun verifyEmail(token: String): Result<ApiResponse<Unit>> {
        return try {
            val request = VerifyEmailRequest(token)
            val response = api.verifyEmail(request)
            Result.success(response)
        } catch (e: Exception) {
            Result.failure(Exception(parseErrorResponse(e)))
        }
    }

    suspend fun forgotPassword(email: String): Result<ApiResponse<Unit>> {
        return try {
            val request = ForgotPasswordRequest(email)
            val response = api.forgotPassword(request)
            Result.success(response)
        } catch (e: Exception) {
            Result.failure(Exception(parseErrorResponse(e)))
        }
    }

    suspend fun resetPassword(token: String, password: String): Result<ApiResponse<Unit>> {
        return try {
            val request = ResetPasswordRequest(token, password)
            val response = api.resetPassword(request)
            Result.success(response)
        } catch (e: Exception) {
            Result.failure(Exception(parseErrorResponse(e)))
        }
    }

    suspend fun resendVerification(email: String): Result<ApiResponse<Unit>> {
        return try {
            val request = ResendVerificationRequest(email)
            val response = api.resendVerification(request)
            Result.success(response)
        } catch (e: Exception) {
            Result.failure(Exception(parseErrorResponse(e)))
        }
    }

    suspend fun getProfile(): Result<ApiResponse<UserProfile>> {
        return try {
            val response = api.getProfile()
            Result.success(response)
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun updateProfile(firstName: String, lastName: String): Result<ApiResponse<UserProfile>> {
        return try {
            val request = UpdateProfileRequest(
                firstName = firstName,
                lastName = lastName
            )
            val response = api.updateProfile(request)
            Result.success(response)
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun changePassword(
        currentPassword: String,
        newPassword: String,
        confirmPassword: String
    ): Result<ApiResponse<Unit>> {
        return try {
            val request = ChangePasswordRequest(
                currentPassword = currentPassword,
                newPassword = newPassword,
                confirmPassword = confirmPassword
            )
            val response = api.changePassword(request)
            Result.success(response)
        } catch (e: Exception) {
            Result.failure(Exception(parseErrorResponse(e)))
        }
    }

    suspend fun logout(): Result<ApiResponse<Unit>> {
        return try {
            val response = api.logout()
            if (response.status == "success") {
                sessionManager.clearSession()
            }
            Result.success(response)
        } catch (e: Exception) {
            sessionManager.clearSession() // Clear session even if API call fails
            Result.failure(Exception(parseErrorResponse(e)))
        }
    }

    suspend fun validateToken(): Result<ApiResponse<ValidateTokenResponse>> {
        return try {
            val response = api.validateToken()
            Result.success(response)
        } catch (e: Exception) {
            Result.failure(Exception(parseErrorResponse(e)))
        }
    }

    fun isLoggedIn(): Boolean = sessionManager.getAuthToken() != null

    fun getCurrentUser(): UserProfile? = sessionManager.getUser()
} 