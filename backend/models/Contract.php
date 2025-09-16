<?php
class Contract
{
    private $pdo;
    private $table = 'contracts';

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Fetch all contracts
    public function getAllContracts()
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} ORDER BY expiryDate ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get all contracts
    public function getAll()
    {
        $sql = "SELECT contractid, parties, typeOfContract, duration, description, filepath, expiryDate, reviewByDate, contractValue 
                FROM {$this->table}";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get one contract by ID (optional for later use)
    public function getById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE contractid = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Add a new contract
    public function create($data) {
    $fields = [];
    $placeholders = [];
    $values = [];

    // Standard columns
    foreach (['parties','typeOfContract','duration','contractValue','description','expiryDate','reviewByDate'] as $col) {
        if (isset($data[$col])) {
            $fields[] = $col;
            $placeholders[] = '?';
            $values[] = $data[$col];
        }
    }

    // Optional file path
    if (!empty($data['filepath'])) {
        $fields[] = "filepath";
        $placeholders[] = "?";
        $values[] = $data['filepath'];
    }

    // Build SQL dynamically
    $sql = "INSERT INTO contracts (" . implode(", ", $fields) . ")
            VALUES (" . implode(", ", $placeholders) . ")";

    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute($values);
}



    public function update($id, $data) {
    $fields = [];
    $values = [];

    foreach (['parties','typeOfContract','duration','contractValue','description','expiryDate','reviewByDate'] as $col) {
        if (isset($data[$col])) {
            $fields[] = "$col = ?";
            $values[] = $data[$col];
        }
    }

    // Handle new uploaded file if present
    if (!empty($data['filepath'])) {
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Sanitize parties name
        $partiesName = isset($data['parties']) ? preg_replace('/[\/\\\\:*?"<>|]/', '_', $data['parties']) : 'contract';

        // Get file extension
        $extension = pathinfo($data['filepath'], PATHINFO_EXTENSION);

        // New filename
        $newFileName = $partiesName . "_" . time() . "." . $extension;

        $fullPath = $uploadDir . $newFileName;

        // Move the uploaded file
        if (move_uploaded_file($_FILES['contractFile']['tmp_name'], $fullPath)) {
            $fields[] = "filepath = ?";
            $values[] = 'uploads/' . $newFileName; // relative path
        }
    }

    if (empty($fields)) {
        return false; // nothing to update
    }

    $values[] = $id; // WHERE id = ?
    
    $sql = "UPDATE contracts SET " . implode(", ", $fields) . " WHERE contractid = ?";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute($values);
}


    // Delete a contract
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE contractid = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
