package com.phinma.upang.data.model

import android.os.Parcelable
import kotlinx.parcelize.Parcelize
import java.util.Date

@Parcelize
data class Request(
    val id: String,
    val studentId: String,
    val typeId: Int,
    val type: RequestType,
    val status: RequestStatus,
    val purpose: String,
    val requirements: List<RequirementSubmission>,
    val remarks: String?,
    val createdAt: Date,
    val updatedAt: Date
) : Parcelable

@Parcelize
data class RequestType(
    val id: Int,
    val name: String,
    val description: String,
    val processingTime: String,
    val fee: Double,
    val requirements: List<Requirement>
) : Parcelable

@Parcelize
data class Requirement(
    val id: String,
    val name: String,
    val description: String,
    val isRequired: Boolean,
    val allowedFileTypes: List<String>,
    val maxFileSize: Long // in bytes
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
    DRAFT,
    PENDING,
    IN_REVIEW,
    NEEDS_REVISION,
    PROCESSING,
    READY_FOR_PICKUP,
    COMPLETED,
    CANCELLED,
    REJECTED
}

enum class RequirementStatus {
    PENDING,
    SUBMITTED,
    VERIFIED,
    REJECTED
}

data class RequestCreateResponse(
    val request: Request
)

data class RequestStatistics(
    val total: Int,
    val pending: Int,
    val inProgress: Int,
    val completed: Int,
    val cancelled: Int,
    val byType: Map<String, Int>,
    val byMonth: Map<String, Int>
)

// Request filters
data class RequestFilter(
    val status: RequestStatus? = null,
    val type: Int? = null,
    val startDate: Date? = null,
    val endDate: Date? = null,
    val searchQuery: String? = null
) 