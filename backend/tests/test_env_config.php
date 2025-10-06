<?php
// Test .env file parsing
echo "<h2>🔍 Environment Configuration Test</h2>";

try {
    require_once '../vendor/autoload.php';
    
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
    
    echo "<div style='color: green; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "✅ .env file loaded successfully!";
    echo "</div>";
    
    echo "<h3>Environment Variables</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Variable</th><th>Value</th></tr>";
    
    $envVars = [
        'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS',
        'SMTP_HOST', 'SMTP_PORT', 'SMTP_USERNAME', 'SMTP_FROM_EMAIL', 'SMTP_FROM_NAME',
        'APP_TIMEZONE', 'UPLOAD_DIR', 'LOG_DIR'
    ];
    
    foreach ($envVars as $var) {
        $value = $_ENV[$var] ?? 'Not set';
        // Mask sensitive values
        if (in_array($var, ['DB_PASS', 'SMTP_PASSWORD'])) {
            $value = $value ? str_repeat('*', strlen($value)) : 'Empty';
        }
        echo "<tr><td>$var</td><td>" . htmlspecialchars($value) . "</td></tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ .env parsing error: " . htmlspecialchars($e->getMessage());
    echo "</div>";
    
    echo "<h3>Troubleshooting</h3>";
    echo "<p>The error suggests there's an issue with the .env file format. Common issues:</p>";
    echo "<ul>";
    echo "<li>Values with spaces need to be quoted</li>";
    echo "<li>No spaces around the = sign</li>";
    echo "<li>Empty values should be empty quotes \"\"</li>";
    echo "</ul>";
}

echo "<h3>Database Connection Test</h3>";
try {
    require_once '../config/db.php';
    echo "<div style='color: green; background: #d4edda; padding: 10px; border-radius: 5px;'>";
    echo "✅ Database connection successful!";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='color: red; background: #f8d7da; padding: 10px; border-radius: 5px;'>";
    echo "❌ Database connection failed: " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "<h3>Quick Links</h3>";
echo "<a href='../../../frontend/index.php' style='margin-right: 15px; padding: 10px; background: #0758aa; color: white; text-decoration: none; border-radius: 4px;'>🔑 Try Login Again</a>";
echo "<a href='comprehensive_test.html' style='margin-right: 15px; padding: 10px; background: #28a745; color: white; text-decoration: none; border-radius: 4px;'>🧪 Run Tests</a>";
?>