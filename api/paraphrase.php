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
    // Enhanced replacements dictionary
    $replacements = [
        // Verbs
        "bueno" => "excelente", "malo" => "deficiente", "feliz" => "dichoso",
        "triste" => "abatido", "usar" => "emplear", "hacer" => "ejecutar",
        "trabajo" => "labor", "ayuda" => "colaboración", "empezar" => "iniciar",
        "fin" => "desenlace", "hola" => "cordiales saludos", "mundo" => "globo terráqueo",
        "rapido" => "vertiginoso", "lento" => "pausado", "decir" => "expresar",
        "ver" => "observar", "tener" => "poseer", "caminar" => "transitar",
        "grande" => "colosal", "pequeño" => "diminuto", "importante" => "crucial",
        "problema" => "desafío", "solucion" => "resolución", "idea" => "concepto",
        
        // Connectors and common words
        "pero" => "sin embargo,", "y" => "asimismo,", "o" => "o alternativamente",
        "porque" => "dado que", "tambien" => "adicionalmente", "asi" => "de esta manera",
        "entonces" => "por consiguiente", "despues" => "posteriormente"
    ];
    
    $sentences = preg_split('/(?<=[.?!])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    $new_sentences = [];

    foreach ($sentences as $sentence) {
        $words = explode(" ", $sentence);
        $new_words = [];
        
        foreach ($words as $word) {
            // Keep punctuation for checking, but clean for replacement
            $clean_word = strtolower(preg_replace("/[^a-zA-ZáéíóúñÁÉÍÓÚÑ]/u", "", $word));
            
            // Check replacement
            if (array_key_exists($clean_word, $replacements)) {
                $replacement = $replacements[$clean_word];
                
                // Match capitalization
                if (ctype_upper(substr($word, 0, 1))) {
                    $replacement = ucfirst($replacement);
                }
                
                // Restore punctuation
                if (preg_match("/[.,?!]+$/", $word, $matches)) {
                    $replacement .= $matches[0];
                }
                
                $new_words[] = $replacement;
            } else {
                $new_words[] = $word;
            }
        }
        $new_sentences[] = implode(" ", $new_words);
    }
    
    return implode(" ", $new_sentences);
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
