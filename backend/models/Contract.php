<?php
// ============================================
// PHPMailer Setup (Email Notification Part)
// ============================================
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer files
require __DIR__ . '/../PHPMailer/PHPMailer.php';
require __DIR__ . '/../PHPMailer/SMTP.php';
require __DIR__ . '/../PHPMailer/Exception.php';

// ============================================
// Contract Class (Original Contract.php Part)
// ============================================
class Contract
{
    private $pdo;
    private $table = 'contracts';

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // --------------------
    // Original CRUD Methods
    // --------------------
    public function getAllContracts()
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} ORDER BY expiryDate ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll()
    {
        $sql = "SELECT contractid, parties, typeOfContract, duration, description, filepath, expiryDate, reviewByDate, contractValue 
                FROM {$this->table}";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE contractid = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
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

        $sql = "INSERT INTO contracts (" . implode(", ", $fields) . ")
                VALUES (" . implode(", ", $placeholders) . ")";

        $stmt = $this->pdo->prepare($sql);
        if ($stmt->execute($values)) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }

    public function update($id, $data)
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
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

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

    // ============================================
    // Email Notification Methods (PHPMailer Part)
    // ============================================
    public function sendExpiryNotifications()
    {
        $this->checkAndSend(90, 'weekly');  // 3 months
        $this->checkAndSend(60, 'twice');   // 2 months
        $this->checkAndSend(30, 'daily');   // 1 month
    }

    private function checkAndSend($days, $frequency)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE expiryDate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['days' => $days]);
        $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($contracts as $contract) {
            if (!$this->shouldSend($frequency)) continue;

            // parties column assumed to store email
            if (filter_var($contract['parties'], FILTER_VALIDATE_EMAIL)) {
                $this->sendEmailNotification(
                    $contract['parties'], 
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

    private function sendEmailNotification($recipientEmail, $contractType, $expiryDate)
    {
        $mail = new PHPMailer(true);

        try {
            $senderEmail = getenv('SMTP_USER') ?: 'dynamic_email@example.com'; // Use env variable
            $senderName  = 'Contract Registry';

            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $senderEmail;
            $mail->Password = getenv('SMTP_PASS') ?: 'YOUR_APP_PASSWORD_HERE'; // Use env variable
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom($senderEmail, $senderName);
            $mail->addAddress($recipientEmail);

            $mail->isHTML(false);
            $mail->Subject = 'Contract Expiry Notification';
            $mail->Body = "Hello,\n\nYour contract titled '{$contractType}' is expiring on {$expiryDate}.\nPlease take necessary action.\n\nThank you.";

            $mail->send();
        } catch (Exception $e) {
            error_log("Mailer Error: {$e->getMessage()}");
        }
    } 
     public function sendEmailNotification1($recipient, $contractType, $expiryDate) {
        $subject = "Contract Expiry Notification";
        $message = "Dear user,\n\nYour contract of type '{$contractType}' is expiring on {$expiryDate}.\n\nRegards,\nContract Registry";
        $headers = "From: no-reply@yourdomain.com\r\n";
        // Use mail() function for sending email
        mail($recipient, $subject, $message, $headers);
    }
}