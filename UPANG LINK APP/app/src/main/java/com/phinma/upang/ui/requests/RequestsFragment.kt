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
import androidx.recyclerview.widget.LinearLayoutManager
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import com.phinma.upang.R
import com.phinma.upang.data.model.Request
import com.phinma.upang.data.model.RequestFilter
import com.phinma.upang.data.model.RequestStatus
import com.phinma.upang.databinding.FragmentRequestsBinding
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class RequestsFragment : Fragment() {

    private var _binding: FragmentRequestsBinding? = null
    private val binding get() = _binding!!
    private val viewModel: RequestsViewModel by viewModels()
    private lateinit var requestsAdapter: RequestsAdapter

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentRequestsBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        setupRecyclerView()
        setupListeners()
        observeViewModel()
        
        // Initial load
        viewModel.loadRequests()
    }

    override fun onResume() {
        super.onResume()
        // Refresh data when returning to the fragment
        viewModel.loadRequests()
    }

    private fun setupRecyclerView() {
        requestsAdapter = RequestsAdapter(
            onItemClick = { request ->
                findNavController().navigate(
                    RequestsFragmentDirections.actionRequestsToDetails(request.id)
                )
            },
            onCancelClick = { request ->
                showCancelConfirmationDialog(request)
            }
        )

        binding.recyclerViewRequests.apply {
            adapter = requestsAdapter
            layoutManager = LinearLayoutManager(context)
            setHasFixedSize(true)
        }
    }

    private fun setupListeners() {
        binding.swipeRefresh.setOnRefreshListener {
            viewModel.loadRequests()
        }

        binding.fabNewRequest.setOnClickListener {
            findNavController().navigate(
                RequestsFragmentDirections.actionRequestsToCreate()
            )
        }

        setupFilterChips()
    }

    private fun setupFilterChips() {
        with(binding) {
            chipAll.setOnClickListener {
                viewModel.loadRequests()
            }

            chipPending.setOnClickListener {
                viewModel.loadRequests(RequestFilter(status = RequestStatus.PENDING.name))
            }

            chipInProgress.setOnClickListener {
                viewModel.loadRequests(RequestFilter(status = RequestStatus.IN_PROGRESS.name))
            }

            chipCompleted.setOnClickListener {
                viewModel.loadRequests(RequestFilter(status = RequestStatus.COMPLETED.name))
            }
        }
    }

    private fun observeViewModel() {
        viewModel.requests.observe(viewLifecycleOwner) { requests ->
            requestsAdapter.submitList(requests)
            
            // Update visibility of views
            binding.apply {
                val isLoading = viewModel.isLoading.value == true
                noRequestsText.isVisible = requests.isEmpty() && !isLoading
                recyclerViewRequests.isVisible = requests.isNotEmpty()
                
                // Show appropriate message for empty state
                if (requests.isEmpty() && !isLoading) {
                    noRequestsText.text = when {
                        viewModel.currentFilter.value?.status != null -> 
                            getString(R.string.no_filtered_requests, viewModel.currentFilter.value?.status?.toLowerCase())
                        else -> getString(R.string.no_requests_found)
                    }
                }
            }
        }

        viewModel.isLoading.observe(viewLifecycleOwner) { isLoading ->
            binding.apply {
                progressBar.isVisible = isLoading
                swipeRefresh.isRefreshing = isLoading
                // Hide no requests text while loading
                if (isLoading) {
                    noRequestsText.isVisible = false
                } else {
                    // Show no requests text only if the list is empty
                    noRequestsText.isVisible = viewModel.requests.value?.isEmpty() == true
                }
            }
        }

        viewModel.error.observe(viewLifecycleOwner) { error ->
            error?.let {
                Toast.makeText(context, it, Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun showCancelConfirmationDialog(request: Request) {
        MaterialAlertDialogBuilder(requireContext())
            .setTitle("Cancel Request")
            .setMessage("Are you sure you want to cancel this request?")
            .setPositiveButton("Yes") { _, _ ->
                viewModel.cancelRequest(request.id)
            }
            .setNegativeButton("No", null)
            .show()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
} 