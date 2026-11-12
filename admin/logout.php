<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    unset($_SESSION['admin_id']);
    session_regenerate_id(true);
    flash('success', 'Anda telah keluar dari Panel Admin.');
}

redirect('login.php');
