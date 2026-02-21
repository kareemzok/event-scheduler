<?php
require_once __DIR__ . '/../includes/db.php';

// Helper to get env or default
if (!function_exists('get_env_val')) {
    function get_env_val($key, $default)
    {
        return getenv($key) ?: $default;
    }
}

try {
    // 1. Clear existing data
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE event_participants;");
    $pdo->exec("TRUNCATE TABLE events;");
    $pdo->exec("TRUNCATE TABLE users;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // 2. Create Users from Environment or Defaults
    $admin_user = get_env_val('DEMO_ADMIN_USER', 'admin_demo');
    $admin_pass = get_env_val('DEMO_ADMIN_PASS', 'admin_pass_2026');
    $user_user = get_env_val('DEMO_USER_USER', 'user_demo');
    $user_pass = get_env_val('DEMO_USER_PASS', 'user_pass_2026');

    $users = [
        [$admin_user, 'admin@eventflow.ai', $admin_pass, 'admin', 'System Administrator'],
        [$user_user, 'demo@eventflow.ai', $user_pass, 'user', 'Demo User for testing features.'],
        ['jane_smith', 'jane@outlook.com', 'jane_pass_2026', 'user', 'Creative director and event planner.'],
    ];

    $userIds = [];
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, bio) VALUES (?, ?, ?, ?, ?)");
    foreach ($users as $user) {
        $hashed_pass = password_hash($user[2], PASSWORD_DEFAULT);
        $stmt->execute([$user[0], $user[1], $hashed_pass, $user[3], $user[4]]);
        $userIds[$user[0]] = $pdo->lastInsertId();
    }

    // 3. Create Events
    $events = [
        [
            'AI Innovation Summit 2026',
            'A global gathering of AI experts to discuss the future of intelligence.',
            date('Y-m-d H:i:s', strtotime('+30 days')),
            'San Francisco, CA',
            $userIds[$admin_user]
        ],
        [
            'React Workshop: Advanced Patterns',
            'Learn the latest React features and best practices in this hands-on workshop.',
            date('Y-m-d H:i:s', strtotime('+15 days')),
            'London, UK',
            $userIds['jane_smith']
        ]
    ];

    $eventIds = [];
    $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, location, created_by) VALUES (?, ?, ?, ?, ?)");
    foreach ($events as $event) {
        $stmt->execute($event);
        $eventIds[] = $pdo->lastInsertId();
    }

    echo "Database seeded successfully!\n";
    echo "---------------------------\n";
    echo "Admin Credentials: $admin_user / $admin_pass\n";
    echo "User Credentials: $user_user / $user_pass\n";
    echo "---------------------------\n";

} catch (PDOException $e) {
    die("Error seeding database: " . $e->getMessage() . "\n");
}
