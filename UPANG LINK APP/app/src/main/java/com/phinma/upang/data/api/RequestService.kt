package com.phinma.upang.data.api

import com.phinma.upang.data.model.ApiResponse
import com.phinma.upang.data.model.CreateRequestResponse
import com.phinma.upang.data.model.RequestType
import com.phinma.upang.data.model.RequirementNote
import okhttp3.MultipartBody
import okhttp3.RequestBody
import retrofit2.Response
import retrofit2.http.*
import retrofit2.http.Path

interface RequestService {
    @GET("requests/types")
    suspend fun getRequestTypes(): Response<ApiResponse<List<RequestType>>>
    
    @GET("requests/types/{typeId}")
    suspend fun getRequestTypeDetails(@Path("typeId") typeId: Int): Response<ApiResponse<RequestType>>
    
    @Multipart
    @POST("requests/create.php")
    suspend fun createRequest(
        @Part("type_id") typeId: RequestBody,
        @Part("purpose") purpose: RequestBody,
        @Part("student_id") studentId: RequestBody
    ): Response<ApiResponse<CreateRequestResponse>>
    
    @Multipart
    @POST("requests/create.php")
    suspend fun createRequestWithFiles(
        @Part("type_id") typeId: RequestBody,
        @Part("purpose") purpose: RequestBody,
        @Part("student_id") studentId: RequestBody,
        @Part files: List<MultipartBody.Part>
    ): Response<ApiResponse<CreateRequestResponse>>
    
    @GET("requests/{requestId}/notes")
    suspend fun getRequestNotes(@Path("requestId") requestId: String): Response<ApiResponse<List<RequirementNote>>>
} 