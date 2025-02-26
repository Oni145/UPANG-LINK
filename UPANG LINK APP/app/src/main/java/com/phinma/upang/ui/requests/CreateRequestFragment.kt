package com.phinma.upang.ui.requests

import android.app.Activity
import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ArrayAdapter
import android.widget.Toast
import androidx.activity.result.contract.ActivityResultContracts
import androidx.core.view.isVisible
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.navigation.fragment.findNavController
import androidx.recyclerview.widget.LinearLayoutManager
import com.phinma.upang.data.model.Request
import com.phinma.upang.data.model.RequestType
import com.phinma.upang.databinding.FragmentCreateRequestBinding
import dagger.hilt.android.AndroidEntryPoint
import java.io.File
import java.io.FileOutputStream

@AndroidEntryPoint
class CreateRequestFragment : Fragment() {

    private var _binding: FragmentCreateRequestBinding? = null
    private val binding get() = _binding!!
    private val viewModel: CreateRequestViewModel by viewModels()
    private lateinit var requirementsAdapter: RequirementsAdapter

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
        _binding = FragmentCreateRequestBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        setupRecyclerView()
        setupListeners()
        observeViewModel()
    }

    private fun setupRecyclerView() {
        requirementsAdapter = RequirementsAdapter(
            onUploadClick = { requirement ->
                launchFilePicker(requirement)
            },
            onRemoveFile = { requirement ->
                viewModel.removeFile(requirement)
            }
        )

        binding.requirementsRecyclerView.apply {
            adapter = requirementsAdapter
            layoutManager = LinearLayoutManager(context)
            setHasFixedSize(true)
        }
    }

    private fun setupListeners() {
        binding.requestTypeInput.setOnItemClickListener { _, _, position, _ ->
            val type = binding.requestTypeInput.adapter.getItem(position) as RequestType
            viewModel.selectRequestType(type)
        }

        binding.submitButton.setOnClickListener {
            val purpose = binding.purposeInput.text.toString()
            viewModel.createRequest(purpose)
        }
    }

    private fun observeViewModel() {
        viewModel.requestTypes.observe(viewLifecycleOwner) { types ->
            val adapter = ArrayAdapter(
                requireContext(),
                android.R.layout.simple_dropdown_item_1line,
                types
            )
            binding.requestTypeInput.setAdapter(adapter)
        }

        viewModel.selectedType.observe(viewLifecycleOwner) { type ->
            binding.apply {
                processingTimeText.text = "Processing time: ${type.processing_time}"
                submitButton.isEnabled = true
            }
        }

        viewModel.requirements.observe(viewLifecycleOwner) { requirements ->
            requirementsAdapter.submitList(requirements.map { RequirementItem.fromRequirement(it) })
            binding.noRequirementsText.isVisible = requirements.isEmpty()
            binding.requirementsRecyclerView.isVisible = requirements.isNotEmpty()
        }

        viewModel.uiState.observe(viewLifecycleOwner) { state ->
            when (state) {
                is CreateRequestViewModel.CreateRequestUiState.Loading -> {
                    showLoading(true)
                }
                is CreateRequestViewModel.CreateRequestUiState.Success -> {
                    showLoading(false)
                }
                is CreateRequestViewModel.CreateRequestUiState.Error -> {
                    showLoading(false)
                    showError(state.message)
                }
                is CreateRequestViewModel.CreateRequestUiState.RequestCreated -> {
                    showLoading(false)
                    handleRequestCreated(state.request)
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

            viewModel.addFile(requirement, file)
        } catch (e: Exception) {
            showError("Failed to process file")
        }
    }

    private fun handleRequestCreated(request: Request) {
        findNavController().navigateUp()
        Toast.makeText(
            requireContext(),
            "Request created successfully",
            Toast.LENGTH_LONG
        ).show()
    }

    private fun showLoading(isLoading: Boolean) {
        binding.progressBar.isVisible = isLoading
        binding.submitButton.isEnabled = !isLoading
        binding.requestTypeInput.isEnabled = !isLoading
        binding.purposeInput.isEnabled = !isLoading
    }

    private fun showError(message: String) {
        Toast.makeText(requireContext(), message, Toast.LENGTH_LONG).show()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
} 