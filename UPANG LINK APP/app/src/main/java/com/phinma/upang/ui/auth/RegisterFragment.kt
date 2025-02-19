package com.phinma.upang.ui.auth

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.core.view.isVisible
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.navigation.fragment.findNavController
import com.phinma.upang.databinding.FragmentRegisterBinding
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class RegisterFragment : Fragment() {

    private var _binding: FragmentRegisterBinding? = null
    private val binding get() = _binding!!
    private val viewModel: RegisterViewModel by viewModels()

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentRegisterBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        setupListeners()
        observeViewModel()
    }

    private fun setupListeners() {
        binding.registerButton.setOnClickListener {
            viewModel.register(
                studentNumber = binding.studentNumberInput.text.toString(),
                firstName = binding.firstNameInput.text.toString(),
                lastName = binding.lastNameInput.text.toString(),
                email = binding.emailInput.text.toString(),
                course = binding.courseInput.text.toString(),
                yearLevel = binding.yearLevelInput.text.toString(),
                block = binding.blockInput.text.toString(),
                password = binding.passwordInput.text.toString(),
                confirmPassword = binding.confirmPasswordInput.text.toString()
            )
        }

        binding.loginLink.setOnClickListener {
            findNavController().navigateUp()
        }
    }

    private fun observeViewModel() {
        viewModel.registerState.observe(viewLifecycleOwner) { state ->
            when (state) {
                is RegisterViewModel.RegisterState.Loading -> {
                    showLoading(true)
                }
                is RegisterViewModel.RegisterState.Success -> {
                    showLoading(false)
                    // Navigate back to login screen
                    findNavController().navigateUp()
                    Toast.makeText(
                        requireContext(),
                        "Registration successful! Please login.",
                        Toast.LENGTH_LONG
                    ).show()
                }
                is RegisterViewModel.RegisterState.Error -> {
                    showLoading(false)
                    showError(state.message)
                }
            }
        }
    }

    private fun showLoading(isLoading: Boolean) {
        binding.progressBar.isVisible = isLoading
        binding.registerButton.isEnabled = !isLoading
        binding.studentNumberInput.isEnabled = !isLoading
        binding.firstNameInput.isEnabled = !isLoading
        binding.lastNameInput.isEnabled = !isLoading
        binding.emailInput.isEnabled = !isLoading
        binding.courseInput.isEnabled = !isLoading
        binding.yearLevelInput.isEnabled = !isLoading
        binding.blockInput.isEnabled = !isLoading
        binding.passwordInput.isEnabled = !isLoading
        binding.confirmPasswordInput.isEnabled = !isLoading
    }

    private fun showError(message: String) {
        Toast.makeText(requireContext(), message, Toast.LENGTH_LONG).show()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
} 