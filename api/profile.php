<?php
require_once 'config.php';
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Récupérer l'ID de l'utilisateur dont on veut voir le profil (par défaut l'utilisateur connecté)
$profile_id = isset($_GET['id']) ? intval($_GET['id']) : $_SESSION['user_id'];
$isOwnProfile = ($profile_id === $_SESSION['user_id']);

try {
    // Récupérer les informations de l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$profile_id]);
    $user = $stmt->fetch();

    if (!$user) {
        die("Utilisateur non trouvé");
    }

    // Récupérer le nombre d'amis
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM friends WHERE (user_id = ? OR friend_id = ?) AND status = 'accepted'");
    $stmt->execute([$profile_id, $profile_id]);
    $friendCount = $stmt->fetch()['count'];

    // Récupérer les publications
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$profile_id]);
    $posts = $stmt->fetchAll();

    // Préparer les données pour la vue
    $data = [
        'user' => [
            'id' => $user['id'],
            'firstname' => $user['firstname'],
            'lastname' => $user['lastname'],
            'email' => $user['email'],
            'avatar' => $user['avatar_url'] ?? 'default-avatar.jpg',
            'cover_photo' => 'default-cover.jpg', // À implémenter si vous avez ce champ
            'bio' => $user['bio'] ?? '',
            'education' => $user['education'] ?? '',
            'location' => $user['location'] ?? '',
            'created_at' => $user['created_at']
        ],
        'friendCount' => $friendCount,
        'posts' => $posts,
        'isOwnProfile' => $isOwnProfile,
        'csrf_token' => bin2hex(random_bytes(32))
    ];

    // Stocker le token CSRF en session
    $_SESSION['csrf_token'] = $data['csrf_token'];

    // Charger la vue
    include 'vues/clients/profile.html';

} catch (PDOException $e) {
    die("Erreur de base de données: " . $e->getMessage());
}
?>