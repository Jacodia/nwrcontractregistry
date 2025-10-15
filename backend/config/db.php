<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

// Read environment variables with fallback to getenv() for compatibility
$dbType = strtolower($_ENV['DB_TYPE'] ?? getenv('DB_TYPE') ?? 'mysql'); // 'mysql' or 'sqlsrv'
$host = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
$port = $_ENV['DB_PORT'] ?? getenv('DB_PORT');
$instance = $_ENV['DB_INSTANCE'] ?? getenv('DB_INSTANCE');
$user = $_ENV['DB_USER'] ?? getenv('DB_USER');
$pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS');
$db   = $_ENV['DB_NAME'] ?? getenv('DB_NAME');

// Basic validation to fail fast if required values are missing
$missing = [];
if (empty($host)) { $missing[] = 'DB_HOST'; }
if (empty($user)) { $missing[] = 'DB_USER'; }
if ($pass === null) { $missing[] = 'DB_PASS'; } // allow empty password but not null
if (empty($db))   { $missing[] = 'DB_NAME'; }

if ($missing) {
    http_response_code(500);
    header('Content-Type: application/json');
    $msg = 'Missing required environment variables: ' . implode(', ', $missing);
    // Log the problem for operators but return a generic message to clients
    error_log(date('c') . " - DB config error: $msg" . PHP_EOL, 3, __DIR__ . '/../logs/db_error.log');
    echo json_encode(['error' => 'Internal Server Error']);
    exit;
}

// Use a secure DSN and stronger PDO options. Support MySQL (default) and SQL Server (sqlsrv).
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    if ($dbType === 'sqlsrv') {
        // Build Server part: support host, port and instance
        $server = $host;
        // If explicit port provided, SQL Server expects server as host,port
        if (!empty($port) && is_numeric($port)) {
            $server = "$host,$port";
        } elseif (!empty($instance)) {
            // Instance name uses backslash escaping
            $server = $host . '\\' . $instance;
        }

        // Handle encryption/trust settings (ODBC Driver 18 defaults to Encrypt=yes)
        $appEnv = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? 'production';
        $trustCert = $_ENV['DB_TRUST_SERVER_CERT'] ?? getenv('DB_TRUST_SERVER_CERT');
        $encrypt = $_ENV['DB_ENCRYPT'] ?? getenv('DB_ENCRYPT');

        // If DB_TRUST_SERVER_CERT explicitly set to true-ish, honor it.
        $trustFlag = '';
        if ($trustCert !== null) {
            $trustFlag = (in_array(strtolower($trustCert), ['1','true','yes'], true) ? ';TrustServerCertificate=Yes' : ';TrustServerCertificate=No');
        } else {
            // Default: in development allow trusting server cert to avoid driver 18 TLS issues
            if ($appEnv === 'development') {
                $trustFlag = ';TrustServerCertificate=Yes';
            }
        }

        // Allow explicit encrypt setting (Encrypt=Yes/No)
        $encryptFlag = '';
        if ($encrypt !== null) {
            $encryptFlag = ';Encrypt=' . (in_array(strtolower($encrypt), ['1','true','yes'], true) ? 'Yes' : 'No');
        }

        $dsn = "sqlsrv:Server={$server};Database={$db}" . $encryptFlag . $trustFlag;
        $pdo = new PDO($dsn, $user, $pass, $options);
    } else {
        // default: mysql
        // If port is provided, include it in DSN
        $portPart = (!empty($port) ? ";port={$port}" : '');
        $dsn = "mysql:host={$host}{$portPart};dbname={$db};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, $options);
    }
} catch (PDOException $e) {
    // Log the full error for operators, but avoid leaking details to clients
    $logFile = __DIR__ . '/../logs/db_error.log';
    $message = date('c') . ' - DB Connection failed: ' . $e->getMessage() . PHP_EOL;
    @error_log($message, 3, $logFile);

    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Internal Server Error']);
    exit;
}

// Export $pdo to the rest of the application
// (other files include this config to get access to $pdo)
?>