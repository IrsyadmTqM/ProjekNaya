<?php
require __DIR__ . '/../includes/bootstrap.php';
start_session('anggota');
logout();
redirect('login.php');
