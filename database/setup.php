<?php
$host = '127.0.0.1';
$user = 'root';
$pass = ''; // Default XAMPP password is empty

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents(__DIR__ . '/schema.sql');
    
    // Execute schema commands
    $pdo->exec($sql);
    
    echo "Database created successfully!";
} catch (PDOException $e) {
    die("DB ERROR: " . $e->getMessage());
}
?>
