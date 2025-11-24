<?php
session_start();
require_once __DIR__ . '/users.php';


function handle_login() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../public/login.php');
        exit;
    }

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $user = getUserByUsername($username);

    if ($user && password_verify($password, $user['password_hash'])) {
        // Login successful
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['theme'] = $user['theme'] ?? 'light'; // Default to light if not set

        header('Location: ../public/index.php');
        exit;
    } else {
        // Login failed
        header('Location: ../public/login.php?error=1');
        exit;
    }
}

function handle_logout() {
    session_unset();
    session_destroy();
    header('Location: ../public/login.php');
    exit;
}


// Simple routing based on 'action' parameter
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        handle_login();
        break;
    case 'logout':
        handle_logout();
        break;
    default:
        header('Location: ../public/index.php');
        exit;
}
