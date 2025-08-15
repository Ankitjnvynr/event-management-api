<?php 


require_once __DIR__ . '/../../src/middlewares/cors.php';
require_once __DIR__ . '/../../src/EmailPasswordAuth.php' ;

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => false,
        'message' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

// Read and decode input JSON
$input = json_decode(file_get_contents("php://input"), true);

$email = isset($input["email"]) ? $input["email"] :null;
$password = isset($input["password"]) ? $input["password"]:null;

$auth = new EmailPasswordAuth();

$result = $auth->login($email, $password);

echo json_encode($result);
