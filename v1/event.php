<?php
require_once __DIR__ . '/../src/middlewares/cors.php';
require_once __DIR__ . '/../src/middlewares/auth.php';  // assume admin auth middleware
require_once __DIR__ . '/../src/events/eventService.php';

header('Content-Type: application/json');

$user = authenticate();
if ($user->role !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$eventService = new EventService();

switch ($method) {
    case 'GET':
        // Admin can list all events paginated
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;

        $filters = [
            'title' => $_GET['title'] ?? null,
            'organizer_name' => $_GET['organizer_name'] ?? null,
            'is_approved' => isset($_GET['is_approved']) ? filter_var($_GET['is_approved'], FILTER_VALIDATE_BOOLEAN) : null,
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null,
        ];

        $events = $eventService->getEventsAdmin($page, $limit, $filters);


        echo json_encode([
            'message' => 'Admin events fetched successfully',
            'data' => $events
        ]);
        break;

    case 'PUT':
        if (!isset($_GET['id']) || $_GET['id'] == '') {
            http_response_code(400);
            echo json_encode(['error' => 'Missing event ID']);
            exit;
        }
        $id = (int) $_GET['id'];
        $data = json_decode(file_get_contents('php://input'), true);

        if ($eventService->updateEvent($id, $data)) {
            echo json_encode(['message' => 'Event updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update event']);
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing event ID']);
            exit;
        }

        $id = (int) $_GET['id'];

        if ($eventService->deleteEvent($id)) {
            echo json_encode(['message' => 'Event deleted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete event']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
