@echo off
"D:\xampp\mysql\bin\mysql.exe" -u root -e "DROP DATABASE IF EXISTS upang_link;"
"D:\xampp\mysql\bin\mysql.exe" -u root < "UPANG LINK API/database/schema.sql"
"D:\xampp\mysql\bin\mysql.exe" -u root upang_link -e "INSERT INTO users (student_number, email, password, first_name, last_name, role, course, year_level, block, admission_year, email_verified) VALUES ('0001-2024-00001', 'jerickogarcia0@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jericko', 'Garcia', 'student', 'BSIT', 3, 'A', '2024', 1);"
pause 