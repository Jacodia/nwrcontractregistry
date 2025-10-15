<?php
// Simple DB connection test script — requires backend/config/db.php
require_once __DIR__ . '/config/db.php';

if (isset($pdo) && $pdo instanceof PDO) {
    echo "DB connection established. PDO driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
} else {
    echo "DB connection not established.\n";
}
