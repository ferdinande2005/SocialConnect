<?php
header('Content-Type: application/json');
include 'config.php';

// Vérification de la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'Méthode non autorisée'], 405);
}

// Récupération des données
$data = json_decode(file_get_contents('php://input'), true);

// Validation des données
if (!isset($data['firstname'], $data['lastname'], $data['email'], $data['password'], $data['confirm_password'])) {
    jsonResponse(['status' => 'error', 'message' => 'Données manquantes'], 400);
}

// Validation des noms
if (!preg_match("/^[À-ſa-zA-Z\s]+$/u", $data['firstname']) || !preg_match("/^[À-ſa-zA-Z\s]+$/u", $data['lastname'])) {
    jsonResponse(['status' => 'error', 'message' => 'Les noms ne sont pas valides'], 400);
}

// Validation de l'email
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['status' => 'error', 'message' => 'Email invalide'], 400);
}

// Validation du mot de passe
if (strlen($data['password']) < 8 || strlen($data['password']) > 72) {
    jsonResponse(['status' => 'error', 'message' => 'Le mot de passe doit contenir entre 8 et 72 caractères'], 400);
}

if ($data['password'] !== $data['confirm_password']) {
    jsonResponse(['status' => 'error', 'message' => 'Les mots de passe ne correspondent pas'], 400);
}

// Sécurisation des données
$firstname = htmlspecialchars($data['firstname'], ENT_QUOTES, 'UTF-8');
$lastname = htmlspecialchars($data['lastname'], ENT_QUOTES, 'UTF-8'); 
$email = htmlspecialchars($data['email'], ENT_QUOTES, 'UTF-8');
$password = htmlspecialchars($data['password'], ENT_QUOTES, 'UTF-8');

// Génération de variables pour l'inscription
$csrf_token = bin2hex(random_bytes(32));
$activation_token = bin2hex(random_bytes(32));
$activation_link = "http://localhost/ReseauSocial/login.html?token=" . $activation_token;

try {
    // Vérification de l'email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        jsonResponse(['status' => 'error', 'message' => 'Email déjà utilisé'], 400);
    }
    
    // Hash du mot de passe
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insertion de l'utilisateur
    $insert = $pdo->prepare("
        INSERT INTO users 
        (firstname, lastname, email, password, csrf_token, activation_token, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'pending')
    ");
    $insert->execute([$firstname, $lastname, $email, $password_hash, $csrf_token, $activation_token]);
    
    // Envoi de l'email de confirmation
    $subject = "Confirmation d'inscription - SocialConnect";
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: no-reply@socialconnect.com\r\n";
    
    $message = "
    <html>
    <head>
    <title>Confirmation d'inscription</title>
    </head>
    <body>
    <h2>Bienvenue sur SocialConnect !</h2>
    <p>Merci de vous être inscrit. Pour activer votre compte, veuillez cliquer sur le lien suivant :</p>
    <p><a href='" . $activation_link . "'>" . $activation_link . "</a></p>
    <p>Cordialement,</p>
    <p>L'équipe SocialConnect</p>
    <a href='http://localhost/ReseauSocial/login.html' style='padding: 10px 20px;
        background-color: #007bff;
        color: #fff;
        text-decoration: none;
        border-radius: 5px;'>Se connecter</a>
    </body>
    </html>
    ";

// Envoi de l'email
mail($to, $subject, $message, $headers);


    echo json_encode(['status' => 'success', 'message' => 'Inscription réussie.']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Données incomplètes.']);
}

?>