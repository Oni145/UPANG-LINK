package com.phinma.upang.ui.requests

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.core.view.isVisible
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.phinma.upang.R
import com.phinma.upang.databinding.ItemRequirementEditBinding

class RequirementEditAdapter : ListAdapter<RequirementEditItem, RequirementEditAdapter.ViewHolder>(DiffCallback()) {

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val binding = ItemRequirementEditBinding.inflate(
            LayoutInflater.from(parent.context),
            parent,
            false
        )
        return ViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val item = getItem(position)
        holder.bind(item)
    }

    fun getRequirements(): List<RequirementEditItem> {
        return currentList
    }

    class ViewHolder(private val binding: ItemRequirementEditBinding) : RecyclerView.ViewHolder(binding.root) {
        fun bind(item: RequirementEditItem) {
            binding.apply {
                requirementNameText.text = item.name
                requirementDescriptionText.text = item.description
                
                // Show/hide input field based on file type
                valueInputLayout.isVisible = !item.isFileType
                fileUploadButton.isVisible = item.isFileType
                
                // Set current value if available
                valueEditText.setText(item.currentValue)
                
                // Show required indicator
                requiredIndicator.isVisible = item.isRequired
                
                // Update value when text changes
                valueEditText.setOnFocusChangeListener { _, hasFocus ->
                    if (!hasFocus) {
                        item.currentValue = valueEditText.text.toString().trim()
                    }
                }
                
                // Handle file upload button
                fileUploadButton.setOnClickListener {
                    // File upload functionality would be implemented here
                    // For now, we're just focusing on fixing compilation errors
                }
                
                // Show submission status if available
                val status = item.submissionStatus
                if (status != null) {
                    statusText.isVisible = true
                    statusText.text = "Status: $status"
                } else {
                    statusText.isVisible = false
                }
            }
        }
    }

    class DiffCallback : DiffUtil.ItemCallback<RequirementEditItem>() {
        override fun areItemsTheSame(oldItem: RequirementEditItem, newItem: RequirementEditItem): Boolean {
            return oldItem.id == newItem.id
        }

        override fun areContentsTheSame(oldItem: RequirementEditItem, newItem: RequirementEditItem): Boolean {
            return oldItem == newItem
        }
    }
} 