package com.phinma.upang.ui.requests

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.core.view.isVisible
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.navigation.fragment.findNavController
import androidx.navigation.fragment.navArgs
import androidx.recyclerview.widget.LinearLayoutManager
import com.phinma.upang.R
import com.phinma.upang.data.model.RequirementField
import com.phinma.upang.data.model.RequirementUpdateItem
import com.phinma.upang.databinding.FragmentRequestEditBinding
import dagger.hilt.android.AndroidEntryPoint
import java.text.SimpleDateFormat
import java.util.Locale
import androidx.core.content.ContextCompat

@AndroidEntryPoint
class RequestEditFragment : Fragment() {

    private var _binding: FragmentRequestEditBinding? = null
    private val binding get() = _binding!!
    private val viewModel: RequestEditViewModel by viewModels()
    private val args: RequestEditFragmentArgs by navArgs()
    private lateinit var requirementsAdapter: RequirementEditAdapter
    private val dateFormat = SimpleDateFormat("MMM dd, yyyy", Locale.getDefault())
    private val apiDateFormat = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.getDefault())

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentRequestEditBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        setupRecyclerView()
        setupListeners()
        observeViewModel()
    }

    private fun setupRecyclerView() {
        requirementsAdapter = RequirementEditAdapter()
        binding.recyclerViewRequirements.apply {
            adapter = requirementsAdapter
            layoutManager = LinearLayoutManager(context)
            setHasFixedSize(true)
        }
    }

    private fun setupListeners() {
        binding.cancelButton.setOnClickListener {
            findNavController().navigateUp()
        }

        binding.saveButton.setOnClickListener {
            saveChanges()
        }
    }

    private fun observeViewModel() {
        viewModel.requestDetails.observe(viewLifecycleOwner) { details ->
            binding.apply {
                requestIdText.text = details.id
                requestTypeText.text = details.document_type
                
                // Set status with appropriate color
                statusText.text = details.status
                val statusColor = when (details.status) {
                    "PENDING" -> R.color.warning
                    "IN_PROGRESS" -> R.color.info
                    "COMPLETED" -> R.color.success
                    "REJECTED" -> R.color.error
                    "CANCELLED" -> R.color.error
                    else -> R.color.black
                }
                statusText.setTextColor(ContextCompat.getColor(requireContext(), statusColor))
                
                // Set purpose
                purposeEditText.setText(details.purpose)
                
                // Format dates
                submissionDateText.text = formatDate(details.submitted_at)
                lastUpdatedText.text = formatDate(details.updated_at)
                
                // Parse requirements
                val requirementsData = details.parseRequirements()
                val requirementItems = mutableListOf<RequirementEditItem>()
                
                // Add fields if available
                requirementsData.fields?.let { fields ->
                    fields.forEach { field ->
                        requirementItems.add(
                            RequirementEditItem(
                                id = field.name,
                                name = field.label,
                                description = field.description ?: "",
                                isRequired = field.required,
                                isFileType = field.type == "file",
                                currentValue = "", // Will be populated from submissions if available
                                submissionStatus = null
                            )
                        )
                    }
                }
                
                // Add required docs if available
                requirementsData.required_docs?.let { docs ->
                    docs.forEach { doc ->
                        requirementItems.add(
                            RequirementEditItem(
                                id = doc.lowercase().replace(" ", "_"),
                                name = doc,
                                description = "Required document",
                                isRequired = true,
                                isFileType = true,
                                currentValue = "",
                                submissionStatus = null
                            )
                        )
                    }
                }
                
                // Update with submission data if available
                details.submissions?.let { submissions ->
                    submissions.forEach { submission ->
                        val matchingItem = requirementItems.find { it.id == submission.requirement_id }
                        matchingItem?.let {
                            it.submissionStatus = submission.submission_status
                            if (!it.isFileType) {
                                it.currentValue = submission.file_path ?: ""
                            }
                        }
                    }
                }
                
                requirementsAdapter.submitList(requirementItems)
                
                // Disable editing if not allowed
                val canEdit = details.can_edit
                purposeEditText.isEnabled = canEdit
                saveButton.isEnabled = canEdit
                
                if (!canEdit) {
                    Toast.makeText(
                        requireContext(),
                        "This request cannot be edited because it is not in PENDING status",
                        Toast.LENGTH_LONG
                    ).show()
                }
            }
        }

        viewModel.loading.observe(viewLifecycleOwner) { isLoading ->
            binding.progressBar.isVisible = isLoading
        }

        viewModel.errorMessage.observe(viewLifecycleOwner) { error ->
            error?.let {
                Toast.makeText(requireContext(), it, Toast.LENGTH_LONG).show()
            }
        }

        viewModel.saveSuccess.observe(viewLifecycleOwner) { success ->
            if (success) {
                Toast.makeText(requireContext(), "Changes saved successfully", Toast.LENGTH_SHORT).show()
                findNavController().navigateUp()
            }
        }
    }

    private fun saveChanges() {
        val purpose = binding.purposeEditText.text.toString().trim()
        
        if (purpose.isEmpty()) {
            Toast.makeText(requireContext(), "Purpose cannot be empty", Toast.LENGTH_SHORT).show()
            return
        }
        
        val requirements = requirementsAdapter.getRequirements()
            .filter { !it.isFileType && it.currentValue.isNotEmpty() }
            .map { RequirementUpdateItem(id = it.id, value = it.currentValue) }
        
        viewModel.saveRequestDetails(purpose, requirements)
    }

    private fun formatDate(dateString: String): String {
        return try {
            val date = apiDateFormat.parse(dateString)
            date?.let { dateFormat.format(it) } ?: "Date not available"
        } catch (e: Exception) {
            "Date not available"
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}

data class RequirementEditItem(
    val id: String,
    val name: String,
    val description: String,
    val isRequired: Boolean,
    val isFileType: Boolean,
    var currentValue: String,
    var submissionStatus: String?
) 