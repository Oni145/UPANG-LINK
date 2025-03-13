package com.phinma.upang.data.model

import android.os.Parcelable
import kotlinx.parcelize.Parcelize

@Parcelize
data class RequirementField(
    val name: String,
    val label: String,
    val type: String,
    val required: Boolean,
    val description: String? = null,
    val allowed_types: String? = null,
    val options: List<String>? = null
) : Parcelable 