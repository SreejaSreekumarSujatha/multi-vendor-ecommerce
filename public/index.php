<?php
// Include our database class
require_once '../core/Database.php';

echo "<h1>Multi-Vendor E-commerce Platform</h1>";

// Test database connection
$db = Database::getInstance();

// Get all users from database
$users = $db->fetchAll("SELECT * FROM users");

echo "<h2>Users in Database:</h2>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Type</th></tr>";

foreach ($users as $user) {
    echo "<tr>";
    echo "<td>" . $user['id'] . "</td>";
    echo "<td>" . $user['name'] . "</td>";
    echo "<td>" . $user['email'] . "</td>";
    echo "<td>" . $user['user_type'] . "</td>";
    echo "</tr>";
}

echo "</table>";
?>