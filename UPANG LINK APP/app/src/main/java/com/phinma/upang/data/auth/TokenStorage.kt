package com.phinma.upang.data.auth

import android.content.Context
import android.content.SharedPreferences
import dagger.hilt.android.qualifiers.ApplicationContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class TokenStorage @Inject constructor(
    @ApplicationContext context: Context
) : TokenProvider {
    private val prefs: SharedPreferences = context.getSharedPreferences("upang_link_prefs", Context.MODE_PRIVATE)

    companion object {
        private const val KEY_AUTH_TOKEN = "auth_token"
        private const val KEY_TOKEN_EXPIRY = "token_expiry"
    }

    override fun getToken(): String? = prefs.getString(KEY_AUTH_TOKEN, null)

    fun saveToken(token: String, expiresAt: String) {
        prefs.edit()
            .putString(KEY_AUTH_TOKEN, token)
            .putString(KEY_TOKEN_EXPIRY, expiresAt)
            .apply()
    }

    fun getTokenExpiry(): String? = prefs.getString(KEY_TOKEN_EXPIRY, null)

    fun clearToken() {
        prefs.edit()
            .remove(KEY_AUTH_TOKEN)
            .remove(KEY_TOKEN_EXPIRY)
            .apply()
    }
} 