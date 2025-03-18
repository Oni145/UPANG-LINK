package com.phinma.upang.data.local

import android.content.Context
import android.content.SharedPreferences
import com.google.gson.Gson
import com.phinma.upang.data.model.UserProfile
import com.phinma.upang.data.auth.TokenStorage
import dagger.hilt.android.qualifiers.ApplicationContext
import javax.inject.Inject
import javax.inject.Singleton
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import java.text.SimpleDateFormat
import java.util.*

@Singleton
class SessionManager @Inject constructor(
    @ApplicationContext context: Context,
    private val gson: Gson,
    private val tokenStorage: TokenStorage
) {
    private val prefs: SharedPreferences = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
    private val dateFormat = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.getDefault())

    fun saveAuthToken(token: String, expiresAt: String?) {
        if (expiresAt != null) {
            tokenStorage.saveToken(token, expiresAt)
        }
    }

    fun getAuthToken(): String? = tokenStorage.getToken()

    fun saveUser(user: UserProfile) {
        val userJson = gson.toJson(user)
        prefs.edit().putString(KEY_USER, userJson).apply()
    }

    fun getUser(): UserProfile? {
        val userJson = prefs.getString(KEY_USER, null)
        return userJson?.let { gson.fromJson(it, UserProfile::class.java) }
    }

    fun clearSession() {
        tokenStorage.clearToken()
        prefs.edit()
            .remove(KEY_USER)
            .apply()
    }

    suspend fun validateToken(): Boolean = withContext(Dispatchers.IO) {
        val token = tokenStorage.getToken() ?: return@withContext false
        val expiryString = tokenStorage.getTokenExpiry() ?: return@withContext false
        
        try {
            val expiryDate = dateFormat.parse(expiryString) ?: return@withContext false
            val now = Date()
            
            if (now.after(expiryDate)) {
                clearSession()
                return@withContext false
            }
            
            // Only check if token exists and is not expired locally
            return@withContext true
            
        } catch (e: Exception) {
            clearSession()
            false
        }
    }

    companion object {
        private const val PREF_NAME = "UpangLinkPrefs"
        private const val KEY_USER = "user"
    }
} 