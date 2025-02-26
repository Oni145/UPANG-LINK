package com.phinma.upang.data.api

import com.phinma.upang.data.model.*
import okhttp3.MultipartBody
import okhttp3.RequestBody
import retrofit2.http.*

interface RequestApi {
    @GET("requests")
    suspend fun getRequests(): ApiResponse<List<Request>>

    @GET("requests/{id}")
    suspend fun getRequest(@Path("id") id: String): ApiResponse<Request>

    @Multipart
    @POST("requests")
    suspend fun createRequest(
        @Part("type_id") typeId: RequestBody,
        @Part("purpose") purpose: RequestBody,
        @Part files: List<MultipartBody.Part>
    ): ApiResponse<RequestCreateResponse>

    @GET("requests/types")
    suspend fun getRequestTypes(): ApiResponse<List<RequestType>>

    @GET("requests/types/{typeId}/requirements")
    suspend fun getRequestRequirements(@Path("typeId") typeId: Int): ApiResponse<List<Requirement>>

    @Multipart
    @POST("requests/{requestId}/requirements/{requirementId}")
    suspend fun uploadRequirement(
        @Path("requestId") requestId: String,
        @Path("requirementId") requirementId: String,
        @Part file: MultipartBody.Part
    ): ApiResponse<Unit>

    @DELETE("requests/{requestId}/requirements/{requirementId}")
    suspend fun deleteRequirement(
        @Path("requestId") requestId: String,
        @Path("requirementId") requirementId: String
    ): ApiResponse<Unit>

    @POST("requests/{id}/cancel")
    suspend fun cancelRequest(@Path("id") id: String): ApiResponse<Unit>

    @GET("requests/statistics")
    suspend fun getRequestStatistics(): ApiResponse<RequestStatistics>
} 