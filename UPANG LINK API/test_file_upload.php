<?php
// Set error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>File Upload Test</h1>";

// Check if files are being received
echo "<h2>FILES Array:</h2>";
echo "<pre>";
print_r($_FILES);
echo "</pre>";

// Check if POST data is being received
echo "<h2>POST Data:</h2>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Process file upload if a file was submitted
if (isset($_FILES['test_file']) && $_FILES['test_file']['error'] === UPLOAD_ERR_OK) {
    $tmp_name = $_FILES['test_file']['tmp_name'];
    $name = basename($_FILES['test_file']['name']);
    $upload_path = $upload_dir . $name;
    
    if (move_uploaded_file($tmp_name, $upload_path)) {
        echo "<h2>File Upload Success!</h2>";
        echo "File saved to: " . $upload_path;
    } else {
        echo "<h2>File Upload Failed!</h2>";
        echo "Error: Could not move uploaded file.";
    }
}

// HTML form for testing
echo <<<HTML
<h2>Test Upload Form</h2>
<form action="" method="post" enctype="multipart/form-data">
    <div>
        <label for="type_id">Type ID:</label>
        <input type="text" name="type_id" id="type_id" value="4">
    </div>
    <div>
        <label for="purpose">Purpose:</label>
        <input type="text" name="purpose" id="purpose" value="Test purpose">
    </div>
    <div>
        <label for="student_id">Student ID:</label>
        <input type="text" name="student_id" id="student_id" value="2020-12345">
    </div>
    <div>
        <label for="test_file">File:</label>
        <input type="file" name="test_file" id="test_file">
    </div>
    <div>
        <input type="submit" value="Upload">
    </div>
</form>
HTML;
?> 