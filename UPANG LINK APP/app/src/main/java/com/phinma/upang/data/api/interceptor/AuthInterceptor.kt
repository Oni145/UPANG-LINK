package com.phinma.upang.data.api.interceptor

import com.phinma.upang.data.auth.TokenProvider
import okhttp3.Interceptor
import okhttp3.Response
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AuthInterceptor @Inject constructor(
    private val tokenProvider: TokenProvider
) : Interceptor {

    override fun intercept(chain: Interceptor.Chain): Response {
        val request = chain.request()
        
        // Don't add token for login and register endpoints
        if (request.url.encodedPath.contains("/login") || 
            request.url.encodedPath.contains("/register")) {
            return chain.proceed(request)
        }

        val token = tokenProvider.getToken()

        return if (token != null) {
            val newRequest = request.newBuilder()
                .header("Authorization", "Bearer $token")
                .build()
            chain.proceed(newRequest)
        } else {
            chain.proceed(request)
        }
    }
} 