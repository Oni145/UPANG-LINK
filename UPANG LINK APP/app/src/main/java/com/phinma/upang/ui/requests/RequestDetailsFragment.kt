package com.phinma.upang.ui.requests

import android.app.Activity
import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.LinearLayout
import android.widget.TextView
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
import com.google.android.material.snackbar.Snackbar

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
        binding.cancelButton.setOnClickListener {
            showCancelConfirmationDialog()
        }
        
        binding.backButton.setOnClickListener {
            findNavController().navigateUp()
        }
    }

    private fun setupViews() {
        binding.apply {
            // ... existing code ...

            // Show cancel button only for pending requests
            cancelButton.isVisible = viewModel.request.value?.status == RequestStatus.PENDING
            cancelButton.setOnClickListener {
                showCancelConfirmationDialog()
            }
        }
    }

    private fun showCancelConfirmationDialog() {
        MaterialAlertDialogBuilder(requireContext())
            .setTitle("Cancel Request")
            .setMessage("Are you sure you want to cancel this request? This action cannot be undone.")
            .setPositiveButton("Cancel Request") { _, _ ->
                viewModel.request.value?.id?.let { requestId ->
                    viewModel.cancelRequest(requestId)
                }
            }
            .setNegativeButton("No, Keep It") { dialog, _ ->
                dialog.dismiss()
            }
            .show()
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

                // Update cancel button visibility based on status
                cancelButton.isVisible = request.status == RequestStatus.PENDING

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
                
                // Debug request data to see what fields are available
                android.util.Log.d("RequestDetails", "Request: ${request.id}, Status: ${request.status}, Remarks: ${request.remarks}")
                android.util.Log.d("RequestDetails", "Notes array: ${request.notes?.size ?: 0} notes")
            }
        }

        // Handle regular notes (from request_notes table)
        viewModel.requirementNotes.observe(viewLifecycleOwner) { notes ->
            android.util.Log.d("RequestDetails", "Received ${notes.size} notes")
        }
        
        // Handle notes from request_requirement_notes table
        viewModel.requestRequirementNotes.observe(viewLifecycleOwner) { notes ->
            android.util.Log.d("RequestDetails", "Received ${notes.size} requirement notes from the database")
            android.util.Log.d("RequestDetails", "Notes data: $notes")
            
            binding.apply {
                // For REJECTED status, always show the admin message card even if no notes
                if (viewModel.request.value?.status == RequestStatus.REJECTED) {
                    adminMessageCard.isVisible = true
                    
                    if (notes.isNotEmpty()) {
                        // Get the latest requirement note with non-null content
                        val validNotes = notes.filter { !it.note.isNullOrBlank() }
                        
                        if (validNotes.isNotEmpty()) {
                            val latestNote = validNotes[0]  // Notes are ordered by created_at DESC
                            adminMessageText.text = latestNote.note
                            android.util.Log.d("RequestDetails", "Displaying rejection note: ${latestNote.note}")
                        } else {
                            // No valid notes available, show default rejection message
                            adminMessageText.text = "Your request has been rejected. Please contact the admin for more details."
                            android.util.Log.d("RequestDetails", "Displaying default rejection message (no valid notes)")
                        }
                    } else {
                        // No notes available, but still show a default rejection message
                        adminMessageText.text = "Your request has been rejected. Please contact the admin for more details."
                        android.util.Log.d("RequestDetails", "Displaying default rejection message")
                    }
                } 
                // Show admin message based on notes for other statuses
                else if (notes.isNotEmpty()) {
                    // Filter out notes with null content
                    val validNotes = notes.filter { !it.note.isNullOrBlank() }
                    
                    // Show admin message for all statuses except PENDING and CANCELLED
                    if (validNotes.isNotEmpty() && 
                        viewModel.request.value?.status != RequestStatus.PENDING && 
                        viewModel.request.value?.status != RequestStatus.CANCELLED) {
                        
                        adminMessageCard.isVisible = true
                        
                        // Format and display the note
                        val latestNote = validNotes[0]  // Notes are ordered by created_at DESC
                        adminMessageText.text = latestNote.note
                        
                        // Log for debugging
                        android.util.Log.d("RequestDetails", "Displaying note: ${latestNote.note}")
                    } else if (viewModel.request.value?.status != RequestStatus.PENDING && 
                        viewModel.request.value?.status != RequestStatus.CANCELLED) {
                        
                        // No valid notes available
                        adminMessageCard.isVisible = true
                        adminMessageText.text = "No Message"
                    } else {
                        adminMessageCard.isVisible = false
                    }
                } else {
                    // Check if we can use remarks field for non-rejected statuses
                    val remarks = viewModel.request.value?.remarks
                    if (!remarks.isNullOrEmpty() && 
                        viewModel.request.value?.status != RequestStatus.PENDING && 
                        viewModel.request.value?.status != RequestStatus.CANCELLED) {
                        
                        adminMessageCard.isVisible = true
                        adminMessageText.text = remarks
                        android.util.Log.d("RequestDetails", "Using remarks as message: $remarks")
                    } else if (viewModel.request.value?.status != RequestStatus.PENDING && 
                        viewModel.request.value?.status != RequestStatus.CANCELLED) {
                        
                        adminMessageCard.isVisible = true
                        adminMessageText.text = "No Message"
                    } else {
                        adminMessageCard.isVisible = false
                    }
                }
            }
        }

        viewModel.loading.observe(viewLifecycleOwner) { isLoading ->
            binding.progressBar.isVisible = isLoading
            binding.contentLayout.isVisible = !isLoading
        }

        viewModel.errorMessage.observe(viewLifecycleOwner) { errorMessage ->
            errorMessage?.let {
                Snackbar.make(binding.root, it, Snackbar.LENGTH_LONG).show()
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
            RequestStatus.CANCELLED -> {
                Pair(R.color.status_rejected, "Cancelled")
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
            // Reset all indicators to inactive state
            pendingIndicator.isSelected = false
            inProgressIndicator.isSelected = false
            approvedIndicator.isSelected = false
            completedIndicator.isSelected = false
            cancelledIndicator.isSelected = false
            
            pendingLine.isSelected = false
            inProgressLine.isSelected = false
            approvedLine.isSelected = false
            completedLine.isSelected = false
            
            // Always hide the 5th indicator (cancelled) since we don't need it
            cancelledIndicator.visibility = View.GONE
            cancelledStatusLabel.visibility = View.GONE
            
            // Always hide the line after the last indicator (completedLine) since it's the end of the timeline
            completedLine.visibility = View.GONE
            
            // Reset indicator texts
            (pendingIndicator.getChildAt(0) as? TextView)?.text = "1"
            (inProgressIndicator.getChildAt(0) as? TextView)?.text = "2"
            (approvedIndicator.getChildAt(0) as? TextView)?.text = "3"
            (completedIndicator.getChildAt(0) as? TextView)?.text = "4"
            
            // Reset status labels
            pendingStatusLabel.text = "Pending"
            inProgressStatusLabel.text = "In Progress"
            approvedStatusLabel.text = "Approved"
            completedStatusLabel.text = "Completed"
            
            // Make sure all step indicators are visible for consistent spacing
            pendingIndicator.visibility = View.VISIBLE
            inProgressIndicator.visibility = View.VISIBLE
            approvedIndicator.visibility = View.VISIBLE
            completedIndicator.visibility = View.VISIBLE
            
            // Make sure all connecting lines are visible for consistent spacing (except completedLine)
            pendingLine.visibility = View.VISIBLE
            inProgressLine.visibility = View.VISIBLE
            approvedLine.visibility = View.VISIBLE
            
            // Reset label opacity
            pendingStatusLabel.alpha = 1f
            inProgressStatusLabel.alpha = 1f
            approvedStatusLabel.alpha = 1f
            completedStatusLabel.alpha = 1f
            
            context?.let { ctx ->
                // Define colors
                val grayColor = ContextCompat.getColor(ctx, R.color.gray_light)
                val pendingColor = ContextCompat.getColor(ctx, R.color.status_pending)
                val approvedColor = ContextCompat.getColor(ctx, R.color.status_approved)
                val rejectedColor = ContextCompat.getColor(ctx, R.color.status_rejected)
                
                // Based on the requested new flow: pending/cancelled → in progress → approved/rejected → completed
                when (status) {
                    RequestStatus.PENDING -> {
                        // Pending state - first step active, others inactive
                        pendingIndicator.setCardBackgroundColor(pendingColor)
                        inProgressIndicator.setCardBackgroundColor(grayColor)
                        approvedIndicator.setCardBackgroundColor(grayColor)
                        completedIndicator.setCardBackgroundColor(grayColor)
                        
                        pendingLine.setBackgroundColor(grayColor)
                        inProgressLine.setBackgroundColor(grayColor)
                        approvedLine.setBackgroundColor(grayColor)
                    }
                    RequestStatus.CANCELLED -> {
                        // For cancelled status, only show the first step labeled as "Cancelled"
                        pendingIndicator.setCardBackgroundColor(rejectedColor)
                        pendingStatusLabel.text = "Cancelled"
                        
                        // Hide the other steps completely
                        inProgressIndicator.visibility = View.GONE
                        inProgressStatusLabel.visibility = View.GONE
                        approvedIndicator.visibility = View.GONE
                        approvedStatusLabel.visibility = View.GONE
                        completedIndicator.visibility = View.GONE
                        completedStatusLabel.visibility = View.GONE
                        
                        // Hide all connecting lines
                        pendingLine.visibility = View.GONE
                        inProgressLine.visibility = View.GONE
                        approvedLine.visibility = View.GONE
                    }
                    RequestStatus.IN_PROGRESS -> {
                        // In Progress - first and second steps active
                        pendingIndicator.setCardBackgroundColor(pendingColor)
                        inProgressIndicator.setCardBackgroundColor(pendingColor)
                        approvedIndicator.setCardBackgroundColor(grayColor)
                        completedIndicator.setCardBackgroundColor(grayColor)
                        
                        pendingLine.setBackgroundColor(pendingColor)
                        inProgressLine.setBackgroundColor(grayColor)
                        approvedLine.setBackgroundColor(grayColor)
                    }
                    RequestStatus.APPROVED -> {
                        // Approved - first three steps active, last inactive
                        pendingIndicator.setCardBackgroundColor(pendingColor)
                        inProgressIndicator.setCardBackgroundColor(pendingColor)
                        approvedIndicator.setCardBackgroundColor(approvedColor)
                        completedIndicator.setCardBackgroundColor(grayColor)
                        
                        pendingLine.setBackgroundColor(pendingColor)
                        inProgressLine.setBackgroundColor(pendingColor)
                        approvedLine.setBackgroundColor(grayColor)
                    }
                    RequestStatus.COMPLETED -> {
                        // Completed - all steps active (but only if it was approved first)
                        pendingIndicator.setCardBackgroundColor(pendingColor)
                        inProgressIndicator.setCardBackgroundColor(pendingColor)
                        approvedIndicator.setCardBackgroundColor(approvedColor)
                        completedIndicator.setCardBackgroundColor(approvedColor)
                        
                        pendingLine.setBackgroundColor(pendingColor)
                        inProgressLine.setBackgroundColor(pendingColor)
                        approvedLine.setBackgroundColor(approvedColor)
                    }
                    RequestStatus.REJECTED -> {
                        // For rejected status, only show the first 3 steps (Pending, In Progress, Rejected)
                        pendingIndicator.setCardBackgroundColor(pendingColor)
                        inProgressIndicator.setCardBackgroundColor(pendingColor)
                        approvedIndicator.setCardBackgroundColor(rejectedColor)
                        
                        // Hide the fourth indicator completely for rejected requests
                        completedIndicator.visibility = View.GONE
                        completedStatusLabel.visibility = View.GONE
                        
                        // Show the rejected label in the third position
                        approvedStatusLabel.text = "Rejected"
                        
                        // Replace "3" with "X" in the rejected indicator
                        (approvedIndicator.getChildAt(0) as? TextView)?.apply {
                            text = "X"
                        }
                        
                        // Set appropriate line colors
                        pendingLine.setBackgroundColor(pendingColor)
                        inProgressLine.setBackgroundColor(pendingColor)
                        approvedLine.visibility = View.GONE
                    }
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