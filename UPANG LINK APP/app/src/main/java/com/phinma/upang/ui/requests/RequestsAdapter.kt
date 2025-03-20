package com.phinma.upang.ui.requests

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.core.view.isVisible
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.phinma.upang.data.model.Request
import com.phinma.upang.data.model.RequestStatus
import com.phinma.upang.databinding.ItemRequestBinding
import java.text.SimpleDateFormat
import java.util.Locale
import androidx.core.content.ContextCompat
import com.phinma.upang.R
import java.text.ParseException
import android.view.View

class RequestsAdapter(
    private val onItemClick: (Request) -> Unit
) : ListAdapter<Request, RequestsAdapter.RequestViewHolder>(RequestDiffCallback()) {

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): RequestViewHolder {
        val binding = ItemRequestBinding.inflate(
            LayoutInflater.from(parent.context),
            parent,
            false
        )
        return RequestViewHolder(binding)
    }

    override fun onBindViewHolder(holder: RequestViewHolder, position: Int) {
        val request = getItem(position)
        holder.bind(request)
    }

    inner class RequestViewHolder(private val binding: ItemRequestBinding) :
        RecyclerView.ViewHolder(binding.root) {

        init {
            binding.root.setOnClickListener {
                val position = adapterPosition
                if (position != RecyclerView.NO_POSITION) {
                    onItemClick(getItem(position))
                }
            }
            
            // Remove cancel button click listener
            // Always hide the cancel button
            binding.cancelButton.visibility = View.GONE
        }

        fun bind(request: Request) {
            binding.apply {
                requestTitle.text = request.request_type
                
                // Set tracking number if available
                if (request.tracking_number != null && request.tracking_number.isNotEmpty()) {
                    requestTrackingNumber.text = request.tracking_number
                    requestTrackingNumber.isVisible = true
                } else {
                    // Use the ID as fallback
                    requestTrackingNumber.text = request.id
                    requestTrackingNumber.isVisible = true
                }
                
                // Format and set the date
                try {
                    val dateFormat = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.getDefault())
                    val displayFormat = SimpleDateFormat("MMM dd, yyyy", Locale.getDefault())
                    
                    val date = dateFormat.parse(request.submitted_at)
                    date?.let {
                        requestDate.text = displayFormat.format(it)
                    }
                } catch (e: ParseException) {
                    requestDate.text = request.submitted_at
                }
                
                // Set status and update UI based on status
                updateStatusViews(request.status ?: RequestStatus.PENDING)
                
                // Debug log to see what values we're getting from the API
                android.util.Log.d("RequestsAdapter", "Request type: ${request.request_type}")
                android.util.Log.d("RequestsAdapter", "Request type object: ${request.type}")
                android.util.Log.d("RequestsAdapter", "Processing time from type: ${request.type?.processing_time}")
                android.util.Log.d("RequestsAdapter", "Processing time from request: ${request.processing_time}")
                
                // Get processing time from the API response or use fallback mapping
                val processingTimeValue = when {
                    // First priority: Use the processing_time from the type object if available
                    request.type != null && !request.type.processing_time.isNullOrEmpty() -> {
                        android.util.Log.d("RequestsAdapter", "Using processing time from type object: ${request.type.processing_time}")
                        request.type.processing_time
                    }
                    // Second priority: Use the processing_time directly from the request if available
                    !request.processing_time.isNullOrEmpty() && request.processing_time != "null" -> {
                        android.util.Log.d("RequestsAdapter", "Using processing time from request: ${request.processing_time}")
                        request.processing_time
                    }
                    // Third priority: Use fallback mapping based on request type
                    else -> {
                        android.util.Log.d("RequestsAdapter", "Using fallback mapping for processing time")
                        when (request.request_type) {
                            "Course Module Request" -> "1-2 working days"
                            "Transcript of Records" -> "5-7 working days"
                            "Certificate of Grades" -> "3-5 working days"
                            "Certificate of Enrollment" -> "1-2 working days"
                            "Certificate of Good Moral" -> "3-5 working days"
                            "Certificate of Graduation" -> "3-5 working days"
                            "Enrollment Certificate" -> "2-3 working days"
                            "ID Replacement" -> "5-7 working days"
                            "New Student ID" -> "5-7 working days"
                            "PE Uniform Request" -> "3-5 working days"
                            "School Uniform Request" -> "3-5 working days"
                            else -> "5-7 working days" // Default
                        }
                    }
                }
                
                processingTime.text = "Processing Time: $processingTimeValue"
                
                // Always hide the cancel button
                cancelButton.visibility = View.GONE
            }
        }
        
        private fun updateStatusViews(status: RequestStatus) {
            val (colorRes, text) = when (status) {
                RequestStatus.PENDING -> {
                    Pair(R.color.status_pending, "Pending")
                }
                RequestStatus.APPROVED -> {
                    Pair(R.color.status_approved, "Approved")
                }
                RequestStatus.IN_PROGRESS -> {
                    Pair(R.color.status_pending, "In Progress")
                }
                RequestStatus.COMPLETED -> {
                    Pair(R.color.status_approved, "Completed")
                }
                RequestStatus.REJECTED -> {
                    Pair(R.color.status_rejected, "Rejected")
                }
            }

            binding.apply {
                requestStatus.text = text
                requestStatus.setTextColor(ContextCompat.getColor(root.context, colorRes))
                // The statusIndicator is not in the layout, so we'll skip setting it
                
                // Always hide the cancel button regardless of status
                cancelButton.visibility = View.GONE
            }
        }
    }
}

class RequestDiffCallback : DiffUtil.ItemCallback<Request>() {
    override fun areItemsTheSame(oldItem: Request, newItem: Request): Boolean {
        return oldItem.id == newItem.id
    }

    override fun areContentsTheSame(oldItem: Request, newItem: Request): Boolean {
        return oldItem == newItem
    }
} 