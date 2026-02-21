<?php
require_once '../includes/db.php';

try {
    // 1. Clear existing data (Optional, handle with care)
    // $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    // $pdo->exec("TRUNCATE TABLE event_participants;");
    // $pdo->exec("TRUNCATE TABLE events;");
    // $pdo->exec("TRUNCATE TABLE users;");
    // $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // 2. Create Users
    $password = password_hash('password123', PASSWORD_DEFAULT);

    $users = [
        ['admin', 'admin@eventflow.ai', 'admin', 'The boss of events.'],
        ['john_doe', 'john@gmail.com', 'user', 'Tech enthusiast and networking pro.'],
        ['jane_smith', 'jane@outlook.com', 'user', 'Creative director and event planner.'],
        ['alex_vance', 'alex@company.com', 'user', 'AI researcher and developer.']
    ];

    $userIds = [];
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, bio) VALUES (?, ?, ?, ?, ?)");
    foreach ($users as $user) {
        $stmt->execute([$user[0], $user[1], $password, $user[2], $user[3]]);
        $userIds[$user[0]] = $pdo->lastInsertId();
    }

    // 3. Create Events
    $events = [
        [
            'AI Innovation Summit 2026',
            'A global gathering of AI experts to discuss the future of intelligence.',
            date('Y-m-d H:i:s', strtotime('+30 days')),
            'San Francisco, CA',
            $userIds['admin']
        ],
        [
            'React Workshop: Advanced Patterns',
            'Learn the latest React features and best practices in this hands-on workshop.',
            date('Y-m-d H:i:s', strtotime('+15 days')),
            'London, UK',
            $userIds['jane_smith']
        ],
        [
            'Summer Rooftop Mixer',
            'Join us for drinks and networking under the stars.',
            date('Y-m-d H:i:s', strtotime('+45 days')),
            'New York, NY',
            $userIds['john_doe']
        ]
    ];

    $eventIds = [];
    $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, location, created_by) VALUES (?, ?, ?, ?, ?)");
    foreach ($events as $event) {
        $stmt->execute($event);
        $eventIds[] = $pdo->lastInsertId();
    }

    // 4. Create Invitations
    $stmt = $pdo->prepare("INSERT INTO event_participants (event_id, user_id, status, invited_by) VALUES (?, ?, ?, ?)");

    // Admin invites everyone to the AI Summit
    foreach ($userIds as $username => $uid) {
        if ($username !== 'admin') {
            $stmt->execute([$eventIds[0], $uid, 'invited', $userIds['admin']]);
        }
    }

    // Jane invites Admin to React Workshop
    $stmt->execute([$eventIds[1], $userIds['admin'], 'attending', $userIds['jane_smith']]);

    echo "Database seeded successfully!\n";
} catch (PDOException $e) {
    die("Error seeding database: " . $e->getMessage() . "\n");
}
