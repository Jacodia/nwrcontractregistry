<?php
require_once 'ContractNotifier.php';

class DebugContractNotifier extends ContractNotifier {
    
    public function checkAndSendDebug($days, $frequency) {
        $today = new DateTime();
        
        echo "=== DEBUG: Checking contracts expiring in $days days ===\n";
        if (!$this->shouldSend($frequency, $today)) {
            echo "Should not send today based on frequency: $frequency\n";
            return;
        }

        echo "Should send today based on frequency: $frequency\n";

        // Create connection (copied from parent constructor)
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

        $pdo = new PDO($dsn, $user, $pass, $options);

        // Fetch contracts expiring in $days
        $stmt = $pdo->prepare("
            SELECT c.parties, c.expiryDate, u.email AS manager_email
            FROM contracts c
            INNER JOIN users u ON c.manager_id = u.userid
            WHERE DATEDIFF(c.expiryDate, :today) = :days
        ");

        $stmt->execute([
            'today' => $today->format('Y-m-d'), 
            'days' => $days
        ]);
        $contracts = $stmt->fetchAll();

        echo "Found " . count($contracts) . " contracts expiring in $days days:\n";
        
        foreach ($contracts as $i => $contract) {
            echo "  Contract " . ($i + 1) . ":\n";
            echo "    Parties: {$contract['parties']}\n";
            echo "    Expiry: {$contract['expiryDate']}\n";
            echo "    Manager Email: {$contract['manager_email']}\n";
            echo "    Sending email...\n";
            
            $this->sendEmailDebug(
                $contract['manager_email'],
                "Contract Expiry Notification",
                "The contract '{$contract['parties']}' will expire in $days days on {$contract['expiryDate']}.
                Please take the necessary actions."
            );
        }
    }
    
    private function sendEmailDebug($to, $subject, $message) {
        echo "    Attempting to send email to: $to\n";
        
        // Call the parent's sendEmail method but with debug info
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // Gmail SMTP settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'uraniathomas23@gmail.com';
            $mail->Password   = 'lkmkivxthjizqojc';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;
            $mail->Timeout    = 10; // Set a timeout
            
            // Recipients
            $mail->setFrom('uraniathomas23@gmail.com', 'NWR Contracts');
            $mail->addAddress($to);

            // Email content
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body    = $message;

            $mail->send();
            echo "    ✅ Email sent successfully to $to\n";
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            echo "    ❌ Email failed to $to. Error: {$mail->ErrorInfo}\n";
            echo "    Exception: " . $e->getMessage() . "\n";
        } catch (Exception $e) {
            echo "    ❌ General error sending to $to: " . $e->getMessage() . "\n";
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
                return $weekday == 1 || $weekday == 3 || $weekday == 4; // Monday & Thursday
            default:
                return false;
        }
    }
}

echo "=== TESTING WITH DEBUG OUTPUT ===\n";
$debugNotifier = new DebugContractNotifier();
$debugNotifier->checkAndSendDebug(5, 'daily');
echo "=== DONE ===\n";
?>