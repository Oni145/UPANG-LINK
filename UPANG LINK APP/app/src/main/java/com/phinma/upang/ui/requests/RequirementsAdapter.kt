package com.phinma.upang.ui.requests

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.core.view.isVisible
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.phinma.upang.data.model.RequirementStatus
import com.phinma.upang.databinding.ItemRequirementBinding

class RequirementsAdapter(
    private val onUploadClick: (RequirementItem) -> Unit,
    private val onRemoveFile: (RequirementItem) -> Unit
) : ListAdapter<RequirementItem, RequirementsAdapter.RequirementViewHolder>(RequirementDiffCallback()) {

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): RequirementViewHolder {
        val binding = ItemRequirementBinding.inflate(
            LayoutInflater.from(parent.context),
            parent,
            false
        )
        return RequirementViewHolder(binding)
    }

    override fun onBindViewHolder(holder: RequirementViewHolder, position: Int) {
        holder.bind(getItem(position))
    }

    inner class RequirementViewHolder(
        private val binding: ItemRequirementBinding
    ) : RecyclerView.ViewHolder(binding.root) {

        fun bind(item: RequirementItem) {
            binding.apply {
                requirementTitle.text = item.name
                requirementDescription.text = item.description
                requiredBadge.isVisible = item.isRequired

                // Show file status
                if (item.fileUrl != null) {
                    uploadButton.isVisible = false
                    removeButton.isVisible = true
                    statusGroup.isVisible = true

                    statusText.text = item.status?.name ?: "PENDING"
                    statusText.setTextColor(when (item.status) {
                        RequirementStatus.VERIFIED -> android.graphics.Color.parseColor("#32CD32") // Green
                        RequirementStatus.REJECTED -> android.graphics.Color.parseColor("#FF0000") // Red
                        else -> android.graphics.Color.parseColor("#FFA500") // Orange
                    })

                    item.remarks?.let { remarks ->
                        remarksText.isVisible = true
                        remarksText.text = remarks
                    } ?: run {
                        remarksText.isVisible = false
                    }
                } else {
                    uploadButton.isVisible = true
                    removeButton.isVisible = false
                    statusGroup.isVisible = false
                    remarksText.isVisible = false
                }

                uploadButton.setOnClickListener { onUploadClick(item) }
                removeButton.setOnClickListener { onRemoveFile(item) }
            }
        }
    }

    private class RequirementDiffCallback : DiffUtil.ItemCallback<RequirementItem>() {
        override fun areItemsTheSame(oldItem: RequirementItem, newItem: RequirementItem): Boolean {
            return oldItem.id == newItem.id
        }

        override fun areContentsTheSame(oldItem: RequirementItem, newItem: RequirementItem): Boolean {
            return oldItem == newItem
        }
    }
} 