<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Require Composer autoload
require '../vendor/autoload.php';
require_once '../vendor/phpmailer/phpmailer/src/Exception.php';
require_once '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '../vendor/phpmailer/phpmailer/src/SMTP.php';

date_default_timezone_set("Africa/Windhoek"); // adjust for timezone

class ContractNotifier {

    private $pdo;
    private $logFile;

    public function __construct() {
        // Setup logging
        $logDir = __DIR__ . '/logs';
        $this->logFile = $logDir . '/reminder_log.txt';

        // Create logs directory if it doesn't exist
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
            $this->logMessage("Created logs directory: $logDir");
        }

        // Log rotation to prevent huge files
        if (file_exists($this->logFile) && filesize($this->logFile) > 10 * 1024 * 1024) { // 10MB limit
            rename($this->logFile, $logDir . '/reminder_log_' . date('YmdHis') . '.txt');
            $this->logMessage("Rotated log file");
        }

        // Database connection
        $host = 'localhost';
        $db   = 'nwr_crdb';
        $user = 'root';
        $pass = '';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
            $this->logMessage("Database connection established successfully");
        } catch (PDOException $e) {
            $this->logMessage("Database connection failed: " . $e->getMessage());
            die("Database connection failed: " . $e->getMessage());
        }
    }

    private function logMessage($msg) {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->logFile, "$timestamp - $msg" . PHP_EOL, FILE_APPEND);
        echo "$msg\n"; // Also echo to console for immediate feedback
    }

    public function checkAndSend($daysCategory, $frequency) {
        $today = new DateTime();

        // Define day ranges based on category
        $ranges = [
            30 => ['min' => 1, 'max' => 30],   // 1-30 days left
            60 => ['min' => 31, 'max' => 60],  // 31-60 days left  
            90 => ['min' => 61, 'max' => 90]   // 61-90 days left
        ];

        if (!isset($ranges[$daysCategory])) {
            $this->logMessage("ERROR: Invalid days category: $daysCategory");
            return;
        }

        $range = $ranges[$daysCategory];
        $this->logMessage("Starting check for contracts expiring in {$range['min']}-{$range['max']} days (Category: $daysCategory, Frequency: $frequency)");
        
        if (!$this->shouldSend($frequency, $today)) {
            $this->logMessage("Skipping notifications today - frequency '$frequency' not scheduled for " . $today->format('l (N)'));
            return;
        }

        $this->logMessage("Proceeding with notifications for contracts expiring in {$range['min']}-{$range['max']} days");

        // Fetch contracts expiring in the range
        $stmt = $this->pdo->prepare("
            SELECT c.parties, c.expiryDate, u.email AS manager_email, u.username,
                   DATEDIFF(c.expiryDate, :today) as days_left
            FROM contracts c
            INNER JOIN users u ON c.manager_id = u.userid
            WHERE DATEDIFF(c.expiryDate, :today) BETWEEN :min_days AND :max_days
            ORDER BY c.expiryDate ASC
        ");

        $this->logMessage("Executing database query for contracts expiring in {$range['min']}-{$range['max']} days");

        $stmt->execute([
            'today' => $today->format('Y-m-d'), 
            'min_days' => $range['min'],
            'max_days' => $range['max']
        ]);
        $contracts = $stmt->fetchAll();

        $contractCount = count($contracts);
        $this->logMessage("Found $contractCount contracts expiring in {$range['min']}-{$range['max']} days");
        
        if ($contractCount == 0) {
            $this->logMessage("No contracts found in range, no emails to send");
            return;
        }

        foreach ($contracts as $contract) {
            $daysLeft = $contract['days_left'];
            $this->logMessage("Processing contract: '{$contract['parties']}' (expires in $daysLeft days) - Manager: {$contract['manager_email']}");
            
            $this->sendEmail(
                $contract['manager_email'],
                "Contract Expiry Notification for {$contract['parties']} - {$daysLeft} days remaining", // Subject
                "Hello, {$contract['username']}\r\n\r\nThe contract '{$contract['parties']}' will expire in {$daysLeft} days on {$contract['expiryDate']}. \r\nPlease take the necessary actions.\r\n\r\nRegards,\r\nNWR Contracts Registry System."
            );
        }
        
        $this->logMessage("Completed processing $contractCount contracts for category $daysCategory");
    }

    private function shouldSend($frequency, $today) {
        $weekday = $today->format('N'); // 1=Monday, 7=Sunday
        switch ($frequency) {
            case 'daily': // Monday to Friday only
                return $weekday >= 1 && $weekday <= 5; // Mon-Fri
            case 'weekly':
                return $weekday == 1 || $weekday == 3; // Monday & Wednesday
            case 'twice':
                return $weekday == 1 || $weekday == 3 || $weekday == 4; // Monday, Wednesday & Thursday
            default:
                return false;
        }
    }

    private function sendEmail($to, $subject, $message) {
        $mail = new PHPMailer(true);

        try {
            $this->logMessage("Attempting to send email to: $to");
            
            // Gmail SMTP settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'uraniathomas23@gmail.com'; // your Gmail
            $mail->Password   = 'lkmkivxthjizqojc';   // Gmail App Password
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;
            $mail->Timeout    = 20; // Set timeout to 20 seconds

            // Recipients
            $mail->setFrom('uraniathomas23@gmail.com', 'NWR Contracts');
            $mail->addAddress($to);

            // Email content
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body    = $message;

            $mail->send();
            $this->logMessage("✅ Email sent successfully to: $to");
            
            // Small delay to avoid rate limiting
            sleep(1);
            
        } catch (Exception $e) {
            $this->logMessage("❌ Failed to send email to $to - Error: {$mail->ErrorInfo}");
            $this->logMessage("Exception details: " . $e->getMessage());
        }
    }
}
?>
