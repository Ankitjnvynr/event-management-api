<?php

require_once __DIR__ . '/../config.php';

class EventService
{
    private $db;

    public function __construct()
    {
        $config = new Config();
        $this->db = $config->getDB();
    }

    // Create new event (public or admin)
    public function createEvent($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO events (
                title, description, start_time, end_time, is_all_day, location,
                color, organizer_name, contact_phone, contact_email,
                website_url, registration_link, external_links, is_approved
            ) VALUES (
                :title, :description, :start_time, :end_time, :is_all_day, :location,
                :color, :organizer_name, :contact_phone, :contact_email,
                :website_url, :registration_link, :external_links, :is_approved
            )
        ");

        return $stmt->execute([
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':start_time' => $data['start_time'],
            ':end_time' => $data['end_time'],
            ':is_all_day' => $data['is_all_day'] ?? false,
            ':location' => $data['location'] ?? null,
            ':color' => $data['color'] ?? null,
            ':organizer_name' => $data['organizer_name'] ?? null,
            ':contact_phone' => $data['contact_phone'] ?? null,
            ':contact_email' => $data['contact_email'] ?? null,
            ':website_url' => $data['website_url'] ?? null,
            ':registration_link' => $data['registration_link'] ?? null,
            ':external_links' => $data['external_links'] ?? null,
            ':is_approved' => $data['is_approved'] ?? false
        ]);
    }

    public function getEventsAdmin($page = 1, $limit = 10, $filters = [])
    {
        $offset = ($page - 1) * $limit;
        $whereClauses = [];
        $params = [];

        // Dynamic filters
        if (!empty($filters['title'])) {
            $whereClauses[] = "title LIKE :title";
            $params[':title'] = '%' . $filters['title'] . '%';
        }

        if (!empty($filters['organizer_name'])) {
            $whereClauses[] = "organizer_name LIKE :organizer_name";
            $params[':organizer_name'] = '%' . $filters['organizer_name'] . '%';
        }

        if (isset($filters['is_approved'])) {
            $whereClauses[] = "is_approved = :is_approved";
            $params[':is_approved'] = (bool) $filters['is_approved'];
        }

        if (!empty($filters['start_date'])) {
            $whereClauses[] = "start_time >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $whereClauses[] = "end_time <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        $whereSQL = '';
        if (count($whereClauses)) {
            $whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);
        }

        // Fetch events with filters
        $sql = "
        SELECT * FROM events
        $whereSQL
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset
    ";

        $stmt = $this->db->prepare($sql);

        // Bind dynamic filters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Count total matching records
        $countSql = "SELECT COUNT(*) FROM events $whereSQL";
        $countStmt = $this->db->prepare($countSql);

        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }

        $countStmt->execute();
        $total = $countStmt->fetchColumn();

        return [
            'page' => $page,
            'limit' => $limit,
            'total' => (int) $total,
            'events' => $events
        ];
    }


    // Get approved events for calendar (filtered by month/year)
    public function getEventsForCalendar($month = null, $year = null)
    {
        // Default to current month/year
        if (!$month || !$year) {
            $month = date('m');
            $year = date('Y');
        }

        $startDate = "$year-$month-01";
        $endDate = date("Y-m-t", strtotime($startDate)); // last day of month

        $stmt = $this->db->prepare("
            SELECT * FROM events
            WHERE is_approved = TRUE
              AND start_time BETWEEN :start_date AND :end_date
            ORDER BY start_time ASC
        ");
        $stmt->execute([
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get single event by ID
    public function getEventById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update event
    public function updateEvent($id, $data)
    {
        $stmt = $this->db->prepare("
            UPDATE events SET
                title = :title,
                description = :description,
                start_time = :start_time,
                end_time = :end_time,
                is_all_day = :is_all_day,
                location = :location,
                color = :color,
                organizer_name = :organizer_name,
                contact_phone = :contact_phone,
                contact_email = :contact_email,
                website_url = :website_url,
                registration_link = :registration_link,
                external_links = :external_links,
                is_approved = :is_approved,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':start_time' => $data['start_time'],
            ':end_time' => $data['end_time'],
            ':is_all_day' => $data['is_all_day'] ?? false,
            ':location' => $data['location'] ?? null,
            ':color' => $data['color'] ?? null,
            ':organizer_name' => $data['organizer_name'] ?? null,
            ':contact_phone' => $data['contact_phone'] ?? null,
            ':contact_email' => $data['contact_email'] ?? null,
            ':website_url' => $data['website_url'] ?? null,
            ':registration_link' => $data['registration_link'] ?? null,
            ':external_links' => $data['external_links'] ?? null,
            ':is_approved' => $data['is_approved'] ?? false
        ]);
    }

    // Delete event
    public function deleteEvent($id)
    {
        $stmt = $this->db->prepare("DELETE FROM events WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
