@echo off
echo Dropping existing database...
"C:\xampp\mysql\bin\mysql.exe" -u root -e "DROP DATABASE IF EXISTS upang_link;"

echo Creating database and importing schema...
"C:\xampp\mysql\bin\mysql.exe" -u root < schema.sql

echo Applying constraints...
"C:\xampp\mysql\bin\mysql.exe" -u root upang_link < fix_constraints.sql

echo Creating admin user...
"C:\xampp\mysql\bin\mysql.exe" -u root upang_link < create_user.sql

echo Setup complete!
pause 