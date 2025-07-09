<?php
echo "<h1>Image Upload Test</h1>";

if ($_POST) {
    $uploadDir = 'uploads/products/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        echo "<p>✅ Created upload directory</p>";
    }
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
            echo "<p>✅ Image uploaded successfully!</p>";
            echo "<p>File saved as: $filepath</p>";
            echo "<img src='$filepath' style='max-width: 200px;'>";
        } else {
            echo "<p>❌ Upload failed</p>";
        }
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <p>
        <label>Test Image Upload:</label><br>
        <input type="file" name="image" accept="image/*" required>
    </p>
    <p>
        <input type="submit" value="Upload Test Image">
    </p>
</form>

<p><a href="?action=products">← Back to Products</a></p>