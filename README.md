# UPANG-LINK System Integration Guide

## Project Structure

The UPANG-LINK system consists of three main components:

1. **Mobile API** - Located in the `UPANG LINK API` folder
   - Handles requests from the mobile application
   - Connects to the `upang_link` database

2. **Web API and Web Dashboard** - Located in the `UPANG LINK API WEB` folder
   - Handles requests from the web dashboard
   - Provides an admin interface for managing requests
   - Connects to the same `upang_link` database

3. **Mobile App** - Located in the `UPANG LINK APP` folder
   - Android application for students to submit and track requests

## Database Setup

All components use the same MySQL database named `upang_link`. To ensure proper integration, follow these steps:

1. Make sure MySQL is running on your XAMPP installation
2. Run the following SQL scripts in order:
   - `UPANG LINK API/database/schema.sql` - Creates the main database structure
   - `UPANG LINK API WEB/database/setup.sql` - Creates admin-related tables
   - `UPANG LINK API WEB/database/integration.sql` - Ensures compatibility between components

## Configuration

### Database Configuration

Both APIs are configured to connect to the same database with these credentials:
- Host: `localhost`
- Database: `upang_link`
- Username: `root`
- Password: `""`

If you need to change these settings, update the following files:
- `UPANG LINK API/config/Database.php`
- `UPANG LINK API WEB/config/Database.php`

### API URLs

The web dashboard is configured to use the following API URL:
- `http://localhost/UPANG-LINK/UPANG%20LINK%20API%20WEB/api`

The mobile app is configured to use:
- `http://localhost/UPANG-LINK/UPANG%20LINK%20API/api`

## Running the System

1. **Start XAMPP**:
   - Start Apache and MySQL services

2. **Access the Web Dashboard**:
   - Open `http://localhost/UPANG-LINK/UPANG%20LINK%20API%20WEB/WEB/login.html`
   - Login with admin credentials:
     - Username: `admin` or `jede.garcia.up@phinmaed.com`
     - Password: `password` (the hash in the database corresponds to this)

3. **Test the Mobile API**:
   - You can test the mobile API endpoints using Postman or any API testing tool
   - Base URL: `http://localhost/UPANG-LINK/UPANG%20LINK%20API/api`

4. **Run the Mobile App**:
   - Open the project in Android Studio
   - Make sure the API URL in the app is correctly set to point to your local server
   - Run the app on an emulator or physical device

## Integration Features

The integration between components includes:

1. **Shared Database**: All components access the same database
2. **Notification System**:
   - When a student submits a request via the mobile app, notifications are created for:
     - The student (visible in the mobile app)
     - All admins (visible in the web dashboard)
   - When an admin updates a request status, a notification is sent to the student

3. **Authentication**:
   - The mobile app uses the `user_sessions` table for authentication
   - The web dashboard uses the `admin_tokens` table for authentication

## Troubleshooting

If you encounter issues with the integration:

1. **Database Connection Issues**:
   - Check that MySQL is running
   - Verify the database credentials in both API configurations
   - Use the test-db.php script to verify database connectivity

2. **API Communication Issues**:
   - Check that the API URLs are correctly configured
   - Verify that Apache is running and the project is in the correct location
   - Check for CORS issues in the browser console

3. **Missing Tables**:
   - Run the integration.sql script to ensure all required tables exist

## Default Accounts

### Admin Users:
- Username: `admin`, Password: `password`
- Username: `jede.garcia.up@phinmaed.com`, Password: `password`

### Student User:
- Email: `jerickogarcia0@gmail.com`, Password: `password` 