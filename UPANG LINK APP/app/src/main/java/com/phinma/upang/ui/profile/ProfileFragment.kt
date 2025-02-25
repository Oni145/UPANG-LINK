package com.phinma.upang.ui.profile

import android.os.Bundle
import android.view.View
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.navigation.fragment.findNavController
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import com.phinma.upang.R
import com.phinma.upang.databinding.FragmentProfileBinding
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class ProfileFragment : Fragment(R.layout.fragment_profile) {

    private var _binding: FragmentProfileBinding? = null
    private val binding get() = _binding!!
    private val viewModel: ProfileViewModel by viewModels()

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        _binding = FragmentProfileBinding.bind(view)

        setupViews()
        setupObservers()
    }

    private fun setupViews() {
        binding.toolbar.setOnMenuItemClickListener { menuItem ->
            when (menuItem.itemId) {
                // R.id.action_settings -> {
                //     // TODO: Navigate to settings screen
                //     true
                // }
                else -> false
            }
        }

        binding.btnEditProfile.setOnClickListener {
            // TODO: Navigate to edit profile screen
        }

        binding.btnLogout.setOnClickListener {
            showLogoutConfirmationDialog()
        }
    }

    private fun setupObservers() {
        viewModel.profileState.observe(viewLifecycleOwner) { state ->
            when (state) {
                is ProfileViewModel.ProfileState.Loading -> {
                    // Handle loading state if needed
                }
                is ProfileViewModel.ProfileState.Success -> {
                    binding.tvName.text = "${state.user.firstName} ${state.user.lastName}"
                    binding.tvEmail.text = state.user.email
                }
                is ProfileViewModel.ProfileState.Error -> {
                    // Handle error state if needed
                }
            }
        }
    }

    private fun showLogoutConfirmationDialog() {
        MaterialAlertDialogBuilder(requireContext())
            .setTitle("Logout")
            .setMessage("Are you sure you want to logout?")
            .setNegativeButton("Cancel") { dialog, _ ->
                dialog.dismiss()
            }
            .setPositiveButton("Logout") { _, _ ->
                viewModel.logout()
                findNavController().navigate(R.id.action_profile_to_login)
            }
            .show()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
} 