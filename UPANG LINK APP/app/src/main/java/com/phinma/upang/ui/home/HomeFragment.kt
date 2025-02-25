package com.phinma.upang.ui.home

import android.os.Bundle
import android.view.View
import androidx.navigation.fragment.findNavController
import com.phinma.upang.R
import com.phinma.upang.databinding.FragmentHomeBinding
import com.phinma.upang.ui.base.BaseFragment
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class HomeFragment : BaseFragment(R.layout.fragment_home) {

    private var _binding: FragmentHomeBinding? = null
    private val binding get() = _binding!!

    override val hasCustomInsetHandling: Boolean = true

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        _binding = FragmentHomeBinding.bind(view)
        setupViews()
    }

    private fun setupViews() {
        // Initially show the "No requests" message
        binding.tvNoRequests.visibility = View.VISIBLE
        binding.rvRecentRequests.visibility = View.GONE

        binding.fabAddRequest.setOnClickListener {
            findNavController().navigate(R.id.action_navigation_home_to_createRequestFragment)
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
} 