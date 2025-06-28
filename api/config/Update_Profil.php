<?php
require_once 'C:\xampp\htdocs\SocialConnect\api\config.php';

header('Content-Type: application/json');

//Vérifier si l'utilisateur est connecté et que le mot de passe a été récemment vérifié
if (!isset($_SESSION['user_id']) || !isset($_SESSION['profile_edit_verified'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
    exit;
}

// Préparer les données de mise à jour
$updateData = [
    'bio' => $_POST['bio'] ?? null,
    'education' => $_POST['education'] ?? null,
    'location' => $_POST['location'] ?? null,
];

// Gérer les téléchargements de fichiers
$uploadDir = 'uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

//  Traiter le téléchargement d'avatar
if (!empty($_FILES['avatar']['name'])) {
    $avatarName = uniqid() . '_' . basename($_FILES['avatar']['name']);
    $avatarPath = $uploadDir . $avatarName;
    
    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $avatarPath)) {
        $updateData['avatar'] = $avatarPath;
    }
}

// Traiter le téléchargement de photo de couverture
if (!empty($_FILES['cover_photo']['name'])) {
    $coverName = uniqid() . '_' . basename($_FILES['cover_photo']['name']);
    $coverPath = $uploadDir . $coverName;
    
    if (move_uploaded_file($_FILES['cover_photo']['tmp_name'], $coverPath)) {
        $updateData['cover_photo'] = $coverPath;
    }
}

// Construire l'instruction SQL de mise à jour
$sql = "UPDATE users SET ";
$params = [];
$updates = [];

foreach ($updateData as $field => $value) {
    if ($value !== null) {
        $updates[] = "$field = ?";
        $params[] = $value;
    }
}

if (empty($updates)) {
    echo json_encode(['success' => false, 'message' => 'Aucune donnée à mettre à jour']);
    exit;
}

$sql .= implode(', ', $updates) . " WHERE id = ?";
$params[] = $_SESSION['user_id'];

// Exécuter la mise à jour
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Effacer le flag de vérification
    unset($_SESSION['profile_edit_verified']);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
}
?>