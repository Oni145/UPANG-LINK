package com.phinma.upang.ui.auth

import android.os.Bundle
import android.view.View
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.navigation.fragment.findNavController
import com.google.android.material.snackbar.Snackbar
import com.phinma.upang.R
import com.phinma.upang.databinding.FragmentResetPasswordSentBinding
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class ResetPasswordSentFragment : Fragment(R.layout.fragment_reset_password_sent) {

    private val viewModel: AuthViewModel by viewModels()
    private var _binding: FragmentResetPasswordSentBinding? = null
    private val binding get() = _binding!!

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        _binding = FragmentResetPasswordSentBinding.bind(view)

        setupClickListeners()
    }

    private fun setupClickListeners() {
        binding.btnBack.setOnClickListener {
            findNavController().navigateUp()
        }

        binding.btnResendEmail.setOnClickListener {
            // TODO: Implement resend password reset email functionality
            Snackbar.make(binding.root, "Password reset email resent", Snackbar.LENGTH_SHORT).show()
        }

        binding.btnLogin.setOnClickListener {
            findNavController().navigate(
                ResetPasswordSentFragmentDirections.actionResetPasswordSentFragmentToLoginFragment()
            )
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
} 