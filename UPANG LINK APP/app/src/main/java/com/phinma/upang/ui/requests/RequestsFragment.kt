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
            chipPending.setOnClickListener {
                viewModel.loadRequests(RequestFilter(status = RequestStatus.PENDING.name))
            }

            chipApproved.setOnClickListener {
                viewModel.loadRequests(RequestFilter(status = RequestStatus.IN_PROGRESS.name))
            }

            chipRejected.setOnClickListener {
                viewModel.loadRequests(RequestFilter(status = RequestStatus.REJECTED.name))
            }

            chipAll.setOnClickListener {
                viewModel.loadRequests()
            }
        }
    }

    private fun observeViewModel() {
        viewModel.requests.observe(viewLifecycleOwner) { requests ->
            requestsAdapter.submitList(requests)
            binding.noRequestsText.isVisible = requests.isEmpty()
            binding.recyclerViewRequests.isVisible = requests.isNotEmpty()
        }

        viewModel.isLoading.observe(viewLifecycleOwner) { isLoading ->
            binding.progressBar.isVisible = isLoading
            binding.swipeRefresh.isRefreshing = isLoading
        }

        viewModel.error.observe(viewLifecycleOwner) { error ->
            error?.let {
                Toast.makeText(context, it, Toast.LENGTH_LONG).show()
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