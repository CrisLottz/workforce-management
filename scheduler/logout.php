<?php
session_start();
unset($_SESSION['scheduler_email']);
session_destroy();
header('Location: login.php');
exit;