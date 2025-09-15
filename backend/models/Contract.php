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
        $sql = "SELECT contractid, parties, typeOfContract, duration, description, expiryDate, reviewByDate, contractValue 
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
    public function create($data)
    {
        $sql = "INSERT INTO {$this->table} 
            (parties, typeOfContract, duration, description, expiryDate, reviewByDate, contractValue, filepath) 
            VALUES (:parties, :typeOfContract, :duration, :description, :expiryDate, :reviewByDate, :contractValue, :filepath)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    // Update an existing contract
    public function update($id, $data)
    {
        $data['id'] = $id;
        $sql = "UPDATE {$this->table} SET 
                parties = :parties, 
                typeOfContract = :typeOfContract, 
                duration = :duration,
                description = :description, 
                expiryDate = :expiryDate, 
                reviewByDate = :reviewByDate, 
                contractValue = :contractValue,
                filepath = :filepath
                WHERE contractid = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    // Delete a contract
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE contractid = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
