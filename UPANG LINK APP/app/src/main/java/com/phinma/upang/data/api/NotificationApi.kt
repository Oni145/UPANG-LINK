package com.phinma.upang.data.api

import com.phinma.upang.data.model.ApiResponse
import com.phinma.upang.data.model.Notification
import retrofit2.http.*

interface NotificationApi {
    @GET("notifications")
    suspend fun getNotifications(): ApiResponse<List<Notification>>

    @GET("notifications/unread")
    suspend fun getUnreadNotifications(): ApiResponse<List<Notification>>

    @POST("notifications/{id}/read")
    suspend fun markAsRead(@Path("id") id: String): ApiResponse<Unit>

    @POST("notifications/read-all")
    suspend fun markAllAsRead(): ApiResponse<Unit>

    @POST("notifications/fcm-token")
    suspend fun updateFcmToken(@Body request: Map<String, String>): ApiResponse<Unit>
} 