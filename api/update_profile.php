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
    die("Token CSRF invalide");
}

// Récupérer l'ID de l'utilisateur
$user_id = $_SESSION['user_id'];

// Traitement des données du formulaire
$bio = $_POST['bio'] ?? '';
$education = $_POST['education'] ?? '';
$location = $_POST['location'] ?? '';

try {
    // Mettre à jour les informations de base
    $stmt = $pdo->prepare("UPDATE profiles SET bio = ?, education = ?, location = ? WHERE id = ?");
    $stmt->execute([$bio, $education, $location, $user_id]);

    // Traitement des fichiers uploadés (avatar et cover photo)
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $avatarPath = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $avatarName = uniqid() . '_' . basename($_FILES['avatar']['name']);
        $avatarPath = $uploadDir . $avatarName;
        move_uploaded_file($_FILES['avatar']['tmp_name'], $avatarPath);

        // Mettre à jour le chemin de l'avatar dans la base
        $stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
        $stmt->execute([$avatarPath, $user_id]);
    }

    $coverPath = null;
    if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] === UPLOAD_ERR_OK) {
        $coverName = uniqid() . '_' . basename($_FILES['cover_photo']['name']);
        $coverPath = $uploadDir . $coverName;
        move_uploaded_file($_FILES['cover_photo']['tmp_name'], $coverPath);

        // Mettre à jour le chemin de la cover photo dans la base (si vous avez ce champ)
        // $stmt = $pdo->prepare("UPDATE users SET cover_photo = ? WHERE id = ?");
        // $stmt->execute([$coverPath, $user_id]);
    }

    // Répondre avec succès
    echo json_encode(['status' => 'success', 'message' => 'Profil mis à jour avec succès']);
    
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
}
?>