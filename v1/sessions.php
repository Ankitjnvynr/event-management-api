<?php
require_once __DIR__ . '/../src/middlewares/cors.php';
require_once __DIR__ . '/../src/middlewares/auth.php';
require_once __DIR__ . '/../src/sessions/sessionService.php';

header('Content-Type: application/json');

$user = authenticate(); // get logged in user
$userId = $user->sub;

//isAdmin();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;

        $result = getUserSessionsPaginated($userId, $page, $limit);

        echo json_encode([
            'success' => true,
            'data' => $result['sessions'],
            'pagination' => [
                'page' => $result['page'],
                'limit' => $result['limit'],
                'total' => $result['total'],
                'total_pages' => $result['total_pages']
            ]
        ]);
        break;

    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        break;
}
