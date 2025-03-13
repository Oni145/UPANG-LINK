package com.phinma.upang.ui.requests

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.core.view.isVisible
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.phinma.upang.data.model.RequirementItem
import com.phinma.upang.data.model.RequirementStatus
import com.phinma.upang.databinding.ItemRequirementBinding

class RequestDetailsAdapter(
    private val onUploadClick: (RequirementItem) -> Unit,
    private val onRemoveFile: (RequirementItem) -> Unit
) : ListAdapter<RequirementItem, RequestDetailsAdapter.ViewHolder>(RequirementItemDiffCallback()) {

    inner class ViewHolder(
        private val binding: ItemRequirementBinding
    ) : RecyclerView.ViewHolder(binding.root) {

        fun bind(item: RequirementItem) {
            binding.apply {
                requirementName.text = item.name
                requirementDescription.text = item.description
                requiredText.isVisible = item.isRequired

                // Show file status
                if (item.fileUrl != null) {
                    uploadButton.isVisible = false
                    fileNameText.isVisible = true
                    fileNameText.text = item.fileUrl.substringAfterLast("/")

                    // Set status text color
                    val statusColor = when (item.status) {
                        RequirementStatus.VERIFIED -> android.graphics.Color.parseColor("#32CD32") // Green
                        RequirementStatus.REJECTED -> android.graphics.Color.parseColor("#FF0000") // Red
                        else -> android.graphics.Color.parseColor("#FFA500") // Orange
                    }
                    fileNameText.setTextColor(statusColor)
                } else {
                    uploadButton.isVisible = true
                    fileNameText.isVisible = false
                }

                uploadButton.setOnClickListener {
                    onUploadClick(item)
                }

                if (item.fileUrl != null) {
                    fileNameText.setOnClickListener {
                        onRemoveFile(item)
                    }
                }
            }
        }
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        return ViewHolder(
            ItemRequirementBinding.inflate(
                LayoutInflater.from(parent.context),
                parent,
                false
            )
        )
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        holder.bind(getItem(position))
    }
}

internal class RequirementItemDiffCallback : DiffUtil.ItemCallback<RequirementItem>() {
    override fun areItemsTheSame(oldItem: RequirementItem, newItem: RequirementItem): Boolean {
        return oldItem.id == newItem.id
    }

    override fun areContentsTheSame(oldItem: RequirementItem, newItem: RequirementItem): Boolean {
        return oldItem == newItem
    }
} 