<?php
require_once __DIR__ . '/../src/middlewares/cors.php';
require_once __DIR__ . '/../src/middlewares/auth.php';
require_once __DIR__ . '/../src/activity/activityService.php';

header('Content-Type: application/json');

$user = authenticate(); // assumes $user->sub is user_id
$userId = $user->sub;
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

        $logs = getActivityLogs($userId, $page, $limit);
        echo json_encode([
            'message' => 'Activity logs fetched',
            'data' => $logs
        ]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $result = createActivityLog($userId, $data);
        echo json_encode([
            'message' => $result ? 'Activity log created' : 'Failed to create log'
        ]);
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing ID']);
            exit;
        }

        $id = (int)$_GET['id'];
        $result = deleteActivityLog($userId, $id);
        echo json_encode([
            'message' => $result ? 'Activity log deleted' : 'Failed to delete log'
        ]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
