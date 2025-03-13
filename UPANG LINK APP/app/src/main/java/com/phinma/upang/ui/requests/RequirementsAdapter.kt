package com.phinma.upang.ui.requests

import android.content.Context
import android.net.Uri
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.core.view.isVisible
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.phinma.upang.data.model.RequirementField
import com.phinma.upang.databinding.ItemRequirementBinding
import com.phinma.upang.databinding.ItemRequirementTextBinding
import com.phinma.upang.databinding.ItemRequirementDropdownBinding
import android.provider.OpenableColumns
import android.text.Editable
import android.text.TextWatcher
import android.util.Log
import android.text.InputType
import android.widget.ArrayAdapter
import android.widget.AutoCompleteTextView
import com.google.android.material.textfield.MaterialAutoCompleteTextView
import com.google.android.material.textfield.TextInputLayout
import com.phinma.upang.R

class RequirementsAdapter(
    private val onRequirementAction: (RequirementField) -> Unit
) : ListAdapter<RequirementField, RecyclerView.ViewHolder>(RequirementDiffCallback()) {

    private val textValues = mutableMapOf<String, String>()
    private val fileUris = mutableMapOf<String, Uri>()

    companion object {
        private const val VIEW_TYPE_TEXT = 1
        private const val VIEW_TYPE_FILE = 2
        private const val VIEW_TYPE_DROPDOWN = 3
    }

    override fun getItemViewType(position: Int): Int {
        val requirement = getItem(position)
        return when (requirement.type.lowercase()) {
            "file" -> VIEW_TYPE_FILE
            "dropdown" -> VIEW_TYPE_DROPDOWN
            else -> VIEW_TYPE_TEXT
        }
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): RecyclerView.ViewHolder {
        return when (viewType) {
            VIEW_TYPE_FILE -> {
                val binding = ItemRequirementBinding.inflate(
                    LayoutInflater.from(parent.context), parent, false
                )
                FileViewHolder(binding)
            }
            VIEW_TYPE_DROPDOWN -> {
                val binding = ItemRequirementDropdownBinding.inflate(
                    LayoutInflater.from(parent.context), parent, false
                )
                DropdownViewHolder(binding)
            }
            else -> {
                val binding = ItemRequirementTextBinding.inflate(
                    LayoutInflater.from(parent.context), parent, false
                )
                TextViewHolder(binding)
            }
        }
    }

    override fun onBindViewHolder(holder: RecyclerView.ViewHolder, position: Int) {
        val requirement = getItem(position)
        when (holder) {
            is TextViewHolder -> holder.bind(requirement)
            is FileViewHolder -> holder.bind(requirement)
            is DropdownViewHolder -> holder.bind(requirement)
        }
    }

    inner class DropdownViewHolder(private val binding: ItemRequirementDropdownBinding) :
        RecyclerView.ViewHolder(binding.root) {

        fun bind(requirement: RequirementField) {
            binding.apply {
                requirementName.text = requirement.label
                requiredText.isVisible = requirement.required
                optionalText.isVisible = !requirement.required

                // Set description if available
                if (!requirement.description.isNullOrEmpty()) {
                    requirementDescription.text = requirement.description
                    requirementDescription.isVisible = true
                } else {
                    requirementDescription.isVisible = false
                }

                // Set up the dropdown
                dropdownLayout.hint = "Select ${requirement.label}"
                
                // Set up the dropdown adapter with options
                requirement.options?.let { options ->
                    val adapter = ArrayAdapter(
                        binding.root.context,
                        android.R.layout.simple_dropdown_item_1line,
                        options
                    )
                    dropdownText.setAdapter(adapter)
                    
                    // Restore saved value if exists
                    val savedValue = textValues[requirement.name]
                    if (!savedValue.isNullOrEmpty()) {
                        dropdownText.setText(savedValue)
                    }
                    
                    // Set up text watcher
                    dropdownText.addTextChangedListener(object : TextWatcher {
                        override fun beforeTextChanged(s: CharSequence?, start: Int, count: Int, after: Int) {}
                        override fun onTextChanged(s: CharSequence?, start: Int, before: Int, count: Int) {}
                        override fun afterTextChanged(s: Editable?) {
                            val text = s?.toString() ?: ""
                            textValues[requirement.name] = text
                            onRequirementAction(requirement)
                        }
                    })
                    
                    // Force the dropdown to show when clicked
                    dropdownText.setOnClickListener {
                        dropdownText.showDropDown()
                    }
                }
            }
        }
    }

    inner class TextViewHolder(private val binding: ItemRequirementTextBinding) :
        RecyclerView.ViewHolder(binding.root) {

        fun bind(requirement: RequirementField) {
            binding.apply {
                requirementName.text = requirement.label
                requiredText.isVisible = requirement.required
                optionalText.isVisible = !requirement.required

                // Set description if available
                if (!requirement.description.isNullOrEmpty()) {
                    requirementDescription.text = requirement.description
                    requirementDescription.isVisible = true
                } else {
                    requirementDescription.isVisible = false
                }

                // Restore saved text value if exists
                inputEditText.setText(textValues[requirement.name] ?: "")

                // Set up text watcher
                inputEditText.addTextChangedListener(object : TextWatcher {
                    override fun beforeTextChanged(s: CharSequence?, start: Int, count: Int, after: Int) {}
                    override fun onTextChanged(s: CharSequence?, start: Int, before: Int, count: Int) {}
                    override fun afterTextChanged(s: Editable?) {
                        val text = s?.toString() ?: ""
                        textValues[requirement.name] = text
                        onRequirementAction(requirement)
                    }
                })

                // Set appropriate input hint based on field name
                when {
                    requirement.name.contains("emergency_contact_number", ignoreCase = true) -> {
                        inputEditText.inputType = InputType.TYPE_CLASS_NUMBER
                        inputTextLayout.hint = "Enter phone number (numbers only)"
                    }
                    requirement.name.contains("course_code", ignoreCase = true) -> {
                        inputEditText.inputType = InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_FLAG_CAP_CHARACTERS
                        inputTextLayout.hint = "Enter Course Code"
                    }
                    else -> {
                        inputEditText.inputType = InputType.TYPE_CLASS_TEXT
                        inputTextLayout.hint = "Enter ${requirement.label}"
                    }
                }
            }
        }
    }

    inner class FileViewHolder(private val binding: ItemRequirementBinding) :
        RecyclerView.ViewHolder(binding.root) {

        fun bind(requirement: RequirementField) {
            binding.apply {
                requirementName.text = requirement.label
                requiredText.isVisible = requirement.required
                
                // Set description if available
                if (!requirement.description.isNullOrEmpty()) {
                    requirementDescription.text = requirement.description
                    requirementDescription.isVisible = true
                } else {
                    requirementDescription.isVisible = false
                }

                // Show file status
                val fileUri = fileUris[requirement.name]
                if (fileUri != null) {
                    uploadButton.isVisible = false
                    fileNameText.isVisible = true
                    fileNameText.text = getFileName(fileUri, binding.root.context)
                } else {
                    uploadButton.isVisible = true
                    fileNameText.isVisible = false
                }

                uploadButton.setOnClickListener {
                    onRequirementAction(requirement)
                }

                fileNameText.setOnClickListener {
                    // Remove file
                    fileUris.remove(requirement.name)
                    uploadButton.isVisible = true
                    fileNameText.isVisible = false
                }
            }
        }

        private fun getFileName(uri: Uri, context: Context): String {
            val cursor = context.contentResolver.query(uri, null, null, null, null)
            cursor?.use {
                if (it.moveToFirst()) {
                    val displayNameIndex = it.getColumnIndex(OpenableColumns.DISPLAY_NAME)
                    if (displayNameIndex != -1) {
                        return it.getString(displayNameIndex)
                    }
                }
            }
            return uri.lastPathSegment ?: "Selected File"
        }
    }

    fun getRequirementValues(): Map<String, String> = textValues.toMap()
    
    fun getFileUris(): Map<String, Uri> = fileUris.toMap()

    fun setFileForRequirement(requirementName: String, uri: Uri) {
        fileUris[requirementName] = uri
        notifyItemChanged(currentList.indexOfFirst { it.name == requirementName })
    }

    fun validateRequirements(): Boolean {
        val allValid = currentList.all { requirement ->
            val isValid = if (!requirement.required) {
                true
            } else if (requirement.type.lowercase() == "file") {
                val hasFile = fileUris[requirement.name] != null
                if (!hasFile) {
                    Log.d("RequirementsAdapter", "Missing required file: ${requirement.name}")
                }
                hasFile
            } else {
                val hasText = textValues[requirement.name]?.isNotBlank() == true
                if (!hasText) {
                    Log.d("RequirementsAdapter", "Missing required text: ${requirement.name}")
                }
                hasText
            }
            
            Log.d("RequirementsAdapter", "Validating ${requirement.name} (${requirement.type}): ${if (isValid) "VALID" else "INVALID"}")
            isValid
        }
        
        Log.d("RequirementsAdapter", "All requirements valid: $allValid")
        return allValid
    }

    fun clearData() {
        textValues.clear()
        fileUris.clear()
    }
}

class RequirementDiffCallback : DiffUtil.ItemCallback<RequirementField>() {
    override fun areItemsTheSame(oldItem: RequirementField, newItem: RequirementField): Boolean {
        return oldItem.name == newItem.name
    }

    override fun areContentsTheSame(oldItem: RequirementField, newItem: RequirementField): Boolean {
        return oldItem == newItem
    }
} 