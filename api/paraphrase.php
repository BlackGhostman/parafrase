<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../includes/db.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->text)) {
    echo json_encode(["message" => "No text provided."]);
    exit();
}

// Simple Mock Paraphrase Logic - In a real scenario, call OpenAI/Gemini API here
function mockParaphrase($text, $mode) {
    // This is just a placeholder to show functionality. 
    // Real implementation requires an NLP library or External API.
    $replacements = [
        "bueno" => "excelente",
        "malo" => "deficiente",
        "feliz" => "contento",
        "triste" => "melancólico",
        "usar" => "utilizar",
        "hacer" => "realizar",
        "trabajo" => "labor",
        "ayuda" => "asistencia",
        "empezar" => "comenzar",
        "fin" => "conclusión",
        "hola" => "saludos",
        "mundo" => "planeta",
        "rapido" => "veloz",
        "lento" => "pausado"
    ];
    
    $words = explode(" ", $text);
    $new_words = [];
    foreach ($words as $word) {
        $clean_word = strtolower(preg_replace("/[^a-zA-Z]/", "", $word));
        if (array_key_exists($clean_word, $replacements)) {
            $new_words[] = $replacements[$clean_word];
        } else {
            $new_words[] = $word;
        }
    }
    
    return implode(" ", $new_words);
}

$mode = isset($data->mode) ? $data->mode : 'standard';
$paraphrased = mockParaphrase($data->text, $mode);

// Save history if user_id is present
if (isset($data->user_id)) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO projects (user_id, original_text, paraphrased_text, title) VALUES (:user_id, :original, :paraphrased, 'Auto-Save')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $data->user_id);
    $stmt->bindParam(":original", $data->text);
    $stmt->bindParam(":paraphrased", $paraphrased);
    $stmt->execute();
}

echo json_encode([
    "original" => $data->text,
    "paraphrased" => $paraphrased,
    "mode" => $mode
]);
?>
