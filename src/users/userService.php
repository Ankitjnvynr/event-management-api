<?php
require_once __DIR__ . '/../config.php';

$config = new Config();
$db = $config->getDB();

function getAllUsersFiltered($page, $limit, $filters) {
    global $db;
    $offset = ($page - 1) * $limit;

    $base = "FROM users u LEFT JOIN user_details d ON u.id = d.user_id WHERE 1=1";
    $params = [];

    foreach ($filters as $key => $value) {
        if ($value === '' || $value === null) continue;

        switch ($key) {
            case 'username':
            case 'email':
            case 'name':
            case 'role':
                $base .= " AND u.$key LIKE ?";
                $params[] = "%$value%";
                break;
            case 'is_verified':
                $base .= " AND u.is_verified = ?";
                $params[] = (int)filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;
            case 'points':
                if (preg_match('/^(>=|<=|>|<)(\d+)$/', $value, $m)) {
                    $base .= " AND u.points {$m[1]} ?";
                    $params[] = (int)$m[2];
                }
                break;
            case 'state':
            case 'city':
            case 'country':
                $base .= " AND d.$key LIKE ?";
                $params[] = "%$value%";
                break;
        }
    }

    $countStmt = $db->prepare("SELECT COUNT(*) $base");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $sql = "SELECT u.id, u.username, u.email, u.name, u.role, u.avatar, u.points, u.is_verified, u.created_at,
                   d.address, d.city, d.district, d.state, d.country, d.phone, d.dob
            $base ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
    $stmt = $db->prepare($sql);

    foreach ($params as $i => $val) {
        $stmt->bindValue($i + 1, $val);
    }

    $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
    $stmt->execute();

    return [
        'users' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'total_pages' => ceil($total / $limit)
    ];
}

function getUserById($id) {
    global $db;
    $stmt = $db->prepare("
        SELECT u.*, d.address, d.city, d.district, d.state, d.country, d.phone, d.dob
        FROM users u
        LEFT JOIN user_details d ON u.id = d.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function createUser($data) {
    global $db;
    $stmt = $db->prepare("
        INSERT INTO users (username, email, name, role, avatar, points, is_verified)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['username'], $data['email'], $data['name'], $data['role'] ?? 'user',
        $data['avatar'] ?? null, $data['points'] ?? 0, $data['is_verified'] ?? false
    ]);
    $userId = $db->lastInsertId();

    if (!empty($data['details'])) {
        $details = $data['details'];
        $stmt = $db->prepare("
            INSERT INTO user_details (user_id, address, city, district, state, country, phone, dob)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $details['address'] ?? null,
            $details['city'] ?? null,
            $details['district'] ?? null,
            $details['state'] ?? null,
            $details['country'] ?? null,
            $details['phone'] ?? null,
            $details['dob'] ?? null
        ]);
    }

    return getUserById($userId);
}

function updateUser($id, $data) {
    global $db;

    $stmt = $db->prepare("
        UPDATE users SET username = ?, email = ?, name = ?, role = ?, avatar = ?, points = ?, is_verified = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $data['username'], $data['email'], $data['name'], $data['role'] ?? 'user',
        $data['avatar'] ?? null, $data['points'] ?? 0, $data['is_verified'] ?? false, $id
    ]);

    if (!empty($data['details'])) {
        $details = $data['details'];

        $exists = $db->prepare("SELECT 1 FROM user_details WHERE user_id = ?");
        $exists->execute([$id]);

        if ($exists->fetch()) {
            $stmt = $db->prepare("
                UPDATE user_details SET address = ?, city = ?, district = ?, state = ?, country = ?, phone = ?, dob = ?
                WHERE user_id = ?
            ");
        } else {
            $stmt = $db->prepare("
                INSERT INTO user_details (user_id, address, city, district, state, country, phone, dob)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
        }

        $stmt->execute([
            $details['address'] ?? null,
            $details['city'] ?? null,
            $details['district'] ?? null,
            $details['state'] ?? null,
            $details['country'] ?? null,
            $details['phone'] ?? null,
            $details['dob'] ?? null,
            $id
        ]);
    }

    return true;
}

function softDeleteUser($id) {
    global $db;
    $stmt = $db->prepare("UPDATE users SET is_verified = FALSE WHERE id = ?");
    return $stmt->execute([$id]);
}

function exportUsersCSV($filters) {
    $result = getAllUsersFiltered(1, PHP_INT_MAX, $filters);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=users_export.csv');

    $out = fopen('php://output', 'w');
    if (!empty($result['users'])) {
        fputcsv($out, array_keys($result['users'][0]));
        foreach ($result['users'] as $row) {
            fputcsv($out, $row);
        }
    }
    fclose($out);
    exit;
}
