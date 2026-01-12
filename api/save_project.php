<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../includes/db.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (
    !isset($data->user_id) ||
    !isset($data->title)
) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Datos incompletos"]);
    exit();
}


// Check if project exists
$checkQuery = "SELECT id FROM projects WHERE user_id = :user_id AND title = :title";
$checkStmt = $db->prepare($checkQuery);
$title = htmlspecialchars(strip_tags($data->title));
$original = htmlspecialchars(strip_tags($data->original_text));
$paraphrased = htmlspecialchars(strip_tags($data->paraphrased_text));

$checkStmt->bindParam(":user_id", $data->user_id);
$checkStmt->bindParam(":title", $title);
$checkStmt->execute();

if ($checkStmt->rowCount() > 0) {
    // Update existing
    $row = $checkStmt->fetch(PDO::FETCH_ASSOC);
    $projectId = $row['id'];
    
    $query = "UPDATE projects SET original_text = :original_text, paraphrased_text = :paraphrased_text, updated_at = NOW() WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $projectId);
    $stmt->bindParam(":original_text", $original);
    $stmt->bindParam(":paraphrased_text", $paraphrased);
    
    $message = "Proyecto actualizado.";
} else {
    // Insert new
    $query = "INSERT INTO projects (user_id, title, original_text, paraphrased_text, created_at) VALUES (:user_id, :title, :original_text, :paraphrased_text, NOW())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $data->user_id);
    $stmt->bindParam(":title", $title);
    $stmt->bindParam(":original_text", $original);
    $stmt->bindParam(":paraphrased_text", $paraphrased);
    
    $message = "Proyecto guardado.";
}

if ($stmt->execute()) {
    http_response_code(200); // 200 OK for both create and update is fine, or 201 for create
    echo json_encode(["success" => true, "message" => $message]);
} else {
    http_response_code(503);
    echo json_encode(["success" => false, "message" => "No se pudo guardar el proyecto."]);
}
?>
