package com.phinma.upang.data.model

import android.os.Parcelable
import com.google.gson.annotations.SerializedName
import kotlinx.parcelize.Parcelize

@Parcelize
data class RequirementField(
    @SerializedName("name") val name: String,
    @SerializedName("label") val label: String,
    @SerializedName("type") val type: String,
    @SerializedName("required") val required: Boolean,
    @SerializedName("description") val description: String? = null,
    @SerializedName("allowed_types") val allowed_types: String? = null,
    @SerializedName("options") val options: List<String>? = null
) : Parcelable {
    fun isDropdown(): Boolean {
        return type.equals("select", ignoreCase = true) || 
               type.equals("dropdown", ignoreCase = true)
    }
    
    fun isFileType(): Boolean {
        return type.equals("file", ignoreCase = true)
    }
    
    fun isTextType(): Boolean {
        return !isDropdown() && !isFileType()
    }
} 