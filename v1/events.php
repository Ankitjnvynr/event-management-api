<?php
require_once __DIR__ . '/../src/middlewares/cors.php';
require_once __DIR__ . '/../src/events/eventService.php';
require_once __DIR__ . '/../src/upload/FileUpload.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$eventService = new EventService();

switch ($method) {
    case 'GET':
        // Only calendar view or single event fetching allowed here
        if (isset($_GET['id'])) {
            $id = (int) $_GET['id'];
            $event = $eventService->getEventById($id);

            if ($event) {
                echo json_encode([
                    'message' => 'Event fetched successfully',
                    'data' => $event
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Event not found']);
            }
        } else {
            // Calendar view - only approved events
            $month = $_GET['month'] ?? null;
            $year = $_GET['year'] ?? null;

            $events = $eventService->getEventsForCalendar($month, $year);

            echo json_encode([
                'message' => 'Calendar events fetched successfully',
                'data' => $events
            ]);
        }
        break;

    case 'POST':

        $uploadedFile = null;

        // Handle optional file upload
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
            $_POST['featured_image'] = $uploadedFile['filename'];
        }

        // Handle input data (JSON or form-data fallback)
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $data = $_POST;
        }

        // Required fields
        $requiredFields = ['title', 'start_time', 'end_time'];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Missing required field: $field"]);

                // Clean up uploaded file if exists
                if (!empty($uploadedFile['filename'])) {
                    removeFile($uploadedFile['filename'], 'events');
                }
                exit;
            }
        }

        // Set default value for approval
        $data['is_approved'] = false;

        // Optional fields
        $optionalFields = [
            'description',
            'is_all_day',
            'location',
            'color',
            'organizer_name',
            'contact_phone',
            'contact_email',
            'website_url',
            'registration_link',
            'external_links',
            'featured_image'
        ];

        foreach ($optionalFields as $field) {
            if (!isset($data[$field])) {
                $data[$field] = null;
            }
        }

        // Create event
        if ($eventService->createEvent($data)) {
            echo json_encode(['message' => 'Event created successfully and pending approval']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create event']);

            // Clean up uploaded file if DB save failed
            if (!empty($uploadedFile['filename'])) {
                removeFile($uploadedFile['filename'], 'events');
            }
        }
        break;


    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
