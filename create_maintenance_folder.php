<?php
// Run this file once to create the maintenance uploads directory
// Access it via: http://localhost/TAHANAN/create_maintenance_folder.php

$uploadDir = __DIR__ . '/uploads/maintenance/';

if (!file_exists($uploadDir)) {
    if (mkdir($uploadDir, 0777, true)) {
        echo "✅ Success! Maintenance uploads directory created at: " . $uploadDir;
        echo "<br><br>You can now delete this file.";
    } else {
        echo "❌ Error: Could not create directory. Please create it manually:";
        echo "<br><code>" . $uploadDir . "</code>";
        echo "<br><br>Make sure the parent directory has write permissions.";
    }
} else {
    echo "✅ Directory already exists: " . $uploadDir;
    echo "<br><br>You can delete this file.";
}

// Also create a .htaccess to allow image access
$htaccess = $uploadDir . '.htaccess';
if (!file_exists($htaccess)) {
    $htaccessContent = "# Allow access to images\n";
    $htaccessContent .= "Options -Indexes\n";
    $htaccessContent .= "<FilesMatch \"\\.(jpg|jpeg|png|gif)$\">\n";
    $htaccessContent .= "    Order Allow,Deny\n";
    $htaccessContent .= "    Allow from all\n";
    $htaccessContent .= "</FilesMatch>";
    
    file_put_contents($htaccess, $htaccessContent);
    echo "<br>✅ .htaccess file created for security";
}
?>