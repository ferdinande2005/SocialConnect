<?php
require_once 'config.php';
session_start();

// Vérifier si la requête est POST et si l'utilisateur est connecté
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die(json_encode(['status' => 'error', 'message' => 'Token CSRF invalide']));
}

// Récupérer le mot de passe soumis
$password = $_POST['password'] ?? '';
$user_id = $_SESSION['user_id'];

try {
    // Récupérer le mot de passe hashé de l'utilisateur
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        die(json_encode(['status' => 'error', 'message' => 'Utilisateur non trouvé']));
    }

    // Vérifier le mot de passe
    if (password_verify($password, $user['password'])) {
        // Générer un nouveau token pour la session de modification
        $_SESSION['edit_token'] = bin2hex(random_bytes(32));
        echo json_encode(['status' => 'success', 'edit_token' => $_SESSION['edit_token']]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Mot de passe incorrect']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
}
?>