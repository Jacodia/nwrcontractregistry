<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Require Composer autoload or PHPMailer files
require 'vendor/autoload.php'; // Make sure you installed PHPMailer via Composer

class ContractNotifier {

    private $pdo;

    public function __construct() {
        // Database connection (update credentials)
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
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public function checkAndSend($days, $frequency) {
        $today = new DateTime();
        $targetDate = (clone $today)->modify("+$days days");

        if (!$this->shouldSend($frequency, $today)) return;

        // Fetch contracts expiring in $days
        $stmt = $this->pdo->prepare("
            SELECT c.contract_name, c.expiry_date, u.email AS manager_email
            FROM contracts c
            INNER JOIN users u ON c.manager_id = u.userid
            WHERE DATEDIFF(c.expiry_date, :today) = :days
              AND u.receive_notifications = 1
        ");
        $stmt->execute(['today' => $today->format('Y-m-d'), 'days' => $days]);
        $contracts = $stmt->fetchAll();

        foreach ($contracts as $contract) {
            $this->sendEmail(
                $contract['manager_email'],
                "Contract Expiry Notification",
                "The contract '{$contract['contract_name']}' will expire on {$contract['expiry_date']}."
            );
        }
    }

    private function shouldSend($frequency, $today) {
        $weekday = $today->format('N'); // 1=Monday, 7=Sunday
        switch ($frequency) {
            case 'daily':
                return true; // PHPMailer sends whenever the cron runs
            case 'weekly':
                return $weekday == 1; // Monday
            case 'twice':
                return $weekday == 2 || $weekday == 5; // Tuesday & Friday
            default:
                return false;
        }
    }

    private function sendEmail($to, $subject, $message) {
        $mail = new PHPMailer(true);

        try {
            // SMTP settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.example.com'; // Replace with your SMTP host
            $mail->SMTPAuth   = true;
            $mail->Username   = 'your_email@example.com'; // SMTP username
            $mail->Password   = 'lkmkivxthjizqojc';         // SMTP password
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('no-reply@nwr.com', 'NWR Contracts');
            $mail->addAddress($to);

            // Content
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body    = $message;

            $mail->send();
            echo "Email sent to $to\n";
        } catch (Exception $e) {
            echo "Email could not be sent to $to. Mailer Error: {$mail->ErrorInfo}\n";
        }
    }
}
?>
