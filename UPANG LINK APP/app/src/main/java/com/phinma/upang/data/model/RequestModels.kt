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
import java.text.SimpleDateFormat
import java.util.Locale

enum class RequestStatus {
    PENDING,
    APPROVED,
    IN_PROGRESS,
    COMPLETED,
    REJECTED,
    CANCELLED;

    companion object {
        fun fromString(value: String): RequestStatus {
            return when (value.lowercase()) {
                "pending" -> PENDING
                "approved" -> APPROVED
                "in_progress" -> IN_PROGRESS
                "completed" -> COMPLETED
                "rejected" -> REJECTED
                "cancelled" -> CANCELLED
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
    val category_name: String,
    val tracking_number: String? = null,
    val notes: List<@RawValue RequestNote>? = null
) : Parcelable {
    fun parseRequirements(): RequirementsData {
        return try {
            val gson = Gson()
            // First parse the outer JSON string
            val requirementsMap = gson.fromJson(requirements, Map::class.java)
            
            // Handle fields if present - FIXED: fields is a direct List<Map>, not a String
            val fieldsArray = requirementsMap["fields"] as? List<*>
            val parsedFields = if (fieldsArray != null) {
                fieldsArray.mapNotNull { fieldItem ->
                    try {
                        val fieldMap = fieldItem as? Map<*, *>
                        if (fieldMap != null) {
                            // Parse options for dropdown/select fields
                            @Suppress("UNCHECKED_CAST")
                            val options = if (fieldMap["type"]?.toString()?.equals("select", ignoreCase = true) == true) {
                                (fieldMap["options"] as? List<*>)?.map { it.toString() }
                            } else {
                                null
                            }
                            
                            RequirementField(
                                name = fieldMap["name"]?.toString() ?: "",
                                label = fieldMap["label"]?.toString() ?: "",
                                type = fieldMap["type"]?.toString() ?: "text",
                                required = fieldMap["required"] as? Boolean ?: false,
                                description = fieldMap["description"]?.toString(),
                                allowed_types = fieldMap["allowed_types"]?.toString(),
                                options = options
                            )
                        } else null
                    } catch (e: Exception) {
                        android.util.Log.e("Request", "Error parsing field: $fieldItem", e)
                        null
                    }
                }
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
    private val gson = Gson()
    
    override fun create(parcel: Parcel): Map<String, Any> {
        val json = parcel.readString() ?: "{}"
        return gson.fromJson(json, object : TypeToken<Map<String, Any>>() {}.type)
    }

    override fun Map<String, Any>.write(parcel: Parcel, flags: Int) {
        parcel.writeString(gson.toJson(this))
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
            
            // Handle fields if present - FIXED: fields is a direct List<Map>, not a String
            val fieldsArray = requirements["fields"] as? List<*>
            val parsedFields = if (fieldsArray != null) {
                fieldsArray.mapNotNull { fieldItem ->
                    try {
                        val fieldMap = fieldItem as? Map<*, *>
                        if (fieldMap != null) {
                            // Parse options for dropdown/select fields
                            @Suppress("UNCHECKED_CAST")
                            val options = if (fieldMap["type"]?.toString()?.equals("select", ignoreCase = true) == true) {
                                (fieldMap["options"] as? List<*>)?.map { it.toString() }
                            } else {
                                null
                            }
                            
                            RequirementField(
                                name = fieldMap["name"]?.toString() ?: "",
                                label = fieldMap["label"]?.toString() ?: "",
                                type = fieldMap["type"]?.toString() ?: "text",
                                required = fieldMap["required"] as? Boolean ?: false,
                                description = fieldMap["description"]?.toString(),
                                allowed_types = fieldMap["allowed_types"]?.toString(),
                                options = options
                            )
                        } else null
                    } catch (e: Exception) {
                        android.util.Log.e("RequestType", "Error parsing field: $fieldItem", e)
                        null
                    }
                }
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

// Adding missing model classes
data class RequestDetails(
    val id: String,
    val request_id: Int? = null,
    val document_type: String,
    val purpose: String,
    val status: String,
    val submitted_at: String,
    val updated_at: String,
    val can_edit: Boolean,
    val submissions: List<RequirementSubmission>? = null
) {
    fun parseRequirements(): RequirementsData {
        return try {
            RequirementsData()
        } catch (e: Exception) {
            RequirementsData()
        }
    }
}

data class RequirementSubmission(
    val requirement_id: String,
    val file_path: String?,
    val submission_status: String
)

data class RequestUpdateData(
    val purpose: String,
    val requirements: List<RequirementUpdateItem>
)

data class RequirementUpdateItem(
    val id: String,
    val value: String
)

data class RequirementNote(
    val note_id: String,
    val request_id: String,
    val admin_id: String,
    val requirement_name: String?,
    val note: String?,
    val created_at: String,
    val admin_name: String? = null,
    val first_name: String? = null,
    val last_name: String? = null
) {
    constructor(
        note_id: Int,
        request_id: Int,
        admin_id: Int,
        requirement_name: String?,
        note: String,
        created_at: String,
        first_name: String?,
        last_name: String?
    ) : this(
        note_id = note_id.toString(),
        request_id = request_id.toString(),
        admin_id = admin_id.toString(),
        requirement_name = requirement_name,
        note = note,
        created_at = created_at,
        first_name = first_name,
        last_name = last_name
    )
    
    fun getAdminName(): String {
        return if (admin_name != null) {
            admin_name
        } else if (first_name != null && last_name != null) {
            "$first_name $last_name"
        } else {
            "Admin"
        }
    }
    
    fun getFormattedDate(): String {
        return try {
            val inputFormat = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.getDefault())
            val outputFormat = SimpleDateFormat("MMM dd, yyyy", Locale.getDefault())
            val date = inputFormat.parse(created_at)
            outputFormat.format(date ?: Date())
        } catch (e: Exception) {
            created_at
        }
    }
}

// Add new RequestNote class to match the API format
data class RequestNote(
    val note_id: Int,
    val request_id: Int, 
    val admin_id: Int,
    val note: String? = null,
    val created_at: String,
    val content: @RawValue Map<String, Any>? = null
) {
    fun getFormattedContent(): String {
        return when {
            // If note has text content directly
            note?.isNotBlank() == true -> 
                note
            // If note has content as a map, format it nicely
            content?.isNotEmpty() == true -> {
                val contentBuilder = StringBuilder()
                content.forEach { (key, value) ->
                    // Skip internal fields that start with underscore
                    if (!key.startsWith("_")) {
                        // Capitalize the key and format value
                        val formattedKey = key.split("_")
                            .joinToString(" ") { it.replaceFirstChar { char -> 
                                if (char.isLowerCase()) char.titlecase(Locale.getDefault()) else char.toString() 
                            } }
                        contentBuilder.append("$formattedKey: $value\n")
                    }
                }
                contentBuilder.toString().trim()
            }
            else -> "No detailed message available"
        }
    }
} 