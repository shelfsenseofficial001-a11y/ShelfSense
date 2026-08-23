<?php
// Update all user passwords to "Password123!"
try {
    $pdo = new PDO("mysql:host=localhost;dbname=shelfsense;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $password = 'Password123!';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email IN ('maria.santos@shelfsense.com', 'juan.delacruz@shelfsense.com', 'ana.reyes@shelfsense.com', 'pedro.cruz@shelfsense.com', 'cashier@shelfsense.com')");
    $stmt->execute([$hash]);
    
    echo "✅ Updated " . $stmt->rowCount() . " users!\n";
    echo "New hash: " . $hash . "\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}