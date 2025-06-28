<?php
require_once 'config.php';

header('Content-Type: application/json');

//Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
    exit;
}

//Récupérer le mot de passe depuis les données POST
$password = $_POST['password'] ?? '';
if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Mot de passe requis']);
    exit;
}

// Verification de mot de passe
if (verifyPassword($_SESSION['user_id'], $password, $pdo)) {
    // Le mot de passe est correct - définir un flag de session
    $_SESSION['profile_edit_verified'] = true;
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Mot de passe incorrect']);
}
?>