# UPANG LINK API Documentation

UPANG LINK is an application that simplifies the process of requesting documents and services within UPANG. This repository contains the API implementation.

## Table of Contents
1. [Features](#features)
2. [Requirements](#requirements)
3. [Installation](#installation)
4. [Configuration](#configuration)
5. [API Endpoints](#api-endpoints)
6. [Authentication](#authentication)
7. [File Handling](#file-handling)
8. [Error Handling](#error-handling)

## Features

- User Authentication (Student)
- Request Management System
- Secure File Upload System
- Request Status Tracking
- Error Handling
- CORS Support

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- PHP Extensions:
  - PDO
  - FileInfo
  - OpenSSL

## Installation

1. Clone the repository:
```bash
git clone https://github.com/your-username/upang-link-api.git
cd upang-link-api
```

2. Create required directories:
```bash
mkdir uploads
chmod 755 uploads
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

## API Endpoints

### Authentication

```http
# Login
POST /api/auth/student/login
Content-Type: application/json

{
    "email": "student@example.com",
    "password": "password"
}

# Register
POST /api/auth/student/register
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

# Get Profile
GET /api/auth/student/profile
Authorization: Bearer {token}

# Update Profile
PUT /api/auth/student/profile
Authorization: Bearer {token}
Content-Type: application/json

{
    "first_name": "Updated",
    "last_name": "Name",
    "course": "BSIT",
    "year_level": 2,
    "block": "B"
}

# Change Password
POST /api/auth/student/change-password
Authorization: Bearer {token}
Content-Type: application/json

{
    "current_password": "old_password",
    "new_password": "new_password",
    "confirm_password": "new_password"
}

# Logout
POST /api/auth/student/logout
Authorization: Bearer {token}
```

### Request Management

```http
# Get All Requests
GET /api/requests
Authorization: Bearer {token}

# Get Request Details
GET /api/requests/{id}
Authorization: Bearer {token}

# Create Request
POST /api/requests
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
    "type_id": 1,
    "purpose": "For employment purposes",
    "files[]": (file1, file2, ...)
}

# Get Request Types
GET /api/requests/types
Authorization: Bearer {token}

# Get Requirements for Request Type
GET /api/requests/requirements/{typeId}
Authorization: Bearer {token}

# Upload Requirement
POST /api/requests/{requestId}/requirements/{requirementId}
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
    "file": (file)
}

# Delete Requirement
DELETE /api/requests/{requestId}/requirements/{requirementId}
Authorization: Bearer {token}

# Cancel Request
POST /api/requests/{id}/cancel
Authorization: Bearer {token}

# Get Request Statistics
GET /api/requests/statistics
Authorization: Bearer {token}
```

## File Handling

The API includes a secure file handling system with the following features:
- File type validation (PDF, JPEG, PNG, DOC, DOCX)
- File size restrictions (default 5MB)
- Unique filename generation
- Organized directory structure

```php
// Supported file types
$allowedMimeTypes = [
    'application/pdf',
    'image/jpeg',
    'image/png',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];

// Maximum file size (5MB)
$maxFileSize = 5 * 1024 * 1024;
```

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
    "data": null
}
```

Common HTTP status codes:
- 200: Success
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 422: Validation Error
- 500: Server Error

## Security Best Practices

1. Input validation for all requests
2. Prepared statements for database queries
3. File type and size validation
4. Token-based authentication
5. Password hashing
6. CORS configuration for development
7. Error handling and logging

## Development

For local development:
```php
// Set development environment
define('ENVIRONMENT', 'development');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configure CORS for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

## Support

For support, please contact:
- Email: support@upang-link.com
- Issue Tracker: https://github.com/your-username/upang-link-api/issues 