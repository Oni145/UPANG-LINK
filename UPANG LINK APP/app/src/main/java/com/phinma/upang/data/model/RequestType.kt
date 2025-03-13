data class RequestType(
    @com.google.gson.annotations.SerializedName(value = "typeId", alternate = ["type_id"])
    val type_id: Int = 0,
    
    @com.google.gson.annotations.SerializedName("name")
    val name: String,
    
    @com.google.gson.annotations.SerializedName("description")
    val description: String,
    
    @com.google.gson.annotations.SerializedName("requirements")
    val requirements: Any?,
    
    @com.google.gson.annotations.SerializedName(value = "processingTime", alternate = ["processing_time"])
    val processing_time: String = "",
    
    @com.google.gson.annotations.SerializedName(value = "categoryName", alternate = ["category_name"])
    val category_name: String? = null,
    
    @com.google.gson.annotations.SerializedName(value = "categoryId", alternate = ["category_id"])
    val category_id: Int = 0
) {
    fun parseRequirements(): Requirements {
        return try {
            if (requirements == null) {
                android.util.Log.d("RequestType", "Requirements is null")
                return Requirements(emptyList())
            }
            
            // Check if requirements is already a Map (LinkedTreeMap)
            if (requirements is Map<*, *>) {
                android.util.Log.d("RequestType", "Requirements is already a Map, processing directly")
                val reqMap = requirements as Map<String, Any>
                
                // Get fields from the Map
                val fieldsValue = reqMap["fields"]
                val instructions = reqMap["instructions"] as? String ?: ""
                
                if (fieldsValue is List<*> && fieldsValue.isNotEmpty()) {
                    android.util.Log.d("RequestType", "Found fields array in Map with ${fieldsValue.size} items")
                    
                    val fields = mutableListOf<RequirementField>()
                    for (fieldObj in fieldsValue) {
                        if (fieldObj is Map<*, *>) {
                            val field = RequirementField(
                                name = (fieldObj["name"] as? String) ?: "",
                                label = (fieldObj["label"] as? String) ?: "",
                                type = (fieldObj["type"] as? String) ?: "text",
                                required = (fieldObj["required"] as? Boolean) ?: false,
                                allowed_types = fieldObj["allowed_types"] as? String,
                                description = (fieldObj["description"] as? String) ?: ""
                            )
                            fields.add(field)
                            android.util.Log.d("RequestType", "Added field from Map: ${field.name}, type: ${field.type}, required: ${field.required}")
                        }
                    }
                    
                    android.util.Log.d("RequestType", "Successfully parsed ${fields.size} fields from Map")
                    return Requirements(fields, instructions)
                }
            }
            
            // If we get here, try parsing as JSON string
            val gson = com.google.gson.Gson()
            val jsonString = if (requirements is String) requirements else gson.toJson(requirements)
            val jsonObject = gson.fromJson(jsonString, com.google.gson.JsonObject::class.java)
            
            if (jsonObject.has("fields") && jsonObject.get("fields").isJsonArray) {
                android.util.Log.d("RequestType", "Found fields array in JSON")
                
                val fieldsArray = jsonObject.getAsJsonArray("fields")
                val fields = mutableListOf<RequirementField>()
                
                for (i in 0 until fieldsArray.size()) {
                    val fieldObject = fieldsArray.get(i).asJsonObject
                    
                    val name = fieldObject.get("name")?.asString ?: ""
                    val label = fieldObject.get("label")?.asString ?: ""
                    val type = fieldObject.get("type")?.asString ?: "text"
                    val required = fieldObject.get("required")?.asBoolean ?: false
                    val allowedTypes = if (fieldObject.has("allowed_types")) fieldObject.get("allowed_types").asString else null
                    val description = fieldObject.get("description")?.asString ?: ""
                    
                    val field = RequirementField(
                        name = name,
                        label = label,
                        type = type,
                        required = required,
                        allowed_types = allowedTypes,
                        description = description
                    )
                    
                    fields.add(field)
                    android.util.Log.d("RequestType", "Added field: $name, type: $type, required: $required")
                }
                
                android.util.Log.d("RequestType", "Successfully parsed ${fields.size} fields")
                val instructions = if (jsonObject.has("instructions")) jsonObject.get("instructions").asString else ""
                return Requirements(fields, instructions)
            }
            
            // If no fields found, return empty requirements
            android.util.Log.d("RequestType", "No fields found in requirements")
            return Requirements(emptyList())
            
        } catch (e: Exception) {
            android.util.Log.e("RequestType", "Error parsing requirements: ${e.message}", e)
            return Requirements(emptyList())
        }
    }
}

data class Requirements(
    @com.google.gson.annotations.SerializedName("fields")
    val fields: List<RequirementField>? = emptyList(),
    
    @com.google.gson.annotations.SerializedName("instructions")
    val instructions: String? = null
)

data class RequirementField(
    @com.google.gson.annotations.SerializedName("name")
    val name: String,
    
    @com.google.gson.annotations.SerializedName("label")
    val label: String,
    
    @com.google.gson.annotations.SerializedName("type")
    val type: String,
    
    @com.google.gson.annotations.SerializedName("required")
    val required: Boolean,
    
    @com.google.gson.annotations.SerializedName("allowed_types")
    val allowed_types: String?,
    
    @com.google.gson.annotations.SerializedName("description")
    val description: String
) 