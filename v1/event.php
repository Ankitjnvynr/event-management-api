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
            'is_approved' => (isset($_GET['is_approved']) && $_GET['is_approved'] != '') ? filter_var($_GET['is_approved'], FILTER_VALIDATE_BOOLEAN) : null,
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

        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input data']);
            exit;
        }

        // Fetch the existing event first
        $existingEvent = $eventService->getEventById($id);
        if (!$existingEvent) {
            http_response_code(404);
            echo json_encode(['error' => 'Event not found']);
            exit;
        }

        // Check if featured_image is being updated
        if (!isset($data['featured_image']) || empty($data['featured_image'])) {
            // Keep existing file if user didn't send new one
            $data['featured_image'] = $existingEvent['featured_image'];
        } else {
            // If a new file name is passed (uploaded earlier),
            // remove the old one if it exists and is different
            if (
                !empty($existingEvent['featured_image']) &&
                $existingEvent['featured_image'] !== $data['featured_image']
            ) {
                removeFile($existingEvent['featured_image'], 'events');
            }
        }

        if ($eventService->updateEvent($id, $data)) {
            echo json_encode(['message' => 'Event updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update event']);
            removeFile($data['featured_image'], 'events');
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
