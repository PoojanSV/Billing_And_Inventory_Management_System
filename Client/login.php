<?php
session_start();

$file = "../Admin/users.json";

if (!file_exists($file)) {
    die("users.json not found.");
}

$users = json_decode(file_get_contents($file), true);

if (!$users) {
    $users = [];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    foreach ($users as $user) {

        if (
            $user["username"] == $username &&
            password_verify($password, $user["password"])
        ) {

            $_SESSION["user"] = $username;

            header("Location: dashboard.php");
            exit();
        }
    }

    echo "User Not Found";
    exit();
}

   echo "User Not Found";
exit();
?>