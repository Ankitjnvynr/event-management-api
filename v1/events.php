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

        if(isset($_POST['eventImg'])){
                $uploadedFile = upload($_POST['eventImg'],'events');
                if(!$uploadedFile['status']){
                    echo json_encode([
                        'status'=>false,
                        'message'=>$uploadedFile['message']
                    ]);
                }
                $_POST['featured_image'] = $uploadedFile['filename'];
        }

        $data = json_decode(file_get_contents('php://input'), true);

        // Required fields
        $requiredFields = ['title', 'start_time', 'end_time'];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Missing required field: $field"]);
                exit;
            }
        }

        // Set default value for approval
        $data['is_approved'] = false;

        // Optional: Set defaults for optional fields if not set
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

        if ($eventService->createEvent($data)) {
            echo json_encode(['message' => 'Event created successfully and pending approval']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create event']);
        }
        break;


    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
