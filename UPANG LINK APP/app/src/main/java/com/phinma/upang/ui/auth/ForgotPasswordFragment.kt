package com.phinma.upang.ui.auth

import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.util.Log
import android.view.View
import androidx.core.view.isVisible
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.navigation.fragment.findNavController
import com.google.android.material.snackbar.Snackbar
import com.phinma.upang.R
import com.phinma.upang.databinding.FragmentForgotPasswordBinding
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class ForgotPasswordFragment : Fragment(R.layout.fragment_forgot_password) {
    private var _binding: FragmentForgotPasswordBinding? = null
    private val binding get() = _binding!!
    private val viewModel: ForgotPasswordViewModel by viewModels()
    private var hasEmailBeenSent = false
    private var isInCooldown = false
    private val cooldownDuration = 30000L // 30 seconds
    private val handler = Handler(Looper.getMainLooper())
    private var remainingTime = 0L

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
                if (hasEmailBeenSent) {
                    if (!isInCooldown) {
                        startCooldown()
                        viewModel.resetPassword(email)
                        Log.d("ForgotPasswordFragment", "Starting cooldown for Resend Email button")
                    } else {
                        val remainingSeconds = remainingTime / 1000
                        Snackbar.make(binding.root, "Please wait $remainingSeconds seconds before resending", Snackbar.LENGTH_SHORT).show()
                        Log.d("ForgotPasswordFragment", "Cooldown active. Remaining time: $remainingSeconds seconds")
                    }
                } else {
                    viewModel.resetPassword(email)
                }
            }

            btnLogin.setOnClickListener {
                findNavController().navigateUp()
            }
        }
    }

    private fun startCooldown() {
        isInCooldown = true
        remainingTime = cooldownDuration
        binding.btnResetPassword.isEnabled = false

        val runnable = object : Runnable {
            override fun run() {
                if (remainingTime > 0) {
                    remainingTime -= 1000
                    val seconds = remainingTime / 1000
                    binding.btnResetPassword.text = "Resend Email ($seconds)"
                    handler.postDelayed(this, 1000)
                } else {
                    isInCooldown = false
                    binding.btnResetPassword.isEnabled = true
                    binding.btnResetPassword.text = "Resend Email"
                }
            }
        }

        handler.post(runnable)
    }

    private fun observeViewModel() {
        viewModel.forgotPasswordState.observe(viewLifecycleOwner) { state ->
            when (state) {
                is ForgotPasswordViewModel.ForgotPasswordState.Loading -> {
                    setLoading(true)
                }
                is ForgotPasswordViewModel.ForgotPasswordState.Success -> {
                    setLoading(false)
                    if (!hasEmailBeenSent) {
                        hasEmailBeenSent = true
                        binding.btnResetPassword.text = "Resend Email"
                        Snackbar.make(binding.root, "Password reset link sent to your email", Snackbar.LENGTH_LONG).show()
                    } else {
                        Snackbar.make(binding.root, "Email resent successfully!", Snackbar.LENGTH_LONG).show()
                    }
                }
                is ForgotPasswordViewModel.ForgotPasswordState.Error -> {
                    setLoading(false)
                    Snackbar.make(binding.root, state.message, Snackbar.LENGTH_SHORT).show()
                }
            }
        }
    }

    private fun setLoading(isLoading: Boolean) {
        if (!isInCooldown) {
            binding.btnResetPassword.isEnabled = !isLoading
        }
        binding.btnLogin.isEnabled = !isLoading
        binding.progressBar.isVisible = isLoading
    }

    override fun onDestroyView() {
        super.onDestroyView()
        handler.removeCallbacksAndMessages(null)
        _binding = null
    }
} 