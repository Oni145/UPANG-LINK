@echo off
REM Set JAVA_HOME to a common location - update this path if needed
set JAVA_HOME=C:\Program Files\Java\jdk-17
echo JAVA_HOME set to %JAVA_HOME%

REM Run Gradle command
call gradlew.bat clean --refresh-dependencies

pause 