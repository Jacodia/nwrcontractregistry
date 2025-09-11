<?php
class Contract {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Get all contracts
    public function getAll() {
        $stmt = $this->pdo->query("
            SELECT contractid, parties, typeOfContract, description, expiryDate, reviewByDate, contractValue 
            FROM contracts 
            ORDER BY expiryDate ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get one contract by ID (optional for later use)
    public function getById($id) {
        $stmt = $this->pdo->prepare("
            SELECT contractid, parties, typeOfContract, description, expiryDate, reviewByDate, contractValue 
            FROM contracts 
            WHERE contractid = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
