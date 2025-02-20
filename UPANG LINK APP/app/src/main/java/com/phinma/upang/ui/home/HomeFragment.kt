package com.phinma.upang.ui.home

import android.os.Bundle
import android.view.View
import androidx.fragment.app.Fragment
import androidx.navigation.fragment.findNavController
import com.phinma.upang.R
import com.phinma.upang.databinding.FragmentHomeBinding
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class HomeFragment : Fragment(R.layout.fragment_home) {

    private var _binding: FragmentHomeBinding? = null
    private val binding get() = _binding!!

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        _binding = FragmentHomeBinding.bind(view)

        setupViews()
    }

    private fun setupViews() {
        binding.newRequestButton.setOnClickListener {
            findNavController().navigate(R.id.action_requests_to_create)
        }

        binding.viewRequestsButton.setOnClickListener {
            findNavController().navigate(R.id.navigation_requests)
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
} 