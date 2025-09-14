<?php
// database connection
include 'db.php'; 

//  data from form
$user = $_POST['email'];
$pass = $_POST['password'];

// Query to check user
$sql = "SELECT * FROM users WHERE username='$user' AND password='$pass'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    header("Location: manage_contract.html");
    exit();
} else {
    echo "Invalid username or password!";
}

$conn->close();
?>
