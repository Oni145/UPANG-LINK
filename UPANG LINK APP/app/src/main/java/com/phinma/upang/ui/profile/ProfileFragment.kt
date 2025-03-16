package com.phinma.upang.ui.profile

import android.app.Dialog
import android.graphics.Color
import android.graphics.drawable.ColorDrawable
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.view.Window
import android.view.animation.AnimationUtils
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.navigation.fragment.findNavController
import com.google.android.material.button.MaterialButton
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import com.google.android.material.textfield.TextInputEditText
import com.google.android.material.textfield.TextInputLayout
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
        applyAnimations()
    }

    private fun setupViews() {
        // Set up app version
        val packageInfo = requireContext().packageManager.getPackageInfo(requireContext().packageName, 0)
        binding.tvAppVersion.text = "UPANG Link v${packageInfo.versionName}"

        binding.btnChangePassword.setOnClickListener {
            showChangePasswordDialog()
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
                    binding.tvName.text = "${state.user.first_name} ${state.user.last_name}"
                    binding.tvEmail.text = state.user.email
                }
                is ProfileViewModel.ProfileState.Error -> {
                    Toast.makeText(requireContext(), state.message, Toast.LENGTH_LONG).show()
                }
            }
        }

        viewModel.changePasswordState.observe(viewLifecycleOwner) { state ->
            when (state) {
                is ProfileViewModel.ChangePasswordState.Loading -> {
                    // Show loading indicator if needed
                }
                is ProfileViewModel.ChangePasswordState.Success -> {
                    Toast.makeText(requireContext(), "Password changed successfully", Toast.LENGTH_SHORT).show()
                }
                is ProfileViewModel.ChangePasswordState.Error -> {
                    Toast.makeText(requireContext(), state.message, Toast.LENGTH_LONG).show()
                }
                null -> { /* Initial state, do nothing */ }
            }
        }
    }

    private fun applyAnimations() {
        // Apply fade-in animation to profile image
        val fadeIn = AnimationUtils.loadAnimation(requireContext(), android.R.anim.fade_in)
        fadeIn.duration = 1000
        binding.ivProfileImage.startAnimation(fadeIn)
        
        // Apply slide-up animation to the card
        val slideUp = AnimationUtils.loadAnimation(requireContext(), R.anim.slide_up)
        binding.tvName.startAnimation(slideUp)
        binding.tvEmail.startAnimation(slideUp)
    }

    private fun showChangePasswordDialog() {
        // Create a custom dialog
        val dialog = Dialog(requireContext())
        dialog.requestWindowFeature(Window.FEATURE_NO_TITLE)
        dialog.setContentView(R.layout.dialog_change_password)
        
        // Make dialog background transparent
        dialog.window?.setBackgroundDrawable(ColorDrawable(Color.TRANSPARENT))
        
        // Set dialog width to match parent
        dialog.window?.setLayout(
            ViewGroup.LayoutParams.MATCH_PARENT,
            ViewGroup.LayoutParams.WRAP_CONTENT
        )
        
        // Get references to views
        val currentPasswordLayout = dialog.findViewById<TextInputLayout>(R.id.tilCurrentPassword)
        val newPasswordLayout = dialog.findViewById<TextInputLayout>(R.id.tilNewPassword)
        val confirmPasswordLayout = dialog.findViewById<TextInputLayout>(R.id.tilConfirmPassword)
        
        val currentPasswordInput = dialog.findViewById<TextInputEditText>(R.id.etCurrentPassword)
        val newPasswordInput = dialog.findViewById<TextInputEditText>(R.id.etNewPassword)
        val confirmPasswordInput = dialog.findViewById<TextInputEditText>(R.id.etConfirmPassword)
        
        val btnCancel = dialog.findViewById<MaterialButton>(R.id.btnCancel)
        val btnChange = dialog.findViewById<MaterialButton>(R.id.btnChange)
        
        // Set click listeners
        btnCancel.setOnClickListener {
            dialog.dismiss()
        }
        
        btnChange.setOnClickListener {
            val currentPassword = currentPasswordInput.text.toString()
            val newPassword = newPasswordInput.text.toString()
            val confirmPassword = confirmPasswordInput.text.toString()
            
            var isValid = true
            
            // Validate current password
            if (currentPassword.isEmpty()) {
                currentPasswordLayout.error = "Current password is required"
                isValid = false
            } else {
                currentPasswordLayout.error = null
            }
            
            // Validate new password
            if (newPassword.isEmpty()) {
                newPasswordLayout.error = "New password is required"
                isValid = false
            } else if (newPassword.length < 6) {
                newPasswordLayout.error = "Password must be at least 6 characters"
                isValid = false
            } else {
                newPasswordLayout.error = null
            }
            
            // Validate confirm password
            if (confirmPassword.isEmpty()) {
                confirmPasswordLayout.error = "Confirm password is required"
                isValid = false
            } else if (confirmPassword != newPassword) {
                confirmPasswordLayout.error = "Passwords do not match"
                isValid = false
            } else {
                confirmPasswordLayout.error = null
            }
            
            if (isValid) {
                viewModel.changePassword(currentPassword, newPassword, confirmPassword)
                dialog.dismiss()
            }
        }
        
        // Show the dialog
        dialog.show()
    }

    private fun showLogoutConfirmationDialog() {
        MaterialAlertDialogBuilder(requireContext(), R.style.ThemeOverlay_App_MaterialAlertDialog)
            .setTitle("Logout")
            .setMessage("Are you sure you want to logout?")
            .setNegativeButton("Cancel") { dialog, _ ->
                dialog.dismiss()
            }
            .setPositiveButton("Logout") { _, _ ->
                viewModel.logout()
                findNavController().navigate(R.id.action_global_loginFragment)
            }
            .show()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
} 