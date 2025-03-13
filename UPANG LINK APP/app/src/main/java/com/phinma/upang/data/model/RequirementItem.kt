package com.phinma.upang.data.model

data class RequirementItem(
    val id: String,
    val name: String,
    val description: String,
    val isRequired: Boolean,
    val allowedFileTypes: List<String>,
    val maxFileSize: Long = 5 * 1024 * 1024, // 5MB default
    val status: RequirementStatus? = null,
    val fileUrl: String? = null,
    val remarks: String? = null
) 