package com.phinma.upang.data.api

import com.phinma.upang.data.model.*
import retrofit2.http.*

interface AuthApi {
    @POST("auth/student/login")
    suspend fun login(@Body loginRequest: LoginRequest): ApiResponse<LoginResponse>

    @POST("auth/student/register")
    suspend fun register(@Body registerRequest: RegisterRequest): ApiResponse<Unit>

    @POST("auth/student/verify-email")
    suspend fun verifyEmail(@Body verifyEmailRequest: VerifyEmailRequest): ApiResponse<Unit>

    @POST("auth/student/resend-verification")
    suspend fun resendVerification(@Body resendVerificationRequest: ResendVerificationRequest): ApiResponse<Unit>

    @POST("auth/student/forgot-password")
    suspend fun forgotPassword(@Body forgotPasswordRequest: ForgotPasswordRequest): ApiResponse<Unit>

    @POST("auth/student/reset-password")
    suspend fun resetPassword(@Body resetPasswordRequest: ResetPasswordRequest): ApiResponse<Unit>

    @POST("auth/student/validate-token")
    suspend fun validateToken(): ApiResponse<ValidateTokenResponse>

    @GET("auth/student/profile")
    suspend fun getProfile(): ApiResponse<UserProfile>

    @PUT("auth/student/profile")
    suspend fun updateProfile(@Body request: UpdateProfileRequest): ApiResponse<UserProfile>

    @GET("get_student_details.php")
    suspend fun getStudentDetails(): ApiResponse<UserProfile>

    @POST("update_student_details.php")
    suspend fun updateStudentDetails(@Body request: UpdateStudentDetailsRequest): ApiResponse<Unit>

    @POST("change_password.php")
    suspend fun changePassword(@Body request: ChangePasswordRequest): ApiResponse<Unit>

    @POST("auth/student/logout")
    suspend fun logout(): ApiResponse<Unit>
} 