package com.phinma.upang.ui

import android.animation.AnimatorSet
import android.animation.ObjectAnimator
import android.content.Intent
import android.os.Bundle
import android.util.Log
import android.view.View
import android.widget.ImageView
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.phinma.upang.R
import com.phinma.upang.data.SessionManager
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import javax.inject.Inject

@AndroidEntryPoint
class SplashActivity : AppCompatActivity() {
    @Inject
    lateinit var sessionManager: SessionManager

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_splash)

        val logo = findViewById<ImageView>(R.id.logoImage)

        // Create bounce animation for logo
        val scaleX = ObjectAnimator.ofFloat(logo, View.SCALE_X, 0.8f, 1.2f, 1f)
        val scaleY = ObjectAnimator.ofFloat(logo, View.SCALE_Y, 0.8f, 1.2f, 1f)
        val translationY = ObjectAnimator.ofFloat(logo, View.TRANSLATION_Y, 0f, -50f, 0f)

        // Combine logo animations
        val bounceAnimator = AnimatorSet().apply {
            playTogether(scaleX, scaleY, translationY)
            duration = 1000
        }

        // Play all animations
        bounceAnimator.start()

        // Check token and user profile after animations
        lifecycleScope.launch {
            delay(1500) // Wait for animations to complete
            
            val token = sessionManager.getAuthToken()
            Log.d("SplashActivity", "Current token: $token")
            
            val isValid = if (token != null) {
                val tokenValid = sessionManager.validateToken()
                Log.d("SplashActivity", "Token validation result: $tokenValid")
                
                if (tokenValid) {
                    val userProfile = sessionManager.getUserProfile()
                    Log.d("SplashActivity", "User profile: $userProfile")
                    
                    if (userProfile == null) {
                        // If token is valid but no user profile, clear session
                        sessionManager.clearSession()
                        false
                    } else {
                        true
                    }
                } else {
                    // If token is invalid, clear session
                    sessionManager.clearSession()
                    false
                }
            } else {
                false
            }
            
            Log.d("SplashActivity", "Final validation result: $isValid")

            startActivity(Intent(this@SplashActivity, MainActivity::class.java).apply {
                flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
                putExtra("token_valid", isValid)
            })
            finish()
        }
    }
} 