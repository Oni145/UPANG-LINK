package com.phinma.upang.ui.home

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.phinma.upang.data.model.Request
import com.phinma.upang.data.model.RequirementNote
import com.phinma.upang.data.repository.RequestRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.Locale
import javax.inject.Inject
import android.util.Log

@HiltViewModel
class HomeViewModel @Inject constructor(
    private val requestRepository: RequestRepository
) : ViewModel() {
    
    private val _updates = MutableLiveData<List<Update>>()
    val updates: LiveData<List<Update>> = _updates
    
    private val _isLoading = MutableLiveData<Boolean>()
    val isLoading: LiveData<Boolean> = _isLoading
    
    private val _errorMessage = MutableLiveData<String?>()
    val errorMessage: LiveData<String?> = _errorMessage
    
    private val dateFormat = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.getDefault())
    private val displayDateFormat = SimpleDateFormat("MMM dd, yyyy", Locale.getDefault())
    
    private val TAG = "HomeViewModel"
    
    init {
        loadRequestUpdates()
    }
    
    fun loadRequestUpdates() {
        viewModelScope.launch {
            try {
                _isLoading.value = true
                _errorMessage.value = null
                
                // Get all the user's requests
                val result = requestRepository.getRequests()
                
                if (result.isSuccess) {
                    val requests = result.getOrNull() ?: emptyList()
                    
                    // Sort requests by updated_at date (most recent first)
                    val sortedRequests = requests
                        .filter { request -> request.status?.name != "PENDING" } // Exclude PENDING requests
                        .sortedByDescending { 
                        try {
                            dateFormat.parse(it.updated_at)?.time ?: 0
                        } catch (e: Exception) {
                            0L
                        }
                    }
                    
                    // Take most recent 5 updates
                    val recentUpdates = sortedRequests.take(5)
                    
                    // Convert to Update objects
                    val updates = mutableListOf<Update>()
                    
                    // Process request status updates
                    for (request in recentUpdates) {
                        try {
                            val date = try {
                                val parsed = dateFormat.parse(request.updated_at)
                                parsed?.let { displayDateFormat.format(it) } ?: "Unknown date"
                            } catch (e: Exception) {
                                "Unknown date"
                            }
                            
                            // Use safe calls to prevent null pointer exceptions
                            val requestType = request.request_type.orEmpty()
                            
                            // Create title based on request type and status
                            val title = when (request.status?.name) {
                                "APPROVED" -> "Request Approved: $requestType"
                                "PENDING" -> "Request Pending: $requestType"
                                "IN_PROGRESS" -> "Request In Progress: $requestType"
                                "COMPLETED" -> "Request Completed: $requestType"
                                "REJECTED" -> "Request Rejected: $requestType"
                                else -> "Request Update: $requestType"
                            }
                            
                            // Create description based on status
                            val description = when (request.status?.name) {
                                "APPROVED" -> "Your request for $requestType has been approved and is now in processing. Tracking No: ${request.tracking_number ?: "N/A"}"
                                "PENDING" -> "Your request for $requestType is pending approval. Tracking No: ${request.tracking_number ?: "N/A"}"
                                "IN_PROGRESS" -> "Your request for $requestType is currently being processed. Tracking No: ${request.tracking_number ?: "N/A"}"
                                "COMPLETED" -> "Your request for $requestType has been completed. Tracking No: ${request.tracking_number ?: "N/A"}"
                                "REJECTED" -> "Your request for $requestType has been rejected. ${request.remarks ?: ""} Tracking No: ${request.tracking_number ?: "N/A"}"
                                else -> "Status update for your $requestType request. Tracking No: ${request.tracking_number ?: "N/A"}"
                            }
                            
                            updates.add(Update(
                                id = request.id ?: request.request_id.toString(),
                                title = title,
                                description = description,
                                date = date,
                                status = request.status?.name?.lowercase() ?: "pending",
                                type = UpdateType.REQUEST_STATUS
                            ))
                            
                            // Try to fetch admin notes for this request
                            try {
                                val notesResult = requestRepository.getRequestNotes(request.request_id.toString())
                                if (notesResult.isSuccess) {
                                    val notes = notesResult.getOrNull() ?: emptyList()
                                    
                                    // Add each note as an update
                                    for (note in notes) {
                                        val noteDate = note.getFormattedDate()
                                        val adminName = note.getAdminName()
                                        
                                        updates.add(Update(
                                            id = "note_${note.note_id}",
                                            title = "Message from $adminName",
                                            description = note.note + "\n\nFor your ${request.request_type} request (Tracking No: ${request.tracking_number ?: "N/A"})",
                                            date = noteDate,
                                            status = request.status?.name?.lowercase() ?: "pending",
                                            type = UpdateType.ADMIN_NOTE
                                        ))
                                    }
                                }
                            } catch (e: Exception) {
                                Log.e(TAG, "Error fetching notes for request ${request.request_id}", e)
                            }
                        } catch (e: Exception) {
                            Log.e(TAG, "Error creating update for request ${request.id}", e)
                        }
                    }
                    
                    // Sort all updates by date (most recent first)
                    val sortedAllUpdates = updates.sortedByDescending { 
                        try {
                            dateFormat.parse(it.date)?.time ?: 0
                        } catch (e: Exception) {
                            0L
                        }
                    }
                    
                    // Take at most 10 updates to show
                    _updates.value = sortedAllUpdates.take(10)
                    Log.d(TAG, "Loaded ${sortedAllUpdates.size} updates")
                } else {
                    val error = result.exceptionOrNull()
                    _errorMessage.value = error?.message ?: "Failed to load updates"
                    Log.e(TAG, "Error loading updates: ${error?.message}")
                    _updates.value = emptyList()
                }
            } catch (e: Exception) {
                _errorMessage.value = e.message ?: "An unexpected error occurred"
                Log.e(TAG, "Exception loading updates", e)
                _updates.value = emptyList()
            } finally {
                _isLoading.value = false
            }
        }
    }
    
    fun clearError() {
        _errorMessage.value = null
    }
} 