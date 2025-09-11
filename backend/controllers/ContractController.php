<?php
require_once __DIR__ . "/../models/Contract.php";

class ContractController {
    private $contract;

    public function __construct($pdo) {
        $this->contract = new Contract($pdo);
    }

    public function getContracts() {
        return $this->contract->getAll();
    }

    public function getContract($id) {
        return $this->contract->getById($id);
    }
}
