<?php
// create_post.php - Gère la création de nouvelles publications

// Inclure le fichier de configuration pour la connexion à la base de données
require_once 'config.php';

// Démarrer la session pour accéder aux informations de l'utilisateur
session_start();

// Vérifier que la requête est de type POST et que l'utilisateur est connecté
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['status' => 'error', 'message' => 'Accès non autorisé']);
    exit;
}

// Vérifier le token CSRF pour prévenir les attaques
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['status' => 'error', 'message' => 'Token CSRF invalide']);
    exit;
}

// Récupérer le contenu du post et le nettoyer
$content = trim($_POST['content'] ?? '');

// Valider que le contenu n'est pas vide
if (empty($content)) {
    echo json_encode(['status' => 'error', 'message' => 'Le contenu ne peut pas être vide']);
    exit;
}

// Récupérer l'ID de l'utilisateur qui poste
$user_id = $_SESSION['user_id'];

try {
    // Préparer la requête d'insertion dans la base de données
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, created_at, updated_at) 
                          VALUES (:user_id, :content, NOW(), NOW())");
    
    // Exécuter la requête avec les paramètres
    $stmt->execute([
        ':user_id' => $user_id,
        ':content' => $content
    ]);
    
    // Récupérer l'ID du nouveau post créé
    $post_id = $pdo->lastInsertId();
    
    // Répondre avec succès
    echo json_encode([
        'status' => 'success', 
        'message' => 'Publication créée avec succès',
        'post_id' => $post_id
    ]);
    
} catch (PDOException $e) {
    // En cas d'erreur de base de données
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'status' => 'error', 
        'message' => 'Erreur lors de la création de la publication: ' . $e->getMessage()
    ]);
}
?>