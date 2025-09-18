<?php


class ContractController {
    private $model;

    public function __construct($pdo) {
        require_once __DIR__ . '/../models/Contract.php';
        require_once __DIR__ . '/../config/auth.php';
        $this->model = new Contract($pdo);
    }

    // fecth all
    public function list() {
        return $this->model->getAll();
    }

    // Filter by contractid
    public function view($id) {
        return $this->model->getById($id);
    }

    // Create contract
    public function create($data) {
        $user = Auth::getCurrentUser();
        if (!$user) {
            throw new Exception("User not authenticated");
        }
        $userid = $user['userid'];
        $newId = $this->model->create($data, $userid);

        if ($newId) {
            return ['success' => true, 'contractid' => $newId];
        } else {
            return ['success' => false, 'error' => 'Contract creation failed'];
        }
    }

    // Update contract
    public function update($id, $data) {
        $user = Auth::getCurrentUser();
        if (!$user) {
            return ['success' => false, 'error' => 'User not logged in'];
        }

        $userid = $user['userid'];
        $success = $this->model->update($id, $data, $userid);

        return ['success' => $success];
    }

    // Delete contract
    public function delete($id) {
        return $this->model->delete($id);
    }
}
