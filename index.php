<?php
//user
session_start();

if(isset($_SESSION['user'])){
    header("Location: client/dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>

<title>User Login</title>

<style>

body{
	
    margin:0;
    padding:0;
    font-family:Arial;
    background:url("img/bg3.png") center center / cover no-repeat fixed;
}

.login-box{
    width:350px;
    margin-top:15%;
	margin-left:auto;
	margin-right:auto;
    background:#fff;
    padding:30px;
    border-radius:10px;
    box-shadow:0 0 10px rgba(0,0,0,.2);
}

h2{
    text-align:center;
    margin-bottom:20px;
}

input{
    width:100%;
    padding:10px;
    margin:10px 0;
    box-sizing:border-box;
}

button{
    width:100%;
    padding:10px;
    background:#00B040;
    color:white;
    border:none;
    cursor:pointer;
    font-size:16px;
	border-radius:5px;
}

button:hover{
    background:#15803D;
}

.error{
    color:red;
    text-align:center;
    margin-bottom:10px;
}


</style>

</head>

<body>

<div class="login-box">

<h2>BIMS Login</h2>

<?php
if(isset($_GET['error'])){
    echo "<div class='error'>Invalid Username or Password</div>";
}
?>

<form action="client/login.php" method="post">

<input
type="text"
name="username"
placeholder="Username"
required>

<input
type="password"
name="password"
placeholder="Password"
required>

<button type="submit">
Login
</button>

</form>

</div>

</body>
</html>