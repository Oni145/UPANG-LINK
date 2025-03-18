package com.phinma.upang.data

import com.phinma.upang.data.api.AuthApi
import com.phinma.upang.data.model.ApiResponse
import com.phinma.upang.data.model.ValidateTokenResponse
import javax.inject.Inject

class TokenValidator @Inject constructor(
    private val authApi: AuthApi
) {
    suspend fun validateToken(): Boolean {
        return try {
            val response = authApi.validateToken()
            response.status == "success"
        } catch (e: Exception) {
            false
        }
    }
} 