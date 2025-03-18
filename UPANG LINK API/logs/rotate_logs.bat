@echo off
echo Rotating log files...

REM Get current date in YYYY-MM-DD format for the backup filename
for /f "tokens=2-4 delims=/ " %%a in ('date /t') do (
    set mm=%%a
    set dd=%%b
    set yy=%%c
)
set datestr=%yy%-%mm%-%dd%

REM Check if error.log exists and is larger than 1MB
if exist error.log (
    for %%I in (error.log) do if %%~zI GTR 1048576 (
        echo Log file is larger than 1MB, rotating...
        copy error.log error_%datestr%.log
        echo. > error.log
        echo Log rotated to error_%datestr%.log
    ) else (
        echo Log file is smaller than 1MB, no rotation needed.
    )
) else (
    echo No error.log file found.
)

REM Delete log files older than 30 days
forfiles /p . /m error_*.log /d -30 /c "cmd /c del @path" 2>nul
if %ERRORLEVEL% EQU 0 (
    echo Deleted log files older than 30 days.
) else (
    echo No old log files to delete.
)

echo Log rotation complete! 