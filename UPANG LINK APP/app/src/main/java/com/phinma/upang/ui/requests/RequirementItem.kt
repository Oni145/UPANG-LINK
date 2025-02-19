package com.phinma.upang.ui.requests

import com.phinma.upang.data.model.Requirement
import com.phinma.upang.data.model.RequirementSubmission
import com.phinma.upang.data.model.RequirementStatus

data class RequirementItem(
    val id: String,
    val name: String,
    val description: String,
    val isRequired: Boolean,
    val allowedFileTypes: List<String>,
    val maxFileSize: Long,
    val fileUrl: String? = null,
    val status: RequirementStatus? = null,
    val remarks: String? = null,
    val submittedAt: java.util.Date? = null,
    val verifiedAt: java.util.Date? = null
) {
    companion object {
        fun fromRequirement(requirement: Requirement) = RequirementItem(
            id = requirement.id,
            name = requirement.name,
            description = requirement.description,
            isRequired = requirement.isRequired,
            allowedFileTypes = requirement.allowedFileTypes,
            maxFileSize = requirement.maxFileSize
        )

        fun fromSubmission(submission: RequirementSubmission) = RequirementItem(
            id = submission.requirementId,
            name = submission.requirement.name,
            description = submission.requirement.description,
            isRequired = submission.requirement.isRequired,
            allowedFileTypes = submission.requirement.allowedFileTypes,
            maxFileSize = submission.requirement.maxFileSize,
            fileUrl = submission.fileUrl,
            status = submission.status,
            remarks = submission.remarks,
            submittedAt = submission.submittedAt,
            verifiedAt = submission.verifiedAt
        )
    }
} 