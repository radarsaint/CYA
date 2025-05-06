<?php
// register.php
require 'db.php';
session_start();

// Grab & trim fields
$email    = trim($_POST['email']    ?? '');
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

// Basic validation
if ($email === '' || $username === '' || $password === '') {
    exit('All fields are required.');
}

// Check if user exists
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? OR username = ?');
$stmt->execute([$email, $username]);
if ($stmt->fetch()) {
    exit('Email or username already taken.');
}

// Hash the password
$hash = password_hash($password, PASSWORD_DEFAULT);

// Insert new user
$stmt = $pdo->prepare(
  'INSERT INTO users (email, username, password_hash) VALUES (?, ?, ?)'
);
$stmt->execute([$email, $username, $hash]);

// Auto‑login the new user
$newId = $pdo->lastInsertId();
$_SESSION['user_id']  = $newId;
$_SESSION['username'] = $username;

// Redirect into character creation
header('Location: character.php');
exit;
