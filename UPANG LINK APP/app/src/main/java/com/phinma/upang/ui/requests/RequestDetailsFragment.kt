package com.phinma.upang.ui.requests

import android.app.Activity
import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.activity.result.contract.ActivityResultContracts
import androidx.core.view.isVisible
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.navigation.fragment.findNavController
import androidx.navigation.fragment.navArgs
import androidx.recyclerview.widget.LinearLayoutManager
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import com.phinma.upang.R
import com.phinma.upang.data.model.RequirementItem
import com.phinma.upang.data.model.RequirementStatus
import com.phinma.upang.data.model.Request
import com.phinma.upang.data.model.RequestStatus
import com.phinma.upang.data.model.getRequirementsMap
import com.phinma.upang.databinding.FragmentRequestDetailsBinding
import dagger.hilt.android.AndroidEntryPoint
import java.io.File
import java.io.FileOutputStream
import java.text.SimpleDateFormat
import java.util.Locale
import androidx.core.content.ContextCompat

@AndroidEntryPoint
class RequestDetailsFragment : Fragment() {

    private var _binding: FragmentRequestDetailsBinding? = null
    private val binding get() = _binding!!
    private val viewModel: RequestDetailsViewModel by viewModels()
    private val args: RequestDetailsFragmentArgs by navArgs()
    private lateinit var requirementsAdapter: RequestDetailsAdapter
    private val dateFormat = SimpleDateFormat("MMM dd, yyyy", Locale.getDefault())
    private val timeFormat = SimpleDateFormat("hh:mm a", Locale.getDefault())
    private val apiDateFormat = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.getDefault())

    private var currentRequirement: RequirementItem? = null

    private val filePickerLauncher = registerForActivityResult(
        ActivityResultContracts.StartActivityForResult()
    ) { result ->
        if (result.resultCode == Activity.RESULT_OK) {
            result.data?.data?.let { uri ->
                currentRequirement?.let { requirement ->
                    handleSelectedFile(uri, requirement)
                }
            }
        }
    }

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentRequestDetailsBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        setupRecyclerView()
        setupListeners()
        observeViewModel()
        viewModel.getRequest(args.requestId)
        
        // Hide bottom navigation
        hideBottomNavigation()
    }

    private fun setupRecyclerView() {
        requirementsAdapter = RequestDetailsAdapter(
            onUploadClick = { requirement ->
                launchFilePicker(requirement)
            },
            onRemoveFile = { requirement ->
                viewModel.deleteRequirement(args.requestId, requirement.id)
            }
        )

        binding.recyclerViewRequirements.apply {
            adapter = requirementsAdapter
            layoutManager = LinearLayoutManager(context)
            setHasFixedSize(true)
        }
    }

    private fun setupListeners() {
        // Remove cancel button click listener
        // Hide the cancel button completely
        binding.cancelButton.visibility = View.GONE
        
        binding.backButton.setOnClickListener {
            findNavController().navigateUp()
        }
    }

    private fun observeViewModel() {
        viewModel.request.observe(viewLifecycleOwner) { request ->
            binding.apply {
                // Set request title and tracking number
                requestTitle.text = request.type?.name ?: request.document_type
                trackingNumberText.text = "Tracking #: ${request.id}"
                
                // Always hide purpose section as requested by user
                purposeCard.visibility = View.GONE
                
                // Set request dates
                createdAtText.text = "Created: ${formatDateWithTime(request.submitted_at)}"
                updatedAtText.text = "Last updated: ${formatDateWithTime(request.updated_at)}"
                
                // Set requester information
                requesterNameText.text = "${request.first_name} ${request.last_name}"
                
                // Update status views and timeline
                updateStatusViews(request.status ?: RequestStatus.PENDING)
                updateStatusTimeline(request.status ?: RequestStatus.PENDING)

                // Always hide requirements section as requested by user
                requirementsLabel.isVisible = false
                recyclerViewRequirements.isVisible = false
                noRequirementsText.isVisible = false

                // Show remarks if available
                request.remarks?.let { remarks ->
                    remarksCard.isVisible = true
                    remarksLabel.isVisible = true
                    remarksText.text = remarks
                } ?: run {
                    remarksCard.isVisible = false
                    remarksLabel.isVisible = false
                }

                // Use processing time from API response
                android.util.Log.d("RequestDetails", "Processing time from API: ${request.processing_time}")
                android.util.Log.d("RequestDetails", "Request type processing time: ${request.type?.processing_time}")
                
                // Get processing time from the API response using the same approach as RequestsAdapter
                val processingTime = when {
                    // First priority: Use the processing_time from the type object if available
                    request.type != null && !request.type.processing_time.isNullOrEmpty() -> {
                        android.util.Log.d("RequestDetails", "Using processing time from type object: ${request.type.processing_time}")
                        request.type.processing_time
                    }
                    // Second priority: Use the processing_time directly from the request if available
                    !request.processing_time.isNullOrEmpty() && request.processing_time != "null" -> {
                        android.util.Log.d("RequestDetails", "Using processing time from request: ${request.processing_time}")
                        request.processing_time
                    }
                    // Third priority: Use fallback mapping based on request type
                    else -> {
                        android.util.Log.d("RequestDetails", "Using fallback mapping for processing time")
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
                processingTimeText.text = processingTime
                
                // Set request category
                categoryText.text = request.category_name
                
                // Always hide the cancel button
                cancelButton.visibility = View.GONE
            }
        }

        viewModel.loading.observe(viewLifecycleOwner) { isLoading ->
            binding.progressBar.isVisible = isLoading
            binding.contentLayout.isVisible = !isLoading
        }

        viewModel.errorMessage.observe(viewLifecycleOwner) { error ->
            error?.let {
                if (it.contains("Authentication error", ignoreCase = true)) {
                    // Show authentication error dialog
                    showAuthenticationErrorDialog(it)
                } else {
                    showError(it)
                }
            }
        }
    }

    private fun formatDate(dateString: String): String {
        return try {
            val date = apiDateFormat.parse(dateString)
            date?.let { dateFormat.format(it) } ?: "Date not available"
        } catch (e: Exception) {
            "Date not available"
        }
    }
    
    private fun formatDateWithTime(dateString: String): String {
        return try {
            val date = apiDateFormat.parse(dateString)
            date?.let { 
                "${dateFormat.format(it)} at ${timeFormat.format(it)}" 
            } ?: "Date not available"
        } catch (e: Exception) {
            "Date not available"
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
            context?.let { ctx ->
                requestStatus.setTextColor(ContextCompat.getColor(ctx, colorRes))
                statusIndicator.setCardBackgroundColor(ContextCompat.getColor(ctx, colorRes))
            }
        }
    }
    
    private fun updateStatusTimeline(status: RequestStatus) {
        binding.apply {
            // Update timeline indicators
            pendingIndicator.isSelected = true
            pendingLine.isSelected = status != RequestStatus.PENDING
            inProgressIndicator.isSelected = status != RequestStatus.PENDING
            inProgressLine.isSelected = status == RequestStatus.COMPLETED || status == RequestStatus.REJECTED
            completedIndicator.isSelected = status == RequestStatus.COMPLETED || status == RequestStatus.REJECTED
            
            // Set colors based on status
            val completedColor = when (status) {
                RequestStatus.COMPLETED -> R.color.status_approved
                RequestStatus.REJECTED -> R.color.status_rejected
                else -> R.color.status_pending
            }
            
            context?.let { ctx ->
                if (status == RequestStatus.COMPLETED || status == RequestStatus.REJECTED) {
                    completedIndicator.setCardBackgroundColor(ContextCompat.getColor(ctx, completedColor))
                }
            }
        }
    }

    private fun launchFilePicker(requirement: RequirementItem) {
        currentRequirement = requirement
        val intent = Intent(Intent.ACTION_GET_CONTENT).apply {
            type = "*/*"
            addCategory(Intent.CATEGORY_OPENABLE)
            putExtra(Intent.EXTRA_MIME_TYPES, requirement.allowedFileTypes.toTypedArray())
        }
        filePickerLauncher.launch(intent)
    }

    private fun handleSelectedFile(uri: Uri, requirement: RequirementItem) {
        try {
            val inputStream = requireContext().contentResolver.openInputStream(uri)
            val fileName = uri.lastPathSegment ?: "file"
            val file = File(requireContext().cacheDir, fileName)
            
            FileOutputStream(file).use { outputStream ->
                inputStream?.copyTo(outputStream)
            }

            if (file.length() > requirement.maxFileSize) {
                showError("File size exceeds the maximum allowed size")
                file.delete()
                return
            }

            viewModel.uploadRequirement(args.requestId, requirement.id, file)
        } catch (e: Exception) {
            showError("Failed to process file")
        }
    }

    private fun showError(message: String) {
        Toast.makeText(requireContext(), message, Toast.LENGTH_LONG).show()
    }

    private fun showAuthenticationErrorDialog(message: String) {
        MaterialAlertDialogBuilder(requireContext())
            .setTitle("Authentication Error")
            .setMessage("$message\nYou may need to log in again.")
            .setPositiveButton("OK") { _, _ ->
                // Navigate to login screen or refresh token
                // For now, just go back
                findNavController().navigateUp()
            }
            .setCancelable(false)
            .show()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        // Show bottom navigation when leaving this fragment
        showBottomNavigation()
        _binding = null
    }
    
    private fun hideBottomNavigation() {
        (activity as? com.phinma.upang.ui.MainActivity)?.findViewById<View>(R.id.bottom_nav)?.visibility = View.GONE
    }
    
    private fun showBottomNavigation() {
        (activity as? com.phinma.upang.ui.MainActivity)?.findViewById<View>(R.id.bottom_nav)?.visibility = View.VISIBLE
    }
} 