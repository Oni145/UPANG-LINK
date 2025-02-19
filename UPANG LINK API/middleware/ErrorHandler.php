<?php

class ErrorHandler {
    /**
     * Handle all errors in the application
     * 
     * @param Throwable $error The caught error or exception
     * @return void
     */
    public static function handleError($error) {
        $statusCode = 500;
        
        // Determine appropriate status code
        if ($error instanceof InvalidArgumentException) {
            $statusCode = 400; // Bad Request
        } elseif ($error instanceof UnauthorizedException) {
            $statusCode = 401; // Unauthorized
        } elseif ($error instanceof ForbiddenException) {
            $statusCode = 403; // Forbidden
        } elseif ($error instanceof NotFoundException) {
            $statusCode = 404; // Not Found
        } elseif ($error instanceof MethodNotAllowedException) {
            $statusCode = 405; // Method Not Allowed
        } elseif ($error instanceof ValidationException) {
            $statusCode = 422; // Unprocessable Entity
        }

        // Get the error code from the exception if it exists
        if (method_exists($error, 'getCode') && $error->getCode() !== 0) {
            $statusCode = $error->getCode();
        }

        // Set response headers
        header('Content-Type: application/json');
        http_response_code($statusCode);

        // Prepare error response
        $response = [
            'status' => 'error',
            'message' => $error->getMessage()
        ];

        // Add validation errors if available
        if ($error instanceof ValidationException && !empty($error->getErrors())) {
            $response['errors'] = $error->getErrors();
        }

        // Add debug information in development
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            $response['debug'] = [
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'trace' => $error->getTraceAsString()
            ];
        }

        echo json_encode($response);
    }
}

// Custom exception classes
class UnauthorizedException extends Exception {}
class ForbiddenException extends Exception {}
class NotFoundException extends Exception {}
class MethodNotAllowedException extends Exception {}
class ValidationException extends Exception {
    private $errors;

    public function __construct($message, $errors = []) {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getErrors() {
        return $this->errors;
    }
} 