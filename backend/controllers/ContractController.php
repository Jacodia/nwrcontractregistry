<?php
class ContractController {
    private $model;

    public function __construct($pdo) {
        require_once __DIR__ . '/../models/Contract.php';
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
        return $this->model->create($data);
    }

    // Update contract
    public function update($id, $data) {
        return $this->model->update($id, $data);
    }

    // Delete contract
    public function delete($id) {
        return $this->model->delete($id);
    }
}
