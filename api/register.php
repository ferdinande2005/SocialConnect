<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once 'config.php';


if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
    header('Access-Control-Allow-Credentials: true');
    http_response_code(200);
    exit();
}

require_once 'config/phpmailer.php';

require '../vendor/phpmailer/phpmailer/src/Exception.php';
require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'Méthode non autorisée'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

if (
    !isset(
        $data['firstname'], $data['lastname'], $data['username'], $data['birthdate'], $data['gender'],
        $data['relationship_status'], $data['profession'], $data['country'], $data['city'], $data['interests'],
        $data['email'], $data['password'], $data['confirm_password']
    )
) {
    jsonResponse(['status' => 'error', 'message' => 'Données manquantes'], 400);
}

if (!preg_match("/^[À-ſa-zA-Z\s]+$/u", $data['firstname']) || !preg_match("/^[À-ſa-zA-Z\s]+$/u", $data['lastname'])) {
    jsonResponse(['status' => 'error', 'message' => 'Les noms ne sont pas valides'], 400);
}

if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['status' => 'error', 'message' => 'Email invalide'], 400);
}

if (strlen($data['password']) < 8 || strlen($data['password']) > 72) {
    jsonResponse(['status' => 'error', 'message' => 'Le mot de passe doit contenir entre 8 et 72 caractères'], 400);
}

if ($data['password'] !== $data['confirm_password']) {
    jsonResponse(['status' => 'error', 'message' => 'Les mots de passe ne correspondent pas'], 400);
}


$firstname = htmlspecialchars($data['firstname'], ENT_QUOTES, 'UTF-8');
$lastname = htmlspecialchars($data['lastname'], ENT_QUOTES, 'UTF-8');
$username = htmlspecialchars($data['username'], ENT_QUOTES, 'UTF-8');
$birthdate = htmlspecialchars($data['birthdate'], ENT_QUOTES, 'UTF-8');
$gender = htmlspecialchars($data['gender'], ENT_QUOTES, 'UTF-8');
$relationship_status = htmlspecialchars($data['relationship_status'], ENT_QUOTES, 'UTF-8');
$profession = htmlspecialchars($data['profession'], ENT_QUOTES, 'UTF-8');
$country = htmlspecialchars($data['country'], ENT_QUOTES, 'UTF-8');
$city = htmlspecialchars($data['city'], ENT_QUOTES, 'UTF-8');
$interests = htmlspecialchars($data['interests'], ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars($data['email'], ENT_QUOTES, 'UTF-8');
$password = $data['password'];

$csrf_token = bin2hex(random_bytes(32));
$activation_token = bin2hex(random_bytes(32));

try {
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        jsonResponse(['status' => 'error', 'message' => 'Email déjà utilisé'], 400);
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    
    $insert = $pdo->prepare("
        INSERT INTO users 
        (firstname, lastname, username, birthdate, gender, relationship_status, profession, country, city, interests, email, password, csrf_token, activation_token, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    $insert->execute([
        $firstname, $lastname, $username, $birthdate, $gender, $relationship_status,
        $profession, $country, $city, $interests, $email, $password_hash,
        $csrf_token, $activation_token
    ]);

    try {
        $mail = new PHPMailer();
        $mail->SMTPDebug = SMTP::DEBUG_OFF;

        $mail->isSMTP();
        $mail->Host = $phpmailer_config['host'];
        $mail->SMTPAuth = $phpmailer_config['smtp_auth'];
        $mail->Username = $phpmailer_config['username'];
        $mail->Password = $phpmailer_config['password'];
        $mail->SMTPSecure = $phpmailer_config['smtp_secure'];
        $mail->Port = $phpmailer_config['port'];

        $mail->setFrom($phpmailer_config['from_email'], $phpmailer_config['from_name']);
        $mail->addAddress($email);

        $mail->isHTML(true);
        $activation_link = "http://localhost/ReseauSocial/api/activate.php?token=" . $activation_token;
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->Subject = "Confirmation d'inscription - SocialConnect";
        $mail->Body = "
        <html>
        <head><title>Confirmation d'inscription</title></head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #333;'>Bienvenue sur SocialConnect !</h2>
                <p style='color: #666;'>Merci de vous être inscrit. Pour activer votre compte, veuillez cliquer sur le lien suivant :</p>
                <p style='margin: 20px 0;'>
                    <a href='{$activation_link}' style='display: inline-block; padding: 12px 24px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 5px;'>
                        Activer mon compte
                    </a>
                </p>
                <p style='color: #666;'>Cordialement,</p>
                <p style='color: #666;'>L'équipe SocialConnect</p>
            </div>
        </body>
        </html>
        ";

        if (!$mail->send()) {
            error_log("Erreur d'envoi de l'email: " . $mail->ErrorInfo);
        }
    } catch (Exception $e) {
        error_log("Erreur d'envoi de l'email: " . $e->getMessage());
    }

    echo json_encode(['status' => 'success', 'message' => 'Inscription réussie.']);

} catch (Exception $e) {
    error_log("Erreur d'inscription: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue lors de l\'inscription. Veuillez réessayer.']);
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}
