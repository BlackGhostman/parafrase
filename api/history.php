<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

include_once '../includes/db.php';

$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['user_id'])) {
    echo json_encode(["message" => "User ID required."]);
    exit();
}

$user_id = $_GET['user_id'];

$query = "SELECT * FROM projects WHERE user_id = :user_id ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();

$projects = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $projects[] = $row;
}

echo json_encode($projects);
?>
