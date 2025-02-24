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
                val firstName = etFirstName.text.toString()
                val lastName = etLastName.text.toString()
                val email = etEmail.text.toString()
                val password = etPassword.text.toString()
                val confirmPassword = etConfirmPassword.text.toString()

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

    private fun observeViewModel() {
        viewModel.registerState.observe(viewLifecycleOwner) { state ->
            when (state) {
                is RegisterViewModel.RegisterState.Loading -> {
                    setLoading(true)
                }
                is RegisterViewModel.RegisterState.Success -> {
                    setLoading(false)
                    Snackbar.make(binding.root, "Registration successful!", Snackbar.LENGTH_LONG).show()
                    findNavController().navigate(R.id.action_registerFragment_to_emailVerificationFragment)
                }
                is RegisterViewModel.RegisterState.Error -> {
                    setLoading(false)
                    Snackbar.make(binding.root, state.message, Snackbar.LENGTH_SHORT).show()
                }
            }
        }
    }

    private fun setLoading(isLoading: Boolean) {
        binding.btnRegister.isEnabled = !isLoading
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
} 