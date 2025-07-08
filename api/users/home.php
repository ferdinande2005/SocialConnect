<?php
//congig
require '../../api/config.php';

//inclusion des headers
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    // Configuration des headers
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
    header('Access-Control-Allow-Credentials: true');
    http_response_code(200);
    exit();
}


// Vérification de la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['status' => 'error', 'message' => 'Méthode non autorisée'], 405);
}

//creer un post 













?>