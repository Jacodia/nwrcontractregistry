<?php
class ContractTypeController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM contractTypes ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($name, $userId) {
        $stmt = $this->pdo->prepare("INSERT INTO contractTypes (name, createdBy) VALUES (?, ?)");
        $stmt->execute([$name, $userId]);
        return ['success' => true, 'id' => $this->pdo->lastInsertId()];
    }
}

?>
