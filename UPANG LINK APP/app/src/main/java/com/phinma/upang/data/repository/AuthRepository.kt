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

    suspend fun login(email: String, password: String): Result<LoginResponse> {
        return try {
            val request = LoginRequest(email, password)
            val response = api.login(request)
            
            if (response.status == "success" && response.data != null) {
                // Save auth token and user profile
                sessionManager.saveAuthToken(response.data.token)
                sessionManager.saveUser(response.data.user)
                Result.success(response.data)
            } else {
                Result.failure(Exception(response.message))
            }
        } catch (e: Exception) {
            Result.failure(Exception(parseErrorResponse(e)))
        }
    }

    suspend fun register(
        firstName: String,
        lastName: String,
        email: String,
        password: String
    ): Result<RegisterResponse> {
        return try {
            // Log input parameters
            Log.d("AuthRepository", "Register Input - firstName: $firstName, lastName: $lastName, email: $email")
            
            val request = RegisterRequest(
                email = email,
                password = password,
                first_name = firstName,
                last_name = lastName
            )
            
            // Log the complete request object
            Log.d("AuthRepository", "Register Request Object: ${gson.toJson(request)}")
            
            val response = api.register(request)
            
            // Log the complete response
            Log.d("AuthRepository", "Register Raw Response: ${gson.toJson(response)}")
            Log.d("AuthRepository", "Register Response Status: ${response.status}")
            Log.d("AuthRepository", "Register Response Message: ${response.message}")
            Log.d("AuthRepository", "Register Response Data: ${response.data?.let { gson.toJson(it) }}")
            
            response.data?.let {
                Result.success(it)
            } ?: Result.failure(Exception(response.message))
        } catch (e: Exception) {
            // Log detailed error information
            Log.e("AuthRepository", "Register Error Type: ${e.javaClass.simpleName}")
            Log.e("AuthRepository", "Register Error Message: ${e.message}")
            Log.e("AuthRepository", "Register Error Stack Trace:", e)
            
            if (e is HttpException) {
                val errorBody = e.response()?.errorBody()?.string()
                Log.e("AuthRepository", "Register HTTP Error Body: $errorBody")
            }
            
            Result.failure(Exception(parseErrorResponse(e)))
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
            Result.failure(Exception(parseErrorResponse(e)))
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
            Result.failure(Exception(parseErrorResponse(e)))
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
            Result.failure(Exception(parseErrorResponse(e)))
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
            Result.failure(Exception(parseErrorResponse(e)))
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
            Result.failure(Exception(parseErrorResponse(e)))
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
            Result.failure(Exception(parseErrorResponse(e)))
        }
    }

    fun isLoggedIn(): Boolean = sessionManager.getAuthToken() != null

    fun getCurrentUser(): UserProfile? = sessionManager.getUser()
} 