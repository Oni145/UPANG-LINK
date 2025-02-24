package com.phinma.upang.data.model

import android.os.Parcelable
import kotlinx.parcelize.Parcelize
import java.util.Date

@Parcelize
data class UserProfile(
    val id: Int,
    val studentNumber: String,
    val email: String,
    val firstName: String,
    val lastName: String,
    val course: String,
    val yearLevel: Int,
    val block: String,
    val admissionYear: String,
    val isEmailVerified: Boolean,
    val createdAt: Date,
    val updatedAt: Date
) : Parcelable

data class LoginRequest(
    val email: String,
    val password: String
)

data class LoginResponse(
    val token: String,
    val expiresAt: Date,
    val user: UserProfile
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