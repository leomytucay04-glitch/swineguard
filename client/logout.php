<?php

session_start();

unset($_SESSION['user']);
unset($_SESSION['last_activity1']);

header("Location: ../login.php");
