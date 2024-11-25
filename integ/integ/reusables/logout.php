<?php
session_start();

session_unset();
session_destroy();
header("Location: ../loginsignup/login_form.php");
exit();