<?php
require_once __DIR__ . '/../config.php';

$config = new Config();
$db = $config->getDB();

function createActivityLog($userId, $data) {
    global $db;

    // Start a transaction to ensure atomicity
    $db->beginTransaction();

    try {
        // Insert into activity_log
        $stmt = $db->prepare("
            INSERT INTO activity_log (user_id, action, details, points_earned, points_spent)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $data['action'] ?? 'unknown',
            $data['details'] ?? null,
            $data['points_earned'] ?? 0,
            $data['points_spent'] ?? 0
        ]);

        // Calculate net points
        $pointsEarned = $data['points_earned'] ?? 0;
        $pointsSpent = $data['points_spent'] ?? 0;
        $netPoints = $pointsEarned - $pointsSpent;

        // Update user's points balance
        $updateStmt = $db->prepare("
            UPDATE users SET points = points + ? WHERE id = ?
        ");
        $updateStmt->execute([$netPoints, $userId]);

        // Commit the transaction
        $db->commit();

        return true;

    } catch (Exception $e) {
        // Rollback on failure
        $db->rollBack();
        return false;
    }
}


function getActivityLogs($userId, $page = 1, $limit = 10) {
    global $db;

    $offset = ($page - 1) * $limit;

    $stmt = $db->prepare("
        SELECT id, action, details, points_earned, points_spent, created_at
        FROM activity_log
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();

    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count
    $countStmt = $db->prepare("SELECT COUNT(*) FROM activity_log WHERE user_id = ?");
    $countStmt->execute([$userId]);
    $total = $countStmt->fetchColumn();

    return [
        'page' => $page,
        'limit' => $limit,
        'total' => (int)$total,
        'logs' => $logs
    ];
}

function deleteActivityLog($userId, $id) {
    global $db;

    // Only delete logs belonging to the current user
    $stmt = $db->prepare("DELETE FROM activity_log WHERE id = ? AND user_id = ?");
    return $stmt->execute([$id, $userId]);
}
