package com.phinma.upang.ui.base

import android.os.Bundle
import android.view.View
import android.view.ViewGroup
import android.widget.Space
import androidx.annotation.LayoutRes
import androidx.constraintlayout.widget.ConstraintLayout
import androidx.coordinatorlayout.widget.CoordinatorLayout
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import androidx.fragment.app.Fragment

abstract class BaseFragment(@LayoutRes contentLayoutId: Int) : Fragment(contentLayoutId) {

    protected open val needsStatusBarSpace: Boolean = true
    protected open val hasCustomInsetHandling: Boolean = false

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        if (needsStatusBarSpace && !hasCustomInsetHandling) {
            setupStatusBarSpace(view)
        }
    }

    private fun setupStatusBarSpace(view: View) {
        // Skip if the root view is CoordinatorLayout (it handles its own insets)
        if (view is CoordinatorLayout) return
        
        // Find the first ConstraintLayout in the view hierarchy
        val container = findFirstConstraintLayout(view)
        
        if (container != null) {
            // Create and add status bar space
            val space = Space(requireContext()).apply {
                id = View.generateViewId()
                layoutParams = ConstraintLayout.LayoutParams(
                    ConstraintLayout.LayoutParams.MATCH_PARENT,
                    0
                ).apply {
                    topToTop = ConstraintLayout.LayoutParams.PARENT_ID
                }
            }
            container.addView(space, 0)

            // Apply window insets
            ViewCompat.setOnApplyWindowInsetsListener(view) { _, windowInsets ->
                val statusBars = windowInsets.getInsets(WindowInsetsCompat.Type.statusBars())
                space.layoutParams.height = statusBars.top
                space.requestLayout()
                windowInsets
            }
        }
    }

    private fun findFirstConstraintLayout(view: View): ConstraintLayout? {
        if (view is ConstraintLayout) return view
        if (view !is ViewGroup) return null

        for (i in 0 until view.childCount) {
            val child = view.getChildAt(i)
            if (child is ConstraintLayout) return child
            findFirstConstraintLayout(child)?.let { return it }
        }
        return null
    }
} 