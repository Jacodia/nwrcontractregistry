<?php
// ============================================
// Contract.php
// Handles contract CRUD + email notifications
// ============================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Require Composer autoload for PHPMailer
require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

class Contract
{
    private $pdo;
    private $table = 'contracts';

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // --------------------
    // CRUD Methods
    // --------------------

    // Only one getAllContracts() — includes manager_email and manager_name
    public function getAllContracts()
    {
        $sql = "SELECT c.*, u.email AS manager_email, u.username AS manager_name
                FROM {$this->table} c
                INNER JOIN users u ON c.manager_id = u.userid
                ORDER BY c.expiryDate ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll()
    {
        $sql = "SELECT contractid, parties, typeOfContract, duration, description, filepath, expiryDate, reviewByDate, contractValue, manager_id 
                FROM {$this->table}";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE contractid = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data, $userid)
    {
        $fields = [];
        $placeholders = [];
        $values = [];

        foreach (['parties', 'typeOfContract', 'duration', 'contractValue', 'description', 'expiryDate', 'reviewByDate', 'filepath'] as $col) {
            if (!empty($data[$col])) {
                $fields[] = $col;
                $placeholders[] = '?';
                $values[] = $data[$col];
            }
        }

        // Add manager_id
        $fields[] = "manager_id";
        $placeholders[] = "?";
        $values[] = $userid;

        $sql = "INSERT INTO {$this->table} (" . implode(", ", $fields) . ")
                VALUES (" . implode(", ", $placeholders) . ")";
        $stmt = $this->pdo->prepare($sql);

        if ($stmt->execute($values)) {
            return $this->pdo->lastInsertId();
        }
        error_log("Creating contract for user $userid with data: " . json_encode($data));
        return false;
    }

    public function update($id, $data, $userid)
    {
        $fields = [];
        $values = [];

        foreach (['parties', 'typeOfContract', 'duration', 'contractValue', 'description', 'expiryDate', 'reviewByDate', 'filepath'] as $col) {
            if (isset($data[$col])) {
                $fields[] = "$col = ?";
                $values[] = $data[$col];
            }
        }

        if (empty($fields)) return false;

        $values[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(", ", $fields) . " WHERE contractid = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE contractid = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    // ------------------------------------------------
    // Email Notification Methods
    // ------------------------------------------------
    public function sendExpiryNotifications()
    {
        $this->checkAndSend(90, 'weekly');  // 3 months
        $this->checkAndSend(60, 'twice');   // 2 months
        $this->checkAndSend(30, 'daily');   // 1 month
    }

    private function checkAndSend($days, $frequency)
    {
        if (!$this->shouldSend($frequency)) return;

        $sql = "SELECT c.*, u.email AS manager_email, u.receive_notifications
                FROM {$this->table} c
                INNER JOIN users u ON c.manager_id = u.userid
                WHERE u.receive_notifications = 1
                AND c.expiryDate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['days' => $days]);
        $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($contracts as $contract) {
            $recipientEmail = $contract['manager_email'];
            if (filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                $this->sendEmailNotification(
                    $recipientEmail,
                    $contract['typeOfContract'],
                    $contract['expiryDate']
                );
            }
        }
    }

    private function shouldSend($frequency)
    {
        $day = date('N'); // 1=Monday, 7=Sunday
        if ($frequency === 'weekly') return $day == 1;
        if ($frequency === 'twice') return ($day == 1 || $day == 4);
        if ($frequency === 'daily') return true;
        return false;
    }

    // ------------------------------------------------
    // Send Email
    // ------------------------------------------------
    public function sendEmailNotification($recipientEmail, $contractType, $expiryDate)
    {
        $mail = new PHPMailer(true);

        try {

            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'] ?? getenv('SMTP_HOST') ?? 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER'] ?? $_ENV['SMTP_FROM_EMAIL'] ?? getenv('SMTP_USER') ?? getenv('SMTP_FROM_EMAIL') ?? '';
            $mail->Password   = $_ENV['SMTP_PASSWORD'] ?? getenv('SMTP_PASSWORD') ?? '';
            $mail->SMTPSecure = $_ENV['SMTP_SECURE'] ?? getenv('SMTP_SECURE') ?? PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = intval($_ENV['SMTP_PORT'] ?? getenv('SMTP_PORT') ?? 587);

            $fromEmail = $_ENV['SMTP_FROM_EMAIL'] ?? getenv('SMTP_FROM_EMAIL') ?? $mail->Username;
            $fromName  = $_ENV['SMTP_FROM_NAME'] ?? getenv('SMTP_FROM_NAME') ?? 'Contract Registry System';

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($recipientEmail);

            $mail->isHTML(false);
            $mail->Subject = 'Contract Expiry Notification';
            $mail->Body = "Hello,\n\nThe contract of '{$contractType}' is set to expire on {$expiryDate}.\nPlease take necessary action before it expires.\n\nThank you.\nContract Registry System";

            $mail->send();
            return true;
        } catch (Exception $e) {
            // Log error without exposing sensitive details
            error_log("Mailer Error for {$recipientEmail}: " . $e->getMessage());
            return false;
        }
    }
}
