package com.phinma.upang.data.model

import android.os.Parcelable
import kotlinx.parcelize.Parcelize
import java.util.Date

@Parcelize
data class Request(
    val id: String,
    val request_id: Int,
    val user_id: Int,
    val type_id: Int,
    val type: RequestType?,
    val document_type: String,
    val purpose: String,
    val status: RequestStatus? = RequestStatus.PENDING,
    val requirements: List<RequirementSubmission>,
    val remarks: String?,
    val submitted_at: String,
    val updated_at: String,
    val request_type: String,
    val processing_time: String,
    val first_name: String,
    val last_name: String,
    val category_name: String
) : Parcelable

@Parcelize
data class RequestType(
    val type_id: Int,
    val category_id: Int,
    val name: String,
    val description: String,
    val requirements: String,
    val processing_time: String,
    val is_active: Int,
    val category_name: String
) : Parcelable

@Parcelize
data class Requirement(
    val id: String,
    val request_type_id: Int,
    val name: String,
    val description: String,
    val isRequired: Boolean,
    val allowedFileTypes: List<String>,
    val maxFileSize: Long, // in bytes
    val created_at: String,
    val updated_at: String
) : Parcelable

@Parcelize
data class RequirementSubmission(
    val id: String,
    val requirementId: String,
    val requirement: Requirement,
    val fileUrl: String?,
    val status: RequirementStatus,
    val remarks: String?,
    val submittedAt: Date?,
    val verifiedAt: Date?
) : Parcelable

enum class RequestStatus {
    PENDING,
    IN_PROGRESS,
    COMPLETED,
    REJECTED;

    companion object {
        fun fromString(value: String): RequestStatus {
            return when (value.lowercase()) {
                "pending" -> PENDING
                "in_progress" -> IN_PROGRESS
                "completed" -> COMPLETED
                "rejected" -> REJECTED
                else -> PENDING // Default to PENDING if unknown status
            }
        }
    }
}

enum class RequirementStatus {
    PENDING,
    SUBMITTED,
    VERIFIED,
    REJECTED
}

data class RequestCreateResponse(
    val request: Request,
    val requirements: List<Requirement>
)

data class RequestStatistics(
    val total: Int,
    val pending: Int,
    val completed: Int,
    val inProgress: Int,
    val cancelled: Int,
    val byType: Map<String, Int>,
    val byMonth: Map<String, Int>
)

data class RequestFilter(
    val status: String? = null,
    val type: Int? = null,
    val startDate: Date? = null,
    val endDate: Date? = null,
    val searchQuery: String? = null
)

data class ApiResponse<T>(
    val status: String,
    val message: String? = null,
    val data: T? = null
) 