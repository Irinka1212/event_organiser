<?php
require_once __DIR__ . '/../config/db.php';

function getPendingRequests(int $organiser_id): array {
    global $connection;

    $stmt = $connection->prepare("
        SELECT er.*, 
               c.username AS client_name, 
               c.email AS client_email
        FROM event_requests er
        JOIN users c ON c.id = er.client_id
        WHERE er.organiser_id = ?
          AND er.status = 'pending'
        ORDER BY er.created_at DESC
    ");
    $stmt->bind_param("i", $organiser_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $res;
}

function getCorrectionRequests(int $organiser_id): array {
    global $connection;

    $stmt = $connection->prepare("
        SELECT er.*, 
               c.username AS client_name, 
               c.email AS client_email
        FROM event_requests er
        JOIN users c ON c.id = er.client_id
        WHERE er.organiser_id = ?
          AND er.status = 'needs_correction'
        ORDER BY er.created_at DESC
    ");
    $stmt->bind_param("i", $organiser_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $res;
}

function getCompletedRequests(int $organiser_id): array {
    global $connection;

    $stmt = $connection->prepare("
        SELECT er.*, 
               c.username AS client_name, 
               c.email AS client_email
        FROM event_requests er
        JOIN users c ON c.id = er.client_id
        WHERE er.organiser_id = ?
          AND er.status = 'accepted_by_client'
        ORDER BY er.created_at DESC
    ");
    $stmt->bind_param("i", $organiser_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $res;
}

function updateRequestStatus(int $request_id, int $organiser_id, string $action) {
    global $connection;

    $map = [
        'accept'   => 'accepted_by_organiser',
        'reject'   => 'rejected_by_organiser',
        'reaccept' => 'accepted_by_organiser'
    ];

    if (!isset($map[$action])) {
        return "Invalid action";
    }

    $stmt = $connection->prepare("
        UPDATE event_requests
        SET status = ?
        WHERE id = ?
          AND organiser_id = ?
    ");
    $stmt->bind_param("sii", $map[$action], $request_id, $organiser_id);

    $ok = $stmt->execute();
    $stmt->close();

    return $ok ? true : "Failed to update request";
}

function organiserCanEdit(string $status): bool {
    return in_array($status, [
        'accepted_by_organiser',
        'declined_by_client'
    ]);
}

function organiserCanUploadGallery(string $status): bool {
    return $status === 'accepted_by_client';
}
