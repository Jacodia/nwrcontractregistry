<?php
// ============================================
// Contract.php
// Handles contract CRUD + email notifications
// ============================================

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

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // --------------------
    // CRUD Methods
    // --------------------
    public function getAllContracts()
    {
        // Updated to fetch manager email
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

        foreach (['parties','typeOfContract','duration','contractValue','description','expiryDate','reviewByDate'] as $col) {
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

        // Add manager_id
        $fields[] = "manager_id";
        $placeholders[] = "?";
        $values[] = $userid;

        $sql = "INSERT INTO contracts (" . implode(", ", $fields) . ")
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

        foreach (['parties','typeOfContract','duration','contractValue','description','expiryDate','reviewByDate'] as $col) {
            if (isset($data[$col])) {
                $fields[] = "$col = ?";
                $values[] = $data[$col];
            }
        }

        // File upload handling
        if (isset($_FILES['contractFile']) && $_FILES['contractFile']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $partiesName = isset($data['parties']) ? preg_replace('/[\/\\\\:*?"<>|]/', '_', $data['parties']) : 'contract';
            $extension = pathinfo($_FILES['contractFile']['name'], PATHINFO_EXTENSION);
            $newFileName = $partiesName . "_" . time() . "." . $extension;
            $fullPath = $uploadDir . $newFileName;

            if (move_uploaded_file($_FILES['contractFile']['tmp_name'], $fullPath)) {
                $fields[] = "filepath = ?";
                $values[] = 'uploads/' . $newFileName;
            }
        }

        if (empty($fields)) return false;

        $values[] = $id;
        $sql = "UPDATE contracts SET " . implode(", ", $fields) . " WHERE contractid = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE contractid = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    // --------------------
    // Email Notification Methods
    // --------------------
    public function sendExpiryNotifications()
    {
        $this->checkAndSend(90, 'weekly');  // 3 months
        $this->checkAndSend(60, 'twice');   // 2 months
        $this->checkAndSend(30, 'daily');   // 1 month
    }

    private function checkAndSend($days, $frequency)
    {
        if (!$this->shouldSend($frequency)) return;

        $sql = "SELECT c.*, u.email AS manager_email
                FROM {$this->table} c
                INNER JOIN users u ON c.manager_id = u.userid
                WHERE c.expiryDate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)";
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

    // Public so it can be tested directly
    public function sendEmailNotification($recipientEmail, $contractType, $expiryDate)
    {
        $mail = new PHPMailer(true);

        try {
            // ===== Sender info =====
            $senderEmail    = 'uraniathomas23@gmail.com'; // Gmail sender
            $senderName     = 'Contract Registry';
            $senderPassword = 'lkmkivxthjizqojc';   // Gmail App Password

            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $senderEmail;
            $mail->Password   = $senderPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom($senderEmail, $senderName);
            $mail->addAddress($recipientEmail);

            $mail->isHTML(false);
            $mail->Subject = 'Contract Expiry Notification';
            $mail->Body    = "Hello,\n\nThe contract of '{$contractType}' is expiring on {$expiryDate}.\nPlease take necessary action.\n\nThank you.";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mailer Error for {$recipientEmail}: {$mail->ErrorInfo}");
            return false;
        }
    }
}
