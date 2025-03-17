@echo off
echo Resetting UPANG LINK Database...

REM Set MySQL credentials and paths
set MYSQL_USER=root
set MYSQL_PASSWORD=
set DATABASE_NAME=upang_link
set MYSQL_PATH=C:\xampp\mysql\bin\mysql.exe

REM Drop existing database
echo Dropping existing database...
"%MYSQL_PATH%" -u %MYSQL_USER% -e "DROP DATABASE IF EXISTS %DATABASE_NAME%;"

REM Create fresh database
echo Creating fresh database...
"%MYSQL_PATH%" -u %MYSQL_USER% -e "CREATE DATABASE %DATABASE_NAME%;"

REM Apply schema directly
echo Applying schema...
"%MYSQL_PATH%" -u %MYSQL_USER% %DATABASE_NAME% < schema.sql

echo Database reset complete!
echo.
echo If you encountered any errors, please check the schema.sql file.
echo.
pause 