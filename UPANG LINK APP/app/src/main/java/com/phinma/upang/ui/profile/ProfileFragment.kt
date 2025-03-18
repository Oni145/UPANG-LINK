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
import android.widget.AutoCompleteTextView
import android.widget.ArrayAdapter
import android.widget.Button
import android.widget.ImageButton
import android.widget.TextView

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
        
        binding.btnStudentDetails.setOnClickListener {
            showStudentDetailsViewDialog()
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
                    
                    // Removed automatic popup for student details
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
        
        viewModel.studentDetailsState.observe(viewLifecycleOwner) { state ->
            when (state) {
                is ProfileViewModel.StudentDetailsState.Loading -> {
                    // Show loading indicator if needed
                    binding.errorMessageView.visibility = View.GONE
                }
                is ProfileViewModel.StudentDetailsState.Success -> {
                    // Always hide the error message, regardless of whether details are complete
                    binding.errorMessageView.visibility = View.GONE
                }
                is ProfileViewModel.StudentDetailsState.Error -> {
                    // Only show the error message for actual API errors, not for incomplete details
                    binding.errorMessageView.visibility = View.GONE
                }
                null -> { /* Initial state, do nothing */ }
            }
        }
        
        viewModel.updateStudentDetailsState.observe(viewLifecycleOwner) { state ->
            when (state) {
                is ProfileViewModel.UpdateStudentDetailsState.Loading -> {
                    // Show loading indicator if needed
                }
                is ProfileViewModel.UpdateStudentDetailsState.Success -> {
                    Toast.makeText(requireContext(), "Student details updated successfully", Toast.LENGTH_SHORT).show()
                    // Reset the state after showing the message to prevent it from showing again
                    viewModel.resetUpdateStudentDetailsState()
                }
                is ProfileViewModel.UpdateStudentDetailsState.Error -> {
                    Toast.makeText(requireContext(), state.message, Toast.LENGTH_LONG).show()
                    // Also reset error state after showing
                    viewModel.resetUpdateStudentDetailsState()
                }
                null -> { /* Initial state, do nothing */ }
            }
        }
    }

    private fun applyAnimations() {
        val fadeIn = AnimationUtils.loadAnimation(requireContext(), android.R.anim.fade_in)
        binding.ivProfileImage.startAnimation(fadeIn)
        binding.tvName.startAnimation(fadeIn)
        binding.tvEmail.startAnimation(fadeIn)
    }

    private fun showChangePasswordDialog() {
        val dialog = Dialog(requireContext())
        dialog.requestWindowFeature(Window.FEATURE_NO_TITLE)
        dialog.setContentView(R.layout.dialog_change_password)
        dialog.window?.setBackgroundDrawable(ColorDrawable(Color.TRANSPARENT))
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
                Toast.makeText(requireContext(), "Logged out successfully", Toast.LENGTH_SHORT).show()
                findNavController().navigate(R.id.action_global_loginFragment)
            }
            .show()
    }

    // Keep the method but don't call it automatically
    private fun showStudentDetailsWarning() {
        MaterialAlertDialogBuilder(requireContext())
            .setTitle("Complete Your Profile")
            .setMessage("Please complete your student details to fully use the app.")
            .setPositiveButton("Update Now") { _, _ ->
                showStudentDetailsEditDialog()
            }
            .setNegativeButton("Later", null)
            .show()
    }
    
    // New method to show view-only dialog
    private fun showStudentDetailsViewDialog() {
        val dialog = Dialog(requireContext())
        dialog.requestWindowFeature(Window.FEATURE_NO_TITLE)
        dialog.setContentView(R.layout.dialog_student_details_view)
        dialog.window?.setBackgroundDrawable(resources.getDrawable(R.drawable.rounded_dialog_background, requireContext().theme))
        dialog.window?.setLayout(
            ViewGroup.LayoutParams.MATCH_PARENT,
            ViewGroup.LayoutParams.WRAP_CONTENT
        )
        
        // Get references to views
        val tvStudentNumber = dialog.findViewById<TextView>(R.id.tvStudentNumber)
        val tvBirthdate = dialog.findViewById<TextView>(R.id.tvBirthdate)
        val tvEmergencyContactName = dialog.findViewById<TextView>(R.id.tvEmergencyContactName)
        val tvEmergencyContactPhone = dialog.findViewById<TextView>(R.id.tvEmergencyContactPhone)
        val tvCourse = dialog.findViewById<TextView>(R.id.tvCourse)
        val tvCurrentYear = dialog.findViewById<TextView>(R.id.tvCurrentYear)
        val btnEdit = dialog.findViewById<ImageButton>(R.id.btnEdit)
        val btnClose = dialog.findViewById<Button>(R.id.btnClose)
        
        // Fill with existing data if available
        viewModel.studentDetailsState.value?.let { state ->
            if (state is ProfileViewModel.StudentDetailsState.Success) {
                // Display student number or placeholder
                tvStudentNumber.text = state.user.student_number ?: "Not provided"
                
                // Display birthdate or placeholder
                tvBirthdate.text = state.user.birthdate ?: "Not provided"
                
                // Split emergency contact into name and phone if possible
                val emergencyContact = state.user.emergency_contact ?: ""
                if (emergencyContact.isNotEmpty()) {
                    val parts = emergencyContact.split(" - ", limit = 2)
                    if (parts.size > 1) {
                        tvEmergencyContactName.text = parts[0]
                        tvEmergencyContactPhone.text = parts[1]
                    } else {
                        tvEmergencyContactName.text = emergencyContact
                        tvEmergencyContactPhone.text = "Not provided"
                    }
                } else {
                    tvEmergencyContactName.text = "Not provided"
                    tvEmergencyContactPhone.text = "Not provided"
                }
                
                // Display course or placeholder
                tvCourse.text = state.user.course ?: "Not provided"
                
                // Display current year or placeholder
                tvCurrentYear.text = state.user.current_year ?: "Not provided"
            }
        }
        
        // Set up button click listeners
        btnClose.setOnClickListener {
            dialog.dismiss()
        }
        
        btnEdit.setOnClickListener {
            dialog.dismiss()
            showStudentDetailsEditDialog()
        }
        
        // Show the dialog
        dialog.show()
    }
    
    // Renamed the original dialog method to indicate it's for editing
    private fun showStudentDetailsEditDialog() {
        val dialog = Dialog(requireContext())
        dialog.requestWindowFeature(Window.FEATURE_NO_TITLE)
        dialog.setContentView(R.layout.dialog_student_details)
        dialog.window?.setBackgroundDrawable(resources.getDrawable(R.drawable.rounded_dialog_background, requireContext().theme))
        dialog.window?.setLayout(
            ViewGroup.LayoutParams.MATCH_PARENT,
            ViewGroup.LayoutParams.WRAP_CONTENT
        )
        
        // Get references to views
        val etStudentNumber = dialog.findViewById<TextInputEditText>(R.id.etStudentNumber)
        val etBirthdate = dialog.findViewById<TextInputEditText>(R.id.etBirthdate)
        val etEmergencyContactName = dialog.findViewById<TextInputEditText>(R.id.etEmergencyContactName)
        val etEmergencyContactPhone = dialog.findViewById<TextInputEditText>(R.id.etEmergencyContactPhone)
        val etCourse = dialog.findViewById<TextInputEditText>(R.id.etCourse)
        val actvCurrentYear = dialog.findViewById<AutoCompleteTextView>(R.id.actvCurrentYear)
        val btnCancel = dialog.findViewById<MaterialButton>(R.id.btnCancel)
        val btnSave = dialog.findViewById<MaterialButton>(R.id.btnSave)
        
        // Set up year level dropdown
        val yearLevels = arrayOf("1st Year", "2nd Year", "3rd Year", "4th Year", "5th Year")
        val adapter = ArrayAdapter(requireContext(), android.R.layout.simple_dropdown_item_1line, yearLevels)
        actvCurrentYear.setAdapter(adapter)
        
        // Pre-fill with existing data if available
        viewModel.studentDetailsState.value?.let { state ->
            if (state is ProfileViewModel.StudentDetailsState.Success) {
                etStudentNumber.setText(state.user.student_number)
                etBirthdate.setText(state.user.birthdate)
                
                // Split emergency contact into name and phone if possible
                val emergencyContact = state.user.emergency_contact ?: ""
                if (emergencyContact.isNotEmpty()) {
                    val parts = emergencyContact.split(" - ", limit = 2)
                    if (parts.size > 1) {
                        etEmergencyContactName.setText(parts[0])
                        etEmergencyContactPhone.setText(parts[1])
                    } else {
                        etEmergencyContactName.setText(emergencyContact)
                        etEmergencyContactPhone.setText("")
                    }
                }
                
                etCourse.setText(state.user.course)
                actvCurrentYear.setText(state.user.current_year, false)
            }
        }
        
        // Set up button click listeners
        btnCancel.setOnClickListener {
            dialog.dismiss()
            // Go back to view dialog
            showStudentDetailsViewDialog()
        }
        
        btnSave.setOnClickListener {
            val studentNumber = etStudentNumber.text.toString().trim()
            val birthdate = etBirthdate.text.toString().trim()
            val emergencyContactName = etEmergencyContactName.text.toString().trim()
            val emergencyContactPhone = etEmergencyContactPhone.text.toString().trim()
            val emergencyContact = "$emergencyContactName - $emergencyContactPhone"
            val course = etCourse.text.toString().trim()
            val currentYear = actvCurrentYear.text.toString().trim()
            
            // Validate inputs
            var isValid = true
            
            if (studentNumber.isEmpty()) {
                etStudentNumber.error = "Required"
                isValid = false
            }
            
            if (birthdate.isEmpty()) {
                etBirthdate.error = "Required"
                isValid = false
            }
            
            if (emergencyContactName.isEmpty()) {
                etEmergencyContactName.error = "Required"
                isValid = false
            }
            
            if (emergencyContactPhone.isEmpty()) {
                etEmergencyContactPhone.error = "Required"
                isValid = false
            }
            
            if (course.isEmpty()) {
                etCourse.error = "Required"
                isValid = false
            }
            
            if (currentYear.isEmpty()) {
                actvCurrentYear.error = "Required"
                isValid = false
            }
            
            if (isValid) {
                viewModel.updateStudentDetails(
                    studentNumber,
                    birthdate,
                    emergencyContact,
                    course,
                    currentYear
                )
                dialog.dismiss()
            }
        }
        
        // Show the dialog
        dialog.show()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
} 