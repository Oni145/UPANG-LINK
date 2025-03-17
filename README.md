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

# UPANG-LINK API Troubleshooting

## Issues Found and Fixed

### 1. Missing Database Columns
The database schema defined in `UPANG LINK API/database/schema.sql` included student detail fields, but these columns were missing in the actual database:
- student_number
- birthdate
- emergency_contact
- course
- current_year

**Solution**: Created and executed a script (`update_users_table.php`) to add the missing columns to the users table.

### 2. API Error Handling
The API endpoints (`get_student_details.php` and `update_student_details.php`) were already properly handling NULL values and missing fields, but were showing warnings due to the missing database columns.

**Solution**: After adding the missing columns to the database, the warnings disappeared and the API now functions correctly.

## Testing Results

### Get Student Details API
- Endpoint: `/UPANG LINK API/api/get_student_details.php`
- Method: GET
- Authentication: Bearer Token
- Response: Returns user details including student-specific fields
- Status: Working correctly

### Update Student Details API
- Endpoint: `/UPANG LINK API/api/update_student_details.php`
- Method: POST
- Authentication: Bearer Token
- Request Body: JSON with student details (student_number, birthdate, emergency_contact, course, current_year)
- Response: Success message when details are updated
- Status: Working correctly

## Test Scripts
The following test scripts were created to verify the API functionality:
- `test_db.php`: Tests database connection and structure
- `test_api.php`: Tests the get_student_details.php endpoint
- `test_update_api.php`: Tests the update_student_details.php endpoint

## Recommendations
1. Always run the database schema script when setting up a new environment
2. Consider adding database migration scripts for future updates
3. Add validation for student detail fields in the update endpoint
4. Implement proper error logging instead of suppressing all errors 