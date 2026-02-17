<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');

// Headers obligatoires en tout premier
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate');

// FIX: Ajouter CORS headers pour éviter les problèmes
header('Access-Control-Allow-Origin: ' . (defined('APP_URL') ? APP_URL : '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../AuthManager.php';
require_once __DIR__ . '/../ExpenseManager.php';

// ── Session via AuthManager (session_name inclus) ─────────────
AuthManager::startSession();   // FIX : plus de session_name() redondant ici

$manager = new ExpenseManager();
$action  = trim($_GET['action'] ?? '');

// ── requireAuth ───────────────────────────────────────────────
function requireAuth(): int {
    if (!AuthManager::isLoggedIn()) {
        jsonResponse(false, 'Non authentifié. Veuillez vous connecter.', null, 401);
    }
    return (int)$_SESSION['user_id'];
}

// ── Lecture corps JSON ─────────────────────────────────────────
// FIX : pas de redéclaration — config.php ne la définit pas, auth_api.php la redéfinit localement
if (!function_exists('getJsonBody')) {
    function getJsonBody(): array {
        $raw = file_get_contents('php://input');
        if (empty($raw)) return [];
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}

// ── Dispatch ──────────────────────────────────────────────────
try {
    switch ($action) {
        // ── Informations mariage dans index───────────────────────────────────
        case 'get_wedding_info':
        $userId = requireAuth();
        try {
            $db = getDBConnection();
            $sql = "SELECT wedding_date, fiance_nom_complet, fiancee_nom_complet, budget_total 
                    FROM wedding_dates WHERE user_id = :user_id LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            jsonResponse(true, '', $info ?: null);
        } catch (PDOException $e) {
            error_log("Erreur get_wedding_info: " . $e->getMessage());
            jsonResponse(false, 'Erreur base de données', null, 500);
        }
        break; // fin case 'get_wedding_info'

        // ── Auth check ────────────────────────────────────────
        case 'check_auth':
            if (AuthManager::isLoggedIn()) {
                jsonResponse(true, '', AuthManager::getCurrentUser());
            }
            jsonResponse(true, '', ['logged_in' => false]);

        // ── Dépenses ──────────────────────────────────────────
        case 'get_all':
            $userId = requireAuth();
            jsonResponse(true, '', $manager->getAllExpenses($userId));

        case 'get_by_id':
            $userId = requireAuth();
            $id     = (int)($_GET['id'] ?? 0);
            if ($id <= 0) jsonResponse(false, 'ID invalide.', null, 400);
            $expense = $manager->getExpenseById($id, $userId);
            if (!$expense) jsonResponse(false, 'Dépense introuvable.', null, 404);
            jsonResponse(true, '', $expense);

        case 'add':
            $userId = requireAuth();
            $data   = getJsonBody();

            if (empty($data['name'])) jsonResponse(false, 'Le nom est requis.', null, 422);

            // Nouvelle catégorie à la volée
            if (!empty($data['new_category'])) {
                $manager->addCategory(trim($data['new_category']), count($manager->getAllCategories()) + 1);
                $data['category_id'] = $manager->getLastCategoryId();
                unset($data['new_category']);
            }
            if (empty($data['category_id'])) jsonResponse(false, 'La catégorie est requise.', null, 422);

            $stats = $manager->getStats($userId);
            if ($stats['total_items'] >= MAX_EXPENSES_PER_USER) {
                jsonResponse(false, "Limite de " . MAX_EXPENSES_PER_USER . " dépenses atteinte.", null, 429);
            }

            $ok = $manager->addExpense($userId, $data);
            jsonResponse($ok, $ok ? 'Dépense ajoutée avec succès.' : "Erreur lors de l'ajout.");

        case 'update':
            $userId = requireAuth();
            $id     = (int)($_GET['id'] ?? 0);
            $data   = getJsonBody();
            if ($id <= 0)             jsonResponse(false, 'ID invalide.',       null, 400);
            if (empty($data['name'])) jsonResponse(false, 'Le nom est requis.', null, 422);
            $ok = $manager->updateExpense($id, $userId, $data);
            jsonResponse($ok, $ok ? 'Dépense mise à jour avec succès.' : 'Erreur lors de la mise à jour.');

        case 'delete':
            $userId = requireAuth();
            $id     = (int)($_GET['id'] ?? 0);
            if ($id <= 0) jsonResponse(false, 'ID invalide.', null, 400);
            $ok = $manager->deleteExpense($id, $userId);
            jsonResponse($ok, $ok ? 'Dépense supprimée avec succès.' : 'Erreur lors de la suppression.');

        case 'toggle_paid':
            $userId = requireAuth();
            $id     = (int)($_GET['id'] ?? 0);
            if ($id <= 0) jsonResponse(false, 'ID invalide.', null, 400);
            $ok = $manager->togglePaid($id, $userId);
            jsonResponse($ok, $ok ? 'Statut mis à jour avec succès.' : 'Erreur lors de la mise à jour.');

        // ── Catégories ────────────────────────────────────────
        case 'get_categories':
            jsonResponse(true, '', $manager->getAllCategories());

        case 'add_category':
            requireAuth();
            $data = getJsonBody();
            if (empty($data['name'])) jsonResponse(false, 'Le nom est requis.', null, 422);
            $ok = $manager->addCategory(
                $data['name'],
                (int)($data['order'] ?? 0),
                $data['color'] ?? '#8b4f8d',
                $data['icon']  ?? 'fas fa-folder'
            );
            jsonResponse($ok, $ok ? 'Catégorie ajoutée.' : 'Erreur ou catégorie déjà existante.');

        // ── Statistiques ──────────────────────────────────────
        case 'get_stats':
            $userId = requireAuth();
            $stats  = $manager->getStats($userId);
            jsonResponse(true, '', [
                'grand_total'        => $manager->getGrandTotal($userId),
                'paid_total'         => $manager->getPaidTotal($userId),
                'unpaid_total'       => $manager->getUnpaidTotal($userId),
                'payment_percentage' => $manager->getPaymentPercentage($userId),
                'total_items'        => $stats['total_items'],
                'paid_items'         => $stats['paid_items'],
                'unpaid_items'       => $stats['unpaid_items'],
            ]);

        case 'category_stats':
            $userId = requireAuth();
            jsonResponse(true, '', $manager->getCategoryStats($userId));

        // ── Date de mariage ───────────────────────────────────
        case 'get_wedding_date':
            $userId = requireAuth();
            $date   = $manager->getWeddingDate($userId);
            jsonResponse(true, '', $date ? ['date' => $date] : null);

        // Dans api.php, ajoutez cette nouvelle action dans le switch
        case 'save_wedding_info':
            $userId = requireAuth();
            $data = getJsonBody();
            
            // Accepter soit le format {date: "YYYY-MM-DD"} soit {wedding_date: "YYYY-MM-DD", ...}
            $weddingDate = $data['wedding_date'] ?? $data['date'] ?? '';
            $fianceNom = $data['fiance_nom_complet'] ?? '';
            $fianceeNom = $data['fiancee_nom_complet'] ?? '';
            $budgetTotal = floatval($data['budget_total'] ?? 0);
            
            if (empty($weddingDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $weddingDate)) {
                jsonResponse(false, 'Format de date invalide (YYYY-MM-DD requis).', null, 422);
            }
            
            // Validation de la date
            $dateTs = strtotime($weddingDate);
            $todayTs = strtotime(date('Y-m-d'));
            
            if ($dateTs < $todayTs) {
                jsonResponse(false, 'La date doit être aujourd\'hui ou dans le futur.', null, 422);
            }
            
            try {
                $db = getDBConnection();
                
                // Vérifier si le mariage existe déjà
                $sql = "SELECT id FROM wedding_dates WHERE user_id = :user_id";
                $stmt = $db->prepare($sql);
                $stmt->execute([':user_id' => $userId]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    // Mise à jour
                    $sql = "UPDATE wedding_dates SET 
                            wedding_date = :wedding_date,
                            fiance_nom_complet = :fiance_nom,
                            fiancee_nom_complet = :fiancee_nom,
                            budget_total = :budget
                            WHERE user_id = :user_id";
                } else {
                    // Insertion
                    $sql = "INSERT INTO wedding_dates 
                            (user_id, wedding_date, fiance_nom_complet, fiancee_nom_complet, budget_total) 
                            VALUES (:user_id, :wedding_date, :fiance_nom, :fiancee_nom, :budget)";
                }
                
                $stmt = $db->prepare($sql);
                $ok = $stmt->execute([
                    ':user_id' => $userId,
                    ':wedding_date' => $weddingDate,
                    ':fiance_nom' => $fianceNom,
                    ':fiancee_nom' => $fianceeNom,
                    ':budget' => $budgetTotal
                ]);
                
                jsonResponse($ok, $ok ? 'Informations enregistrées avec succès.' : "Erreur lors de l'enregistrement.");
                
            } catch (PDOException $e) {
                error_log("Erreur save_wedding_info: " . $e->getMessage());
                jsonResponse(false, 'Erreur base de données.', null, 500);
            }
            break;
            
            $dateTs  = mktime(0, 0, 0,
                (int)substr($date, 5, 2),
                (int)substr($date, 8, 2),
                (int)substr($date, 0, 4)
            );
            $todayTs = mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y'));
            
            if ($dateTs < $todayTs) {
                jsonResponse(false, 'La date doit être aujourd\'hui ou dans le futur.', null, 422);
            }
            
            $ok = $manager->saveWeddingDate($userId, $date);
            jsonResponse($ok, $ok ? 'Date enregistrée avec succès.' : "Erreur lors de l'enregistrement.");

        case 'save_wedding_date':
            $userId = requireAuth();
            $data   = getJsonBody();
            $date   = trim($data['date'] ?? '');
            // FIX BUG #3 : validation corrigée — on compare les timestamps correctement
            $dateTs  = mktime(0, 0, 0,
                (int)substr($date, 5, 2),
                (int)substr($date, 8, 2),
                (int)substr($date, 0, 4)
            );
            $todayTs = mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y'));
            if ($dateTs < $todayTs) {
                jsonResponse(false, 'La date doit être aujourd\'hui ou dans le futur.', null, 422);
            }
            $ok = $manager->saveWeddingDate($userId, $date);
            jsonResponse($ok, $ok ? 'Date enregistrée avec succès.' : "Erreur lors de l'enregistrement.");

        default:
            jsonResponse(false, 'Action inconnue : ' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8'), null, 404);
    }

} catch (Throwable $e) {
    error_log("[API] " . $e->getMessage() . " — " . $e->getFile() . ":" . $e->getLine());
    jsonResponse(false, 'Erreur interne du serveur.', null, 500);
}
