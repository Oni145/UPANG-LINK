package com.phinma.upang.data.model

data class ResetPasswordRequest(
    val token: String,
    val password: String
) 