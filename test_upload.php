<?php
require_once 'includes/config.php';

// Show PHP information about uploads
echo "<h1>Upload Test</h1>";
echo "<h2>PHP File Upload Configuration</h2>";
echo "<pre>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n";
echo "</pre>";

// Check if the upload directory exists and is writable
echo "<h2>Upload Directory Check</h2>";
echo "<pre>";
echo "UPLOAD_DIR: " . UPLOAD_DIR . "\n";
echo "Directory exists: " . (file_exists(UPLOAD_DIR) ? 'Yes' : 'No') . "\n";
echo "Directory is writable: " . (is_writable(UPLOAD_DIR) ? 'Yes' : 'No') . "\n";
echo "</pre>";

// Process test upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_upload'])) {
    echo "<h2>Upload Result</h2>";
    echo "<pre>";
    echo "File info: ";
    print_r($_FILES['test_upload']);
    
    $result = handleImageUpload($_FILES['test_upload']);
    echo "\nUpload result: " . ($result ? "Success ($result)" : "Failed") . "\n";
    
    if ($result) {
        echo "File URL: " . '/uploads/' . $result . "\n";
        echo "<img src='uploads/{$result}' style='max-width:300px; margin-top:20px;'>";
    }
    
    echo "</pre>";
}
?>

<h2>Test Form</h2>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="test_upload">
    <button type="submit">Upload Test</button>
</form>

<h2>Recent Error Logs</h2>
<pre>
<?php
if (file_exists('error.log')) {
    $errorLog = file('error.log');
    $lastEntries = array_slice($errorLog, -20);
    echo implode('', $lastEntries);
} else {
    echo "No error log found";
}
?>
</pre>
