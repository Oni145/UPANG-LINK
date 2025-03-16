package com.phinma.upang.ui.requests

import android.app.Activity
import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.util.Log
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ArrayAdapter
import android.widget.Toast
import androidx.activity.result.contract.ActivityResultContracts
import androidx.core.view.isVisible
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import androidx.recyclerview.widget.LinearLayoutManager
import com.phinma.upang.R
import com.phinma.upang.data.model.RequestType
import com.phinma.upang.data.model.RequirementField
import com.phinma.upang.databinding.FragmentCreateRequestBinding
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.flow.collect
import kotlinx.coroutines.launch
import com.google.android.material.bottomnavigation.BottomNavigationView
import android.app.Dialog
import android.view.Window
import android.widget.Button
import com.google.android.material.snackbar.Snackbar

@AndroidEntryPoint
class CreateRequestFragment : Fragment() {

    private var _binding: FragmentCreateRequestBinding? = null
    private val binding get() = _binding!!
    private val viewModel: CreateRequestViewModel by viewModels()
    private lateinit var requirementsAdapter: RequirementsAdapter
    private var selectedRequestType: RequestType? = null
    private var currentRequirement: RequirementField? = null

    private val filePickerLauncher = registerForActivityResult(
        ActivityResultContracts.StartActivityForResult()
    ) { result ->
        if (result.resultCode == Activity.RESULT_OK) {
            result.data?.data?.let { uri ->
                currentRequirement?.let { requirement ->
                    requirementsAdapter.setFileForRequirement(requirement.name, uri)
                }
            }
        }
    }

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentCreateRequestBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        hideBottomNavigation()
        setupViews()
        observeViewModel()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        showBottomNavigation()
        _binding = null
    }

    private fun hideBottomNavigation() {
        activity?.findViewById<BottomNavigationView>(R.id.bottom_nav)?.visibility = View.GONE
    }

    private fun showBottomNavigation() {
        activity?.findViewById<BottomNavigationView>(R.id.bottom_nav)?.visibility = View.VISIBLE
    }

    private fun setupViews() {
        // Setup requirements adapter
        requirementsAdapter = RequirementsAdapter { requirement ->
            if (requirement.type.lowercase() == "file") {
                // Launch file picker for file type requirements
                launchFilePicker(requirement)
            } else {
                // Text input is handled automatically in the adapter
                Log.d("CreateRequest", "Text input updated for: ${requirement.name}")
            }
        }
        binding.requirementsRecyclerView.apply {
            adapter = requirementsAdapter
            layoutManager = LinearLayoutManager(context)
        }

        // Setup back button
        binding.backButton.setOnClickListener {
            findNavController().navigateUp()
        }

        // Setup submit button
        binding.submitButton.setOnClickListener {
            submitRequest()
        }
    }

    private fun observeViewModel() {
        viewLifecycleOwner.lifecycleScope.launch {
            // Observe request types
            viewModel.requestTypes.collect { types ->
                if (types.isNotEmpty()) {
                    setupRequestTypeDropdown(types)
                    binding.requestTypeLayout.visibility = View.VISIBLE
                }
            }
        }

        viewLifecycleOwner.lifecycleScope.launch {
            // Observe loading state
            viewModel.loading.collect { isLoading ->
                if (isLoading) {
                    if (selectedRequestType != null) {
                        // Show loading indicator for requirements
                        binding.placeholderRequirements.visibility = View.VISIBLE
                        binding.noRequirementsText.visibility = View.GONE
                        binding.requirementsRecyclerView.visibility = View.GONE
                    }
                    binding.progressBar.visibility = View.VISIBLE
                    binding.submitButton.isEnabled = !isLoading
                } else {
                    binding.progressBar.visibility = View.GONE
                    binding.submitButton.isEnabled = true
                }
            }
        }

        viewLifecycleOwner.lifecycleScope.launch {
            // Observe errors
            viewModel.error.collect { error ->
                error?.let {
                    Toast.makeText(requireContext(), it, Toast.LENGTH_LONG).show()
                    viewModel.clearError()
                }
            }
        }
        
        viewLifecycleOwner.lifecycleScope.launch {
            // Observe success
            viewModel.success.collect { response ->
                response?.let {
                    // Use the tracking number directly from the API response
                    val trackingNumber = it.tracking_number
                    
                    // Show a Snackbar with the success message
                    Snackbar.make(
                        binding.root,
                        "Request created successfully!",
                        Snackbar.LENGTH_LONG
                    ).show()
                    
                    // Navigate back to the requests screen instead of details
                    findNavController().popBackStack()
                    
                    viewModel.clearSuccess()
                }
            }
        }

        viewLifecycleOwner.lifecycleScope.launch {
            // Observe requirements
            viewModel.requirements.collect { requirements ->
                Log.d("CreateRequest", "Received ${requirements.size} requirements")
                
                // Update the requirements RecyclerView adapter with the new requirements
                requirementsAdapter.submitList(requirements)
                
                // Update the UI based on the requirements
                refreshRequirementsUI(requirements)
            }
        }
        
        viewLifecycleOwner.lifecycleScope.launch {
            // Observe if requirements are needed
            viewModel.requirementsNeeded.collect { needed ->
                Log.d("CreateRequest", "Requirements needed: $needed")
                
                // Make sure the requirements card is always visible
                binding.requirementsCard.visibility = View.VISIBLE
                
                // Update UI elements based on requirements needed
                if (!needed && selectedRequestType != null) {
                    binding.noRequirementsText.text = "No requirements needed for ${selectedRequestType?.name}"
                    binding.noRequirementsText.visibility = View.VISIBLE
                    binding.placeholderRequirements.visibility = View.GONE
                    binding.requirementsRecyclerView.visibility = View.GONE
                    
                    // Show a toast message
                    Toast.makeText(
                        requireContext(), 
                        "No requirements needed for ${selectedRequestType?.name}", 
                        Toast.LENGTH_SHORT
                    ).show()
                }
            }
        }
    }

    private fun setupRequestTypeDropdown(types: List<RequestType>) {
        val adapter = ArrayAdapter(
            requireContext(),
            R.layout.item_request_type_dropdown,
            types.map { it.name }
        )
        binding.requestTypeDropdown.apply {
            setAdapter(adapter)
            setOnItemClickListener { _, _, position, _ ->
                val requestType = types[position]
                Log.d("CreateRequest", "Selected request type: ${requestType.name}, id=${requestType.type_id}")
                
                // Always update the selected request type and reload requirements
                selectedRequestType = requestType
                
                // Reset fields when changing request type
                requirementsAdapter.clearData()
                
                // Show processing time
                binding.processingTimeText.apply {
                    text = "Processing Time: ${requestType.processing_time ?: "5-7 working days"}"
                    visibility = View.VISIBLE
                }
                
                // Show size chart button for uniform requests
                binding.sizeChartButton.visibility = if (requestType.name.contains("Uniform", ignoreCase = true)) {
                    View.VISIBLE
                } else {
                    View.GONE
                }
                
                // Always show the loading state for requirements
                binding.requirementsCard.visibility = View.VISIBLE
                binding.placeholderRequirements.visibility = View.VISIBLE
                binding.noRequirementsText.visibility = View.GONE
                binding.requirementsRecyclerView.visibility = View.GONE
                
                // Load requirements for the selected request type
                viewModel.loadRequirements(requestType)
            }
        }
        
        // Set up size chart button click listener
        binding.sizeChartButton.setOnClickListener {
            showSizeChartDialog()
        }
    }

    private fun showSizeChartDialog() {
        val dialog = Dialog(requireContext())
        dialog.requestWindowFeature(Window.FEATURE_NO_TITLE)
        dialog.setContentView(R.layout.dialog_size_chart)
        
        // Set dialog width to match parent with proper margins
        val window = dialog.window
        window?.setLayout(
            (resources.displayMetrics.widthPixels * 0.95).toInt(), 
            ViewGroup.LayoutParams.WRAP_CONTENT
        )
        window?.setBackgroundDrawableResource(android.R.color.transparent)
        
        // Add rounded corners and elevation
        window?.decorView?.setBackgroundResource(R.drawable.rounded_dialog_background)
        
        // Set up close button
        dialog.findViewById<Button>(R.id.closeButton).setOnClickListener {
            dialog.dismiss()
        }
        
        // Show dialog with animation
        dialog.show()
    }

    private fun refreshRequirementsUI(requirements: List<RequirementField>) {
        Log.d("CreateRequest", "refreshRequirementsUI called with ${requirements.size} requirements")
        
        // Force visibility of the requirements card 
        binding.requirementsCard.visibility = View.VISIBLE
        
        // Filter requirements for uniform requests - keep only Student ID text field and uniform_size
        val filteredRequirements = if (selectedRequestType?.name?.contains("Uniform", ignoreCase = true) == true) {
            requirements.filter { requirement ->
                // Keep only Student ID text field (not file) and uniform_size field
                (requirement.name.equals("student_id", ignoreCase = true) && requirement.type.equals("text", ignoreCase = true)) ||
                requirement.name.equals("uniform_size", ignoreCase = true) ||
                requirement.name.equals("PE Uniform Size", ignoreCase = true) ||
                requirement.name.equals("School Uniform Size", ignoreCase = true)
            }
        } else {
            requirements
        }
        
        Log.d("CreateRequest", "After filtering: ${filteredRequirements.size} requirements for ${selectedRequestType?.name}")
        filteredRequirements.forEach { req ->
            Log.d("CreateRequest", "Kept requirement: ${req.name} (${req.type})")
        }
        
        if (filteredRequirements.isEmpty()) {
            // No requirements to display
            Log.d("CreateRequest", "No requirements to display")
            binding.placeholderRequirements.visibility = View.GONE
            binding.noRequirementsText.visibility = View.VISIBLE
            binding.requirementsRecyclerView.visibility = View.GONE
            
            // Check if this is because we haven't selected a request type yet
            if (selectedRequestType == null) {
                binding.noRequirementsText.text = "Please select a request type to see requirements"
            } else {
                binding.noRequirementsText.text = "No requirements needed for ${selectedRequestType?.name}"
            }
        } else {
            // We have requirements, display them
            Log.d("CreateRequest", "Displaying ${filteredRequirements.size} requirements")
            binding.placeholderRequirements.visibility = View.GONE
            binding.noRequirementsText.visibility = View.GONE
            binding.requirementsRecyclerView.visibility = View.VISIBLE
            
            // Update the adapter with filtered requirements
            requirementsAdapter.submitList(filteredRequirements)
            
            // Log requirements types for debugging
            val fileRequirements = filteredRequirements.filter { it.type.lowercase() == "file" }
            val textRequirements = filteredRequirements.filter { it.type.lowercase() == "text" }
            val dropdownRequirements = filteredRequirements.filter { it.type.lowercase() == "dropdown" }
            
            Log.d("CreateRequest", "Showing ${filteredRequirements.size} requirements: " +
                "${fileRequirements.size} file fields, ${textRequirements.size} text fields, " +
                "${dropdownRequirements.size} dropdown fields")
            
            // Scroll to the top of the requirements section
            binding.requirementsRecyclerView.scrollToPosition(0)
        }
    }

    private fun launchFilePicker(requirement: RequirementField) {
        currentRequirement = requirement
        val intent = Intent(Intent.ACTION_GET_CONTENT).apply {
            type = "*/*"
            addCategory(Intent.CATEGORY_OPENABLE)
            
            // Set allowed file types if specified
            requirement.allowed_types?.let { types ->
                val mimeTypes = types.split(",").map { type ->
                    when (type.trim().lowercase()) {
                        "pdf" -> "application/pdf"
                        "doc", "docx" -> "application/msword"
                        "jpg", "jpeg" -> "image/jpeg"
                        "png" -> "image/png"
                        else -> "*/*"
                    }
                }.toTypedArray()
                putExtra(Intent.EXTRA_MIME_TYPES, mimeTypes)
            }
        }
        filePickerLauncher.launch(intent)
    }

    private fun submitRequest() {
        val selectedType = selectedRequestType
        if (selectedType == null) {
            Toast.makeText(requireContext(), "Please select a request type", Toast.LENGTH_LONG).show()
            return
        }

        // Validate type_id
        if (selectedType.type_id <= 0) {
            Toast.makeText(requireContext(), "Invalid request type selected. Please try again.", Toast.LENGTH_LONG).show()
            return
        }

        // Validate requirements
        if (!requirementsAdapter.validateRequirements()) {
            Toast.makeText(requireContext(), "Please fill in all required fields", Toast.LENGTH_LONG).show()
            return
        }

        // Get text values and files
        val textValues = requirementsAdapter.getRequirementValues().toMutableMap()
        val fileUris = requirementsAdapter.getFileUris().toMutableMap()
        
        // Log all values for debugging
        Log.d("CreateRequest", "Submitting request with the following values:")
        textValues.forEach { (key, value) ->
            Log.d("CreateRequest", "Text field: $key = $value")
        }
        fileUris.forEach { (key, uri) ->
            Log.d("CreateRequest", "File field: $key = ${uri.lastPathSegment}")
        }
        
        // For uniform requests, handle specially
        if (selectedType.name.contains("Uniform", ignoreCase = true)) {
            // Remove all file uploads for uniform requests
            if (fileUris.isNotEmpty()) {
                Log.d("CreateRequest", "Removing all file uploads for uniform request")
                fileUris.clear()
            }
            
            // Keep only student_id and uniform_size text fields
            val keysToKeep = setOf("student_id", "uniform_size", "PE Uniform Size", "School Uniform Size")
            val keysToRemove = textValues.keys.filter { key -> 
                !keysToKeep.any { it.equals(key, ignoreCase = true) }
            }
            
            keysToRemove.forEach { key ->
                Log.d("CreateRequest", "Removing unnecessary text field: $key")
                textValues.remove(key)
            }
            
            // Get the uniform size value
            val uniformSize = textValues.entries.find { 
                it.key.equals("uniform_size", ignoreCase = true) || 
                it.key.equals("PE Uniform Size", ignoreCase = true) || 
                it.key.equals("School Uniform Size", ignoreCase = true) 
            }?.value ?: "Not specified"
            
            // Get the student ID value
            val studentId = textValues.entries.find { 
                it.key.equals("student_id", ignoreCase = true) 
            }?.value ?: ""
            
            // Create a clean map with standardized keys
            val cleanTextValues = mutableMapOf<String, String>()
            cleanTextValues["student_id"] = studentId
            cleanTextValues["uniform_size"] = uniformSize
            
            // Set the purpose with uniform type and size
            val uniformType = if (selectedType.name.contains("PE", ignoreCase = true)) "PE" else "School"
            val purpose = "$uniformType Uniform Request - Size: $uniformSize"
            
            Log.d("CreateRequest", "Submitting uniform request with: student_id=$studentId, uniform_size=$uniformSize")
            
            // Submit the request with uniform-specific purpose and clean values
            viewModel.createRequest(
                typeId = selectedType.type_id,
                purpose = purpose,
                files = fileUris,
                textValues = cleanTextValues
            )
        } else {
            // Submit the request with standard purpose
            viewModel.createRequest(
                typeId = selectedType.type_id,
                purpose = if (selectedType.name.contains("Course Module", ignoreCase = true)) {
                    "Course Module Request for ${textValues["course_name"] ?: "Current Semester"}"
                } else {
                    textValues["purpose"] ?: "N/A"
                },
                files = fileUris,
                textValues = textValues
            )
        }
    }
} 