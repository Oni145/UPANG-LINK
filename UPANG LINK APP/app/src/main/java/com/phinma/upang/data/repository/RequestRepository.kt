package com.phinma.upang.data.repository

import com.phinma.upang.data.model.*
import java.io.File
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import android.util.Log

interface RequestRepository {
    suspend fun getRequests(filter: RequestFilter? = null): Result<List<Request>>
    suspend fun getRequest(id: String): Result<Request>
    suspend fun createRequest(typeId: Int, purpose: String, files: List<File>): Result<RequestCreateResponse>
    suspend fun getRequestTypes(): Result<List<RequestType>>
    suspend fun getRequestRequirements(typeId: Int): Result<List<Requirement>>
    suspend fun uploadRequirement(requestId: String, requirementId: String, file: File): Result<Unit>
    suspend fun deleteRequirement(requestId: String, requirementId: String): Result<Unit>
    suspend fun cancelRequest(id: String): Result<Unit>
    suspend fun getRequestStatistics(): Result<RequestStatistics>
    
    // Adding missing methods
    suspend fun getRequestDetails(requestId: String): Result<RequestDetails>
    suspend fun updateRequestDetails(requestId: String, updateData: RequestUpdateData): Result<Unit>
    
    // New method to get admin notes for a request
    suspend fun getRequestNotes(requestId: String): Result<List<RequirementNote>>
} 