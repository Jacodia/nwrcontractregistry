<?php
$targetDir = "uploads/";
$targetFile = $targetDir . basename($_FILES["contractFile"]["name"]);
$uploadOk = 1;
$fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));



// Check if file already exists
if (file_exists($targetFile)) {
    echo "Sorry, file already exists.";
    $uploadOk = 0;
}

// Check file size (5MB maximum)
if ($_FILES["contractFile"]["size"] > 5000000) {
    echo "Sorry, your file is too large.";
    $uploadOk = 0;
}

// Allow certain file formats
if ($fileType != "pdf" && $fileType != "doc" && $fileType != "docx") {
    echo "Sorry, only PDF, DOC & DOCX files are allowed.";
    $uploadOk = 0;
}

// Check if $uploadOk is set to 0 by an error
if ($uploadOk == 0) {
    echo "Sorry, your file was not uploaded.";
// if everything is ok, try to upload file
} else {
    if (move_uploaded_file($_FILES["contractFile"]["tmp_name"], $targetFile)) {
        echo "The file " . htmlspecialchars(basename($_FILES["contractFile"]["name"])) . " has been uploaded.";

        // Save file path to database
        require_once 'config/db.php';
        $stmt = $pdo->prepare("UPDATE contracts SET filepath = :filepath WHERE contractid = :contractid");
        $stmt->bindParam(':filepath', $targetFile);
        $stmt->bindParam(':contractid', $_POST['contractid']);
        $stmt->execute();

    } else {
        echo "Sorry, there was an error uploading your file.";
    }
}

?>