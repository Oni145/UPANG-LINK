package com.phinma.upang.ui.auth

import android.os.Bundle
import android.view.View
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.navigation.fragment.findNavController
import com.phinma.upang.R
import com.phinma.upang.databinding.FragmentForgotPasswordBinding
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class ForgotPasswordFragment : Fragment(R.layout.fragment_forgot_password) {
    private var _binding: FragmentForgotPasswordBinding? = null
    private val binding get() = _binding!!
    private val viewModel: AuthViewModel by viewModels()

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        _binding = FragmentForgotPasswordBinding.bind(view)

        setupClickListeners()
        observeViewModel()
    }

    private fun setupClickListeners() {
        with(binding) {
            btnBack.setOnClickListener {
                findNavController().navigateUp()
            }

            btnResetPassword.setOnClickListener {
                val email = etEmail.text.toString()
                viewModel.resetPassword(email)
            }

            btnLogin.setOnClickListener {
                findNavController().navigateUp()
            }
        }
    }

    private fun observeViewModel() {
        viewModel.resetPasswordState.observe(viewLifecycleOwner) { state ->
            when (state) {
                is AuthViewModel.AuthState.Loading -> {
                    binding.btnResetPassword.isEnabled = false
                    // Show loading indicator if needed
                }
                is AuthViewModel.AuthState.Success -> {
                    binding.btnResetPassword.isEnabled = true
                    findNavController().navigate(R.id.action_forgotPasswordFragment_to_resetPasswordSentFragment)
                }
                is AuthViewModel.AuthState.Error -> {
                    binding.btnResetPassword.isEnabled = true
                    // Show error message
                }
                else -> {
                    binding.btnResetPassword.isEnabled = true
                }
            }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
} 