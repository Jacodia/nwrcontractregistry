<?php
// backend/logout.php - Logout handler
session_start();
require_once 'config/auth.php';

Auth::logout();
header("Location: ../frontend/index.php");
exit();
?>