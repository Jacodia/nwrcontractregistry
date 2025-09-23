<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Require Composer autoload
require 'vendor/autoload.php';

class ContractNotifier {

    private $pdo;

    public function __construct() {
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
            SELECT c.parties, c.expiryDate, u.email AS manager_email
            FROM contracts c
            INNER JOIN users u ON c.manager_id = u.userid
            WHERE DATEDIFF(c.expiryDate, :today) = :days
              AND u.receive_notifications = 1
        ");
        $stmt->execute([
            'today' => $today->format('Y-m-d'), 
            'days' => $days
        ]);
        $contracts = $stmt->fetchAll();

        foreach ($contracts as $contract) {
            $this->sendEmail(
                $contract['manager_email'],
                "Contract Expiry Notification",
                "The contract '{$contract['parties']}' will expire on {$contract['expiryDate']}."
            );
        }
    }

    private function shouldSend($frequency, $today) {
        $weekday = $today->format('N'); // 1=Monday, 7=Sunday
        switch ($frequency) {
            case 'daily':
                return true;
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
            // Gmail SMTP settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'uraniathomas23@gmail.com'; // your Gmail
            $mail->Password   = 'lkmkivxthjizqojc';   // Gmail App Password
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('uraniathomas23@gmail.com', 'NWR Contracts');
            $mail->addAddress($to);

            // Email content
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
