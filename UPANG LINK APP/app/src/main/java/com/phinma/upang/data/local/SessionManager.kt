package com.phinma.upang.data.local

import android.content.Context
import android.content.SharedPreferences
import com.google.gson.Gson
import com.phinma.upang.data.model.UserProfile
import dagger.hilt.android.qualifiers.ApplicationContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class SessionManager @Inject constructor(
    @ApplicationContext context: Context,
    private val gson: Gson
) {
    private val prefs: SharedPreferences = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)

    fun saveAuthToken(token: String) {
        prefs.edit().putString(KEY_AUTH_TOKEN, token).apply()
    }

    fun getAuthToken(): String? {
        return prefs.getString(KEY_AUTH_TOKEN, null)
    }

    fun saveUser(user: UserProfile) {
        val userJson = gson.toJson(user)
        prefs.edit().putString(KEY_USER, userJson).apply()
    }

    fun getUser(): UserProfile? {
        val userJson = prefs.getString(KEY_USER, null)
        return userJson?.let { gson.fromJson(it, UserProfile::class.java) }
    }

    fun clearSession() {
        prefs.edit().clear().apply()
    }

    companion object {
        private const val PREF_NAME = "UpangLinkPrefs"
        private const val KEY_AUTH_TOKEN = "auth_token"
        private const val KEY_USER = "user"
    }
} 