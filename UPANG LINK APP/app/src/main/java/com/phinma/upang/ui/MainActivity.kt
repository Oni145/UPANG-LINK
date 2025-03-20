package com.phinma.upang.ui

import android.app.Dialog
import android.content.Context
import android.content.IntentFilter
import android.net.ConnectivityManager
import android.net.Network
import android.net.NetworkCapabilities
import android.net.NetworkRequest
import android.os.Build
import android.os.Bundle
import android.util.Log
import android.view.View
import android.widget.Button
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.WindowCompat
import androidx.core.view.WindowInsetsCompat
import androidx.core.view.WindowInsetsControllerCompat
import androidx.navigation.NavController
import androidx.navigation.NavOptions
import androidx.navigation.fragment.NavHostFragment
import androidx.navigation.ui.setupWithNavController
import com.phinma.upang.R
import com.phinma.upang.databinding.ActivityMainBinding
import com.phinma.upang.data.NetworkUtils
import com.phinma.upang.data.SessionManager
import dagger.hilt.android.AndroidEntryPoint
import javax.inject.Inject

@AndroidEntryPoint
class MainActivity : AppCompatActivity() {
    
    @Inject
    lateinit var sessionManager: SessionManager
    
    private lateinit var binding: ActivityMainBinding
    private lateinit var navController: NavController
    private var noInternetDialog: Dialog? = null
    private lateinit var connectivityManager: ConnectivityManager
    private lateinit var networkCallback: ConnectivityManager.NetworkCallback

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        
        // Configure window to draw under system bars
        WindowCompat.setDecorFitsSystemWindows(window, false)
        
        // Make the status bar transparent
        window.statusBarColor = android.graphics.Color.TRANSPARENT
        
        // Hide the system UI
        hideSystemUI()
        
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)

        // Set up network monitoring
        setupNetworkCallback()

        // Set up navigation
        val navHostFragment = supportFragmentManager.findFragmentById(R.id.nav_host_fragment) as NavHostFragment
        navController = navHostFragment.navController

        // Set up bottom navigation
        binding.bottomNav.setupWithNavController(navController)

        // Check token validity from SplashActivity
        val isTokenValid = intent.getBooleanExtra("token_valid", false)
        Log.d("MainActivity", "Token valid from intent: $isTokenValid")

        // Set up navigation based on token validity
        val navGraph = navController.navInflater.inflate(R.navigation.nav_graph_main)
        
        if (isTokenValid) {
            Log.d("MainActivity", "Setting start destination to mainFragment")
            navGraph.setStartDestination(R.id.mainFragment)
        } else {
            Log.d("MainActivity", "Setting start destination to loginFragment")
            navGraph.setStartDestination(R.id.loginFragment)
        }
        
        navController.graph = navGraph

        // Update UI based on current destination
        updateUIForDestination(navController.currentDestination?.id)

        // Show/hide bottom navigation based on current destination
        navController.addOnDestinationChangedListener { _, destination, _ ->
            Log.d("MainActivity", "Navigation to: ${resources.getResourceName(destination.id)}")
            updateUIForDestination(destination.id)
        }
    }

    private fun updateUIForDestination(destinationId: Int?) {
        when (destinationId) {
            R.id.loginFragment,
            R.id.registerFragment,
            R.id.forgotPasswordFragment,
            R.id.emailVerificationFragment,
            R.id.resetPasswordSentFragment -> {
                binding.bottomNav.visibility = View.GONE
            }
            else -> {
                binding.bottomNav.visibility = View.VISIBLE
            }
        }
    }

    private fun hideSystemUI() {
        val windowInsetsController = WindowInsetsControllerCompat(window, window.decorView)
        windowInsetsController.show(WindowInsetsCompat.Type.systemBars())
    }
    
    private fun setupNetworkCallback() {
        connectivityManager = getSystemService(Context.CONNECTIVITY_SERVICE) as ConnectivityManager
        
        networkCallback = object : ConnectivityManager.NetworkCallback() {
            override fun onAvailable(network: Network) {
                Log.d("MainActivity", "Network available")
                runOnUiThread {
                    noInternetDialog?.dismiss()
                }
            }
            
            override fun onLost(network: Network) {
                Log.d("MainActivity", "Network lost")
                runOnUiThread {
                    if (!isFinishing) {
                        showNoInternetDialog()
                    }
                }
            }
        }
        
        val networkRequest = NetworkRequest.Builder()
            .addCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET)
            .build()
            
        connectivityManager.registerNetworkCallback(networkRequest, networkCallback)
    }
    
    private fun showNoInternetDialog() {
        if (noInternetDialog?.isShowing == true) return
        
        noInternetDialog = Dialog(this, R.style.NoInternetDialogStyle).apply {
            setContentView(R.layout.dialog_no_internet)
            setCancelable(false)
            
            // Set dialog width to 90% of screen width
            window?.setLayout(
                (resources.displayMetrics.widthPixels * 0.9).toInt(),
                android.view.WindowManager.LayoutParams.WRAP_CONTENT
            )
            
            // Ensure button has the correct background
            findViewById<androidx.appcompat.widget.AppCompatButton>(R.id.btnTryAgain)?.apply {
                setBackgroundResource(R.drawable.sky_blue_button)
            }
            
            findViewById<androidx.appcompat.widget.AppCompatButton>(R.id.btnTryAgain).setOnClickListener {
                if (NetworkUtils.isNetworkAvailable(this@MainActivity)) {
                    dismiss()
                } else {
                    // Provide some feedback
                    val tryAgainButton = findViewById<androidx.appcompat.widget.AppCompatButton>(R.id.btnTryAgain)
                    val shakeAnimation = android.animation.ObjectAnimator.ofFloat(
                        tryAgainButton, "translationX", 0f, 25f, -25f, 25f, -25f, 15f, -15f, 6f, -6f, 0f
                    )
                    shakeAnimation.duration = 500
                    shakeAnimation.start()
                }
            }
            
            show()
        }
    }
    
    override fun onDestroy() {
        super.onDestroy()
        try {
            connectivityManager.unregisterNetworkCallback(networkCallback)
        } catch (e: Exception) {
            Log.e("MainActivity", "Error unregistering network callback", e)
        }
        noInternetDialog?.dismiss()
        noInternetDialog = null
    }
} 