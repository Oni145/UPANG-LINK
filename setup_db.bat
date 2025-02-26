@echo off
echo Dropping existing database...
"C:\xampp\mysql\bin\mysql.exe" -u root -e "DROP DATABASE IF EXISTS upang_link;"

echo Creating new database and importing schema...
"C:\xampp\mysql\bin\mysql.exe" -u root < "C:\xampp\htdocs\UPANG-LINK\UPANG LINK API\database\schema.sql"

echo Setup complete!
pause 