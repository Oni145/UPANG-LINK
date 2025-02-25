package com.phinma.upang.ui

import android.content.Context
import android.os.Bundle
import android.view.View
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.GravityCompat
import androidx.core.view.WindowCompat
import androidx.core.view.WindowInsetsCompat
import androidx.core.view.WindowInsetsControllerCompat
import androidx.drawerlayout.widget.DrawerLayout
import androidx.navigation.NavController
import androidx.navigation.fragment.NavHostFragment
import androidx.navigation.ui.AppBarConfiguration
import androidx.navigation.ui.navigateUp
import androidx.navigation.ui.setupActionBarWithNavController
import androidx.navigation.ui.setupWithNavController
import com.phinma.upang.R
import com.phinma.upang.databinding.ActivityMainBinding
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class MainActivity : AppCompatActivity() {
    
    private lateinit var binding: ActivityMainBinding

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

        // Ensure the NavController is set up before accessing it
        val navHostFragment = supportFragmentManager.findFragmentById(R.id.nav_host_fragment) as NavHostFragment
        val navController = navHostFragment.navController

        val sharedPreferences = getSharedPreferences("app_prefs", Context.MODE_PRIVATE)
        val token = sharedPreferences.getString("user_token", null)

        if (token != null) {
            // User is logged in, navigate to main screen
            navigateToMainScreen(navController)
        } else {
            // User is not logged in, navigate to login screen
            navigateToLoginScreen(navController)
        }
    }

    private fun hideSystemUI() {
        val windowInsetsController = WindowInsetsControllerCompat(window, window.decorView)
        windowInsetsController.show(WindowInsetsCompat.Type.systemBars())
    }

    private fun navigateToMainScreen(navController: NavController) {
        navController.navigate(R.id.mainFragment)
    }

    private fun navigateToLoginScreen(navController: NavController) {
        navController.navigate(R.id.loginFragment)
    }
} 