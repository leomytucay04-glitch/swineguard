<?php

session_start();

unset($_SESSION['admin']);
 unset($_SESSION['last_activity']);

header("Location: ../index.php");
