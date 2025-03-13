package com.phinma.upang.ui.requests

import android.app.Application
import android.content.Context
import android.net.Uri
import android.provider.OpenableColumns
import android.util.Log
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.viewModelScope
import com.phinma.upang.data.api.RequestService
import com.phinma.upang.data.model.CreateRequestResponse
import com.phinma.upang.data.model.RequestType
import com.phinma.upang.data.model.RequirementField
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import okhttp3.RequestBody.Companion.asRequestBody
import okio.source
import okio.buffer
import java.io.File
import java.io.FileOutputStream
import javax.inject.Inject

// Extension functions to safely check if something is empty based on type
fun Any?.isNullOrEmptyStringSafe(): Boolean {
    if (this == null) return true
    return when (this) {
        is String -> this.isEmpty()
        is Map<*, *> -> this.isEmpty()
        is Collection<*> -> this.isEmpty()
        is Array<*> -> this.isEmpty()
        else -> false // Non-empty for other types
    }
}

fun Any?.isNotEmptyStringSafe(): Boolean {
    return !this.isNullOrEmptyStringSafe()
}

@HiltViewModel
class CreateRequestViewModel @Inject constructor(
    private val requestService: RequestService,
    application: Application
) : AndroidViewModel(application) {

    private val _requestTypes = MutableStateFlow<List<RequestType>>(emptyList())
    val requestTypes: StateFlow<List<RequestType>> = _requestTypes

    // Add a map to cache which request types have required fields
    private val requestTypesWithRequiredFields = mutableMapOf<Int, Boolean>()

    private val _requirements = MutableStateFlow<List<RequirementField>>(emptyList())
    val requirements: StateFlow<List<RequirementField>> = _requirements

    private val _requirementsNeeded = MutableStateFlow<Boolean>(true)
    val requirementsNeeded: StateFlow<Boolean> = _requirementsNeeded

    private val _loading = MutableStateFlow(false)
    val loading: StateFlow<Boolean> = _loading

    private val _error = MutableStateFlow<String?>(null)
    val error: StateFlow<String?> = _error

    private val _success = MutableStateFlow<CreateRequestResponse?>(null)
    val success: StateFlow<CreateRequestResponse?> = _success

    init {
        loadRequestTypes()
    }

    private fun loadRequestTypes() {
        viewModelScope.launch {
            try {
                _loading.value = true
                _error.value = null
                
                Log.d("CreateRequestVM", "Loading request types from API")
                val response = requestService.getRequestTypes()
                
                if (response.isSuccessful) {
                    response.body()?.data?.let { types ->
                        if (types.isEmpty()) {
                            _error.value = "No request types available. Please try again later."
                            Log.w("CreateRequestVM", "API returned empty request types list")
                        } else {
                            // Log each request type for debugging
                            types.forEach { type ->
                                Log.d("CreateRequestVM", "Received request type: name=${type.name}, typeId=${type.type_id}, raw=${type}")
                            }
                            _requestTypes.value = types
                            Log.d("CreateRequestVM", "Successfully loaded ${types.size} request types")
                        }
                    } ?: run {
                        _error.value = "Failed to load request types: Server returned empty response"
                        Log.e("CreateRequestVM", "API returned null response body")
                    }
                } else {
                    val errorBody = response.errorBody()?.string()
                    Log.e("CreateRequestVM", "API error response: $errorBody")
                    _error.value = "Error: ${response.message()}"
                }
            } catch (e: Exception) {
                _error.value = "Error connecting to server: ${e.message}"
                Log.e("CreateRequestVM", "Exception loading request types: ${e.message}", e)
            } finally {
                _loading.value = false
            }
        }
    }

    fun loadRequirements(requestType: RequestType) {
        _loading.value = true
        Log.d("CreateRequestVM", "Loading requirements for ${requestType.name}")
        
        try {
            // Parse the requirements from the request type
            val requirementsMap = requestType.requirements as? Map<*, *>
            
            if (requirementsMap != null && requirementsMap.containsKey("fields")) {
                // Get the fields array from the requirements map
                @Suppress("UNCHECKED_CAST")
                val fieldsArray = requirementsMap["fields"] as? List<*>
                
                if (fieldsArray != null && fieldsArray.isNotEmpty()) {
                    // Convert each field map to a RequirementField object
                    val fields = fieldsArray.mapNotNull { fieldItem ->
                        try {
                            val fieldMap = fieldItem as? Map<*, *>
                            if (fieldMap != null) {
                                // Parse options for dropdown fields
                                @Suppress("UNCHECKED_CAST")
                                val options = if (fieldMap["type"]?.toString()?.equals("dropdown", ignoreCase = true) == true) {
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
                            Log.e("CreateRequestVM", "Error parsing field: $fieldItem", e)
                            null
                        }
                    }
                    
                    if (fields.isNotEmpty()) {
                        _requirements.value = fields
                        _requirementsNeeded.value = true
                        requestTypesWithRequiredFields[requestType.type_id] = true
                        Log.d("CreateRequestVM", "Loaded ${fields.size} requirements")
                    } else {
                        _requirements.value = emptyList()
                        _requirementsNeeded.value = false
                        requestTypesWithRequiredFields[requestType.type_id] = false
                        Log.d("CreateRequestVM", "No requirements found")
                    }
                } else {
                    _requirements.value = emptyList()
                    _requirementsNeeded.value = false
                    requestTypesWithRequiredFields[requestType.type_id] = false
                    Log.d("CreateRequestVM", "No requirements array found")
                }
            } else {
                _requirements.value = emptyList()
                _requirementsNeeded.value = false
                requestTypesWithRequiredFields[requestType.type_id] = false
                Log.d("CreateRequestVM", "No requirements map found")
            }
        } catch (e: Exception) {
            Log.e("CreateRequestVM", "Error parsing requirements: ${e.message}", e)
            _requirements.value = emptyList()
            _requirementsNeeded.value = false
            requestTypesWithRequiredFields[requestType.type_id] = false
        } finally {
            _loading.value = false
        }
    }
    
    private fun createDefaultStudentIdRequirement(requestType: RequestType) {
        Log.d("CreateRequestVM", "Creating default requirement for ${requestType.name}")
        
        // Check if this is a uniform request
        if (requestType.name.contains("Uniform", ignoreCase = true)) {
            val requirements = mutableListOf<RequirementField>()
            
            // Add student ID number field (text, not file)
            val studentIdRequirement = RequirementField(
                name = "student_id_number",
                label = if (requestType.name.contains("PE", ignoreCase = true)) 
                    "Student ID Number (PE)" else "Student ID Number (School)",
                type = "text",
                required = true,
                description = "Enter your student ID number",
                allowed_types = null,
                options = null
            )
            requirements.add(studentIdRequirement)
            
            // Add uniform size field as dropdown with standard sizes
            val uniformSizeRequirement = RequirementField(
                name = "uniform_size",
                label = if (requestType.name.contains("PE", ignoreCase = true)) 
                    "PE Uniform Size" else "School Uniform Size",
                type = "dropdown",
                required = true,
                description = "Please select your uniform size (check size chart for measurements)",
                allowed_types = null,
                options = listOf("XXS (0)", "XS (2-4)", "S (6-8)", "M (10-12)", "L (14-16)", "XL (18-20)", "2XL (22-24)", "3XL (26-28)", "4XL (30-32)")
            )
            requirements.add(uniformSizeRequirement)
            
            _requirements.value = requirements
            _requirementsNeeded.value = true
            requestTypesWithRequiredFields[requestType.type_id] = true
            Log.d("CreateRequestVM", "Created ${requirements.size} requirements for ${requestType.name}")
        } else {
            // Default for other request types
            val yearLevelRequirement = RequirementField(
                name = "year_level",
                label = "Year Level",
                type = "dropdown",
                required = true,
                description = "Select your year level",
                allowed_types = null,
                options = listOf("1st Year", "2nd Year", "3rd Year", "4th Year", "5th Year")
            )
            
            _requirements.value = listOf(yearLevelRequirement)
            _requirementsNeeded.value = true
            requestTypesWithRequiredFields[requestType.type_id] = true
            Log.d("CreateRequestVM", "Created year level requirement for ${requestType.name}")
        }
    }

    fun createRequest(
        typeId: Int,
        purpose: String,
        files: Map<String, Uri>,
        textValues: Map<String, String>
    ) {
        try {
            _loading.value = true
            _error.value = null
            
            Log.d("CreateRequestVM", "Starting request creation: typeId=$typeId, purpose=$purpose")

            // Validate type_id first
            if (typeId <= 0) {
                _error.value = "Invalid request type selected"
                _loading.value = false
                return
            }

            // Create request bodies
            val typeIdBody = typeId.toString().toRequestBody("text/plain".toMediaTypeOrNull())
            val purposeBody = purpose.toRequestBody("text/plain".toMediaTypeOrNull())
            val studentIdBody = (textValues["student_id"] ?: "").toRequestBody("text/plain".toMediaTypeOrNull())
            
            // Create multipart file parts if any
            val fileParts = mutableListOf<MultipartBody.Part>()
            val context = getApplication<Application>().applicationContext
            
            files.forEach { (fieldName, uri) ->
                try {
                    // Get file name
                    val fileName = getFileNameFromUri(context, uri) ?: "file"
                    
                    // Create a temporary file
                    val inputStream = context.contentResolver.openInputStream(uri)
                    val file = File(context.cacheDir, fileName)
                    
                    try {
                        FileOutputStream(file).use { outputStream ->
                            inputStream?.copyTo(outputStream)
                        }
                        
                        // Create request body from file
                        val mediaType = context.contentResolver.getType(uri)?.toMediaTypeOrNull() 
                            ?: "application/octet-stream".toMediaTypeOrNull()
                        
                        // Create RequestBody from file content
                        val requestFile = file.readBytes().toRequestBody(mediaType)
                        
                        // Add to file parts
                        val filePart = MultipartBody.Part.createFormData(
                            fieldName,
                            fileName,
                            requestFile
                        )
                        
                        fileParts.add(filePart)
                        Log.d("CreateRequestVM", "Added file part for field: $fieldName, file: $fileName")
                    } finally {
                        inputStream?.close()
                    }
                } catch (e: Exception) {
                    Log.e("CreateRequestVM", "Error processing file for $fieldName: ${e.message}", e)
                }
            }
            
            // Create request
            viewModelScope.launch {
                try {
                    Log.d("CreateRequestVM", "Sending request with: type_id=$typeId, purpose=$purpose, student_id=${textValues["student_id"]}")
                    
                    val response = if (fileParts.isEmpty()) {
                        // Simple request without files
                        requestService.createRequest(
                            typeId = typeIdBody,
                            purpose = purposeBody,
                            studentId = studentIdBody
                        )
                    } else {
                        // Request with files
                        requestService.createRequestWithFiles(
                            typeId = typeIdBody,
                            purpose = purposeBody,
                            studentId = studentIdBody,
                            files = fileParts
                        )
                    }

                    if (response.isSuccessful) {
                        val apiResponse = response.body()
                        if (apiResponse?.status == "success") {
                            _success.value = apiResponse.data
                            Log.d("CreateRequestVM", "Request created successfully: ${apiResponse.data}")
                        } else {
                            _error.value = apiResponse?.message ?: "Unknown error occurred"
                            Log.e("CreateRequestVM", "Error creating request: ${apiResponse?.message}")
                        }
                    } else {
                        val errorBody = response.errorBody()?.string()
                        Log.e("CreateRequestVM", "API error response: $errorBody")
                        _error.value = try {
                            // Try to parse error message from JSON
                            val errorJson = com.google.gson.JsonParser().parse(errorBody).asJsonObject
                            errorJson.get("message")?.asString ?: "Failed to create request"
                        } catch (e: Exception) {
                            errorBody ?: "Failed to create request"
                        }
                    }
                } catch (e: Exception) {
                    Log.e("CreateRequestVM", "Exception in API call: ${e.message}", e)
                    _error.value = "Error: ${e.message}"
                } finally {
                    _loading.value = false
                }
            }
            
        } catch (e: Exception) {
            Log.e("CreateRequestVM", "Exception creating request: ${e.message}")
            _error.value = "Error: ${e.message}"
            _loading.value = false
        }
    }

    private fun getFileNameFromUri(context: Context, uri: Uri): String? {
        // Try to get the display name from the content provider
        val cursor = context.contentResolver.query(uri, null, null, null, null)
        cursor?.use {
            if (it.moveToFirst()) {
                val displayNameIndex = it.getColumnIndex(OpenableColumns.DISPLAY_NAME)
                if (displayNameIndex != -1) {
                    return it.getString(displayNameIndex)
                }
            }
        }
        
        // Fallback to the last segment of the URI path
        return uri.lastPathSegment
    }

    fun clearError() {
        _error.value = null
    }

    fun clearSuccess() {
        _success.value = null
    }
} 