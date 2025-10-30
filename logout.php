<?php
session_start();
session_destroy();
header("Location: Loginpage.php");
exit();
?>