<?php
require_once 'includes/functions.php';
unset($_SESSION['user_id']); session_regenerate_id(true); redirect('index.php');
