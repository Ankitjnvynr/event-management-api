<?php
require_once __DIR__ . '/../src/middlewares/cors.php';
require_once __DIR__ . '/../src/middlewares/auth.php';
require_once __DIR__ . '/../src/profile/profileService.php';

header('Content-Type: application/json');

// Authenticate user and get ID from token/session
$user = authenticate();
$userId = $user->sub;

// Route requests based on HTTP method
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $profile = getProfile($userId);
        echo json_encode([
            'message' => 'Profile fetched',
            'profile' => $profile
        ]);
        break;

    case 'POST':
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $result = createOrUpdateProfile($userId, $data);
        echo json_encode([
            'message' => $result ? 'Profile saved successfully' : 'Failed to save profile'
        ]);
        break;

    case 'DELETE':
        $result = deleteProfile($userId);
        echo json_encode([
            'message' => $result ? 'Profile deleted successfully' : 'Failed to delete profile'
        ]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
