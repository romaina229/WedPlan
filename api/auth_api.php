<?php
declare(strict_types=1);
// ================================================================
// api/auth_api.php — Endpoints d'authentification
// Budget Mariage PJPM v2.1 — CORRIGÉ
// ================================================================

error_reporting(E_ALL);
ini_set('display_errors', '0');      // TEMPORAIRE pour voir l'erreur 500

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../AuthManager.php';

AuthManager::startSession();          // session AVANT tout header

// Headers CORS
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Access-Control-Allow-Origin: ' . (defined('APP_URL') ? APP_URL : '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$auth   = new AuthManager();
$action = trim($_GET['action'] ?? '');

// FIX : guard contre redéclaration si api.php et auth_api.php inclus ensemble
if (!function_exists('getJsonBody')) {
    function getJsonBody(): array {
        $raw = file_get_contents('php://input');
        if (empty($raw)) return [];
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}

try {
    switch ($action) {

        case 'register':
            $data = getJsonBody();
            if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
                jsonResponse(false, 'Champs requis manquants (username, email, password).', null, 422);
            }
            $result = $auth->register(
                $data['username'],
                $data['email'],
                $data['password'],
                $data['fullname'] ?? null
            );
            jsonResponse($result['success'], $result['message']);

        case 'login':
            $data = getJsonBody();
            if (empty($data['username']) || empty($data['password'])) {
                jsonResponse(false, 'Identifiants requis.', null, 422);
            }
            $result = $auth->login($data['username'], $data['password']);
            if ($result['success']) {
                jsonResponse(true, $result['message'], $result['user'] ?? null);
            }
            jsonResponse(false, $result['message'], null, 401);

        case 'logout':
            $result = $auth->logout();
            jsonResponse($result['success'], $result['message']);

        // FIX : retourne logged_in dans data pour que le JS puisse tester r.data.logged_in
        case 'check':
            if (AuthManager::isLoggedIn()) {
                $user = AuthManager::getCurrentUser();
                jsonResponse(true, '', ['logged_in' => true, 'user' => $user]);
            }
            jsonResponse(true, '', ['logged_in' => false]);

        case 'change_password':
            if (!AuthManager::isLoggedIn()) {
                jsonResponse(false, 'Non authentifié.', null, 401);
            }
            $data = getJsonBody();
            if (empty($data['old_password']) || empty($data['new_password'])) {
                jsonResponse(false, 'Anciens et nouveaux mots de passe requis.', null, 422);
            }
            $result = $auth->changePassword(
                (int)$_SESSION['user_id'],
                $data['old_password'],
                $data['new_password']
            );
            jsonResponse($result['success'], $result['message']);

        case 'update_profile':
            if (!AuthManager::isLoggedIn()) {
                jsonResponse(false, 'Non authentifié.', null, 401);
            }
            $data = getJsonBody();
            if (empty($data['email'])) {
                jsonResponse(false, "L'e-mail est requis.", null, 422);
            }
            $result = $auth->updateProfile(
                (int)$_SESSION['user_id'],
                $data['email'],
                $data['full_name'] ?? null
            );
            jsonResponse($result['success'], $result['message']);

        default:
            jsonResponse(false, 'Action non reconnue : ' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8'), null, 404);
    }

} catch (Throwable $e) {
    error_log("[AUTH_API] " . $e->getMessage() . " — " . $e->getFile() . ":" . $e->getLine());
    jsonResponse(false, 'Erreur interne du serveur.', null, 500);
}
