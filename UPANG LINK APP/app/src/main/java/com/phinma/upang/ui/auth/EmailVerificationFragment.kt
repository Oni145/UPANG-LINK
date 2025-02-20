package com.phinma.upang.ui.auth

import android.os.Bundle
import android.view.View
import androidx.core.view.isVisible
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.navigation.fragment.findNavController
import com.google.android.material.snackbar.Snackbar
import com.phinma.upang.R
import com.phinma.upang.databinding.FragmentEmailVerificationBinding
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class EmailVerificationFragment : Fragment(R.layout.fragment_email_verification) {

    private val viewModel: AuthViewModel by viewModels()
    private var _binding: FragmentEmailVerificationBinding? = null
    private val binding get() = _binding!!

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        _binding = FragmentEmailVerificationBinding.bind(view)

        setupClickListeners()
        observeViewModel()
    }

    private fun setupClickListeners() {
        binding.btnBack.setOnClickListener {
            findNavController().navigateUp()
        }

        binding.btnResendEmail.setOnClickListener {
            viewModel.resendVerificationEmail()
        }

        binding.btnLogin.setOnClickListener {
            findNavController().navigate(
                EmailVerificationFragmentDirections.actionEmailVerificationFragmentToLoginFragment()
            )
        }
    }

    private fun observeViewModel() {
        viewModel.resendEmailState.observe(viewLifecycleOwner) { state ->
            when (state) {
                is AuthViewModel.AuthState.Loading -> {
                    setLoading(true)
                }
                is AuthViewModel.AuthState.Success -> {
                    setLoading(false)
                    Snackbar.make(binding.root, "Verification email resent", Snackbar.LENGTH_SHORT).show()
                }
                is AuthViewModel.AuthState.Error -> {
                    setLoading(false)
                    Snackbar.make(binding.root, state.message, Snackbar.LENGTH_SHORT).show()
                }
                else -> {
                    setLoading(false)
                }
            }
        }
    }

    private fun setLoading(isLoading: Boolean) {
        binding.progressBar.isVisible = isLoading
        binding.btnResendEmail.isEnabled = !isLoading
        binding.btnLogin.isEnabled = !isLoading
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
} 