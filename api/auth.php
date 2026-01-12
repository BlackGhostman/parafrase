<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../includes/db.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->action)) {
    echo json_encode(["message" => "No action specified."]);
    exit();
}

if ($data->action == 'register') {
    if (!empty($data->username) && !empty($data->email) && !empty($data->password)) {
        $check_query = "SELECT id FROM users WHERE email = :email OR username = :username LIMIT 1";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':email', $data->email);
        $check_stmt->bindParam(':username', $data->username);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            echo json_encode(["message" => "User already exists."]);
        } else {
            $query = "INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password)";
            $stmt = $db->prepare($query);
            $hash = password_hash($data->password, PASSWORD_BCRYPT);
            
            $stmt->bindParam(':username', $data->username);
            $stmt->bindParam(':email', $data->email);
            $stmt->bindParam(':password', $hash);
            
            if ($stmt->execute()) {
                echo json_encode(["message" => "User registered successfully.", "success" => true]);
            } else {
                echo json_encode(["message" => "Unable to register user."]);
            }
        }
    } else {
        echo json_encode(["message" => "Incomplete data."]);
    }
} elseif ($data->action == 'login') {
    if (!empty($data->email) && !empty($data->password)) {
        $query = "SELECT id, username, password_hash FROM users WHERE email = :email LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $data->email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($data->password, $row['password_hash'])) {
                // In a real app, generate JWT here. For simplicity, we return user info.
                echo json_encode([
                    "message" => "Login successful.",
                    "success" => true,
                    "user" => [
                        "id" => $row['id'],
                        "username" => $row['username'],
                        "email" => $data->email
                    ]
                ]);
            } else {
                echo json_encode(["message" => "Invalid password.", "success" => false]);
            }
        } else {
            echo json_encode(["message" => "User not found.", "success" => false]);
        }
    } else {
        echo json_encode(["message" => "Incomplete data."]);
    }
}
?>
