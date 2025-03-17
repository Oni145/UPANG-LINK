-- Add student details fields to users table
USE upang_link;

ALTER TABLE users
ADD COLUMN student_number VARCHAR(50) AFTER role,
ADD COLUMN birthdate DATE AFTER student_number,
ADD COLUMN emergency_contact VARCHAR(100) AFTER birthdate,
ADD COLUMN course VARCHAR(100) AFTER emergency_contact,
ADD COLUMN current_year VARCHAR(20) AFTER course;

-- Update the init_all.bat to include this migration
-- Add the following line to init_all.bat:
-- mysql -u root < add_student_details.sql 