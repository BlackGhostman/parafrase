<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../includes/dictionary.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->text)) {
    echo json_encode(["synonyms" => []]);
    exit();
}

$word = $data->text;
// Limpiar palabra
$clean_word = strtolower(preg_replace("/[^a-zA-ZáéíóúñÁÉÍÓÚÑ]/u", "", $word));

$synonyms = [];

// 1. Intentar API Externa (UCM)
try {
    // Usar curl para mejor control de timeout
    $ch = curl_init();
    $url = "http://sesat.fdi.ucm.es:8080/servicios/rest/sinonimos/json/" . urlencode($clean_word);
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2); // 2 segundos máximo
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $json = json_decode($response, true);
        if (isset($json['sinonimos']) && is_array($json['sinonimos'])) {
            $synonyms = array_map(function($item) {
                return $item['sinonimo'];
            }, $json['sinonimos']);
        }
    }
} catch (Exception $e) {
    // Fallo silencioso, usar local
}

// 2. Si no hay resultados externos, usar local
if (empty($synonyms)) {
    $synonyms = getSynonyms($clean_word);
}

// 3. Unir y limpiar (si hubiera ambos, opcionalmente)
// En este caso, si la externa funciona, usamos esa. Si no, la local.

echo json_encode([
    "word" => $clean_word,
    "synonyms" => array_values(array_unique($synonyms)),
    "source" => empty($synonyms) ? "none" : (isset($json) ? "external" : "local")
]);
?>
