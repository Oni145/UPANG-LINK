package com.phinma.upang.data.repository

import com.phinma.upang.data.model.*
import java.io.File

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
} 