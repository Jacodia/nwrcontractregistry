<?php
class User {
    private $pdo;
    private $table = 'users';

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        $stmt = $this->pdo->prepare("
            SELECT userid, username, email, role, 
                   DATE_FORMAT(created_at, '%Y-%m-%d') as created_at
            FROM users 
            ORDER BY role DESC, username ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateRole($userId, $newRole) {
        $stmt = $this->pdo->prepare("UPDATE users SET role = ? WHERE userid = ?");
        return $stmt->execute([$newRole, $userId]);
    }

    public function delete($userId) {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE userid = ?");
        return $stmt->execute([$userId]);
    }

    public function getById($userId) {
        $stmt = $this->pdo->prepare("SELECT userid, username, email, role FROM users WHERE userid = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getStats() {
        $stmt = $this->pdo->prepare("
            SELECT role, COUNT(*) as count
            FROM users 
            GROUP BY role
            ORDER BY FIELD(role, 'admin', 'manager', 'user')
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
