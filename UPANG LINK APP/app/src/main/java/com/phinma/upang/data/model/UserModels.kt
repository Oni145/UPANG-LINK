package com.phinma.upang.data.model

import android.os.Parcelable
import kotlinx.parcelize.Parcelize
import java.util.Date

@Parcelize
data class UserProfile(
    val user_id: Int,
    val student_number: String,
    val email: String,
    val first_name: String,
    val last_name: String,
    val role: String,
    val course: String,
    val year_level: Int,
    val block: String,
    val admission_year: String,
    val email_verified: Int,
    val created_at: String,
    val updated_at: String
) : Parcelable

data class LoginRequest(
    val email: String,
    val password: String
)

data class LoginResponse(
    val token: String,
    val expires_at: String,
    val user: UserProfile
)

data class ApiLoginResponse(
    val status: String,
    val message: String,
    val data: LoginResponse?
)

data class RegisterRequest(
    val email: String,
    val password: String,
    val first_name: String,
    val last_name: String
)

data class RegisterResponse(
    val verificationToken: String,
    val expiresAt: Date
)

data class UpdateProfileRequest(
    val firstName: String,
    val lastName: String,
    val course: String,
    val yearLevel: Int,
    val block: String
)

data class ChangePasswordRequest(
    val currentPassword: String,
    val newPassword: String,
    val confirmPassword: String
) 