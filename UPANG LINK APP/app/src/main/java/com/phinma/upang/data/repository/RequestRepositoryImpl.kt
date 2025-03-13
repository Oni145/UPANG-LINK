package com.phinma.upang.data.repository

import android.content.Context
import android.util.Log
import com.phinma.upang.data.api.RequestApi
import com.phinma.upang.data.model.*
import dagger.hilt.android.qualifiers.ApplicationContext
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.RequestBody.Companion.toRequestBody
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
            val response = api.getRequest(id)
            response.data?.let {
                Result.success(it)
            } ?: Result.failure(Exception(response.message))
        } catch (e: Exception) {
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
                .toRequestBody("text/plain".toMediaTypeOrNull())
            val purposeBody = purpose
                .toRequestBody("text/plain".toMediaTypeOrNull())

            val fileParts = files.map { file ->
                MultipartBody.Part.createFormData(
                    name = "files[]",
                    filename = file.name,
                    body = file.asRequestBody("application/octet-stream".toMediaTypeOrNull())
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
                body = file.asRequestBody("application/octet-stream".toMediaTypeOrNull())
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
            val response = api.cancelRequest(id)
            if (response.status == "success") {
                Result.success(Unit)
            } else {
                Result.failure(Exception(response.message))
            }
        } catch (e: Exception) {
            Result.failure(e)
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
            if (e.message?.contains("404") == true) {
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
                Result.failure(e)
            }
        }
    }
} 