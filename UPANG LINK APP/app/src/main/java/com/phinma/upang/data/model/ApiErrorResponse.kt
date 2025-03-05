package com.phinma.upang.data.model

import com.google.gson.annotations.SerializedName

data class ApiErrorResponse(
    @SerializedName("status") val status: String,
    @SerializedName("message") val message: String,
    @SerializedName("code") val code: Int
) 