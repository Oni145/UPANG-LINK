# UPANG LINK API WEB

## Overview
UPANG LINK API WEB is a web application that provides an interface for managing student requests and documents.

## Setup Instructions

### Prerequisites
- XAMPP (or any PHP server with Apache and MySQL)
- PHP 7.4 or higher
- Composer (for managing dependencies)

### Installation

1. Clone or download this repository to your XAMPP htdocs folder:
   ```
   C:\xampp\htdocs\UPANG-LINK\UPANG LINK API WEB
   ```

2. Install dependencies using Composer:
   ```
   cd "C:\xampp\htdocs\UPANG-LINK\UPANG LINK API WEB"
   composer install
   ```

3. Make sure Apache and MySQL are running in XAMPP.

### Running the Application

Simply navigate to the application in your web browser:
```
http://localhost/UPANG-LINK/UPANG%20LINK%20API%20WEB/
```

The application will automatically route requests to the appropriate API endpoints or web pages.

### Troubleshooting

If you encounter issues:

1. Make sure Apache and MySQL are running in XAMPP.
2. Check that PHP is properly configured in your XAMPP installation.
3. Verify that all required PHP extensions are enabled in your php.ini file:
   - openssl
   - mbstring

### Directory Structure

- `/api` - API endpoints
- `/WEB` - Web interface files (HTML, CSS, JS)
- `/controllers` - Controller classes
- `/models` - Data models
- `/config` - Configuration files
- `/middleware` - Middleware components
- `/uploads` - File upload directory
- `/database` - Database-related files

## License

This project is proprietary and confidential. 