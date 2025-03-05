package com.phinma.upang.ui.auth

import android.os.Bundle
import android.view.View
import androidx.core.view.isVisible
import androidx.fragment.app.Fragment
import androidx.fragment.app.activityViewModels
import androidx.navigation.fragment.findNavController
import com.google.android.material.snackbar.Snackbar
import com.phinma.upang.R
import com.phinma.upang.databinding.FragmentResetPasswordSentBinding
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class ResetPasswordSentFragment : Fragment(R.layout.fragment_reset_password_sent) {

    private val viewModel: ForgotPasswordViewModel by activityViewModels()
    private var _binding: FragmentResetPasswordSentBinding? = null
    private val binding get() = _binding!!

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        _binding = FragmentResetPasswordSentBinding.bind(view)

        setupClickListeners()
        observeViewModel()
        updateEmailText()
    }

    private fun updateEmailText() {
        val email = viewModel.lastEmail
        if (email.isBlank()) {
            findNavController().navigateUp()
            return
        }
        binding.tvSubtitle.text = getString(R.string.reset_password_sent_message, email)
    }

    private fun setupClickListeners() {
        binding.btnBack.setOnClickListener {
            findNavController().navigateUp()
        }

        binding.btnResendEmail.setOnClickListener {
            val email = viewModel.lastEmail
            if (email.isBlank()) {
                Snackbar.make(binding.root, "Email address not found", Snackbar.LENGTH_SHORT).show()
                findNavController().navigateUp()
                return@setOnClickListener
            }
            viewModel.resetPassword(email)
        }

        binding.btnLogin.setOnClickListener {
            findNavController().navigate(
                ResetPasswordSentFragmentDirections.actionResetPasswordSentFragmentToLoginFragment()
            )
        }
    }

    private fun observeViewModel() {
        viewModel.email.observe(viewLifecycleOwner) { email ->
            if (email.isNotBlank()) {
                binding.tvSubtitle.text = getString(R.string.reset_password_sent_message, email)
            }
        }

        viewModel.forgotPasswordState.observe(viewLifecycleOwner) { state ->
            when (state) {
                is ForgotPasswordViewModel.ForgotPasswordState.Loading -> {
                    setLoading(true)
                }
                is ForgotPasswordViewModel.ForgotPasswordState.Success -> {
                    setLoading(false)
                    viewModel.clearState()
                    // Show success message and update UI
                    Snackbar.make(binding.root, state.message, Snackbar.LENGTH_SHORT).show()
                    binding.ivSuccess.isVisible = true
                    binding.tvTitle.text = getString(R.string.check_your_email)
                    updateEmailText()
                }
                is ForgotPasswordViewModel.ForgotPasswordState.Error -> {
                    setLoading(false)
                    Snackbar.make(binding.root, state.message, Snackbar.LENGTH_SHORT).show()
                }
            }
        }
    }

    private fun setLoading(isLoading: Boolean) {
        binding.btnResendEmail.isEnabled = !isLoading
        binding.btnLogin.isEnabled = !isLoading
        binding.progressBar.isVisible = isLoading
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
} 