package com.phinma.upang.ui.home

import android.os.Bundle
import android.view.View
import androidx.navigation.fragment.findNavController
import androidx.recyclerview.widget.LinearLayoutManager
import com.phinma.upang.R
import com.phinma.upang.databinding.FragmentHomeBinding
import com.phinma.upang.ui.base.BaseFragment
import dagger.hilt.android.AndroidEntryPoint
import javax.inject.Inject

@AndroidEntryPoint
class HomeFragment : BaseFragment(R.layout.fragment_home) {

    private var _binding: FragmentHomeBinding? = null
    private val binding get() = _binding!!
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
        
        // In a real implementation, you would load actual updates from your data source
        loadRequestUpdates()
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
    
    // In a real implementation, this method would fetch actual request updates from your backend
    private fun loadRequestUpdates() {
        // This is a placeholder for demonstration purposes
        // In a real app, you would:
        // 1. Fetch the user's requests from your API or database
        // 2. Check for status changes or updates
        // 3. Create Update objects based on those changes
        // 4. Display them in the RecyclerView
        
        // For now, we'll show a message indicating no updates
        binding.tvNoUpdates.visibility = View.VISIBLE
        binding.rvUpdates.visibility = View.GONE
        
        // Example of how you would implement this with real data:
        /*
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val requestUpdates = requestRepository.getRequestUpdates()
                
                if (requestUpdates.isNotEmpty()) {
                    val updates = requestUpdates.map { requestUpdate ->
                        Update(
                            id = requestUpdate.id,
                            title = "Request ${requestUpdate.statusText}: ${requestUpdate.requestCode}",
                            description = requestUpdate.message,
                            date = requestUpdate.dateUpdated,
                            type = UpdateType.REQUEST_STATUS,
                            status = requestUpdate.status
                        )
                    }
                    
                    binding.tvNoUpdates.visibility = View.GONE
                    binding.rvUpdates.visibility = View.VISIBLE
                    updatesAdapter.submitList(updates)
                } else {
                    binding.tvNoUpdates.visibility = View.VISIBLE
                    binding.rvUpdates.visibility = View.GONE
                }
            } catch (e: Exception) {
                // Handle error
                binding.tvNoUpdates.visibility = View.VISIBLE
                binding.rvUpdates.visibility = View.GONE
            }
        }
        */
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
} 