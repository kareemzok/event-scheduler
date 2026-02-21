<?php
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        createEvent($pdo);
    } elseif ($action === 'update') {
        updateEvent($pdo);
    } elseif ($action === 'delete') {
        deleteEvent($pdo);
    } elseif ($action === 'update_status') {
        updateStatus($pdo);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        listEvents($pdo);
    } elseif ($action === 'get') {
        getEvent($pdo);
    }
}

function createEvent($pdo)
{
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $date = $_POST['event_date'] ?? '';
    $location = $_POST['location'] ?? '';
    $is_public = isset($_POST['is_public']) ? 1 : 0;

    if (!$title || !$date) {
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, location, created_by, is_public) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $date, $location, $_SESSION['user_id'], $is_public]);

        $eventId = $pdo->lastInsertId();

        // Auto-add creator as "attending"
        $stmt = $pdo->prepare("INSERT INTO event_participants (event_id, user_id, status) VALUES (?, ?, 'attending')");
        $stmt->execute([$eventId, $_SESSION['user_id']]);

        echo json_encode(['success' => true, 'id' => $eventId]);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function updateEvent($pdo)
{
    $id = $_POST['id'] ?? 0;
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $date = $_POST['event_date'] ?? '';
    $location = $_POST['location'] ?? '';

    $stmt = $pdo->prepare("SELECT created_by FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $event = $stmt->fetch();

    if ($event && ($event['created_by'] == $_SESSION['user_id'] || $_SESSION['role'] === 'admin')) {
        $stmt = $pdo->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, location = ? WHERE id = ?");
        $stmt->execute([$title, $description, $date, $location, $id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Unauthorized']);
    }
}

function getEvent($pdo)
{
    $id = $_GET['id'] ?? 0;
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode($stmt->fetch());
}

function listEvents($pdo)
{
    $userId = $_SESSION['user_id'];
    $q = $_GET['q'] ?? '';
    $date_start = $_GET['date_start'] ?? '';
    $date_end = $_GET['date_end'] ?? '';
    $location = $_GET['location'] ?? '';
    $status = $_GET['status'] ?? '';

    $query = "SELECT e.*, ep.status as user_status, u.username as creator_name 
              FROM events e 
              LEFT JOIN event_participants ep ON e.id = ep.event_id AND ep.user_id = ?
              JOIN users u ON e.created_by = u.id
              WHERE (e.is_public = 1 OR e.created_by = ? OR ep.user_id = ?)";

    $params = [$userId, $userId, $userId];

    if ($q) {
        $query .= " AND (e.title LIKE ? OR e.description LIKE ?)";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }

    if ($date_start) {
        $query .= " AND e.event_date >= ?";
        $params[] = $date_start;
    }

    if ($date_end) {
        $query .= " AND e.event_date <= ?";
        $params[] = $date_end;
    }

    if ($location) {
        $query .= " AND e.location LIKE ?";
        $params[] = "%$location%";
    }

    if ($status) {
        if ($status === 'invited') {
            $query .= " AND ep.status IS NULL";
        } else {
            $query .= " AND ep.status = ?";
            $params[] = $status;
        }
    }

    $query .= " ORDER BY e.event_date ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $events = $stmt->fetchAll();

    echo json_encode($events);
}

function deleteEvent($pdo)
{
    $id = $_POST['id'] ?? 0;
    $userId = $_SESSION['user_id'];

    // Check ownership or admin
    $stmt = $pdo->prepare("SELECT created_by FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $event = $stmt->fetch();

    if ($event && ($event['created_by'] == $userId || $_SESSION['role'] === 'admin')) {
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Unauthorized or not found']);
    }
}
function updateStatus($pdo)
{
    $eventId = $_POST['event_id'] ?? 0;
    $status = $_POST['status'] ?? '';
    $userId = $_SESSION['user_id'];

    if (!$eventId || !$status) {
        echo json_encode(['error' => 'Missing data']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO event_participants (event_id, user_id, status) 
                              VALUES (?, ?, ?) 
                              ON DUPLICATE KEY UPDATE status = ?, responded_at = CURRENT_TIMESTAMP");
        $stmt->execute([$eventId, $userId, $status, $status]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>