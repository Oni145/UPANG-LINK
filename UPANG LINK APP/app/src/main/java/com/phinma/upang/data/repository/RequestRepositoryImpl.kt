package com.phinma.upang.data.repository

import android.content.Context
import android.util.Log
import com.phinma.upang.data.api.RequestApi
import com.phinma.upang.data.model.*
import dagger.hilt.android.qualifiers.ApplicationContext
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.RequestBody.Companion.toRequestBody
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.MultipartBody
import okhttp3.RequestBody
import retrofit2.HttpException
import java.io.File
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class RequestRepositoryImpl @Inject constructor(
    private val api: RequestApi,
    @ApplicationContext private val context: Context
) : RequestRepository {

    companion object {
        private const val TAG = "RequestRepository"
    }

    override suspend fun getRequests(filter: RequestFilter?): Result<List<Request>> {
        return try {
            val response = api.getRequests()
            Log.d(TAG, "Response: $response")
            
            when (response.status?.lowercase()) {
                "success" -> {
                    val requests = response.data ?: emptyList()
                    Log.d(TAG, "Got ${requests.size} requests")
                    
                    // Apply filters if provided
                    val filteredRequests = if (filter != null) {
                        requests.filter { request ->
                            var matches = true
                            
                            // Filter by status
                            if (!filter.status.isNullOrEmpty()) {
                                matches = matches && request.status?.name.equals(filter.status, ignoreCase = true)
                            }
                            
                            // Filter by type
                            if (filter.type != null) {
                                matches = matches && request.type_id == filter.type
                            }
                            
                            // Filter by search query
                            if (!filter.searchQuery.isNullOrEmpty()) {
                                matches = matches && (
                                    request.document_type.contains(filter.searchQuery, ignoreCase = true) ||
                                    request.purpose?.contains(filter.searchQuery, ignoreCase = true) == true ||
                                    request.request_type.contains(filter.searchQuery, ignoreCase = true)
                                )
                            }
                            
                            matches
                        }
                    } else {
                        requests
                    }
                    
                    Result.success(filteredRequests)
                }
                "error" -> {
                    Log.d(TAG, "Error response: ${response.message}, code: ${response.code}")
                    when {
                        response.error_type == "MALFORMED_RESPONSE" -> {
                            Log.e(TAG, "Server returned malformed response")
                            Result.failure(Exception("Server error. Please try again later."))
                        }
                        response.message?.contains("Unauthorized", ignoreCase = true) == true ||
                        response.message?.contains("token", ignoreCase = true) == true -> {
                            Log.e(TAG, "Authentication error: ${response.message}")
                            Result.failure(Exception("Authentication failed. Please log in again."))
                        }
                        response.code == 404 || response.message?.contains("No requests found") == true -> {
                            Log.d(TAG, "No requests found")
                            Result.success(emptyList())
                        }
                        else -> {
                            Log.e(TAG, "Error response: ${response.message}")
                            Result.failure(Exception(response.message ?: "Unknown error"))
                        }
                    }
                }
                else -> {
                    Log.e(TAG, "Unknown response status: ${response.status}")
                    Result.failure(Exception("Server returned an invalid response"))
                }
            }
        } catch (e: Exception) {
            Log.e(TAG, "Error getting requests", e)
            when (e) {
                is HttpException -> {
                    when (e.code()) {
                        401 -> Result.failure(Exception("Authentication failed. Please log in again."))
                        404 -> Result.success(emptyList())
                        else -> Result.failure(Exception("Server error (${e.code()}). Please try again later."))
                    }
                }
                is com.google.gson.JsonSyntaxException,
                is com.google.gson.stream.MalformedJsonException -> {
                    Log.e(TAG, "JSON parsing error", e)
                    Result.failure(Exception("Server returned invalid data. Please try again later."))
                }
                else -> {
                    Log.e(TAG, "Unexpected error", e)
                    Result.failure(Exception("An unexpected error occurred. Please try again later."))
                }
            }
        }
    }

    override suspend fun getRequest(id: String): Result<Request> {
        return try {
            Log.d(TAG, "Fetching request with ID: $id")
            
            // Check if the ID is a tracking number
            val isNewFormat = id.matches(Regex("REQ-\\d{8}-\\d{4}"))
            val isOldFormat = id.matches(Regex("REQ-\\d{4}-\\d{3}"))
            
            if (isNewFormat) {
                Log.d(TAG, "ID is a new format tracking number: $id")
            } else if (isOldFormat) {
                Log.d(TAG, "ID is an old format tracking number: $id")
            } else {
                Log.e(TAG, "Invalid tracking number format: $id")
                return Result.failure(Exception("Invalid tracking number format. Expected format: REQ-YYYYMMDD-XXXX or REQ-YYYY-XXX"))
            }
            
            val response = api.getRequest(id)
            
            if (response.status == "success" && response.data != null) {
                Log.d(TAG, "Successfully fetched request: ${response.data.id}")
                Result.success(response.data)
            } else {
                Log.e(TAG, "Error fetching request: ${response.message}")
                Result.failure(Exception(response.message ?: "Failed to get request details"))
            }
        } catch (e: Exception) {
            Log.e(TAG, "Exception fetching request", e)
            Result.failure(e)
        }
    }

    override suspend fun createRequest(
        typeId: Int,
        purpose: String,
        files: List<File>
    ): Result<RequestCreateResponse> {
        return try {
            val typeIdBody = typeId.toString()
                .toRequestBody("text/plain".toMediaType())
            val purposeBody = purpose
                .toRequestBody("text/plain".toMediaType())

            val fileParts = files.map { file ->
                MultipartBody.Part.createFormData(
                    name = "files[]",
                    filename = file.name,
                    body = file.asRequestBody("application/octet-stream".toMediaType())
                )
            }

            val response = api.createRequest(typeIdBody, purposeBody, fileParts)
            response.data?.let {
                Result.success(it)
            } ?: Result.failure(Exception(response.message))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    override suspend fun getRequestTypes(): Result<List<RequestType>> {
        return try {
            val response = api.getRequestTypes()
            response.data?.let {
                Result.success(it)
            } ?: Result.failure(Exception(response.message))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    override suspend fun getRequestRequirements(typeId: Int): Result<List<Requirement>> {
        return try {
            val response = api.getRequestRequirements(typeId)
            response.data?.let {
                Result.success(it)
            } ?: Result.failure(Exception(response.message))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    override suspend fun uploadRequirement(
        requestId: String,
        requirementId: String,
        file: File
    ): Result<Unit> {
        return try {
            val filePart = MultipartBody.Part.createFormData(
                name = "file",
                filename = file.name,
                body = file.asRequestBody("application/octet-stream".toMediaType())
            )

            val response = api.uploadRequirement(requestId, requirementId, filePart)
            if (response.status == "success") {
                Result.success(Unit)
            } else {
                Result.failure(Exception(response.message))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    override suspend fun deleteRequirement(
        requestId: String,
        requirementId: String
    ): Result<Unit> {
        return try {
            val response = api.deleteRequirement(requestId, requirementId)
            if (response.status == "success") {
                Result.success(Unit)
            } else {
                Result.failure(Exception(response.message))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    override suspend fun cancelRequest(id: String): Result<Unit> {
        return try {
            Log.d(TAG, "Attempting to cancel request with ID: $id")
            
            // First get the request using getRequest method - this will have the internal request_id
            val requestResult = getRequest(id)
            
            if (requestResult.isFailure) {
                Log.e(TAG, "Error getting request: ${requestResult.exceptionOrNull()?.message}")
                return Result.failure(Exception("Failed to find request: ${requestResult.exceptionOrNull()?.message}"))
            }
            
            val request = requestResult.getOrNull() ?: return Result.failure(Exception("Request not found"))
            
            // Extract the internal request_id
            val internalRequestId = request.request_id.toString()
            Log.d(TAG, "Got internal request_id: $internalRequestId for tracking number: $id")
            
            // Now cancel the request using the internal request_id
            val requestBody = "{\"request_id\":\"$internalRequestId\"}".toRequestBody("application/json".toMediaType())
            val response = api.cancelRequest(requestBody)
            
            if (response.status == "success") {
                Log.d(TAG, "Successfully canceled request: $id")
                Result.success(Unit)
            } else {
                Log.e(TAG, "Error canceling request: ${response.message}")
                Result.failure(Exception(response.message ?: "Failed to cancel request"))
            }
        } catch (e: Exception) {
            Log.e(TAG, "Exception canceling request", e)
            
            // Check if it's a 401 Unauthorized error
            if (e is retrofit2.HttpException && e.code() == 401) {
                Log.e(TAG, "Authentication error (401) when canceling request")
                Result.failure(Exception("Authentication error. Please log in again."))
            } else {
                Result.failure(e)
            }
        }
    }

    override suspend fun getRequestStatistics(): Result<RequestStatistics> {
        return try {
            val response = api.getRequestStatistics()
            if (response.status == "error" && response.message?.contains("404") == true) {
                // Return empty statistics for 404 with all required parameters
                Result.success(RequestStatistics(
                    total = 0,
                    pending = 0,
                    completed = 0,
                    inProgress = 0,
                    cancelled = 0,
                    byType = emptyMap(),
                    byMonth = emptyMap()
                ))
            } else {
                response.data?.let {
                    Result.success(it)
                } ?: Result.failure(Exception(response.message))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    override suspend fun getRequestDetails(requestId: String): Result<RequestDetails> {
        return try {
            val response = api.getRequestDetails(requestId)
            
            // Fix the type casting issue
            if (response.status == "success" && response.data != null) {
                when (val data = response.data) {
                    is RequestDetails -> Result.success(data)
                    is Map<*, *> -> {
                        try {
                            val requestDetails = RequestDetails(
                                id = data["id"]?.toString() ?: "",
                                request_id = data["request_id"]?.toString()?.toIntOrNull(),
                                document_type = data["document_type"]?.toString() ?: "",
                                purpose = data["purpose"]?.toString() ?: "",
                                status = data["status"]?.toString() ?: "",
                                submitted_at = data["submitted_at"]?.toString() ?: "",
                                updated_at = data["updated_at"]?.toString() ?: "",
                                can_edit = data["can_edit"] as? Boolean ?: false,
                                submissions = null // We'll handle this separately if needed
                            )
                            Result.success(requestDetails)
                        } catch (e: Exception) {
                            Log.e(TAG, "Error converting Map to RequestDetails: ${e.message}")
                            Result.failure(Exception("Failed to parse request details"))
                        }
                    }
                    else -> {
                        Log.e(TAG, "Unexpected data type for request details: ${data?.javaClass?.name}")
                        Result.failure(Exception("Unexpected data type for request details"))
                    }
                }
            } else {
                Result.failure(Exception(response.message ?: "Failed to get request details"))
            }
        } catch (e: Exception) {
            Log.e(TAG, "Error getting request details", e)
            Result.failure(e)
        }
    }

    override suspend fun updateRequestDetails(requestId: String, updateData: RequestUpdateData): Result<Unit> {
        return try {
            val response = api.updateRequestDetails(requestId, updateData)
            if (response.status == "success") {
                Result.success(Unit)
            } else {
                Result.failure(Exception(response.message ?: "Failed to update request details"))
            }
        } catch (e: Exception) {
            Log.e(TAG, "Error updating request details", e)
            Result.failure(e)
        }
    }

    override suspend fun getRequestNotes(requestId: String): Result<List<RequirementNote>> {
        return try {
            Log.d(TAG, "Fetching notes for request ID: $requestId")
            val response = api.getRequestNotes(requestId)
            
            // Debug output
            if (response.status == "success" && response.data != null) {
                val debugData = response.data
                Log.d(TAG, "Response data class: ${debugData.javaClass.name}")
                if (debugData is List<*> && debugData.isNotEmpty()) {
                    val firstItem = debugData[0]
                    Log.d(TAG, "First item class: ${firstItem?.javaClass?.name}")
                }
            }

            if (response.status == "success") {
                val data = response.data
                // Convert Map objects to RequirementNote objects
                val notes = if (data is List<*>) {
                    data.mapNotNull { item ->
                        if (item is Map<*, *>) {
                            try {
                                RequirementNote(
                                    note_id = (item["note_id"]?.toString() ?: "0"),
                                    request_id = (item["request_id"]?.toString() ?: requestId),
                                    admin_id = (item["admin_id"]?.toString() ?: "0"),
                                    requirement_name = item["requirement_name"]?.toString(),
                                    note = item["note"]?.toString(),
                                    created_at = item["created_at"]?.toString() ?: "",
                                    admin_name = item["admin_name"]?.toString(),
                                    first_name = item["first_name"]?.toString(),
                                    last_name = item["last_name"]?.toString()
                                )
                            } catch (e: Exception) {
                                Log.e(TAG, "Error converting map to RequirementNote: ${e.message}")
                                null
                            }
                        } else if (item is RequirementNote) {
                            item
                        } else {
                            null
                        }
                    }
                } else {
                    emptyList()
                }
                Log.d(TAG, "Successfully fetched ${notes.size} notes for request: $requestId")
                Result.success(notes)
            } else {
                // If there are no notes, return an empty list instead of an error
                if (response.status == "error" && (response.message?.contains("No notes found") == true || response.code == 404)) {
                    Log.d(TAG, "No notes found for request: $requestId")
                    Result.success(emptyList())
                } else {
                    Log.e(TAG, "Error fetching notes: ${response.message}")
                    Result.failure(Exception(response.message ?: "Failed to get request notes"))
                }
            }
        } catch (e: Exception) {
            Log.e(TAG, "Exception fetching notes for request: $requestId", e)
            
            // If the API endpoint isn't available yet, return an empty list instead of failing
            if (e is retrofit2.HttpException && (e.code() == 404 || e.code() == 501)) {
                Log.e(TAG, "API endpoint for notes not available (${e.code()})")
                Result.success(emptyList())
            } else {
                Result.failure(e)
            }
        }
    }
    
    override suspend fun getRequestRequirementNotes(requestId: String): Result<List<RequirementNote>> {
        return try {
            Log.d(TAG, "Fetching requirement notes for request ID: $requestId")
            
            // Try the direct endpoint first
            try {
                Log.d(TAG, "Using direct endpoint for requirement notes")
                val directResponse = api.getDirectRequirementNotes(requestId)
                
                if (directResponse.status == "success" && directResponse.data != null && directResponse.data is List<*>) {
                    val data = directResponse.data
                    Log.d(TAG, "Direct endpoint returned data: $data")
                    
                    val notes = (data as List<*>).mapNotNull { item ->
                        if (item is Map<*, *>) {
                            try {
                                RequirementNote(
                                    note_id = (item["note_id"]?.toString() ?: "0"),
                                    request_id = (item["request_id"]?.toString() ?: requestId),
                                    admin_id = (item["admin_id"]?.toString() ?: "0"),
                                    requirement_name = "General", // Default value
                                    note = item["note"]?.toString(),
                                    created_at = item["created_at"]?.toString() ?: "",
                                    admin_name = item["admin_name"]?.toString(),
                                    first_name = null,
                                    last_name = null
                                )
                            } catch (e: Exception) {
                                Log.e(TAG, "Error converting map from direct endpoint to RequirementNote: ${e.message}")
                                null
                            }
                        } else null
                    }
                    
                    if (notes.isNotEmpty()) {
                        Log.d(TAG, "Direct endpoint returned ${notes.size} notes: $notes")
                        return Result.success(notes)
                    } else {
                        Log.d(TAG, "Direct endpoint returned empty list, falling back to legacy endpoint")
                    }
                } else {
                    Log.d(TAG, "Direct endpoint failed or returned no data, falling back to legacy endpoint")
                }
            } catch (e: Exception) {
                Log.e(TAG, "Error using direct endpoint: ${e.message}")
                Log.d(TAG, "Falling back to legacy endpoint")
            }
            
            // Fall back to legacy endpoint
            val response = api.getRequestRequirementNotes(requestId)
            
            if (response.status == "success") {
                val data = response.data
                
                // Check if the response data is a list of requests instead of notes
                if (data != null && data is List<*> && data.isNotEmpty()) {
                    val firstItem = data[0]
                    
                    if (firstItem is Map<*, *> && firstItem.containsKey("tracking_number")) {
                        // This is likely a list of requests, not requirement notes
                        Log.d(TAG, "API returned request objects instead of notes. Using fallback.")
                        
                        // Try to get remarks from the main request object
                        // Fetch the request details first
                        val requestDetails = api.getRequest(requestId)
                        if (requestDetails.status == "success" && requestDetails.data != null) {
                            // Check if the request is rejected
                            val isRejected = requestDetails.data.status == RequestStatus.REJECTED
                            
                            // Create a synthetic note from the remarks if available
                            val remarks = requestDetails.data.remarks
                            if (!remarks.isNullOrEmpty()) {
                                val syntheticNote = RequirementNote(
                                    note_id = "0",
                                    request_id = requestId,
                                    admin_id = "0",
                                    requirement_name = "General",
                                    note = remarks,
                                    created_at = requestDetails.data.updated_at ?: "",
                                    admin_name = "Admin"
                                )
                                Log.d(TAG, "Created synthetic note from remarks: ${syntheticNote.note}")
                                return Result.success(listOf(syntheticNote))
                            }
                            // If no remarks but the request is rejected, create a fallback rejection note
                            else if (isRejected) {
                                val syntheticNote = RequirementNote(
                                    note_id = "0",
                                    request_id = requestId,
                                    admin_id = "0",
                                    requirement_name = "General",
                                    note = "Your request has been rejected.",
                                    created_at = requestDetails.data.updated_at ?: "",
                                    admin_name = "Admin"
                                )
                                Log.d(TAG, "Created synthetic rejection note for rejected request")
                                return Result.success(listOf(syntheticNote))
                            }
                        }
                        
                        // Return empty list if we couldn't create a synthetic note
                        Log.d(TAG, "No remarks available to create synthetic note")
                        return Result.success(emptyList())
                    }
                }
                
                // Standard processing for normal note response
                val notes = if (data is List<*>) {
                    data.mapNotNull { item ->
                        if (item is Map<*, *>) {
                            try {
                                RequirementNote(
                                    note_id = (item["note_id"]?.toString() ?: "0"),
                                    request_id = (item["request_id"]?.toString() ?: requestId),
                                    admin_id = (item["admin_id"]?.toString() ?: "0"),
                                    requirement_name = item["requirement_name"]?.toString(),
                                    note = item["note"]?.toString(),
                                    created_at = item["created_at"]?.toString() ?: "",
                                    admin_name = item["admin_name"]?.toString(),
                                    first_name = item["first_name"]?.toString(),
                                    last_name = item["last_name"]?.toString()
                                )
                            } catch (e: Exception) {
                                Log.e(TAG, "Error converting map to RequirementNote: ${e.message}")
                                null
                            }
                        } else if (item is RequirementNote) {
                            item
                        } else {
                            null
                        }
                    }
                } else {
                    emptyList()
                }
                Log.d(TAG, "Successfully fetched ${notes.size} requirement notes for request: $requestId")
                Result.success(notes)
            } else {
                // If there are no notes, return an empty list instead of an error
                if (response.status == "error" && (response.message?.contains("No notes found") == true || response.code == 404)) {
                    Log.d(TAG, "No requirement notes found for request: $requestId")
                    Result.success(emptyList())
                } else {
                    Log.e(TAG, "Error fetching requirement notes: ${response.message}")
                    Result.failure(Exception(response.message ?: "Failed to get request requirement notes"))
                }
            }
        } catch (e: Exception) {
            Log.e(TAG, "Exception fetching requirement notes for request: $requestId", e)
            
            // If the API endpoint isn't available yet, return an empty list instead of failing
            if (e is retrofit2.HttpException && (e.code() == 404 || e.code() == 501)) {
                Log.e(TAG, "API endpoint for requirement notes not available (${e.code()})")
                Result.success(emptyList())
            } else {
                Result.failure(e)
            }
        }
    }
} 