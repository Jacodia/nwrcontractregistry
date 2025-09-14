<?php
session_start();
include 'db.php'; 
// Example: if you don’t yet track logged-in users with session, 
// set a default user_id for testing (like 1).
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

// Folder to save uploaded files
$targetDir = "uploads/";
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0777, true);
}

$fileName = basename($_FILES["contract_file"]["name"]);
$targetFilePath = $targetDir . $fileName;

// It will only allows PDF/DOC/DOCX
$fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
$allowedTypes = ["pdf", "doc", "docx"];

if (in_array($fileType, $allowedTypes)) {
    if (move_uploaded_file($_FILES["contract_file"]["tmp_name"], $targetFilePath)) {
        
        // Insert into DB
        // We need to change the contract to the correct table that we will create.
        $sql = "INSERT INTO contracts (user_id, file_name, file_path) 
                VALUES ('$user_id', '$fileName', '$targetFilePath')";
        
        if ($conn->query($sql) === TRUE) {
            echo "Contract uploaded successfully!";
        } else {
            echo " Database error: " . $conn->error;
        }

    } else {
        echo "File upload failed.";
    }
} else {
    echo "Invalid file type. Only PDF, DOC, DOCX allowed.";
}

$conn->close();
?>
