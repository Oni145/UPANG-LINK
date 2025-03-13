package com.phinma.upang.data.model

import android.os.Parcelable
import android.os.Parcel
import com.google.gson.Gson
import com.google.gson.reflect.TypeToken
import kotlinx.parcelize.Parcelize
import kotlinx.parcelize.TypeParceler
import kotlinx.parcelize.Parceler
import kotlinx.parcelize.RawValue
import java.util.Date

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

data class ApiResponse<T>(
    val status: String,
    val message: String? = null,
    val data: T? = null,
    val error_type: String? = null,
    val code: Int? = null
)

data class RequestFilter(
    val status: String? = null,
    val type: Int? = null,
    val startDate: Date? = null,
    val endDate: Date? = null,
    val searchQuery: String? = null
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

data class RequestCreateResponse(
    val request: Request,
    val requirements: List<Requirement>
)

data class CreateRequestResponse(
    val request_id: Int,
    val tracking_number: String,
    val token: String,
    val status: String,
    val submitted_at: String
)

@Parcelize
data class Request(
    val id: String,
    val request_id: Int,
    val user_id: Int,
    val type_id: Int,
    val type: @RawValue RequestType?,
    val document_type: String,
    val purpose: String?,
    val status: RequestStatus? = RequestStatus.PENDING,
    val requirements: String,
    val remarks: String?,
    val submitted_at: String,
    val updated_at: String,
    val request_type: String,
    val processing_time: String,
    val first_name: String,
    val last_name: String,
    val category_name: String
) : Parcelable {
    fun parseRequirements(): RequirementsData {
        return try {
            val gson = Gson()
            // First parse the outer JSON string
            val requirementsMap = gson.fromJson(requirements, Map::class.java)
            
            // Handle fields if present
            val fields = (requirementsMap["fields"] as? List<*>)?.firstOrNull() as? String
            val parsedFields = if (fields != null) {
                gson.fromJson<List<RequirementField>>(fields, object : TypeToken<List<RequirementField>>() {}.type)
            } else {
                null
            }
            
            // Handle required_docs if present
            @Suppress("UNCHECKED_CAST")
            val requiredDocs = requirementsMap["required_docs"] as? List<String>
            
            // Handle instructions
            val instructions = requirementsMap["instructions"] as? String
            
            RequirementsData(
                fields = parsedFields,
                required_docs = requiredDocs,
                instructions = instructions
            )
        } catch (e: Exception) {
            android.util.Log.e("Request", "Error parsing requirements: ${e.message}")
            RequirementsData()
        }
    }
}

object MapParceler : Parceler<Map<String, Any>> {
    override fun create(parcel: Parcel): Map<String, Any> {
        val json = parcel.readString() ?: "{}"
        return Gson().fromJson(json, object : TypeToken<Map<String, Any>>() {}.type)
    }

    override fun Map<String, Any>.write(parcel: Parcel, flags: Int) {
        parcel.writeString(Gson().toJson(this))
    }
}

@Parcelize
data class RequestType(
    val type_id: Int,
    val category_id: Int,
    val name: String,
    val description: String,
    val requirements: @RawValue Map<String, Any>,
    val processing_time: String,
    val is_active: Int,
    val category_name: String
) : Parcelable {
    override fun toString(): String {
        return name
    }

    fun parseRequirements(): RequirementsData {
        return try {
            val gson = Gson()
            
            // Handle fields if present
            val fields = (requirements["fields"] as? List<*>)?.firstOrNull() as? String
            val parsedFields = if (fields != null) {
                gson.fromJson<List<RequirementField>>(fields, object : TypeToken<List<RequirementField>>() {}.type)
            } else {
                null
            }
            
            // Handle required_docs if present
            @Suppress("UNCHECKED_CAST")
            val requiredDocs = requirements["required_docs"] as? List<String>
            
            // Handle instructions
            val instructions = requirements["instructions"] as? String
            
            RequirementsData(
                fields = parsedFields,
                required_docs = requiredDocs,
                instructions = instructions
            )
        } catch (e: Exception) {
            android.util.Log.e("RequestType", "Error parsing requirements: ${e.message}")
            RequirementsData()
        }
    }
}

fun Request.getRequirementsMap(): Map<String, Any> {
    return try {
        val gson = Gson()
        gson.fromJson(requirements, object : TypeToken<Map<String, Any>>() {}.type)
    } catch (e: Exception) {
        emptyMap()
    }
}

fun RequestType.getRequirementsMap(): Map<String, Any> {
    return requirements
}

@Parcelize
data class RequirementsData(
    val fields: @RawValue List<RequirementField>? = null,
    val required_docs: List<String>? = null,
    val instructions: String? = null
) : Parcelable 