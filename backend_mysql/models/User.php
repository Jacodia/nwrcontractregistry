<?php
require_once __DIR__ . '/../config/db.php';

class User {
    private $pdo;

    public function __construct() {
        $this->pdo = get_db_connection();
    }

    public function findByUsername($username) {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        return $stmt->fetch();
    }

    // For completeness: create a user (password should be hashed before storing)
    public function create($username, $password_plain) {
        $hash = password_hash($password_plain, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare('INSERT INTO users (username, password) VALUES (:username, :password)');
        $stmt->execute([':username' => $username, ':password' => $hash]);
        return $this->pdo->lastInsertId();
    }
}

?>
