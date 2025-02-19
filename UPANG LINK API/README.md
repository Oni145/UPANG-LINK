# UPANG LINK API Documentation

UPANG LINK is an application that simplifies the process of requesting essential items and services within UPANG. This repository contains the API implementation.

## Table of Contents
1. [Features](#features)
2. [Requirements](#requirements)
3. [Installation](#installation)
4. [Configuration](#configuration)
5. [API Endpoints](#api-endpoints)
6. [Authentication](#authentication)
7. [File Handling](#file-handling)
8. [Email System](#email-system)
9. [Rate Limiting](#rate-limiting)
10. [Error Handling](#error-handling)
11. [Security](#security)
12. [Examples](#examples)

## Features

- User Authentication (Admin & Student)
- Email Verification System
- Session Management with Token Expiration
- Request Management System
- Real-time Notifications
- Secure File Upload Management
- Rate Limiting
- Error Logging
- CORS Support

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

3. Set up the database:
```bash
mysql -u root -p < database/schema.sql
```

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

#### Admin Endpoints

```http
# Login
POST /auth/admin/login
Content-Type: application/json

{
    "email": "admin@example.com",
    "password": "password"
}

# Response
{
    "status": "success",
    "message": "Login successful",
    "data": {
        "user": {
            "user_id": 1,
            "email": "admin@example.com",
            "first_name": "Admin",
            "last_name": "User",
            "role": "admin"
        },
        "token": "your-auth-token",
        "expires_at": "2024-02-21 12:00:00"
    }
}

# Register
POST /auth/admin/register
Content-Type: application/json

{
    "email": "admin@example.com",
    "password": "password",
    "first_name": "Admin",
    "last_name": "User"
}
```

#### Student Endpoints

```http
# Register
POST /auth/student/register
Content-Type: application/json

{
    "student_number": "0001-2023-00001",
    "email": "student@example.com",
    "password": "password",
    "first_name": "Student",
    "last_name": "User",
    "course": "BSIT",
    "year_level": 1,
    "block": "A",
    "admission_year": "2023"
}

# Verify Email
POST /auth/student/verify-email
Content-Type: application/json

{
    "token": "verification_token"
}

# Login
POST /auth/student/login
Content-Type: application/json

{
    "email": "student@example.com",
    "password": "password"
}
```

### Request Management

```http
# Create Request
POST /requests
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
    "type_id": 1,
    "requirements": {
        "clearance_form": (file),
        "request_letter": (file),
        "purpose": "Transcript request for employment"
    }
}

# Get Request Status
GET /requests/{id}
Authorization: Bearer {token}

# Update Request Status (Admin only)
PUT /requests/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "status": "approved"
}
```

## File Handling

```php
// Example: Upload file
$fileHandler = new FileHandler();
$result = $fileHandler->uploadFile($_FILES['document'], 'requests');

if ($result['status'] === 'success') {
    $filePath = $result['path'];
    $fileUrl = $fileHandler->getFileUrl($filePath);
}
```

## Email System

```php
// Example: Send verification email
$emailHandler = new EmailHandler();
$emailHandler->sendVerificationEmail($userEmail, $verificationToken);
```

## Rate Limiting

The API implements rate limiting to prevent abuse:
- 1000 requests per hour per IP address
- Rate limit headers in response:
  - X-RateLimit-Remaining
  - Retry-After (when limit exceeded)

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
    "code": 400
}
```

## Security Best Practices

1. Always use HTTPS in production
2. Store sensitive data in environment variables
3. Implement proper input validation
4. Use prepared statements for database queries
5. Keep dependencies updated
6. Enable error logging
7. Use rate limiting
8. Implement proper CORS policies

## Examples

### Complete Request Flow

1. Student Registration:
```javascript
// Frontend (JavaScript)
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

2. Submit Document Request:
```javascript
async function submitRequest() {
    const formData = new FormData();
    formData.append('type_id', 1);
    formData.append('clearance_form', clearanceFile);
    formData.append('request_letter', requestLetterFile);
    formData.append('purpose', 'Transcript request for employment');

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

3. Admin Processing:
```javascript
async function processRequest(requestId, status) {
    const response = await fetch(`/requests/${requestId}`, {
        method: 'PUT',
        headers: {
            'Authorization': `Bearer ${adminToken}`,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            status: status,
            note: "Your request has been approved"
        })
    });
    
    const data = await response.json();
    // Handle response
}
```

## Development

1. Enable error reporting in development:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

2. Use the test database:
```sql
USE upang_link_test;
```

3. Test email functionality:
```php
// config/config.php
'email' => [
    'host' => 'localhost',
    'port' => 1025, // Mailhog
    ...
]
```

## Production Deployment

1. Update configuration:
- Set proper database credentials
- Configure production email settings
- Update CORS settings
- Set proper file permissions
- Enable HTTPS

2. Security checklist:
- Enable HTTPS
- Set secure headers
- Configure rate limiting
- Enable error logging
- Disable debug mode
- Set proper file permissions
- Configure backup system

## Support

For support, please contact:
- Email: support@upang-link.com
- Documentation: https://docs.upang-link.com
- Issue Tracker: https://github.com/your-username/upang-link-api/issues 