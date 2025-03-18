# UPANG LINK API Documentation

UPANG LINK is an application that simplifies the process of requesting essential documents and services within UPANG. This repository contains the API implementation that powers both the web and mobile applications.

## Table of Contents
1. [Features](#features)
2. [Requirements](#requirements)
3. [Installation](#installation)
4. [Configuration](#configuration)
5. [API Endpoints](#api-endpoints)
6. [Authentication](#authentication)
8. [Request System](#request-system)
9. [File Handling](#file-handling)
10. [Email System](#email-system)
11. [Notifications](#notifications)
12. [Rate Limiting](#rate-limiting)
13. [Error Handling](#error-handling)
14. [Security](#security)
15. [Examples](#examples)
16. [Development](#development)
17. [Production Deployment](#production-deployment)
18. [Support](#support)
19. [Mobile App Integration](#mobile-app-integration)

## Features

- User Authentication (Admin & Student)
- Email Verification System
- Password Reset System
- Session Management with Token Expiration
- Request Management System
- Real-time Notifications
- Secure File Upload Management
- Rate Limiting
- Error Logging
- CORS Support
- Mobile App Integration

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- SSL certificate (for production)
- PHP Extensions:
  - PDO
  - GD (for image processing)
  - OpenSSL
  - FileInfo
- XAMPP (recommended for development)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/your-username/upang-link-api.git
cd upang-link-api
```

2. Create required directories:
```bash
mkdir uploads logs
chmod 755 uploads logs
```

3. Set up the database using the provided batch script:
```bash
# Double click setup_db.bat
# OR run from command line:
setup_db.bat
```

This script will:
- Drop existing database if it exists
- Create new database and import schema
- Fix database constraints
- Create test user account:
  - Email: jerickogarcia0@gmail.com
  - Password: password
  - Role: student
  - Course: BSIT
  - Sample requests included

4. Configure your web server:
```apache
# Apache (.htaccess)
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ /index.php [QSA,L]
```

## Configuration

1. Copy and edit the configuration file:
```php
// config/config.php
return [
    'app' => [
        'name' => 'UPANG LINK',
        'version' => '1.0.0',
        'frontend_url' => 'http://your-frontend-url',
        'api_url' => 'http://your-api-url',
    ],
    'database' => [
        'host' => 'localhost',
        'name' => 'upang_link',
        'username' => 'your_username',
        'password' => 'your_password'
    ],
    'email' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'your-email@gmail.com',
        'password' => 'your-app-password',
        'from_name' => 'UPANG LINK',
        'from_email' => 'noreply@upang-link.com'
    ],
    'security' => [
        'token_expiry' => 24, // hours
        'verification_expiry' => 24, // hours
        'password_min_length' => 8,
        'allowed_file_types' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
        'max_file_size' => 5 * 1024 * 1024 // 5MB
    ]
];
```

## API Endpoints

### Authentication

#### Student Authentication
```http
# Register Student
POST /auth/student/register
{
    "student_number": "0001-2023-00001",
    "email": "student@upang.edu.ph",
    "password": "password",
    "first_name": "John",
    "last_name": "Doe",
    "course": "BSIT",
    "year_level": 1
}

# Login
POST /auth/student/login
{
    "email": "student@upang.edu.ph",
    "password": "password"
}

Response:
{
    "status": "success",
    "data": {
        "user": {
            "user_id": 1,
            "email": "student@upang.edu.ph",
            "first_name": "John",
            "last_name": "Doe",
            "role": "student"
        },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "expires_at": "2024-03-22 10:00:00"
    }
}
```

#### Admin Authentication
```http
# Login
POST /auth/admin/login
{
    "email": "admin@upang.edu.ph",
    "password": "password"
}
```

### Request Management

#### Get Request Types
```http
GET /requests/types
Authorization: Bearer {token}

Response:
{
    "status": "success",
    "data": {
        "categories": [
            {
                "category_id": 1,
                "name": "Academic Documents",
                "types": [
                    {
                        "type_id": 1,
                        "name": "Transcript of Records",
                        "description": "Official academic transcript",
                        "requirements": {
                            "fields": [...],
                            "instructions": "..."
                        },
                        "processing_time": "5-7 working days"
                    }
                ]
            }
        ]
    }
}
```

#### Create Request
```http
POST /requests
Content-Type: multipart/form-data
Authorization: Bearer {token}

{
    "type_id": 1,
    "purpose": "Employment requirement",
    "requirements": {
        "clearance_form": (file),
        "request_letter": (file)
    }
}

Response:
{
    "status": "success",
    "data": {
        "request_id": 1,
        "tracking_number": "REQ-2024-0001",
        "token": "abc123...",
        "status": "PENDING",
        "submitted_at": "2024-03-21 10:00:00"
    }
}
```

#### Track Request
```http
GET /requests/{tracking_number}
Authorization: Bearer {token}

Response:
{
    "status": "success",
    "data": {
        "request": {
            "request_id": 1,
            "tracking_number": "REQ-2024-0001",
            "type": "Transcript of Records",
            "status": "IN_PROGRESS",
            "submitted_at": "2024-03-21 10:00:00",
            "updated_at": "2024-03-21 11:00:00"
        },
        "requirements": [
            {
                "name": "Clearance Form",
                "status": "verified",
                "file_url": "...",
                "remarks": null
            },
            {
                "name": "Request Letter",
                "status": "pending_verification",
                "file_url": "...",
                "remarks": null
            }
        ],
        "history": [
            {
                "status": "IN_PROGRESS",
                "changed_by": "Admin User",
                "reason": null,
                "timestamp": "2024-03-21 11:00:00"
            }
        ]
    }
}
```

#### Update Request Status (Admin)
```http
PUT /requests/{request_id}/status
Authorization: Bearer {admin_token}

{
    "status": "IN_PROGRESS",
    "remarks": "Processing your request. Please prepare payment."
}

# For rejections
{
    "status": "REJECTED",
    "rejection_reason": "Incomplete requirements. Missing valid ID.",
    "remarks": "Please submit a valid school ID."
}
```

### File Management

#### Upload File
```http
POST /files/upload
Content-Type: multipart/form-data
Authorization: Bearer {token}

{
    "file": (file),
    "type": "requirement",
    "request_id": 1,
    "requirement_name": "clearance_form"
}

Response:
{
    "status": "success",
    "data": {
        "file_id": 1,
        "file_name": "clearance_form.pdf",
        "file_url": "...",
        "uploaded_at": "2024-03-21 10:00:00"
    }
}
```

#### Get File
```http
GET /files/{file_token}
Authorization: Bearer {token}

# File will be streamed with proper content type
```

### Notifications

#### Get User Notifications
```http
GET /notifications
Authorization: Bearer {token}

Response:
{
    "status": "success",
    "data": {
        "notifications": [
            {
                "id": 1,
                "title": "Request Status Updated",
                "message": "Your request (REQ-2024-0001) is now being processed.",
                "type": "request_update",
                "read": false,
                "created_at": "2024-03-21 11:00:00"
            }
        ],
        "unread_count": 1
    }
}
```

#### Mark Notification as Read
```http
PUT /notifications/{id}/read
Authorization: Bearer {token}
```

### Error Responses

```http
# Validation Error
{
    "status": "error",
    "message": "Invalid input",
    "errors": {
        "email": ["Email is required"],
        "password": ["Password must be at least 8 characters"]
    },
    "code": 400
}

# Authentication Error
{
    "status": "error",
    "message": "Invalid credentials",
    "code": 401
}

# Authorization Error
{
    "status": "error",
    "message": "Insufficient permissions",
    "code": 403
}

# Resource Not Found
{
    "status": "error",
    "message": "Request not found",
    "code": 404
}

# Rate Limit Error
{
    "status": "error",
    "message": "Too many requests",
    "code": 429,
    "retry_after": 60
}
```

## Request System

The API includes a comprehensive request management system that handles different types of requests with their specific requirements.

### Request Types

Each request type includes:
- A unique identifier
- Name and description
- Category assignment
- Processing time estimate
- Specific requirements fields and documents

```http
# Get Request Types
GET /requests/types
Authorization: Bearer {token}

Response:
{
    "status": "success",
    "data": [
        {
            "type_id": 1,
            "category_id": 1,
            "name": "Transcript of Records",
            "description": "Official academic record showing all courses taken and grades received",
            "requirements": {
                "fields": [
                    {
                        "name": "purpose",
                        "label": "Purpose",
                        "type": "text",
                        "required": true,
                        "description": "Purpose of requesting the document"
                    }
                ],
                "required_docs": [
                    "Clearance form",
                    "Request letter"
                ],
                "instructions": "Please submit a formal request letter addressed to the Registrar."
            },
            "processing_time": "5-7 working days",
            "is_active": 1,
            "category_name": "Academic Documents"
        }
    ]
}
```

### Requirement Fields

Requirements can have various field types:
- `text`: Simple text input
- `textarea`: Multi-line text input
- `select`: Selection from options
- `date`: Date input
- `file`: File upload with validations (type, size)

Each field includes:
- `name`: Internal field identifier
- `label`: Display name for the field
- `type`: Field type (text, textarea, select, date, file)
- `required`: Whether the field is mandatory
- `allowed_types`: For file fields, the allowed file types
- `description`: Additional information about the field

### Creating Requests

```http
# Create Request
POST /requests
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
    "type_id": 1,
    "purpose": "Transcript request for employment",
    "requirements": {
        "clearance_form": (file),
        "request_letter": (file)
    }
}

# Response
{
    "status": "success",
    "message": "Request created successfully",
    "data": {
        "request": {
            "request_id": 15,
            "user_id": 2,
            "type_id": 1,
            "document_type": "Transcript of Records",
            "purpose": "Transcript request for employment",
            "status": "pending",
            "submitted_at": "2024-03-21 10:00:00",
            "updated_at": "2024-03-21 10:00:00",
            "processing_time": "5-7 working days"
        },
        "requirements": [
            {
                "id": "req-15-1",
                "request_type_id": 1,
                "name": "Clearance Form",
                "description": "Completed and signed clearance form",
                "isRequired": true,
                "allowedFileTypes": ["pdf", "jpg", "jpeg", "png"],
                "maxFileSize": 5242880,
                "created_at": "2024-03-21 10:00:00",
                "updated_at": "2024-03-21 10:00:00",
                "status": "submitted",
                "fileUrl": "https://api.upang-link.com/uploads/requests/clearance_form_123.pdf"
            },
            {
                "id": "req-15-2",
                "request_type_id": 1,
                "name": "Request Letter",
                "description": "Formal request letter addressed to the Registrar",
                "isRequired": true,
                "allowedFileTypes": ["pdf", "doc", "docx"],
                "maxFileSize": 5242880,
                "created_at": "2024-03-21 10:00:00",
                "updated_at": "2024-03-21 10:00:00",
                "status": "submitted",
                "fileUrl": "https://api.upang-link.com/uploads/requests/request_letter_123.pdf"
            }
        ]
    }
}
```

### Managing Requests

```http
# View All Requests (with filtering)
GET /requests?status=pending&type=1&search=transcript
Authorization: Bearer {token}

# View Single Request with Requirements
GET /requests/{id}
Authorization: Bearer {token}

# Update Request Status (Admin only)
PUT /requests/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "status": "in_progress",
    "remarks": "Processing your request. Please prepare payment."
}

# Request Statistics (Admin only)
GET /requests/statistics
Authorization: Bearer {token}

Response:
{
    "status": "success",
    "data": {
        "total": 150,
        "pending": 45,
        "completed": 80,
        "inProgress": 20,
        "cancelled": 5,
        "byType": {
            "Transcript of Records": 45,
            "Certificate of Enrollment": 35,
            "Good Moral Character": 70
        },
        "byMonth": {
            "January": 30,
            "February": 45,
            "March": 75
        }
    }
}
```

## File Handling

The API includes a robust file handling system with the following features:
- Secure file uploads with type validation
- File size restrictions
- Image compression
- Unique filename generation
- Organized directory structure
- URL generation for file access

```php
// Example: Upload file
$fileHandler = new FileHandler();
$result = $fileHandler->uploadFile($_FILES['document'], 'requests');

if ($result['status'] === 'success') {
    $filePath = $result['path'];
    $fileUrl = $fileHandler->getFileUrl($filePath);
}
```

### File Validation

The API performs the following validations on uploaded files:
- File type checking (MIME type and extension)
- File size limit enforcement
- Malware scanning
- Image dimension validation (for image files)

### File Access Controls

Files are stored outside the web root and accessed through a secure endpoint:
```http
GET /files/{file_token}
Authorization: Bearer {token}
```

## Notifications

The API includes a notification system for real-time updates:

```http
# Get User Notifications
GET /notifications
Authorization: Bearer {token}

Response:
{
    "status": "success",
    "data": [
        {
            "id": "notif-123",
            "user_id": 2,
            "title": "Request Status Updated",
            "message": "Your request for Transcript of Records has been approved",
            "type": "request_update",
            "read": false,
            "created_at": "2024-03-21 10:00:00",
            "data": {
                "request_id": 15,
                "status": "approved"
            }
        }
    ]
}

# Mark Notification as Read
PUT /notifications/{id}/read
Authorization: Bearer {token}

# Mark All Notifications as Read
PUT /notifications/read-all
Authorization: Bearer {token}
```

## Email System

The API includes a comprehensive email system that supports:
- SMTP configuration
- HTML email templates
- Email verification
- Password reset emails
- Request status notifications
- Fallback to PHP mail() function

```php
// Example: Send verification email
$emailHandler = new EmailHandler();
$emailHandler->sendVerificationEmail($userEmail, $verificationToken);

// Example: Send password reset email
$emailHandler->sendResetPasswordEmail($userEmail, $resetToken);

// Example: Send request status update
$emailHandler->sendRequestStatusEmail($userEmail, $requestId, $status);
```

## Rate Limiting

The API implements rate limiting to prevent abuse:
- 1000 requests per hour per IP address
- Rate limit headers in response:
  - X-RateLimit-Remaining
  - Retry-After (when limit exceeded)
- Endpoint-specific limits can be configured

## Error Handling

All API responses follow this format:

```json
// Success Response
{
    "status": "success",
    "message": "Operation successful",
    "data": {}
}

// Error Response
{
    "status": "error",
    "message": "Error description",
    "error_type": "validation_error",
    "code": 400
}
```

### Error Types

The API returns specific error types for better client handling:
- `validation_error`: Input validation failed
- `authentication_error`: Authentication issues
- `authorization_error`: Permission issues
- `resource_error`: Resource not found or access denied
- `server_error`: Internal server error
- `rate_limit_error`: Rate limit exceeded

## Security Best Practices

1. Always use HTTPS in production
2. Store sensitive data in environment variables
3. Implement proper input validation
4. Use prepared statements for database queries
5. Keep dependencies updated
6. Enable error logging
7. Use rate limiting
8. Implement proper CORS policies
9. Secure password reset process
   - One-time use tokens
   - 1-hour expiration
   - Secure token generation
   - Email verification

## Examples

### Complete Authentication Flow

1. Student Registration:
```javascript
async function registerStudent() {
    const response = await fetch('/auth/student/register', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            student_number: "0001-2023-00001",
            email: "student@example.com",
            password: "password",
            first_name: "Student",
            last_name: "User",
            course: "BSIT"
        })
    });
    
    const data = await response.json();
    // Handle verification email
}
```

2. Password Reset Flow:
```javascript
// Request password reset
async function forgotPassword() {
    const response = await fetch('/auth/forgot-password', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            email: "student@example.com"
        })
    });
    
    const data = await response.json();
    // User receives reset email
}

// Reset password
async function resetPassword(token, newPassword) {
    const response = await fetch('/auth/reset-password', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            token: token,
            password: newPassword
        })
    });
    
    const data = await response.json();
    // Handle password reset response
}
```

3. Submit Document Request with Files:
```javascript
async function submitRequest() {
    const formData = new FormData();
    formData.append('type_id', 1);
    formData.append('purpose', 'Transcript request for employment');
    
    // Add files with proper requirement IDs
    formData.append('clearance_form', clearanceFile);
    formData.append('request_letter', requestLetterFile);

    const response = await fetch('/requests', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${token}`
        },
        body: formData
    });
    
    const data = await response.json();
    // Handle response
}
```

4. Working with Requirements:
```javascript
// Get request type details with requirements
async function getRequestTypeDetails(typeId) {
    const response = await fetch(`/requests/types/${typeId}`, {
        headers: {
            'Authorization': `Bearer ${token}`
        }
    });
    
    const data = await response.json();
    // Parse requirements for the UI
    const requirementFields = data.data.requirements.fields;
    const requiredDocs = data.data.requirements.required_docs;
    
    // Build dynamic form based on requirements
    buildRequirementsForm(requirementFields, requiredDocs);
}

// Function to build form dynamically
function buildRequirementsForm(fields, requiredDocs) {
    const form = document.getElementById('requirementsForm');
    
    // Add fields
    fields.forEach(field => {
        let input;
        switch(field.type) {
            case 'text':
                input = document.createElement('input');
                input.type = 'text';
                break;
            case 'textarea':
                input = document.createElement('textarea');
                break;
            case 'file':
                input = document.createElement('input');
                input.type = 'file';
                input.setAttribute('accept', field.allowed_types);
                break;
            // Handle other types
        }
        
        input.name = field.name;
        input.required = field.required;
        form.appendChild(input);
    });
    
    // Add required document uploads
    requiredDocs.forEach(doc => {
        const docId = doc.toLowerCase().replace(' ', '_');
        const input = document.createElement('input');
        input.type = 'file';
        input.name = docId;
        input.required = true;
        form.appendChild(input);
    });
}
```

## Development

### Local Setup

1. Configure XAMPP:
```bash
# Enable required PHP extensions in php.ini
extension=pdo_mysql
extension=fileinfo
extension=openssl
extension=gd
```

2. Clone and Setup:
```bash
# Clone repository
git clone https://github.com/your-username/upang-link-api.git
cd upang-link-api

# Install dependencies
composer install

# Initialize database
./database/init_all.bat
```

3. Configure Development Environment:
```php
// config/config.php
return [
    'app' => [
        'env' => 'development',
        'debug' => true
    ],
    'database' => [
        'host' => 'localhost',
        'name' => 'upang_link',
        'username' => 'root',
        'password' => ''
    ]
];
```

### Testing

1. Database Testing:
```sql
-- Create test database
CREATE DATABASE upang_link_test;

-- Import schema
mysql -u root upang_link_test < database/schema.sql
```

2. Run Tests:
```bash
# Install PHPUnit
composer require --dev phpunit/phpunit

# Run test suite
./vendor/bin/phpunit tests/
```

## Production Deployment

### Server Setup

1. Requirements:
- Ubuntu 20.04 LTS
- Apache 2.4+
- PHP 7.4+
- MySQL 5.7+
- SSL certificate

2. Installation:
```bash
# Update system
sudo apt update && sudo apt upgrade

# Install requirements
sudo apt install apache2 mysql-server php7.4 php7.4-mysql php7.4-gd
```

3. Security Configuration:
```apache
# Apache security headers
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-XSS-Protection "1; mode=block"
Header always set X-Content-Type-Options "nosniff"
```

### Monitoring

1. Error Logging:
```php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/upang-link/error.log');
```

2. Database Monitoring:
```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;
```

### Backup Strategy

1. Database:
```bash
# Daily backup
mysqldump -u backup_user -p upang_link > /backup/upang_link_$(date +%Y%m%d).sql
```

2. Files:
```bash
# Backup uploads
rsync -av /var/www/upang-link/uploads/ /backup/files/
```

## Support

For technical support:
- Email: support@upang-link.com
- Phone: +63 XXX XXX XXXX
- Issues: https://github.com/your-username/upang-link-api/issues

## Mobile App Integration

The API is fully compatible with the UPANG LINK mobile app:
- Authentication endpoints support mobile clients
- File upload endpoints handle multipart form data from mobile
- Notifications support push notification tokens
- Responsive file serving optimized for mobile devices 

### Android Implementation

#### Creating Requests

1. Model Classes:
```kotlin
// Request Type Model
data class RequestType(
    val type_id: Int,
    val name: String,
    val description: String,
    val requirements: Requirements,
    val processing_time: String
)

data class Requirements(
    val fields: List<RequirementField>
)

data class RequirementField(
    val name: String,
    val label: String,
    val type: String,
    val required: Boolean,
    val allowed_types: String?,
    val description: String
)

// Request Creation Models
data class CreateRequestPayload(
    val type_id: Int,
    val purpose: String
)

data class RequirementItem(
    val id: String,
    val name: String,
    val description: String,
    val isRequired: Boolean,
    val allowedFileTypes: List<String>,
    val maxFileSize: Long = 5 * 1024 * 1024 // 5MB default
)
```

2. API Service Interface:
```kotlin
interface RequestService {
    @GET("requests/types")
    suspend fun getRequestTypes(): Response<ApiResponse<List<RequestType>>>
    
    @Multipart
    @POST("requests")
    suspend fun createRequest(
        @Part("request") request: CreateRequestPayload,
        @Part files: List<MultipartBody.Part>
    ): Response<ApiResponse<CreateRequestResponse>>
}
```

3. ViewModel Implementation:
```kotlin
class CreateRequestViewModel : ViewModel() {
    private val _requirements = MutableLiveData<List<RequirementItem>>()
    val requirements: LiveData<List<RequirementItem>> = _requirements

    fun loadRequirements(requestType: RequestType) {
        val items = requestType.requirements.fields.map { field ->
            RequirementItem(
                id = field.name,
                name = field.label,
                description = field.description,
                isRequired = field.required,
                allowedFileTypes = field.allowed_types?.split(",") ?: emptyList()
            )
        }
        _requirements.value = items
    }

    fun createRequest(
        typeId: Int,
        purpose: String,
        files: Map<String, Uri>
    ) = viewModelScope.launch {
        try {
            // Create multipart files
            val fileParts = files.map { (name, uri) ->
                val file = File(uri.path!!)
                val requestBody = file.asRequestBody("multipart/form-data".toMediaTypeOrNull())
                MultipartBody.Part.createFormData(name, file.name, requestBody)
            }

            // Create request payload
            val requestPayload = CreateRequestPayload(typeId, purpose)
            
            // Make API call
            val response = requestService.createRequest(requestPayload, fileParts)
            // Handle response...
        } catch (e: Exception) {
            // Handle error...
        }
    }
}
```

4. Fragment Implementation:
```kotlin
class CreateRequestFragment : Fragment() {
    private val viewModel: CreateRequestViewModel by viewModels()
    private lateinit var binding: FragmentCreateRequestBinding
    private lateinit var requirementsAdapter: RequirementsAdapter

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        
        setupRequestTypeSpinner()
        setupRequirementsList()
        observeViewModel()
    }

    private fun setupRequestTypeSpinner() {
        val requestTypeAdapter = ArrayAdapter(
            requireContext(),
            R.layout.item_request_type_dropdown,
            requestTypes.map { it.name }
        )
        binding.requestTypeDropdown.apply {
            setAdapter(requestTypeAdapter)
            setOnItemClickListener { _, _, position, _ ->
                val selectedType = requestTypes[position]
                viewModel.loadRequirements(selectedType)
            }
        }
    }

    private fun setupRequirementsList() {
        requirementsAdapter = RequirementsAdapter { requirementId ->
            // Launch file picker
            pickFile(requirementId)
        }
        binding.requirementsRecyclerView.apply {
            adapter = requirementsAdapter
            layoutManager = LinearLayoutManager(context)
        }
    }

    private fun observeViewModel() {
        viewModel.requirements.observe(viewLifecycleOwner) { requirements ->
            requirementsAdapter.submitList(requirements)
            binding.requirementsSection.isVisible = requirements.isNotEmpty()
        }
    }

    private fun pickFile(requirementId: String) {
        // Launch file picker with correct MIME types
        val requirement = requirementsAdapter.currentList.find { it.id == requirementId }
        val mimeTypes = requirement?.allowedFileTypes?.map { 
            when (it) {
                "pdf" -> "application/pdf"
                "doc", "docx" -> "application/msword"
                "jpg", "jpeg" -> "image/jpeg"
                "png" -> "image/png"
                else -> "*/*"
            }
        } ?: listOf("*/*")

        getContent.launch("*/*") { uri ->
            uri?.let { 
                // Validate file size
                val size = contentResolver.openFileDescriptor(uri, "r")?.statSize ?: 0
                if (size > requirement?.maxFileSize ?: 5_242_880) {
                    showError("File too large. Maximum size is 5MB")
                    return@let
                }
                // Update adapter with file
                requirementsAdapter.setFileForRequirement(requirementId, uri)
            }
        }
    }

    private fun submitRequest() {
        val selectedTypePosition = binding.requestTypeDropdown.text.toString()
        val selectedType = requestTypes.find { it.name == selectedTypePosition }
        val purpose = binding.purposeEditText.text.toString()

        if (selectedType == null) {
            showError("Please select a request type")
            return
        }

        if (purpose.isBlank()) {
            showError("Please enter a purpose")
            return
        }

        // Collect all files
        val files = requirementsAdapter.getRequirementFiles()
        
        // Validate required files
        val missingRequired = requirementsAdapter.currentList
            .filter { it.isRequired && !files.containsKey(it.id) }
        if (missingRequired.isNotEmpty()) {
            showError("Please provide all required documents")
            return
        }

        // Submit request
        viewModel.createRequest(
            typeId = selectedType.type_id,
            purpose = purpose,
            files = files
        )
    }
}
```

5. Requirements Adapter:
```kotlin
class RequirementsAdapter(
    private val onPickFile: (String) -> Unit
) : ListAdapter<RequirementItem, RequirementsAdapter.ViewHolder>(RequirementDiffCallback()) {

    private val requirementFiles = mutableMapOf<String, Uri>()

    fun setFileForRequirement(requirementId: String, uri: Uri) {
        requirementFiles[requirementId] = uri
        notifyItemChanged(currentList.indexOfFirst { it.id == requirementId })
    }

    fun getRequirementFiles(): Map<String, Uri> = requirementFiles.toMap()

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val binding = ItemRequirementBinding.inflate(
            LayoutInflater.from(parent.context), parent, false
        )
        return ViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val item = getItem(position)
        holder.bind(item)
    }

    inner class ViewHolder(
        private val binding: ItemRequirementBinding
    ) : RecyclerView.ViewHolder(binding.root) {
        
        fun bind(item: RequirementItem) {
            binding.apply {
                requirementName.text = item.name
                requirementDescription.text = item.description
                
                val hasFile = requirementFiles.containsKey(item.id)
                uploadButton.text = if (hasFile) "Change File" else "Upload File"
                uploadButton.setOnClickListener { onPickFile(item.id) }
                
                fileNameText.isVisible = hasFile
                fileNameText.text = requirementFiles[item.id]?.lastPathSegment
                
                requiredText.isVisible = item.isRequired
            }
        }
    }
}

class RequirementDiffCallback : DiffUtil.ItemCallback<RequirementItem>() {
    override fun areItemsTheSame(oldItem: RequirementItem, newItem: RequirementItem) = 
        oldItem.id == newItem.id
    
    override fun areContentsTheSame(oldItem: RequirementItem, newItem: RequirementItem) =
        oldItem == newItem
}
```

This implementation:
- Properly handles the API's request type and requirements structure
- Validates files against allowed types and size limits
- Manages required vs optional requirements
- Provides proper UI feedback
- Handles file picking and validation
- Implements proper error handling
- Uses multipart form data for file uploads

The code follows Android best practices:
- Uses ViewModel for business logic
- Implements proper lifecycle management
- Uses LiveData for reactive updates
- Follows Material Design guidelines
- Implements proper error handling
- Uses coroutines for async operations

## Database Schema

### Core Tables
- `users`: Stores user information and authentication details
- `user_sessions`: Manages active user sessions and tokens
- `categories`: Categorizes different types of requests
- `request_types`: Defines available request types and their requirements
- `requests`: Stores user document requests
- `request_notes`: Tracks admin comments and updates
- `required_documents`: Manages document submissions
- `notifications`: Handles user notifications

### Request Management Tables
- `requirement_templates`: Defines templates for request requirements
- `request_requirement_notes`: Stores admin notes for specific requirements
- `request_status_history`: Tracks changes in request status
- `request_tokens`: Manages request-specific access tokens

### Database Setup

1. Using the provided batch scripts:
```bash
# Initialize complete database (recommended)
init_all.bat

# Or individual steps:
setup_db.bat      # Create fresh database
create_db.bat     # Import schema only
```

2. Manual database setup:
```sql
-- Create database
CREATE DATABASE upang_link;
USE upang_link;

-- Import schema
source schema.sql

-- Apply constraints
source fix_constraints.sql

-- Create test user
source create_user.sql

-- Update schema
source update_schema.sql
```

## Request System Details

### Request Categories
1. Academic Documents
   - Transcripts
   - Certificates
   - Academic records
2. Student ID
   - New ID requests
   - ID replacements
3. Uniforms
   - PE uniforms
   - School uniforms
4. Books and Modules
   - Course materials
   - Learning resources

### Request Types and Requirements

Each request type includes:
```json
{
    "type_id": 1,
    "category_id": 1,
    "name": "Transcript of Records",
    "description": "Official academic transcript",
    "requirements": {
        "fields": [
            {
                "name": "clearance_form",
                "label": "Clearance Form",
                "type": "file",
                "required": true,
                "allowed_types": ["pdf", "jpg", "png"],
                "description": "Fully accomplished clearance form"
            },
            {
                "name": "request_letter",
                "label": "Request Letter",
                "type": "file",
                "required": true,
                "allowed_types": ["pdf", "doc", "docx"],
                "description": "Formal letter stating purpose"
            },
            {
                "name": "purpose",
                "label": "Purpose",
                "type": "text",
                "required": true,
                "description": "State the purpose of requesting TOR"
            }
        ],
        "instructions": "Please ensure all required documents are complete."
    },
    "processing_time": "5-7 working days"
}
```

### Request Status Flow
```
PENDING → IN_PROGRESS → COMPLETED
   ↓
REJECTED
```

Status changes are automatically tracked in `request_status_history` with:
- Previous status
- New status
- Changed by (admin)
- Reason (required for rejections)
- Timestamp

### File Requirements

1. File Types:
   - Documents: pdf, doc, docx
   - Images: jpg, jpeg, png
   - Maximum file size: 5MB

2. Validation:
   - MIME type checking
   - File size validation
   - File extension verification
   - Malware scanning

### Request Tokens

Secure request access using unique tokens:
```php
// Generate request token
$token = bin2hex(random_bytes(32));

// Store token
$sql = "INSERT INTO request_tokens (request_id, token, expires_at) 
        VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))";
```

Access requests using tokens:
```http
GET /requests/track/{token}
``` 