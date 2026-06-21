<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/security.php';

$username = 'cafe_staff';
$plainPassword = 'staff123';
$role = 'staff';

try {
    $db = new DB();
    $conn = $db->connect();
    $hashed = bcryptHash($plainPassword);

    $sql = "INSERT INTO staff (username, bcrypt_password, role) VALUES (:username, :password, :role)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':username' => $username,
        ':password' => $hashed,
        ':role' => $role
    ]);

    echo "✅ Staff account created successfully.\n";
    echo "Username: $username\n";
    echo "Password: $plainPassword\n";
    echo "Hash stored: $hashed\n";
} catch (PDOException $e) {
    if ($e->errorInfo[1] == 1062) {
        echo "⚠️ Username already exists.\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}
