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

class RequestsAdapter(
    private val onItemClick: (Request) -> Unit,
    private val onCancelClick: (Request) -> Unit
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
        holder.bind(getItem(position))
    }

    inner class RequestViewHolder(
        private val binding: ItemRequestBinding
    ) : RecyclerView.ViewHolder(binding.root) {

        private val dateFormat = SimpleDateFormat("MMM dd, yyyy", Locale.getDefault())

        fun bind(request: Request) {
            binding.apply {
                root.setOnClickListener { onItemClick(request) }
                requestTitle.text = request.type.name
                requestDescription.text = request.purpose
                requestDate.text = dateFormat.format(request.createdAt)
                requestStatus.text = request.status.name

                // Show cancel button only for pending requests
                cancelButton.isVisible = request.status == RequestStatus.PENDING
                cancelButton.setOnClickListener { onCancelClick(request) }

                // Set status color based on request status
                val statusColor = when (request.status) {
                    RequestStatus.DRAFT -> android.graphics.Color.parseColor("#808080") // Gray
                    RequestStatus.PENDING -> android.graphics.Color.parseColor("#FFA500") // Orange
                    RequestStatus.IN_REVIEW -> android.graphics.Color.parseColor("#1E90FF") // Blue
                    RequestStatus.NEEDS_REVISION -> android.graphics.Color.parseColor("#FF4500") // Orange Red
                    RequestStatus.PROCESSING -> android.graphics.Color.parseColor("#1E90FF") // Blue
                    RequestStatus.READY_FOR_PICKUP -> android.graphics.Color.parseColor("#32CD32") // Green
                    RequestStatus.COMPLETED -> android.graphics.Color.parseColor("#32CD32") // Green
                    RequestStatus.CANCELLED -> android.graphics.Color.parseColor("#FF0000") // Red
                    RequestStatus.REJECTED -> android.graphics.Color.parseColor("#FF0000") // Red
                }
                requestStatus.setTextColor(statusColor)
            }
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