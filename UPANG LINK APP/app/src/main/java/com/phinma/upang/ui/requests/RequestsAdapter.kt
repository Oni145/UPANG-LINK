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
    private val onItemClick: (Request) -> Unit,
    private val onCancelClick: (Request) -> Unit
) : ListAdapter<Request, RequestsAdapter.RequestViewHolder>(RequestDiffCallback()) {

    inner class RequestViewHolder(
        val binding: ItemRequestBinding
    ) : RecyclerView.ViewHolder(binding.root)

    private val displayDateFormat = SimpleDateFormat("MMM dd, yyyy", Locale.getDefault())
    private val apiDateFormat = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.getDefault())

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
        with(holder.binding) {
            // Show tracking number if available
            if (!request.id.isNullOrEmpty()) {
                requestTrackingNumber.text = request.id
                requestTrackingNumber.visibility = View.VISIBLE
            } else {
                requestTrackingNumber.visibility = View.GONE
            }
            
            // Safely handle potentially null type
            requestTitle.text = request.type?.name ?: request.document_type
            
            // Hide description if not needed
            if (request.purpose.isNullOrEmpty()) {
                requestDescription.visibility = View.GONE
            } else {
                requestDescription.visibility = View.VISIBLE
                requestDescription.text = request.purpose
            }

            // Format the date safely
            val formattedDate = try {
                val date = apiDateFormat.parse(request.submitted_at)
                date?.let { displayDateFormat.format(it) } ?: "Date not available"
            } catch (e: ParseException) {
                "Date not available"
            }
            requestDate.text = formattedDate

            // Set processing time
            processingTime.text = "Processing Time: ${request.processing_time ?: "5-7 working days"}"

            // Use the status directly, defaulting to PENDING if null
            updateStatusViews(request.status ?: RequestStatus.PENDING, holder)

            root.setOnClickListener {
                onItemClick(request)
            }

            cancelButton.setOnClickListener {
                onCancelClick(request)
            }
        }
    }

    private fun updateStatusViews(status: RequestStatus, holder: RequestViewHolder) {
        with(holder.binding) {
            when (status) {
                RequestStatus.PENDING -> {
                    requestStatus.text = "Pending"
                    requestStatus.setTextColor(ContextCompat.getColor(root.context, R.color.warning))
                    requestStatus.chipBackgroundColor = ContextCompat.getColorStateList(root.context, R.color.status_pending_bg)
                }
                RequestStatus.IN_PROGRESS -> {
                    requestStatus.text = "In Progress"
                    requestStatus.setTextColor(ContextCompat.getColor(root.context, R.color.info))
                    requestStatus.chipBackgroundColor = ContextCompat.getColorStateList(root.context, R.color.status_progress_bg)
                }
                RequestStatus.COMPLETED -> {
                    requestStatus.text = "Completed"
                    requestStatus.setTextColor(ContextCompat.getColor(root.context, R.color.success))
                    requestStatus.chipBackgroundColor = ContextCompat.getColorStateList(root.context, R.color.status_completed_bg)
                }
                RequestStatus.REJECTED -> {
                    requestStatus.text = "Rejected"
                    requestStatus.setTextColor(ContextCompat.getColor(root.context, R.color.error))
                    requestStatus.chipBackgroundColor = ContextCompat.getColorStateList(root.context, R.color.status_rejected_bg)
                }
            }
            // Show cancel button only for pending requests
            cancelButton.isVisible = status == RequestStatus.PENDING
        }
    }

    private class RequestDiffCallback : DiffUtil.ItemCallback<Request>() {
        override fun areItemsTheSame(oldItem: Request, newItem: Request): Boolean {
            return oldItem.id == newItem.id
        }

        override fun areContentsTheSame(oldItem: Request, newItem: Request): Boolean {
            return oldItem == newItem
        }
    }
} 