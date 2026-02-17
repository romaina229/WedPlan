<?php
declare(strict_types=1);
// ================================================================
// AuthManager.php — CORRIGÉ v2.1
// ================================================================

require_once __DIR__ . '/config.php';

class AuthManager {
    private PDO $conn;

    public function __construct() {
        $this->conn = getDBConnection();
        $this->initTables();
    }

    // ── Helper session — avec gestion d'erreur ──
    public static function startSession(): void {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        
        // FIX: Définir le nom de session AVANT start
        if (session_name() !== SESSION_NAME) {
            session_name(SESSION_NAME);
        }
        
        // Configuration des cookies
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => true,    // Mettre true en production avec HTTPS
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        
        @session_start();
    }

    // ── Initialisation des tables ─────────────────────────────
    private function initTables(): void {
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS users (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                username   VARCHAR(50)  NOT NULL UNIQUE,
                email      VARCHAR(100) NOT NULL UNIQUE,
                password   VARCHAR(255) NOT NULL,
                full_name  VARCHAR(100) DEFAULT NULL,
                role       ENUM('admin','user') DEFAULT 'user',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_login TIMESTAMP  NULL DEFAULT NULL,
                INDEX idx_username (username),
                INDEX idx_email    (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS admin_logs (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                user_id     INT DEFAULT NULL,
                action      VARCHAR(255) NOT NULL,
                action_type ENUM('admin','auth','data','error') DEFAULT 'auth',
                details     TEXT DEFAULT NULL,
                ip_address  VARCHAR(45)  DEFAULT NULL,
                created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id     (user_id),
                INDEX idx_action_type (action_type),
                INDEX idx_created_at  (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // ── Journalisation ────────────────────────────────────────
    public function logActivity(?int $userId, string $action, string $type = 'auth', ?string $details = null): void {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $this->conn->prepare("
                INSERT INTO admin_logs (user_id, action, action_type, details, ip_address)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([$userId, $action, $type, $details, $ip]);
        } catch (PDOException $e) {
            error_log("[LOG] " . $e->getMessage());
        }
    }

    // ── Inscription ───────────────────────────────────────────
    public function register(string $username, string $email, string $password, ?string $fullName = null): array {
        $username = trim($username);
        $email    = trim($email);
        if (mb_strlen($username) < 3)
            return ['success' => false, 'message' => "Le nom d'utilisateur doit contenir au moins 3 caractères."];
        if (mb_strlen($password) < 6)
            return ['success' => false, 'message' => "Le mot de passe doit contenir au moins 6 caractères."];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            return ['success' => false, 'message' => "Adresse e-mail invalide."];
        if ($this->userExists($username, $email))
            return ['success' => false, 'message' => "Ce nom d'utilisateur ou cet e-mail existe déjà."];
        try {
            $this->conn->prepare("
                INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)
            ")->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $fullName]);
            $this->logActivity(null, "Inscription : $username", 'auth', "Email: $email");
            return ['success' => true, 'message' => "Inscription réussie ! Vous pouvez maintenant vous connecter."];
        } catch (PDOException $e) {
            error_log("[REGISTER] " . $e->getMessage());
            return ['success' => false, 'message' => "Erreur lors de l'inscription."];
        }
    }

    // ── Connexion ─────────────────────────────────────────────
    public function login(string $usernameOrEmail, string $password): array {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $this->logActivity(null, "Échec connexion : $usernameOrEmail", 'auth', 'Identifiants incorrects');
            return ['success' => false, 'message' => "Nom d'utilisateur ou mot de passe incorrect."];
        }

        $this->conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

        self::startSession();
        session_regenerate_id(true);
        
        $_SESSION['user_id']       = $user['id'];
        $_SESSION['username']      = $user['username'];
        $_SESSION['email']         = $user['email'];
        $_SESSION['full_name']     = $user['full_name'];
        $_SESSION['role']          = $user['role'];
        $_SESSION['logged_in']     = true;
        $_SESSION['LAST_ACTIVITY'] = time();

        $this->logActivity($user['id'], "Connexion réussie", 'auth');
        
        // FIX: Ajouter la redirection préférée dans la réponse
        return [
            'success' => true,
            'message' => "Connexion réussie.",
            'redirect' => APP_URL . '/index_mobile.php', // Par défaut version mobile
            'user'    => [
                'id'        => $user['id'],
                'username'  => $user['username'],
                'email'     => $user['email'],
                'full_name' => $user['full_name'],
                'role'      => $user['role'],
            ]
        ];
    }
    // ── Déconnexion ───────────────────────────────────────────
    public function logout(): array {
        self::startSession();
        $userId = $_SESSION['user_id'] ?? null;
        $this->logActivity($userId, "Déconnexion", 'auth');
        session_unset();
        session_destroy();
        return ['success' => true, 'message' => "Déconnexion réussie."];
    }

    // ── Vérification session ──────────────────────────────────
    public static function isLoggedIn(): bool {
        self::startSession();
        return isset($_SESSION['logged_in'])
            && $_SESSION['logged_in'] === true
            && self::checkSessionTimeout();
    }

    public static function checkSessionTimeout(): bool {
        if (!isset($_SESSION['LAST_ACTIVITY'])) {
            $_SESSION['LAST_ACTIVITY'] = time();
            return true;
        }
        if (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            return false;
        }
        $_SESSION['LAST_ACTIVITY'] = time();
        return true;
    }

    public static function getCurrentUser(): ?array {
        if (!self::isLoggedIn()) return null;
        return [
            'id'        => $_SESSION['user_id'] ?? null,
            'username'  => $_SESSION['username'] ?? null,
            'email'     => $_SESSION['email'] ?? null,
            'full_name' => $_SESSION['full_name'] ?? null,
            'role'      => $_SESSION['role'] ?? null,
        ];
    }

    public static function requireLogin(string $redirect = ''): void {
        if (!self::isLoggedIn()) {
            $url = $redirect ?: APP_URL . '/auth/login.php';
            header("Location: $url");
            exit;
        }
    }

    public static function requireAdmin(string $redirect = ''): void {
        self::requireLogin();
        if (($_SESSION['role'] ?? '') !== 'admin') {
            header("Location: " . ($redirect ?: APP_URL . '/index.php'));
            exit;
        }
    }

    // ── Gestion utilisateurs ──────────────────────────────────
    private function userExists(string $username, string $email): bool {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $email]);
        return (bool)$stmt->fetch();
    }

    public function getAllUsers(): array {
        return $this->conn->query("
            SELECT id, username, email, full_name, role, created_at, last_login
            FROM users ORDER BY created_at DESC
        ")->fetchAll();
    }

    public function getUserById(int $id): ?array {
        $stmt = $this->conn->prepare("
            SELECT id, username, email, full_name, role, created_at, last_login
            FROM users WHERE id = ? LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function countUsers(): int {
        return (int)$this->conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }

    public function changePassword(int $userId, string $oldPassword, string $newPassword): array {
        $stmt = $this->conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($oldPassword, $user['password']))
            return ['success' => false, 'message' => "Ancien mot de passe incorrect."];
        if (mb_strlen($newPassword) < 6)
            return ['success' => false, 'message' => "Le nouveau mot de passe doit contenir au moins 6 caractères."];
        $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?")
            ->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
        $this->logActivity($userId, "Changement de mot de passe", 'auth');
        return ['success' => true, 'message' => "Mot de passe modifié avec succès."];
    }

    public function updateProfile(int $userId, string $email, ?string $fullName): array {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            return ['success' => false, 'message' => "Adresse e-mail invalide."];
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch())
            return ['success' => false, 'message' => "Cet e-mail est déjà utilisé."];
        $this->conn->prepare("UPDATE users SET email = ?, full_name = ? WHERE id = ?")
            ->execute([$email, $fullName, $userId]);
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['email']     = $email;
            $_SESSION['full_name'] = $fullName;
        }
        $this->logActivity($userId, "Mise à jour profil", 'auth');
        return ['success' => true, 'message' => "Profil mis à jour avec succès."];
    }

    // ── Journaux ──────────────────────────────────────────────
    public function getLogs(int $page = 1, int $perPage = 50, string $type = '', string $search = ''): array {
        $offset = ($page - 1) * $perPage;
        $where  = "WHERE 1=1";
        $params = [];
        if ($type !== '')   { $where .= " AND l.action_type = ?"; $params[] = $type; }
        if ($search !== '') { $where .= " AND (l.action LIKE ? OR u.username LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

        $stmt = $this->conn->prepare("
            SELECT l.*, u.username FROM admin_logs l
            LEFT JOIN users u ON l.user_id = u.id
            $where ORDER BY l.created_at DESC LIMIT $perPage OFFSET $offset
        ");
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        $c = $this->conn->prepare("SELECT COUNT(*) FROM admin_logs l LEFT JOIN users u ON l.user_id = u.id $where");
        $c->execute($params);
        $total = (int)$c->fetchColumn();
        return ['logs' => $logs, 'total' => $total, 'pages' => (int)ceil($total / $perPage)];
    }

    public function clearLogs(): bool {
        $uid = $_SESSION['user_id'] ?? null;
        $this->conn->exec("TRUNCATE TABLE admin_logs");
        $this->logActivity($uid, "Journaux effacés", 'admin');
        return true;
    }
}
