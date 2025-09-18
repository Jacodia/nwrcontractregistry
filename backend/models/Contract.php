<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer files
require __DIR__ . '/../PHPMailer/PHPMailer.php';
require __DIR__ . '/../PHPMailer/SMTP.php';
require __DIR__ . '/../PHPMailer/Exception.php';

class Contract
{
    private $pdo;
    private $table = 'contracts';
    private $logTable = 'notification_log'; // to track sent emails

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->createNotificationLogTable();
    }

    // --------------------
    // CRUD Methods (unchanged)
    // --------------------
    public function getAllContracts()
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} ORDER BY expiryDate ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $fields = [];
        $placeholders = [];
        $values = [];

        foreach (['parties','typeOfContract','duration','contractValue','description','expiryDate','reviewByDate','manager_id'] as $col) {
            if (isset($data[$col])) {
                $fields[] = $col;
                $placeholders[] = '?';
                $values[] = $data[$col];
            }
        }

        if (!empty($data['filepath'])) {
            $fields[] = "filepath";
            $placeholders[] = "?";
            $values[] = $data['filepath'];
        }

        $sql = "INSERT INTO contracts (" . implode(", ", $fields) . ")
                VALUES (" . implode(", ", $placeholders) . ")";
        $stmt = $this->pdo->prepare($sql);
        if ($stmt->execute($values)) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }

    // --------------------
    // Email Notification Methods
    // --------------------

    // Send notifications for all managers (for cron job)
    public function sendExpiryNotifications()
    {
        $this->checkAndSend(90, 'weekly');  // 3 months
        $this->checkAndSend(60, 'twice');   // 2 months
        $this->checkAndSend(30, 'daily');   // 1 month
    }

    // Send notifications only for a specific manager (on login)
    public function sendExpiryNotificationsForManager($managerId)
    {
        $sql = "SELECT c.*, u.email AS manager_email
                FROM {$this->table} c
                INNER JOIN users u ON c.manager_id = u.userid
                WHERE c.manager_id = :manager_id
                  AND c.expiryDate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['manager_id' => $managerId]);
        $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($contracts as $contract) {
            $recipientEmail = $contract['manager_email'];
            if (filter_var($recipientEmail, FILTER_VALIDATE_EMAIL) && !$this->hasSentNotification($contract['contractid'], $recipientEmail)) {
                $this->sendEmailNotification($recipientEmail, $contract['typeOfContract'], $contract['expiryDate']);
                $this->logNotification($contract['contractid'], $recipientEmail);
            }
        }
    }

    private function checkAndSend($days, $frequency)
    {
        $sql = "SELECT c.*, u.email AS manager_email
                FROM {$this->table} c
                INNER JOIN users u ON c.manager_id = u.userid
                WHERE c.expiryDate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['days' => $days]);
        $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($contracts as $contract) {
            if (!$this->shouldSend($frequency)) continue;

            $recipientEmail = $contract['manager_email'];
            if (filter_var($recipientEmail, FILTER_VALIDATE_EMAIL) && !$this->hasSentNotification($contract['contractid'], $recipientEmail)) {
                $this->sendEmailNotification($recipientEmail, $contract['typeOfContract'], $contract['expiryDate']);
                $this->logNotification($contract['contractid'], $recipientEmail);
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

    private function sendEmailNotification($recipientEmail, $contractType, $expiryDate)
    {
        $mail = new PHPMailer(true);
        try {
            $senderEmail = getenv('SMTP_USER') ?: 'dynamic_email@example.com';
            $senderName  = 'Contract Registry';

            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $senderEmail;
            $mail->Password = getenv('SMTP_PASS') ?: 'YOUR_APP_PASSWORD_HERE';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom($senderEmail, $senderName);
            $mail->addAddress($recipientEmail);

            $mail->isHTML(false);
            $mail->Subject = 'Contract Expiry Notification';
            $mail->Body = "Hello,\n\nA contract of type '{$contractType}' is expiring on {$expiryDate}.\nPlease take necessary action.\n\nThank you.";

            $mail->send();
        } catch (Exception $e) {
            error_log("Mailer Error: {$e->getMessage()}");
        }
    }

    // --------------------
    // Notification Logging (prevents duplicates)
    // --------------------
    private function createNotificationLogTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->logTable} (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    contract_id INT UNSIGNED NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_notification (contract_id, email)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->pdo->exec($sql);
    }

    private function hasSentNotification($contractId, $email)
    {
        $stmt = $this->pdo->prepare("SELECT id FROM {$this->logTable} WHERE contract_id = ? AND email = ?");
        $stmt->execute([$contractId, $email]);
        return $stmt->fetch() ? true : false;
    }

    private function logNotification($contractId, $email)
    {
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO {$this->logTable} (contract_id, email) VALUES (?, ?)");
        $stmt->execute([$contractId, $email]);
    }
}
