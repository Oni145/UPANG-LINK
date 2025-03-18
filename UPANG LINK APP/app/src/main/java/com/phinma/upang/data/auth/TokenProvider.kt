package com.phinma.upang.data.auth

interface TokenProvider {
    fun getToken(): String?
} 