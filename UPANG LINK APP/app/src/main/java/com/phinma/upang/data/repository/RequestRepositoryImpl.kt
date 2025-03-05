package com.phinma.upang.data.repository

import android.content.Context
import com.phinma.upang.data.api.RequestApi
import com.phinma.upang.data.model.*
import dagger.hilt.android.qualifiers.ApplicationContext
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import java.io.File
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class RequestRepositoryImpl @Inject constructor(
    private val api: RequestApi,
    @ApplicationContext private val context: Context
) : RequestRepository {

    override suspend fun getRequests(filter: RequestFilter?): Result<List<Request>> {
        return try {
            val response = api.getRequests()
            when (response.status) {
                "success" -> {
                    val requests = response.data?.map { request ->
                        // Parse the status string from the API response
                        val statusString = request.status?.toString() ?: request.request_type
                        val parsedStatus = try {
                            RequestStatus.fromString(statusString)
                        } catch (e: Exception) {
                            RequestStatus.PENDING
                        }
                        request.copy(status = parsedStatus)
                    } ?: emptyList()
                    // Apply filters if provided
                    val filteredRequests = filter?.let { f ->
                        requests.filter { request ->
                            var matches = true
                            f.status?.let { statusStr -> 
                                val filterStatus = RequestStatus.fromString(statusStr)
                                matches = matches && request.status == filterStatus
                            }
                            f.searchQuery?.let { query ->
                                matches = matches && (
                                    request.purpose.contains(query, ignoreCase = true) ||
                                    request.document_type.contains(query, ignoreCase = true)
                                )
                            }
                            matches
                        }
                    } ?: requests
                    Result.success(filteredRequests)
                }
                "error" -> {
                    if (response.message == "No requests found" || response.message?.contains("404") == true) {
                        Result.success(emptyList())
                    } else {
                        Result.failure(Exception(response.message ?: "Unknown error"))
                    }
                }
                else -> Result.failure(Exception("Unknown response status"))
            }
        } catch (e: Exception) {
            if (e.message?.contains("404") == true) {
                Result.success(emptyList())
            } else {
                Result.failure(e)
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