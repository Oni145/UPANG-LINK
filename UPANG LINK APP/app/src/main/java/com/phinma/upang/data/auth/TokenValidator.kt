package com.phinma.upang.data.auth

import android.util.Log
import com.phinma.upang.data.api.AuthApi
import retrofit2.HttpException
import javax.inject.Inject

class TokenValidator @Inject constructor(
    private val authApi: AuthApi
) {
    companion object {
        private const val TAG = "TokenValidator"
    }

    suspend fun validateToken(): Result<Boolean> {
        return try {
            val response = authApi.validateToken()
            Log.d(TAG, "Token validation response: $response")
            
            if (response.status == "success") {
                val data = response.data
                if (data != null && data.email_verified == 1) {
                    Result.success(true)
                } else {
                    Log.d(TAG, "Email not verified or invalid response data")
                    Result.success(false)
                }
            } else {
                Log.d(TAG, "Token validation failed: ${response.message}")
                Result.success(false)
            }
        } catch (e: HttpException) {
            when (e.code()) {
                401 -> {
                    Log.e(TAG, "Token unauthorized", e)
                    Result.success(false)
                }
                else -> {
                    Log.e(TAG, "HTTP error during token validation: ${e.code()}", e)
                    Result.failure(e)
                }
            }
        } catch (e: Exception) {
            Log.e(TAG, "Exception during token validation", e)
            Result.failure(e)
        }
    }
} 