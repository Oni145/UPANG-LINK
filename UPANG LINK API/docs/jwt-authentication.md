# JWT Authentication for UPANG LINK API

This document describes how JWT (JSON Web Token) authentication is implemented in the UPANG LINK API.

## Overview

JWT is a compact, URL-safe means of representing claims to be transferred between two parties. It is used for authentication and information exchange in a secure way.

The UPANG LINK API uses JWT to:

1. Authenticate users
2. Verify the integrity of claims
3. Pass user information securely between the client and server

## How JWT Works in UPANG LINK

1. **Login**: When a user logs in with valid credentials, the server generates a JWT token containing the user's ID and other data.
2. **Token Storage**: The client stores this token (usually in localStorage or sessionStorage).
3. **Authenticated Requests**: For subsequent requests to protected endpoints, the client includes the token in the Authorization header.
4. **Verification**: The server verifies the token signature and expiration before processing the request.

## Implementation

### JWT Helper Class

The JWT implementation is in `helpers/jwt_helper.php` and provides the following features:

- Token generation with configurable expiration time
- Token decoding and validation
- Signature verification using HMAC SHA-256
- Expiration checking

### Configuration

JWT settings are stored in `config/jwt_config.php`:

```php
return [
    // Secret key for signing tokens
    'secret_key' => 'UPANG-LINK-SECRET-KEY-2023-DEVELOPMENT-KEY',
    
    // Token validity duration in seconds
    'token_validity' => 86400, // 24 hours
    
    // Whether to check token expiration
    'check_expiration' => true,
    
    // Whether to validate the signature
    'validate_signature' => true
];
```

**IMPORTANT**: For production, you should change the secret key and store it in environment variables, not directly in the code.

### Authentication Middleware

A middleware for handling JWT authentication is available in `middleware/jwt_auth.php` with these functions:

- `authenticate()`: Checks if a valid token is present and returns the user ID
- `requireAuth()`: Middleware that requires authentication for an endpoint
- `getAuthUser()`: Returns the full decoded token data

## Using JWT Authentication

### In Frontend Code

1. **Login and get token**:

```javascript
async function login(email, password) {
  const response = await fetch('/api/auth/login.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ email, password })
  });
  
  const data = await response.json();
  
  if (data.status === 'success') {
    // Store token
    localStorage.setItem('token', data.data.token);
    return data.data.user;
  } else {
    throw new Error(data.message);
  }
}
```

2. **Make authenticated requests**:

```javascript
async function fetchProtectedData() {
  const token = localStorage.getItem('token');
  
  if (!token) {
    throw new Error('Not authenticated');
  }
  
  const response = await fetch('/api/protected-endpoint.php', {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  return await response.json();
}
```

### In API Endpoints

To protect an API endpoint, include the JWT middleware at the beginning of your PHP file:

```php
<?php
// Include the JWT authentication middleware
require_once __DIR__ . '/../middleware/jwt_auth.php';

// Require authentication (this will exit if authentication fails)
$user_id = requireAuth();

// Now you can use $user_id to identify the authenticated user
```

## Testing

You can test JWT authentication using the included tools:

1. `generate-token.php`: Generates tokens for testing purposes
2. `test-protected-endpoint.php`: Tests accessing a protected endpoint

## Security Considerations

- The secret key should be kept secure and never committed to version control
- Tokens should be short-lived (24 hours or less) to minimize risk from theft
- Use HTTPS for all API communication to prevent token interception
- Store tokens securely on the client side

## Troubleshooting

Common JWT issues:

1. **"Invalid token format"**: The token is not in the correct format (header.payload.signature)
2. **"Invalid token signature"**: The signature is not valid, indicating tampering
3. **"Token has expired"**: The token's expiration time has passed
4. **"Token does not contain user_id"**: The token payload doesn't include a user ID

Check the server logs for more detailed error messages to help diagnose issues. 