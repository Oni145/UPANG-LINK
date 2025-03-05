@echo off
echo Setting up UPANG LINK database...

echo Step 1: Dropping existing database...
"C:\xampp\mysql\bin\mysql.exe" -u root -e "DROP DATABASE IF EXISTS upang_link;"

echo Step 2: Creating new database and importing schema...
"C:\xampp\mysql\bin\mysql.exe" -u root < "C:\xampp\htdocs\UPANG-LINK\UPANG LINK API\database\schema.sql"

echo Step 3: Fixing constraints...
"C:\xampp\mysql\bin\mysql.exe" -u root < "C:\xampp\htdocs\UPANG-LINK\UPANG LINK API\database\fix_constraints.sql"

echo Step 4: Creating test user account...
"C:\xampp\mysql\bin\mysql.exe" -u root < "C:\xampp\htdocs\UPANG-LINK\UPANG LINK API\database\create_user.sql"

echo Database setup complete!
echo Test account created:
echo Email: jerickogarcia0@gmail.com
echo Password: password
pause 