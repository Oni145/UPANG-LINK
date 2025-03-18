package com.phinma.upang.data

import android.content.Context
import android.content.SharedPreferences
import android.util.Log
import com.phinma.upang.data.model.UserProfile
import com.phinma.upang.data.auth.TokenStorage
import com.phinma.upang.data.auth.TokenValidator
import com.phinma.upang.data.local.SessionManager as LocalSessionManager
import dagger.hilt.android.qualifiers.ApplicationContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class SessionManager @Inject constructor(
    @ApplicationContext context: Context,
    private val tokenStorage: TokenStorage,
    private val tokenValidator: TokenValidator,
    private val localSessionManager: LocalSessionManager
) {
    private val prefs: SharedPreferences = context.getSharedPreferences("upang_link_prefs", Context.MODE_PRIVATE)

    companion object {
        private const val KEY_USER_ID = "user_id"
        private const val KEY_EMAIL = "email"
        private const val KEY_FIRST_NAME = "first_name"
        private const val KEY_LAST_NAME = "last_name"
        private const val KEY_ROLE = "role"
        private const val KEY_EMAIL_VERIFIED = "email_verified"
        private const val TAG = "SessionManager"
    }

    fun saveAuthToken(token: String, expiresAt: String) {
        tokenStorage.saveToken(token, expiresAt)
    }

    fun getAuthToken(): String? = tokenStorage.getToken()

    fun getTokenExpiry(): String? = tokenStorage.getTokenExpiry()

    fun saveUserDetails(userId: Int, email: String, firstName: String, lastName: String, role: String, emailVerified: Int) {
        prefs.edit()
            .putInt(KEY_USER_ID, userId)
            .putString(KEY_EMAIL, email)
            .putString(KEY_FIRST_NAME, firstName)
            .putString(KEY_LAST_NAME, lastName)
            .putString(KEY_ROLE, role)
            .putInt(KEY_EMAIL_VERIFIED, emailVerified)
            .apply()
        
        // Also save to local session manager
        val userProfile = UserProfile(
            user_id = userId,
            email = email,
            first_name = firstName,
            last_name = lastName,
            role = role,
            email_verified = emailVerified,
            created_at = "",
            updated_at = ""
        )
        localSessionManager.saveUser(userProfile)
    }

    fun getUserProfile(): UserProfile? {
        val userId = prefs.getInt(KEY_USER_ID, -1)
        if (userId == -1) return null

        val email = prefs.getString(KEY_EMAIL, null) ?: return null
        val firstName = prefs.getString(KEY_FIRST_NAME, null) ?: return null
        val lastName = prefs.getString(KEY_LAST_NAME, null) ?: return null
        val role = prefs.getString(KEY_ROLE, null) ?: return null
        val emailVerified = prefs.getInt(KEY_EMAIL_VERIFIED, 0)

        return UserProfile(
            user_id = userId,
            email = email,
            first_name = firstName,
            last_name = lastName,
            role = role,
            email_verified = emailVerified,
            created_at = "", // These fields are not stored locally
            updated_at = ""  // These fields are not stored locally
        )
    }

    fun clearSession() {
        tokenStorage.clearToken()
        localSessionManager.clearSession()
        prefs.edit().clear().apply()
    }

    suspend fun validateToken(): Boolean {
        val token = tokenStorage.getToken()
        if (token == null) {
            Log.d(TAG, "No token found")
            return false
        }

        return try {
            // First check if token is expired locally
            if (!localSessionManager.validateToken()) {
                Log.d(TAG, "Local token validation failed")
                clearSession()
                return false
            }

            // Then validate with API and check email verification
            tokenValidator.validateToken()
                .fold(
                    onSuccess = { isValid -> 
                        if (!isValid) {
                            Log.d(TAG, "API token validation failed")
                            clearSession()
                        }
                        isValid
                    },
                    onFailure = { error -> 
                        Log.e(TAG, "Token validation failed: ${error.message}")
                        clearSession()
                        false 
                    }
                )
        } catch (e: Exception) {
            Log.e(TAG, "Exception during token validation", e)
            clearSession()
            false
        }
    }
} 