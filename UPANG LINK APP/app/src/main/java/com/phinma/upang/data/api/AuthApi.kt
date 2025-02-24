package com.phinma.upang.data.api

import com.phinma.upang.data.model.*
import retrofit2.http.*

interface AuthApi {
    @POST("auth/student/login")
    suspend fun login(@Body request: LoginRequest): ApiResponse<LoginResponse>

    @POST("auth/student/register")
    @Headers("Content-Type: application/json")
    suspend fun register(@Body request: RegisterRequest): ApiResponse<RegisterResponse>

    @POST("auth/student/verify-email")
    suspend fun verifyEmail(@Body request: Map<String, String>): ApiResponse<Unit>

    @POST("auth/student/forgot-password")
    suspend fun forgotPassword(@Body request: Map<String, String>): ApiResponse<Unit>

    @POST("auth/student/reset-password")
    suspend fun resetPassword(@Body request: Map<String, String>): ApiResponse<Unit>

    @POST("auth/student/resend-verification")
    suspend fun resendVerification(@Body request: Map<String, String>): ApiResponse<Unit>

    @GET("auth/student/profile")
    suspend fun getProfile(): ApiResponse<UserProfile>

    @PUT("auth/student/profile")
    suspend fun updateProfile(@Body request: UpdateProfileRequest): ApiResponse<UserProfile>

    @POST("auth/student/change-password")
    suspend fun changePassword(@Body request: ChangePasswordRequest): ApiResponse<Unit>

    @POST("auth/student/logout")
    suspend fun logout(): ApiResponse<Unit>
} 