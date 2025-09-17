<?php
require_once __DIR__ . '/../models/User.php';

class UserController {
    private $userModel;

    public function __construct($pdo) {
        $this->userModel = new User($pdo);
    }

    public function getAllUsers() {
        return $this->userModel->getAll();
    }

    public function updateUserRole($userId, $newRole) {
        return $this->userModel->updateRole($userId, $newRole);
    }

    public function deleteUser($userId) {
        return $this->userModel->delete($userId);
    }

    public function getUserById($userId) {
        return $this->userModel->getById($userId);
    }

    public function getUserStats() {
        return $this->userModel->getStats();
    }
}
