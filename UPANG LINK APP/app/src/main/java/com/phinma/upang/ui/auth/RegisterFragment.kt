package com.phinma.upang.ui.auth

import android.os.Bundle
import android.util.Log
import android.view.View
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.navigation.fragment.findNavController
import com.google.android.material.snackbar.Snackbar
import com.phinma.upang.R
import com.phinma.upang.databinding.FragmentRegisterBinding
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class RegisterFragment : Fragment(R.layout.fragment_register) {
    private var _binding: FragmentRegisterBinding? = null
    private val binding get() = _binding!!
    private val viewModel: RegisterViewModel by viewModels()
    
    // Variable to track if button was recently clicked
    private var isButtonClickable = true

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        _binding = FragmentRegisterBinding.bind(view)
        setupClickListeners()
        observeViewModel()
    }

    private fun setupClickListeners() {
        with(binding) {
            btnBack.setOnClickListener {
                findNavController().navigateUp()
            }

            btnRegister.setOnClickListener {
                // Prevent rapid clicks
                if (!isButtonClickable) {
                    return@setOnClickListener
                }
                
                // Disable button immediately
                setButtonClickable(false)
                
                val firstName = etFirstName.text.toString()
                val lastName = etLastName.text.toString()
                val email = etEmail.text.toString()
                val password = etPassword.text.toString()
                val confirmPassword = etConfirmPassword.text.toString()

                // If validation fails, re-enable the button after a short delay
                if (firstName.isBlank() || lastName.isBlank() || email.isBlank() || 
                    password.isBlank() || confirmPassword.isBlank()) {
                    Snackbar.make(binding.root, "Please fill in all fields", Snackbar.LENGTH_SHORT).show()
                    setButtonClickableWithDelay()
                    return@setOnClickListener
                }

                // Log the input values
                Log.d("RegisterFragment", "First Name: $firstName")
                Log.d("RegisterFragment", "Last Name: $lastName")
                Log.d("RegisterFragment", "Email: $email")
                Log.d("RegisterFragment", "Password: $password")
                Log.d("RegisterFragment", "Confirm Password: $confirmPassword")

                viewModel.register(
                    firstName = firstName,
                    lastName = lastName,
                    email = email,
                    password = password,
                    confirmPassword = confirmPassword
                )
            }

            // Toggle password visibility
            tilPassword.setEndIconOnClickListener {
                val isPasswordVisible = etPassword.inputType == android.text.InputType.TYPE_TEXT_VARIATION_VISIBLE_PASSWORD
                etPassword.inputType = if (isPasswordVisible) {
                    android.text.InputType.TYPE_CLASS_TEXT or android.text.InputType.TYPE_TEXT_VARIATION_PASSWORD
                } else {
                    android.text.InputType.TYPE_TEXT_VARIATION_VISIBLE_PASSWORD
                }
                etPassword.setSelection(etPassword.text?.length ?: 0)
            }

            // Toggle confirm password visibility
            tilConfirmPassword.setEndIconOnClickListener {
                val isConfirmPasswordVisible = etConfirmPassword.inputType == android.text.InputType.TYPE_TEXT_VARIATION_VISIBLE_PASSWORD
                etConfirmPassword.inputType = if (isConfirmPasswordVisible) {
                    android.text.InputType.TYPE_CLASS_TEXT or android.text.InputType.TYPE_TEXT_VARIATION_PASSWORD
                } else {
                    android.text.InputType.TYPE_TEXT_VARIATION_VISIBLE_PASSWORD
                }
                etConfirmPassword.setSelection(etConfirmPassword.text?.length ?: 0)
            }

            btnLogin.setOnClickListener {
                findNavController().navigate(R.id.action_registerFragment_to_loginFragment)
            }
        }
    }

    // Helper method to re-enable button after a delay
    private fun setButtonClickableWithDelay() {
        binding.btnRegister.postDelayed({
            setButtonClickable(true)
        }, 1500) // 1.5 seconds debounce time
    }
    
    // Helper method to update button clickable state
    private fun setButtonClickable(clickable: Boolean) {
        isButtonClickable = clickable
        binding.btnRegister.isEnabled = clickable
    }

    private fun observeViewModel() {
        viewModel.registerState.observe(viewLifecycleOwner) { state ->
            when (state) {
                is RegisterViewModel.RegisterState.Loading -> {
                    setLoading(true)
                }
                is RegisterViewModel.RegisterState.Success -> {
                    setLoading(false)
                    Snackbar.make(binding.root, "Registration successful!", Snackbar.LENGTH_LONG).show()
                    
                    // Create a bundle to pass the email
                    val email = binding.etEmail.text.toString()
                    val bundle = Bundle().apply {
                        putString("email", email)
                    }
                    
                    // Navigate with the email as an argument
                    findNavController().navigate(
                        R.id.action_registerFragment_to_emailVerificationFragment,
                        bundle
                    )
                }
                is RegisterViewModel.RegisterState.Error -> {
                    setLoading(false)
                    Snackbar.make(binding.root, state.message, Snackbar.LENGTH_SHORT).show()
                    // Re-enable button after error
                    setButtonClickableWithDelay()
                }
            }
        }
    }

    private fun setLoading(isLoading: Boolean) {
        binding.btnRegister.isEnabled = !isLoading
        // Update clickable state to match
        isButtonClickable = !isLoading
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
} 