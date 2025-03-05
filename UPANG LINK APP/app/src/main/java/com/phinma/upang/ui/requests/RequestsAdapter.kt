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
            // Safely handle potentially null type
            requestTitle.text = request.type?.name ?: request.document_type
            requestDescription.text = request.purpose

            // Format the date safely
            val formattedDate = try {
                val date = apiDateFormat.parse(request.submitted_at)
                date?.let { displayDateFormat.format(it) } ?: "Date not available"
            } catch (e: ParseException) {
                "Date not available"
            }
            requestDate.text = formattedDate

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
                    requestStatus.setBackgroundResource(R.drawable.bg_status_pending)
                }
                RequestStatus.IN_PROGRESS -> {
                    requestStatus.text = "In Progress"
                    requestStatus.setTextColor(ContextCompat.getColor(root.context, R.color.warning))
                    requestStatus.setBackgroundResource(R.drawable.bg_status_pending)
                }
                RequestStatus.COMPLETED -> {
                    requestStatus.text = "Completed"
                    requestStatus.setTextColor(ContextCompat.getColor(root.context, R.color.success))
                    requestStatus.setBackgroundResource(R.drawable.bg_status_approved)
                }
                RequestStatus.REJECTED -> {
                    requestStatus.text = "Rejected"
                    requestStatus.setTextColor(ContextCompat.getColor(root.context, R.color.error))
                    requestStatus.setBackgroundResource(R.drawable.bg_status_rejected)
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