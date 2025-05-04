<?php
require 'db.php';

// Grab fields
$email    = $_POST['email'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Basic validation
if (!$email || !$username || !$password) {
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
$stmt = $pdo->prepare('INSERT INTO users (email, username, password_hash) VALUES (?, ?, ?)');
$stmt->execute([$email, $username, $hash]);

header('Location: index.html#signin');
exit;
