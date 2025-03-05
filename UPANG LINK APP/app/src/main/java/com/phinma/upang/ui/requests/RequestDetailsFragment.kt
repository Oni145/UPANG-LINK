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
import com.phinma.upang.data.model.*
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
    private lateinit var requirementsAdapter: RequirementsAdapter
    private val dateFormat = SimpleDateFormat("MMM dd, yyyy", Locale.getDefault())
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
    }

    private fun setupRecyclerView() {
        requirementsAdapter = RequirementsAdapter(
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
    }

    private fun observeViewModel() {
        viewModel.request.observe(viewLifecycleOwner) { request ->
            binding.apply {
                requestTitle.text = request.type?.name ?: request.document_type
                requestDescription.text = request.purpose
                requestDate.text = "Created: ${formatDate(request.submitted_at)}"
                updateStatusViews(request.status ?: RequestStatus.PENDING)

                // Update requirements list
                requirementsAdapter.submitList(request.requirements.map { RequirementItem.fromSubmission(it) })

                // Show remarks if available
                request.remarks?.let { remarks ->
                    remarksCard.isVisible = true
                    remarksText.text = remarks
                } ?: run {
                    remarksCard.isVisible = false
                }
            }
        }

        viewModel.loading.observe(viewLifecycleOwner) { isLoading ->
            binding.progressBar.isVisible = isLoading
        }

        viewModel.errorMessage.observe(viewLifecycleOwner) { error ->
            error?.let {
                showError(it)
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

    private fun updateStatusViews(status: RequestStatus) {
        val (colorRes, text) = when (status) {
            RequestStatus.PENDING -> {
                Pair(R.color.status_pending, "Pending")
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
            }
            cancelButton.isVisible = status == RequestStatus.PENDING
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

    private fun showCancelConfirmationDialog() {
        MaterialAlertDialogBuilder(requireContext())
            .setTitle("Cancel Request")
            .setMessage("Are you sure you want to cancel this request?")
            .setPositiveButton("Yes") { _, _ ->
                viewModel.cancelRequest(args.requestId)
            }
            .setNegativeButton("No", null)
            .show()
    }

    private fun showError(message: String) {
        Toast.makeText(requireContext(), message, Toast.LENGTH_LONG).show()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
} 