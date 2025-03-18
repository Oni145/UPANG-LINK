package com.phinma.upang.data.model

import android.os.Parcelable
import kotlinx.parcelize.Parcelize
import java.util.Date

@Parcelize
data class Notification(
    val id: String,
    val title: String,
    val body: String,
    val type: String,
    val data: Map<String, String>,
    val isRead: Boolean,
    val createdAt: Date
) : Parcelable

enum class NotificationType {
    REQUEST_STATUS_CHANGE,
    REQUEST_COMMENT,
    SYSTEM_ANNOUNCEMENT,
    GENERAL
} 