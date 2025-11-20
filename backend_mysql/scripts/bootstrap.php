<?php
// Run this file in the browser to bootstrap the MySQL schema using XAMPP's PHP/Apache.
header('Content-Type: text/html; charset=utf-8');
echo "<h2>Bootstrap: nwrcontractregistry schema</h2>\n";
require_once __DIR__ . '/../config/db.php';

$sqlFile = __DIR__ . '/schema_mysql.sql';
if (!file_exists($sqlFile)) {
    echo "<p style='color:red'>SQL file not found: " . htmlspecialchars($sqlFile) . "</p>";
    exit;
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    echo "<p style='color:red'>Failed to read SQL file.</p>";
    exit;
}

try {
    $pdo = get_db_connection();
    // split by semicolon and execute each statement
    $parts = array_filter(array_map('trim', explode(';', $sql)));
    $executed = 0;
    echo "<pre>";
    foreach ($parts as $part) {
        if ($part === '') continue;
        try {
            $pdo->exec($part);
            $executed++;
            echo "OK: " . htmlspecialchars(substr($part, 0, 120)) . "\n---\n";
        } catch (Exception $e) {
            echo "ERROR executing statement: " . htmlspecialchars($e->getMessage()) . "\nStatement snippet: " . htmlspecialchars(substr($part,0,120)) . "\n---\n";
        }
    }
    echo "</pre>";
    echo "<p style='color:green'>Executed statements: " . $executed . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Bootstrap failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p>After bootstrap, run the PowerShell test runner: <code>backend_mysql/scripts/run_tests.ps1</code></p>";

?>
<?php
// Bootstrap script to create minimal tables and seed a test user/contract.
require_once __DIR__ . '/../config/db.php';

$pdo = get_db_connection();

// Create users table
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Create contracts table
$pdo->exec("CREATE TABLE IF NOT EXISTS contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) DEFAULT NULL,
    party VARCHAR(255) DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Insert test user if missing
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = :u LIMIT 1');
$stmt->execute([':u' => 'testuser']);
$exists = $stmt->fetch();
if (!$exists) {
    $hash = password_hash('secret', PASSWORD_DEFAULT);
    $ins = $pdo->prepare('INSERT INTO users (username, password) VALUES (:u, :p)');
    $ins->execute([':u' => 'testuser', ':p' => $hash]);
    echo "Inserted test user\n";
} else {
    echo "Test user exists\n";
}

// Insert initial contract if none
$count = $pdo->query('SELECT COUNT(*) as c FROM contracts')->fetch();
if (intval($count['c']) === 0) {
    $insc = $pdo->prepare('INSERT INTO contracts (title, party, start_date, end_date, notes) VALUES (:t, :p, :s, :e, :n)');
    $insc->execute([
        ':t' => 'Initial Contract',
        ':p' => 'Initial Party',
        ':s' => '2025-01-01',
        ':e' => '2025-12-31',
        ':n' => 'seeded by bootstrap'
    ]);
    echo "Inserted initial contract\n";
} else {
    echo "Contracts present: {$count['c']}\n";
}

echo "Bootstrap completed\n";

?>
