<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../includes/db.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

// Debug logging
file_put_contents("debug.log", date('Y-m-d H:i:s') . " - Request: " . json_encode($data) . "\n", FILE_APPEND);


if (!isset($data->id) || !isset($data->user_id)) {
    http_response_code(400);
    echo json_encode(["message" => "Datos incompletos."]);
    exit();
}

// Ensure the user owns the project
$query = "DELETE FROM projects WHERE id = :id AND user_id = :user_id";
$stmt = $db->prepare($query);

$stmt->bindParam(":id", $data->id);
$stmt->bindParam(":user_id", $data->user_id);

if ($stmt->execute()) {
    if ($stmt->rowCount() > 0) {
         echo json_encode(["success" => true, "message" => "Proyecto eliminado."]);
    } else {
         echo json_encode(["success" => false, "message" => "No se encontró el proyecto o no tienes permiso."]);
    }
} else {
    http_response_code(503);
    echo json_encode(["success" => false, "message" => "No se pudo eliminar el proyecto."]);
}
?>
