<?php
//ajout de l'entete de la reponse
header('Content-Type: application/json');
//inclusion de la base de données
include 'config.php';

//recuperation de la reponse
$data = json_decode(file_get_contents('php://input'), true);

//verification des données
if (isset($data['email'], $data['password'])) {
    $email = htmlspecialchars($data['email']);
    $password = $data['password'];

    //verification de l'email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);

    //verification de l'email
    if ($stmt->rowCount() == 1) {
        $user = $stmt->fetch();

        //verification du mot de passe
        if (password_verify($password, $user['password'])) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Connexion réussie.',
                'user' => [
                    'id' => $user['id'],
                    'firstname' => $user['firstname'],
                    'lastname' => $user['lastname'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ]);
            exit;
        }
    }
    echo json_encode(['status' => 'error', 'message' => 'Email ou mot de passe incorrect.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Données manquantes.']);
}
?>
