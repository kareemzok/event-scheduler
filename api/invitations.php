<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$action = $_GET['action'] ?? '';
$userId = (int) $_SESSION['user_id'];

try {
    ensureInvitationLinksTable($pdo);
} catch (PDOException $e) {
    jsonResponse(['error' => 'Invitation links table is unavailable. Please run schema update.'], 500);
}

if ($action === 'invite' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    inviteUser($pdo, $userId);
} elseif ($action === 'respond' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    respondToInvite($pdo, $userId);
} elseif ($action === 'list_invites' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    listInvites($pdo, $userId);
} elseif ($action === 'get_invite_by_token' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    getInviteByToken($pdo, $userId);
} else {
    jsonResponse(['error' => 'Invalid action'], 400);
}

function inviteUser(PDO $pdo, int $userId): void
{
    $eventId = (int) ($_POST['event_id'] ?? 0);
    $targetInput = trim($_POST['username'] ?? '');

    if (!$eventId || $targetInput === '') {
        jsonResponse(['error' => 'Event and target user are required'], 422);
    }

    $eventStmt = $pdo->prepare("SELECT id, title, created_by FROM events WHERE id = ?");
    $eventStmt->execute([$eventId]);
    $event = $eventStmt->fetch();

    if (!$event) {
        jsonResponse(['error' => 'Event not found'], 404);
    }

    $isAdmin = ($_SESSION['role'] ?? 'user') === 'admin';
    if ((int) $event['created_by'] !== $userId && !$isAdmin) {
        jsonResponse(['error' => 'Unauthorized to invite users to this event'], 403);
    }

    $identifier = normalizeIdentifier($targetInput);
    if ($identifier === '') {
        jsonResponse(['error' => 'Invalid username or email'], 422);
    }

    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->execute([$targetInput, $targetInput]);
    $targetUser = $stmt->fetch();

    if ($targetUser && (int) $targetUser['id'] === $userId) {
        jsonResponse(['error' => 'You cannot invite yourself'], 422);
    }

    $token = generateInviteToken();
    $inviteeName = $targetInput;
    $signupRequired = false;

    try {
        $pdo->beginTransaction();

        if ($targetUser) {
            $inviteeName = $targetUser['username'];

            $participantStmt = $pdo->prepare("
                INSERT INTO event_participants (event_id, user_id, invited_by, status)
                VALUES (?, ?, ?, 'invited')
                ON DUPLICATE KEY UPDATE
                    invited_by = VALUES(invited_by),
                    invited_at = CURRENT_TIMESTAMP,
                    status = IF(status IN ('attending', 'maybe'), status, 'invited')
            ");
            $participantStmt->execute([$eventId, (int) $targetUser['id'], $userId]);

            $linkStmt = $pdo->prepare("
                INSERT INTO invitation_links (event_id, invitee_user_id, invitee_identifier, invited_by, token, is_active)
                VALUES (?, ?, ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE
                    invitee_user_id = VALUES(invitee_user_id),
                    invitee_identifier = VALUES(invitee_identifier),
                    invited_by = VALUES(invited_by),
                    token = VALUES(token),
                    is_active = 1,
                    updated_at = CURRENT_TIMESTAMP
            ");
            $linkStmt->execute([$eventId, (int) $targetUser['id'], $identifier, $userId, $token]);
        } else {
            $signupRequired = true;

            $linkStmt = $pdo->prepare("
                INSERT INTO invitation_links (event_id, invitee_user_id, invitee_identifier, invited_by, token, is_active)
                VALUES (?, NULL, ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE
                    invitee_identifier = VALUES(invitee_identifier),
                    invited_by = VALUES(invited_by),
                    token = VALUES(token),
                    is_active = 1,
                    updated_at = CURRENT_TIMESTAMP
            ");
            $linkStmt->execute([$eventId, $identifier, $userId, $token]);
        }

        $pdo->commit();

        $inviteUrl = buildInviteUrl($token);
        $shareText = $signupRequired
            ? 'You are invited to "' . $event['title'] . '" on ' . APP_NAME . '. Sign up (or log in) and respond here:'
            : $_SESSION['username'] . ' invited you to "' . $event['title'] . '" on ' . APP_NAME . '. Respond here:';

        jsonResponse([
            'success' => true,
            'event_id' => $eventId,
            'event_title' => $event['title'],
            'invitee' => $inviteeName,
            'invite_url' => $inviteUrl,
            'invitee_signup_required' => $signupRequired,
            'share_text' => $shareText
        ]);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        jsonResponse(['error' => 'Invitation failed. Please try again.'], 500);
    }
}

function respondToInvite(PDO $pdo, int $userId): void
{
    $eventId = (int) ($_POST['event_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $allowed = ['attending', 'maybe', 'declined'];
    if (!in_array($status, $allowed, true)) {
        jsonResponse(['error' => 'Invalid status'], 422);
    }

    $existsStmt = $pdo->prepare("SELECT id FROM event_participants WHERE event_id = ? AND user_id = ? LIMIT 1");
    $existsStmt->execute([$eventId, $userId]);
    if (!$existsStmt->fetch()) {
        jsonResponse(['error' => 'Invitation not found for this user'], 404);
    }

    $stmt = $pdo->prepare("
        UPDATE event_participants
        SET status = ?, responded_at = CURRENT_TIMESTAMP
        WHERE event_id = ? AND user_id = ?
    ");
    $stmt->execute([$status, $eventId, $userId]);

    $deactivateStmt = $pdo->prepare("UPDATE invitation_links SET is_active = 0 WHERE event_id = ? AND invitee_user_id = ?");
    $deactivateStmt->execute([$eventId, $userId]);

    jsonResponse(['success' => true]);
}

function listInvites(PDO $pdo, int $userId): void
{
    $scope = strtolower(trim((string) ($_GET['scope'] ?? 'received')));
    if ($scope === 'sent') {
        listSentInvites($pdo, $userId);
        return;
    }

    listReceivedInvites($pdo, $userId);
}

function listReceivedInvites(PDO $pdo, int $userId): void
{
    $stmt = $pdo->prepare("
        SELECT e.*, u.username as inviter, il.token
        FROM events e
        JOIN event_participants ep ON e.id = ep.event_id
        JOIN users u ON ep.invited_by = u.id
        LEFT JOIN invitation_links il ON il.event_id = e.id AND il.invitee_user_id = ep.user_id AND il.is_active = 1
        WHERE ep.user_id = ? AND ep.status = 'invited'
        ORDER BY e.event_date ASC
    ");
    $stmt->execute([$userId]);
    $invites = $stmt->fetchAll();

    foreach ($invites as &$invite) {
        $invite['invite_url'] = !empty($invite['token']) ? buildInviteUrl($invite['token']) : null;
    }
    unset($invite);

    jsonResponse($invites);
}

function listSentInvites(PDO $pdo, int $userId): void
{
    $stmt = $pdo->prepare("
        SELECT
            e.id,
            e.title,
            e.description,
            e.event_date,
            e.location,
            il.id AS invitation_id,
            il.event_id,
            il.invitee_user_id,
            il.invitee_identifier,
            il.token,
            il.is_active,
            il.updated_at AS invited_at,
            u.username AS invitee_username,
            ep.status AS invitee_status,
            ep.responded_at
        FROM invitation_links il
        JOIN events e ON e.id = il.event_id
        LEFT JOIN users u ON u.id = il.invitee_user_id
        LEFT JOIN event_participants ep ON ep.event_id = il.event_id AND ep.user_id = il.invitee_user_id
        WHERE il.invited_by = ?
        ORDER BY il.updated_at DESC
    ");
    $stmt->execute([$userId]);
    $invites = $stmt->fetchAll();

    foreach ($invites as &$invite) {
        $status = resolveSentInviteStatus($invite);
        $inviteeUsername = trim((string) ($invite['invitee_username'] ?? ''));
        $inviteeIdentifier = trim((string) ($invite['invitee_identifier'] ?? ''));
        if ($inviteeUsername !== '') {
            $invite['invitee'] = $inviteeUsername;
        } elseif ($inviteeIdentifier !== '') {
            $invite['invitee'] = $inviteeIdentifier;
        } else {
            $invite['invitee'] = 'User #' . (int) ($invite['invitee_user_id'] ?? 0);
        }
        $invite['invite_status'] = $status;
        $invite['invite_status_label'] = formatInviteStatusLabel($status);
        $invite['invite_status_class'] = mapInviteStatusClass($status);
        $invite['invite_url'] = ((int) $invite['is_active'] === 1 && !empty($invite['token']))
            ? buildInviteUrl($invite['token'])
            : null;
    }
    unset($invite);

    jsonResponse($invites);
}

function resolveSentInviteStatus(array $invite): string
{
    if ($invite['invitee_user_id'] === null) {
        return 'pending_signup';
    }

    $status = (string) ($invite['invitee_status'] ?? '');
    if (in_array($status, ['attending', 'maybe', 'declined'], true)) {
        return $status;
    }

    return 'invited';
}

function formatInviteStatusLabel(string $status): string
{
    switch ($status) {
        case 'attending':
            return 'Attending';
        case 'maybe':
            return 'Maybe';
        case 'declined':
            return 'Declined';
        case 'pending_signup':
            return 'Pending Signup';
        default:
            return 'Pending Response';
    }
}

function mapInviteStatusClass(string $status): string
{
    if (in_array($status, ['attending', 'maybe', 'declined'], true)) {
        return $status;
    }

    return 'invited';
}

function getInviteByToken(PDO $pdo, int $userId): void
{
    $token = trim($_GET['token'] ?? '');
    if ($token === '') {
        jsonResponse(['error' => 'Missing invite token'], 422);
    }

    $stmt = $pdo->prepare("
        SELECT
            il.id,
            il.event_id,
            il.token,
            il.is_active,
            il.invitee_user_id,
            il.invitee_identifier,
            il.invited_by,
            e.title,
            e.description,
            e.event_date,
            e.location,
            inviter.username AS inviter_name
        FROM invitation_links il
        JOIN events e ON e.id = il.event_id
        JOIN users inviter ON inviter.id = il.invited_by
        WHERE il.token = ?
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $invite = $stmt->fetch();

    if (!$invite) {
        jsonResponse(['error' => 'Invite link is invalid'], 404);
    }

    if (!(int) $invite['is_active']) {
        jsonResponse(['error' => 'This invite link has already been used'], 410);
    }

    $reservedUserId = $invite['invitee_user_id'] !== null ? (int) $invite['invitee_user_id'] : null;
    if ($reservedUserId !== null && $reservedUserId !== $userId) {
        jsonResponse(['error' => 'Invite link is invalid for this account'], 403);
    }

    if ($reservedUserId === null) {
        $currentUserStmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ? LIMIT 1");
        $currentUserStmt->execute([$userId]);
        $currentUser = $currentUserStmt->fetch();
        if (!$currentUser) {
            jsonResponse(['error' => 'User not found'], 404);
        }

        if (!identifierMatchesUser($invite['invitee_identifier'], $currentUser['username'], $currentUser['email'])) {
            jsonResponse([
                'error' => 'This invite is reserved for "' . ($invite['invitee_identifier'] ?? 'a different account') . '". Please sign up/login with that username or email.'
            ], 403);
        }

        $claimStmt = $pdo->prepare("
            UPDATE invitation_links
            SET invitee_user_id = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND invitee_user_id IS NULL
        ");
        $claimStmt->execute([$userId, (int) $invite['id']]);
    }

    $participantStmt = $pdo->prepare("
        INSERT INTO event_participants (event_id, user_id, invited_by, status)
        VALUES (?, ?, ?, 'invited')
        ON DUPLICATE KEY UPDATE
            invited_by = VALUES(invited_by),
            invited_at = CURRENT_TIMESTAMP,
            status = IF(status IN ('attending', 'maybe'), status, 'invited')
    ");
    $participantStmt->execute([(int) $invite['event_id'], $userId, (int) $invite['invited_by']]);

    $statusStmt = $pdo->prepare("SELECT status FROM event_participants WHERE event_id = ? AND user_id = ? LIMIT 1");
    $statusStmt->execute([(int) $invite['event_id'], $userId]);
    $statusRow = $statusStmt->fetch();

    $invite['user_status'] = $statusRow['status'] ?? 'invited';
    $invite['invite_url'] = buildInviteUrl($token);
    jsonResponse(['success' => true, 'invite' => $invite]);
}

function normalizeIdentifier(string $value): string
{
    return strtolower(trim($value));
}

function identifierMatchesUser(?string $inviteeIdentifier, string $username, string $email): bool
{
    $inviteeIdentifier = normalizeIdentifier((string) $inviteeIdentifier);
    if ($inviteeIdentifier === '') {
        return true;
    }

    $normalizedUsername = normalizeIdentifier($username);
    $normalizedEmail = normalizeIdentifier($email);

    return $inviteeIdentifier === $normalizedUsername || $inviteeIdentifier === $normalizedEmail;
}

function generateInviteToken(): string
{
    return bin2hex(random_bytes(24));
}

function buildInviteUrl(string $token): string
{
    return rtrim(resolveBaseUrl(), '/\\') . '/dashboard.php?invite_token=' . rawurlencode($token);
}

function resolveBaseUrl(): string
{
    $base = defined('BASE_URL') ? BASE_URL : '';
    if ($base === '') {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
        $base = $protocol . '://' . $host . rtrim($scriptDir, '/\\') . '/';
    }

    $trimmedBase = rtrim($base, '/\\');
    if (str_ends_with($trimmedBase, '/api')) {
        $trimmedBase = substr($trimmedBase, 0, -4);
    }

    return $trimmedBase . '/';
}

function ensureInvitationLinksTable(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS invitation_links (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            invitee_user_id INT NULL,
            invitee_identifier VARCHAR(191) NULL,
            invited_by INT NOT NULL,
            token VARCHAR(64) NOT NULL UNIQUE,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_event_invitee (event_id, invitee_user_id),
            UNIQUE KEY unique_event_invitee_identifier (event_id, invitee_identifier),
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY (invitee_user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    if (!columnExists($pdo, 'invitation_links', 'invitee_identifier')) {
        $pdo->exec("ALTER TABLE invitation_links ADD COLUMN invitee_identifier VARCHAR(191) NULL AFTER invitee_user_id");
    }

    $pdo->exec("ALTER TABLE invitation_links MODIFY invitee_user_id INT NULL");

    if (!indexExists($pdo, 'invitation_links', 'unique_event_invitee')) {
        $pdo->exec("ALTER TABLE invitation_links ADD UNIQUE KEY unique_event_invitee (event_id, invitee_user_id)");
    }

    if (!indexExists($pdo, 'invitation_links', 'unique_event_invitee_identifier')) {
        $pdo->exec("ALTER TABLE invitation_links ADD UNIQUE KEY unique_event_invitee_identifier (event_id, invitee_identifier)");
    }
}

function columnExists(PDO $pdo, string $tableName, string $columnName): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");
    $stmt->execute([$tableName, $columnName]);
    $result = $stmt->fetch();

    return (int) ($result['total'] ?? 0) > 0;
}

function indexExists(PDO $pdo, string $tableName, string $indexName): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND INDEX_NAME = ?
    ");
    $stmt->execute([$tableName, $indexName]);
    $result = $stmt->fetch();

    return (int) ($result['total'] ?? 0) > 0;
}

function jsonResponse(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}
