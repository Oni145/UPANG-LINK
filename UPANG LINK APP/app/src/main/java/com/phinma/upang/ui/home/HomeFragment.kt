package com.phinma.upang.ui.home

import android.os.Bundle
import android.view.View
import android.widget.Toast
import androidx.core.view.isVisible
import androidx.fragment.app.viewModels
import androidx.navigation.fragment.findNavController
import androidx.recyclerview.widget.LinearLayoutManager
import com.phinma.upang.R
import com.phinma.upang.databinding.FragmentHomeBinding
import com.phinma.upang.ui.base.BaseFragment
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class HomeFragment : BaseFragment(R.layout.fragment_home) {

    private var _binding: FragmentHomeBinding? = null
    private val binding get() = _binding!!
    private val viewModel: HomeViewModel by viewModels()
    private lateinit var updatesAdapter: UpdatesAdapter
    
    // In a real implementation, you would inject your repositories or services
    // @Inject
    // lateinit var requestRepository: RequestRepository

    override val hasCustomInsetHandling: Boolean = true

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        _binding = FragmentHomeBinding.bind(view)
        setupViews()
        setupUpdatesSection()
        observeViewModel()
    }

    private fun setupViews() {
        // Set up the floating action button
        binding.fabAddRequest.setOnClickListener {
            findNavController().navigate(R.id.action_navigation_home_to_createRequestFragment)
        }
    }
    
    private fun setupUpdatesSection() {
        // Initialize the updates adapter
        updatesAdapter = UpdatesAdapter()
        binding.rvUpdates.apply {
            layoutManager = LinearLayoutManager(requireContext())
            adapter = updatesAdapter
        }
        
        // Initially hide both until we determine if we have updates
        binding.tvNoUpdates.visibility = View.GONE
        binding.rvUpdates.visibility = View.GONE
    }
    
    private fun observeViewModel() {
        // Observe updates
        viewModel.updates.observe(viewLifecycleOwner) { updates ->
            if (updates.isNotEmpty()) {
                binding.tvNoUpdates.visibility = View.GONE
                binding.rvUpdates.visibility = View.VISIBLE
                updatesAdapter.submitList(updates)
            } else {
                binding.tvNoUpdates.visibility = View.VISIBLE
                binding.rvUpdates.visibility = View.GONE
            }
        }
        
        // Observe loading state
        viewModel.isLoading.observe(viewLifecycleOwner) { isLoading ->
            // You can add a progress indicator here if needed
        }
        
        // Observe error messages
        viewModel.errorMessage.observe(viewLifecycleOwner) { errorMessage ->
            errorMessage?.let {
                Toast.makeText(requireContext(), it, Toast.LENGTH_LONG).show()
                viewModel.clearError()
            }
        }
    }
    
    override fun onResume() {
        super.onResume()
        // Refresh data when returning to the fragment
        viewModel.loadRequestUpdates()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
} 