<?php
require_once __DIR__ . '/../config/db.php';

class Contract {
    private $pdo;

    public function __construct() {
        $this->pdo = get_db_connection();
    }

    public function all() {
        $stmt = $this->pdo->query('SELECT * FROM contracts ORDER BY id DESC');
        return $stmt->fetchAll();
    }

    public function find($id) {
        $stmt = $this->pdo->prepare('SELECT * FROM contracts WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $stmt = $this->pdo->prepare('INSERT INTO contracts (title, party, start_date, end_date, notes) VALUES (:title, :party, :start_date, :end_date, :notes)');
        $stmt->execute([
            ':title' => $data['title'] ?? null,
            ':party' => $data['party'] ?? null,
            ':start_date' => $data['start_date'] ?? null,
            ':end_date' => $data['end_date'] ?? null,
            ':notes' => $data['notes'] ?? null,
        ]);
        return $this->pdo->lastInsertId();
    }
}

?>
