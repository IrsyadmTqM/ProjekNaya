<?php
require __DIR__ . '/../includes/bootstrap.php';
start_session('bendahara');
logout();
redirect('login.php');
