package com.phinma.upang.ui.auth

import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.view.View
import androidx.activity.OnBackPressedCallback
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
    private var userEmail: String = ""
    private var isResendButtonClickable = true
    private val handler = Handler(Looper.getMainLooper())
    private var pendingButtonTask: Runnable? = null

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        _binding = FragmentEmailVerificationBinding.bind(view)

        // Get email from arguments or saved state
        arguments?.getString("email")?.let {
            userEmail = it
        }
        
        // Log the email to verify it's being received
        android.util.Log.d("EmailVerificationFragment", "Received email: $userEmail")

        setupClickListeners()
        observeViewModel()
        setupBackPressHandler()
    }

    private fun setupBackPressHandler() {
        // Register a back press callback to handle system back button
        requireActivity().onBackPressedDispatcher.addCallback(viewLifecycleOwner, object : OnBackPressedCallback(true) {
            override fun handleOnBackPressed() {
                // Cancel any pending tasks before navigating
                cancelPendingTasks()
                // Navigate directly to login instead of going back
                findNavController().navigate(R.id.action_emailVerificationFragment_to_loginFragment)
            }
        })
    }

    private fun setupClickListeners() {
        binding.btnBack.setOnClickListener {
            // Cancel any pending tasks before navigating
            cancelPendingTasks()
            // Navigate directly to login screen instead of going back
            findNavController().navigate(R.id.action_emailVerificationFragment_to_loginFragment)
        }

        binding.btnResendEmail.setOnClickListener {
            // Prevent rapid clicks
            if (!isResendButtonClickable) {
                return@setOnClickListener
            }
            
            if (userEmail.isNotBlank()) {
                // Disable the button immediately
                setResendButtonClickable(false)
                viewModel.resendVerificationEmail(userEmail)
            } else {
                Snackbar.make(binding.root, "Email address not found", Snackbar.LENGTH_SHORT).show()
            }
        }

        binding.btnLogin.setOnClickListener {
            // Cancel any pending tasks before navigating
            cancelPendingTasks()
            findNavController().navigate(R.id.action_emailVerificationFragment_to_loginFragment)
        }
    }
    
    // Helper method to re-enable button after a delay
    private fun setResendButtonClickableWithDelay() {
        pendingButtonTask = Runnable {
            _binding?.let { // Only proceed if binding is not null
                setResendButtonClickable(true)
            }
        }
        handler.postDelayed(pendingButtonTask!!, 10000) // 10 seconds cooldown to prevent spam
    }
    
    // Helper method to update button clickable state
    private fun setResendButtonClickable(clickable: Boolean) {
        isResendButtonClickable = clickable
        _binding?.btnResendEmail?.isEnabled = clickable
    }

    private fun observeViewModel() {
        viewModel.resendEmailState.observe(viewLifecycleOwner) { state ->
            when (state) {
                is AuthViewModel.AuthState.Loading -> {
                    setLoading(true)
                }
                is AuthViewModel.AuthState.Success -> {
                    setLoading(false)
                    _binding?.let { // Check if binding exists
                        Snackbar.make(it.root, "Verification email resent", Snackbar.LENGTH_SHORT).show()
                        // Start the cooldown timer
                        setResendButtonClickableWithDelay()
                    }
                }
                is AuthViewModel.AuthState.Error -> {
                    setLoading(false)
                    _binding?.let { // Check if binding exists
                        Snackbar.make(it.root, state.message, Snackbar.LENGTH_SHORT).show()
                    }
                    // Re-enable the button but with a short delay
                    pendingButtonTask = Runnable {
                        _binding?.let { // Only proceed if binding is not null
                            setResendButtonClickable(true)
                        }
                    }
                    handler.postDelayed(pendingButtonTask!!, 2000) // 2 seconds delay after error
                }
                else -> {
                    setLoading(false)
                }
            }
        }
    }

    private fun setLoading(isLoading: Boolean) {
        _binding?.let { binding ->
            binding.progressBar.isVisible = isLoading
            binding.btnResendEmail.isEnabled = !isLoading && isResendButtonClickable
            binding.btnLogin.isEnabled = !isLoading
        }
    }

    private fun cancelPendingTasks() {
        pendingButtonTask?.let {
            handler.removeCallbacks(it)
        }
        pendingButtonTask = null
    }

    override fun onDestroyView() {
        cancelPendingTasks()
        super.onDestroyView()
        _binding = null
    }
} 