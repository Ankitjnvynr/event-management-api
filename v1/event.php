<?php
require_once __DIR__ . '/../src/middlewares/cors.php';
require_once __DIR__ . '/../src/middlewares/auth.php';  // assume admin auth middleware
require_once __DIR__ . '/../src/events/eventService.php';
require_once __DIR__ . '/../src/upload/FileUpload.php';

header('Content-Type: application/json');

// $user = authenticate();
// if ($user->role !== 'admin') {
//     http_response_code(403);
//     echo json_encode(['error' => 'Access denied']);
//     exit;
// }

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

    // ✅ Ensure it's multipart/form-data
    if (!isset($_SERVER['CONTENT_TYPE']) || strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') === false) {
        http_response_code(400);
        echo json_encode(['error' => 'Content-Type must be multipart/form-data']);
        exit;
    }

    // ✅ Parse raw input into $_POST and $_FILES (because PHP doesn’t do this for PUT)
    $rawData = file_get_contents("php://input");

    // Extract boundary
    preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
    $boundary = $matches[1] ?? '';

    if (empty($boundary)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid multipart data (no boundary)']);
        exit;
    }

    $blocks = preg_split("/-+$boundary/", $rawData);
    array_pop($blocks); // remove last -- block

    $_PUT = [];
    $_FILES = [];

    foreach ($blocks as $block) {
        if (empty(trim($block))) {
            continue;
        }

        // If block is a file
        if (strpos($block, 'filename="') !== false) {
            preg_match('/name="([^"]*)"; filename="([^"]*)"/', $block, $matches);
            $name = $matches[1];
            $filename = $matches[2];

            preg_match('/Content-Type: (.*)\r\n/', $block, $matches);
            $type = $matches[1];

            $fileData = substr($block, strpos($block, "\r\n\r\n") + 4, -2);

            // Save temp file
            $tmpName = tempnam(sys_get_temp_dir(), 'php');
            file_put_contents($tmpName, $fileData);

            $_FILES[$name] = [
                'name' => $filename,
                'type' => $type,
                'tmp_name' => $tmpName,
                'error' => 0,
                'size' => strlen($fileData),
            ];
        } else {
            // Otherwise it's a field
            preg_match('/name="([^"]*)"/', $block, $matches);
            $name = $matches[1];
            $value = substr($block, strpos($block, "\r\n\r\n") + 4, -2);
            $_PUT[$name] = $value;
        }
    }

    // ✅ Build $data from parsed fields
    $data = [
        'title' => $_PUT['title'] ?? null,
        'description' => $_PUT['description'] ?? null,
        'start_time' => $_PUT['start_time'] ?? null,
        'end_time' => $_PUT['end_time'] ?? null,
        'venue' => $_PUT['venue'] ?? null,
        'featured_image' => $_PUT['featured_image'] ?? null, // fallback
        'status' => $_PUT['status'] ?? null,
    ];

    // ✅ Fetch the existing event first
    $existingEvent = $eventService->getEventById($id);
    if (!$existingEvent) {
        http_response_code(404);
        echo json_encode(['error' => 'Event not found']);
        exit;
    }

    $uploadedFile = null;

    // ✅ Handle optional file upload
    if (isset($_FILES['eventImg']) && $_FILES['eventImg']['error'] === UPLOAD_ERR_OK) {
        $uploadedFile = upload($_FILES['eventImg'], 'events');

        if (!$uploadedFile['status']) {
            http_response_code(400);
            echo json_encode([
                'status' => false,
                'message' => $uploadedFile['message']
            ]);
            exit;
        }

        // Replace with new file
        $data['featured_image'] = $uploadedFile['filename'];

        // Remove old file if exists and different
        if (!empty($existingEvent['featured_image']) && $existingEvent['featured_image'] !== $data['featured_image']) {
            removeFile($existingEvent['featured_image'], 'events');
        }
    } else {
        // Keep old image if no new upload
        if (!isset($data['featured_image']) || empty($data['featured_image'])) {
            $data['featured_image'] = $existingEvent['featured_image'];
        }
    }

    // ✅ Update DB
    if ($eventService->updateEvent($id, $data)) {
        echo json_encode(['message' => 'Event updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update event']);

        if (!empty($uploadedFile['filename'])) {
            removeFile($uploadedFile['filename'], 'events');
        }
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
