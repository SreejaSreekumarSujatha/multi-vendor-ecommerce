<?php
$uploadDir = 'uploads/products/';

echo "<h1>Directory Permissions Test</h1>";

if (is_dir($uploadDir)) {
    echo "<p>✅ Directory exists: $uploadDir</p>";
} else {
    echo "<p>❌ Directory doesn't exist: $uploadDir</p>";
    if (mkdir($uploadDir, 0755, true)) {
        echo "<p>✅ Created directory successfully</p>";
    } else {
        echo "<p>❌ Failed to create directory</p>";
    }
}

if (is_writable($uploadDir)) {
    echo "<p>✅ Directory is writable</p>";
} else {
    echo "<p>❌ Directory is not writable</p>";
}

// Test file creation
$testFile = $uploadDir . 'test.txt';
if (file_put_contents($testFile, 'test')) {
    echo "<p>✅ Can create files in directory</p>";
    unlink($testFile); // Delete test file
} else {
    echo "<p>❌ Cannot create files in directory</p>";
}
?>