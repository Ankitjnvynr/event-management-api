<?php
require_once __DIR__ . '/../src/middlewares/cors.php';
require_once __DIR__ . '/../src/middlewares/auth.php';
require_once __DIR__ . '/../src/users/userService.php';

header('Content-Type: application/json');

$user = authenticate();

isAdmin();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            exportUsersCSV($_GET);
            break;
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, (int)($_GET['limit'] ?? 10));
        $filters = $_GET;
        unset($filters['page'], $filters['limit']);

        $result = getAllUsersFiltered($page, $limit, $filters);
        echo json_encode([
            'success' => true,
            'data' => $result['users'],
            'pagination' => [
                'page' => $result['page'],
                'limit' => $result['limit'],
                'total' => $result['total'],
                'total_pages' => $result['total_pages']
            ]
        ]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        $newUser = createUser($input);
        echo json_encode(['success' => true, 'message' => 'User created', 'user' => $newUser]);
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            break;
        }
        $updated = updateUser($input['id'], $input);
        echo json_encode(['success' => $updated, 'message' => $updated ? 'User updated' : 'Failed to update']);
        break;

    case 'DELETE':
        parse_str(file_get_contents('php://input'), $del);
        if (empty($del['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            break;
        }
        $deleted = softDeleteUser($del['id']);
        echo json_encode(['success' => $deleted, 'message' => $deleted ? 'User deleted' : 'Failed to delete']);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        break;
}
