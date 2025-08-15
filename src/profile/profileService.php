<?php
require_once __DIR__ . '/../config.php';

$config = new Config();
$db = $config->getDB();

function getProfile($userId) {
    global $db;
    $stmt = $db->prepare("
        SELECT u.id, u.username, u.email, u.name, u.role, u.avatar, u.points, u.is_verified,
               ud.address, ud.city, ud.district, ud.state, ud.country, ud.phone, ud.dob
        FROM users u
        LEFT JOIN user_details ud ON u.id = ud.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function createOrUpdateProfile($userId, $data) {
    global $db;

    $stmt = $db->prepare("SELECT user_id FROM user_details WHERE user_id = ?");
    $stmt->execute([$userId]);
    $exists = $stmt->fetchColumn();

    if ($exists) {
        $stmt = $db->prepare("
            UPDATE user_details SET 
                address = ?, city = ?, district = ?, state = ?, 
                country = ?, phone = ?, dob = ?
            WHERE user_id = ?
        ");
        return $stmt->execute([
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['district'] ?? null,
            $data['state'] ?? null,
            $data['country'] ?? null,
            $data['phone'] ?? null,
            $data['dob'] ?? null,
            $userId
        ]);
    } else {
        $stmt = $db->prepare("
            INSERT INTO user_details 
            (user_id, address, city, district, state, country, phone, dob)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $userId,
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['district'] ?? null,
            $data['state'] ?? null,
            $data['country'] ?? null,
            $data['phone'] ?? null,
            $data['dob'] ?? null
        ]);
    }
}

function deleteProfile($userId) {
    global $db;
    $stmt = $db->prepare("DELETE FROM user_details WHERE user_id = ?");
    return $stmt->execute([$userId]);
}
