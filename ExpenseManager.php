<?php
declare(strict_types=1);
// ================================================================
// ExpenseManager.php — Gestion des dépenses & catégories
// Budget Mariage PJPM v2.0
// ================================================================

require_once __DIR__ . '/config.php';

class ExpenseManager {
    private PDO $conn;

    public function __construct() {
        $this->conn = getDBConnection();
        $this->initTables();
    }

    // ── Création des tables ───────────────────────────────────
    private function initTables(): void {
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS categories (
                id            INT AUTO_INCREMENT PRIMARY KEY,
                name          VARCHAR(255) NOT NULL UNIQUE,
                color         VARCHAR(7)   DEFAULT '#8b4f8d',
                icon          VARCHAR(50)  DEFAULT 'fas fa-folder',
                display_order INT          DEFAULT 0,
                created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS expenses (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                user_id      INT            NOT NULL,
                category_id  INT            NOT NULL,
                name         VARCHAR(255)   NOT NULL,
                quantity     INT            NOT NULL DEFAULT 1,
                unit_price   DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
                frequency    INT            NOT NULL DEFAULT 1,
                paid         TINYINT(1)     NOT NULL DEFAULT 0,
                payment_date DATE           DEFAULT NULL,
                notes        TEXT           DEFAULT NULL,
                created_at   TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
                updated_at   TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id)     REFERENCES users(id)      ON DELETE CASCADE,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
                INDEX idx_user_id    (user_id),
                INDEX idx_category   (category_id),
                INDEX idx_paid       (paid)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS wedding_dates (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                user_id      INT  NOT NULL UNIQUE,
                wedding_date DATE NOT NULL,
                created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // ── Dépenses ──────────────────────────────────────────────
    public function getAllExpenses(int $userId): array {
        $stmt = $this->conn->prepare("
            SELECT e.*, c.name AS category_name, c.display_order, c.color, c.icon
            FROM expenses e
            JOIN categories c ON e.category_id = c.id
            WHERE e.user_id = ?
            ORDER BY c.display_order, e.id
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getExpenseById(int $id, int $userId): ?array {
        $stmt = $this->conn->prepare("SELECT * FROM expenses WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$id, $userId]);
        return $stmt->fetch() ?: null;
    }

    public function addExpense(int $userId, array $data): bool {
        $stmt = $this->conn->prepare("
            INSERT INTO expenses (user_id, category_id, name, quantity, unit_price, frequency, paid, payment_date, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $userId,
            (int)$data['category_id'],
            trim($data['name']),
            max(1, (int)$data['quantity']),
            max(0, (float)$data['unit_price']),
            max(1, (int)$data['frequency']),
            empty($data['paid']) ? 0 : 1,
            !empty($data['payment_date']) ? $data['payment_date'] : null,
            !empty($data['notes'])        ? trim($data['notes'])   : null,
        ]);
    }

    public function updateExpense(int $id, int $userId, array $data): bool {
        $stmt = $this->conn->prepare("
            UPDATE expenses
            SET category_id = ?, name = ?, quantity = ?, unit_price = ?,
                frequency = ?, paid = ?, payment_date = ?, notes = ?
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([
            (int)$data['category_id'],
            trim($data['name']),
            max(1, (int)$data['quantity']),
            max(0, (float)$data['unit_price']),
            max(1, (int)$data['frequency']),
            empty($data['paid']) ? 0 : 1,
            !empty($data['payment_date']) ? $data['payment_date'] : null,
            !empty($data['notes'])        ? trim($data['notes'])   : null,
            $id,
            $userId,
        ]);
    }

    public function deleteExpense(int $id, int $userId): bool {
        $stmt = $this->conn->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    public function togglePaid(int $id, int $userId): bool {
        $stmt = $this->conn->prepare("
            UPDATE expenses
            SET paid = IF(paid = 0, 1, 0),
                payment_date = IF(paid = 0, CURDATE(), NULL)
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$id, $userId]);
    }

    // ── Catégories ────────────────────────────────────────────
    public function getAllCategories(): array {
        return $this->conn->query("SELECT * FROM categories ORDER BY display_order, id")->fetchAll();
    }

    public function addCategory(string $name, int $order = 0, string $color = '#8b4f8d', string $icon = 'fas fa-folder'): bool {
        $stmt = $this->conn->prepare("
            INSERT IGNORE INTO categories (name, display_order, color, icon) VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([trim($name), $order, $color, $icon]);
    }

    public function getLastCategoryId(): int {
        return (int)$this->conn->lastInsertId();
    }

    // ── Totaux & statistiques ─────────────────────────────────
    public function getGrandTotal(int $userId): float {
        $stmt = $this->conn->prepare("
            SELECT COALESCE(SUM(quantity * unit_price * frequency), 0) AS total
            FROM expenses WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        return (float)$stmt->fetchColumn();
    }

    public function getPaidTotal(int $userId): float {
        $stmt = $this->conn->prepare("
            SELECT COALESCE(SUM(quantity * unit_price * frequency), 0) AS total
            FROM expenses WHERE user_id = ? AND paid = 1
        ");
        $stmt->execute([$userId]);
        return (float)$stmt->fetchColumn();
    }

    public function getUnpaidTotal(int $userId): float {
        return $this->getGrandTotal($userId) - $this->getPaidTotal($userId);
    }

    public function getPaymentPercentage(int $userId): float {
        $total = $this->getGrandTotal($userId);
        if ($total == 0) return 0.0;
        return round(($this->getPaidTotal($userId) / $total) * 100, 2);
    }

    public function getCategoryTotal(int $categoryId, int $userId): float {
        $stmt = $this->conn->prepare("
            SELECT COALESCE(SUM(quantity * unit_price * frequency), 0)
            FROM expenses WHERE category_id = ? AND user_id = ?
        ");
        $stmt->execute([$categoryId, $userId]);
        return (float)$stmt->fetchColumn();
    }

    public function getCategoryPaidTotal(int $categoryId, int $userId): float {
        $stmt = $this->conn->prepare("
            SELECT COALESCE(SUM(quantity * unit_price * frequency), 0)
            FROM expenses WHERE category_id = ? AND user_id = ? AND paid = 1
        ");
        $stmt->execute([$categoryId, $userId]);
        return (float)$stmt->fetchColumn();
    }

    public function getStats(int $userId): array {
        $stmt = $this->conn->prepare("
            SELECT
                COUNT(*)                                        AS total_items,
                SUM(CASE WHEN paid = 1 THEN 1 ELSE 0 END)     AS paid_items,
                SUM(CASE WHEN paid = 0 THEN 1 ELSE 0 END)     AS unpaid_items
            FROM expenses WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $r = $stmt->fetch();
        return [
            'total_items'  => (int)($r['total_items']  ?? 0),
            'paid_items'   => (int)($r['paid_items']   ?? 0),
            'unpaid_items' => (int)($r['unpaid_items'] ?? 0),
        ];
    }

    public function getCategoryStats(int $userId): array {
        $categories = $this->getAllCategories();
        $result     = [];
        foreach ($categories as $cat) {
            $total   = $this->getCategoryTotal($cat['id'], $userId);
            $paid    = $this->getCategoryPaidTotal($cat['id'], $userId);
            $result[] = [
                'id'         => $cat['id'],
                'name'       => $cat['name'],
                'color'      => $cat['color'],
                'icon'       => $cat['icon'],
                'total'      => $total,
                'paid'       => $paid,
                'remaining'  => $total - $paid,
                'percentage' => $total > 0 ? round(($paid / $total) * 100, 1) : 0,
            ];
        }
        return $result;
    }

    // ── Date de mariage ───────────────────────────────────────
    public function getWeddingDate(int $userId): ?string {
        $stmt = $this->conn->prepare("SELECT wedding_date FROM wedding_dates WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() ?: null;
    }

    public function saveWeddingDate(int $userId, string $date): bool {
        $stmt = $this->conn->prepare("
            INSERT INTO wedding_dates (user_id, wedding_date) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE wedding_date = VALUES(wedding_date), updated_at = NOW()
        ");
        return $stmt->execute([$userId, $date]);
    }
}
