<?php
/**
 * index.php — Page principale Desktop — Budget Mariage PJPM v2.1
 */
declare(strict_types=1);
define('ROOT_PATH', __DIR__ . '/');
require_once ROOT_PATH . 'config.php';
require_once ROOT_PATH . 'AuthManager.php';

AuthManager::startSession();

if (!AuthManager::isLoggedIn()) {
    header('Location: ' . APP_URL . '/auth/login.php');
    exit;
}

$currentUser = AuthManager::getCurrentUser();

// FIX: URL absolue pour les API
define('API_URL', APP_URL . '/api/api.php');
define('AUTH_API_URL', APP_URL . '/api/auth_api.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="description" content="Gérez votre budget de mariage : dépenses, paiements et organisation en un seul outil.">
    <meta name="theme-color" content="#8b4f8d">
    <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
    <link rel="shortcut icon" href="<?= APP_URL ?>/assets/images/wedding.jpg" type="image/jpeg">
    <title><?= APP_NAME ?> — Tableau de bord | <?= htmlspecialchars($currentUser['name'] ?? 'Utilisateur') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lexend+Giga:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/weddingdate.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>

<?php include ROOT_PATH . 'includes/header.php'; ?>

<main class="app-container" id="mainContent">

    <!-- ── Bannière date du mariage ─────────────────────────── -->
    <div id="wedding-date-container"></div>

    <!-- ── Onglets ───────────────────────────────────────────── -->
    <!--<nav class="nav-tabs" role="tablist" aria-label="Sections du tableau de bord">
        <button class="nav-tab active" role="tab" aria-selected="true"
            aria-controls="dashboard-tab" id="tab-dashboard" onclick="switchTab('dashboard')">
            <i class="fas fa-home" aria-hidden="true"></i> Tableau de bord
        </button>
        <button class="nav-tab" role="tab" aria-selected="false"
            aria-controls="stats-tab" id="tab-stats" onclick="switchTab('stats')">
            <i class="fas fa-chart-pie" aria-hidden="true"></i> Statistiques
        </button>
        <button class="nav-tab" role="tab" aria-selected="false"
            aria-controls="details-tab" id="tab-details" onclick="switchTab('details')">
            <i class="fas fa-list-alt" aria-hidden="true"></i> Dépenses
        </button>
        <button class="nav-tab" role="tab" aria-selected="false"
            aria-controls="payments-tab" id="tab-payments" onclick="switchTab('payments')">
            <i class="fas fa-money-check-alt" aria-hidden="true"></i> Paiements
        </button>
    </nav>-->

    <!-- ══════════════════════════════════════════════════════════
         ONGLET 1 — TABLEAU DE BORD
    ══════════════════════════════════════════════════════════════ -->
    <section id="dashboard-tab" class="tab-content active fade-in"
             role="tabpanel" aria-labelledby="tab-dashboard">

        <!-- KPI Cards -->
        <div class="stats-grid" id="stats-grid" aria-live="polite">
            <div class="stat-card skeleton"><div class="stat-loader"></div></div>
            <div class="stat-card skeleton"><div class="stat-loader"></div></div>
            <div class="stat-card skeleton"><div class="stat-loader"></div></div>
            <div class="stat-card skeleton"><div class="stat-loader"></div></div>
        </div>

        <!-- Barre de progression + récap catégories -->
        <div class="table-container">
            <div class="progress-container" id="progress-container"></div>

            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-folder-open"></i> Récapitulatif par catégorie
                </h2>
                <div class="export-actions">
                    <button class="btn-export btn-export-csv" onclick="exportData('csv','all')"
                        title="Télécharger en CSV">
                        <i class="fas fa-file-csv"></i> CSV
                    </button>
                    <button class="btn-export btn-export-pdf" onclick="exportData('pdf','all')"
                        title="Aperçu / Imprimer PDF">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="category-summary-table" aria-label="Récapitulatif des dépenses par catégorie">
                    <thead>
                        <tr>
                            <th scope="col">Catégorie</th>
                            <th scope="col" class="text-right">Total prévu</th>
                            <th scope="col" class="text-right">Dépensé</th>
                            <th scope="col" class="text-right">Reste</th>
                            <th scope="col" class="text-center">Avancement</th>
                        </tr>
                    </thead>
                    <tbody id="category-summary-body">
                        <tr><td colspan="5" class="loading-row">
                            <i class="fas fa-spinner fa-spin"></i> Chargement…
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════
         ONGLET 2 — STATISTIQUES & GRAPHIQUES
    ══════════════════════════════════════════════════════════════ -->
    <section id="stats-tab" class="tab-content fade-in"
             role="tabpanel" aria-labelledby="tab-stats" hidden>

        <!-- Barre d'export -->
        <div class="table-container export-toolbar">
            <span class="export-toolbar-label">
                <i class="fas fa-download"></i> Exporter le rapport :
            </span>
            <div class="export-actions">
                <button class="btn-export btn-export-csv" onclick="exportData('csv','all')">
                    <i class="fas fa-file-csv"></i> CSV complet
                </button>
                <button class="btn-export btn-export-csv" onclick="exportData('csv','paid')">
                    <i class="fas fa-check-circle"></i> CSV payés
                </button>
                <button class="btn-export btn-export-csv" onclick="exportData('csv','unpaid')">
                    <i class="fas fa-clock"></i> CSV en attente
                </button>
                <button class="btn-export btn-export-pdf" onclick="exportData('pdf','all')">
                    <i class="fas fa-file-pdf"></i> Rapport PDF complet
                </button>
            </div>
        </div>

        <!-- Graphiques principaux -->
        <div class="charts-grid">
            <div class="chart-card">
                <h3 class="chart-title">
                    <i class="fas fa-chart-pie"></i> Répartition par catégorie
                </h3>
                <div class="chart-wrapper">
                    <canvas id="pie-chart" aria-label="Répartition des dépenses par catégorie"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h3 class="chart-title">
                    <i class="fas fa-chart-bar"></i> Payé vs Reste à payer
                </h3>
                <div class="chart-wrapper">
                    <canvas id="bar-chart" aria-label="Comparaison payé et reste à payer"></canvas>
                </div>
            </div>
        </div>

        <!-- Jauges circulaires -->
        <div class="chart-card" style="margin-top:24px">
            <h3 class="chart-title">
                <i class="fas fa-tachometer-alt"></i> Progression par catégorie
            </h3>
            <div id="gauges-container" class="gauges-grid">
                <p class="chart-loading">
                    <i class="fas fa-spinner fa-spin"></i> Chargement des données…
                </p>
            </div>
        </div>

        <!-- Résumé financier horizontal -->
        <div class="chart-card" style="margin-top:24px">
            <h3 class="chart-title">
                <i class="fas fa-chart-line"></i> Vue financière globale
            </h3>
            <div class="chart-wrapper chart-wrapper-summary">
                <canvas id="summary-chart" aria-label="Résumé financier global"></canvas>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════
         ONGLET 3 — DÉTAIL DES DÉPENSES
    ══════════════════════════════════════════════════════════════ -->
    <section id="details-tab" class="tab-content"
             role="tabpanel" aria-labelledby="tab-details" hidden>

        <div class="filters-section">
            <div class="filters-header">
                <button class="btn btn-primary" onclick="openModal()" aria-label="Ajouter une dépense">
                    <i class="fas fa-plus"></i> Ajouter une dépense
                </button>
                <button class="btn btn-secondary" onclick="toggleFilters()"
                    aria-controls="filters-panel" aria-expanded="false" id="toggle-filters-btn">
                    <i class="fas fa-filter"></i> Filtres
                    <span class="filter-badge" id="filter-count" style="display:none"></span>
                </button>
                <div class="export-actions" style="margin-left:auto">
                    <button class="btn-export btn-export-csv" onclick="exportData('csv','all')" title="CSV">
                        <i class="fas fa-file-csv"></i>
                    </button>
                    <button class="btn-export btn-export-pdf" onclick="exportData('pdf','all')" title="PDF">
                        <i class="fas fa-file-pdf"></i>
                    </button>
                </div>
            </div>

            <div id="filters-panel" class="filters-panel" style="display:none" aria-label="Filtres">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="filter-category">Catégorie</label>
                        <select id="filter-category" onchange="applyFilters()">
                            <option value="">Toutes</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="filter-status">Statut</label>
                        <select id="filter-status" onchange="applyFilters()">
                            <option value="">Tous</option>
                            <option value="paid">Payé</option>
                            <option value="unpaid">Non payé</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="filter-search">Recherche</label>
                        <input type="search" id="filter-search" placeholder="Nom de la dépense…"
                            oninput="applyFilters()" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="filter-min">Montant min (FCFA)</label>
                        <input type="number" id="filter-min" placeholder="0" min="0" oninput="applyFilters()">
                    </div>
                    <div class="form-group">
                        <label for="filter-max">Montant max (FCFA)</label>
                        <input type="number" id="filter-max" placeholder="Illimité" min="0" oninput="applyFilters()">
                    </div>
                    <div class="form-group" style="display:flex;align-items:flex-end">
                        <button class="btn btn-warning" onclick="resetFilters()" style="width:100%">
                            <i class="fas fa-undo"></i> Réinitialiser
                        </button>
                    </div>
                </div>
                <p class="filter-results" id="filter-results-text" aria-live="polite"></p>
            </div>
        </div>

        <div class="table-container">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-receipt"></i> Détail des dépenses</h2>
            </div>
            <div class="table-responsive">
                <table id="expenses-table" aria-label="Tableau détaillé des dépenses">
                    <thead>
                        <tr>
                            <th scope="col">Catégorie</th>
                            <th scope="col">Nature de la dépense</th>
                            <th scope="col" class="text-center">Qté</th>
                            <th scope="col" class="text-right">Prix unit.</th>
                            <th scope="col" class="text-center">Fréq.</th>
                            <th scope="col" class="text-right">Montant</th>
                            <th scope="col" class="text-center">Statut</th>
                            <th scope="col" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="expenses-body">
                        <tr><td colspan="8" class="loading-row">
                            <i class="fas fa-spinner fa-spin"></i> Chargement…
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════
         ONGLET 4 — PAIEMENTS
    ══════════════════════════════════════════════════════════════ -->
    <section id="payments-tab" class="tab-content"
             role="tabpanel" aria-labelledby="tab-payments" hidden>

        <div class="table-container">
            <div class="section-header">
                <h2 class="section-title" style="color:var(--success)">
                    <i class="fas fa-check-circle"></i> Éléments payés
                </h2>
                <button class="btn-export btn-export-csv" onclick="exportData('csv','paid')">
                    <i class="fas fa-file-csv"></i> Export payés
                </button>
            </div>
            <div class="table-responsive">
                <table aria-label="Dépenses payées">
                    <thead>
                        <tr>
                            <th scope="col">Catégorie</th>
                            <th scope="col">Nom</th>
                            <th scope="col" class="text-center">Qté</th>
                            <th scope="col" class="text-right">Prix unit.</th>
                            <th scope="col" class="text-right">Montant</th>
                            <th scope="col" class="text-center">Date paiement</th>
                            <th scope="col" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="paid-expenses-body">
                        <tr><td colspan="7" class="loading-row">
                            <i class="fas fa-spinner fa-spin"></i> Chargement…
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-container" style="margin-top:24px">
            <div class="section-header">
                <h2 class="section-title" style="color:var(--warning)">
                    <i class="fas fa-clock"></i> Éléments en attente
                </h2>
                <button class="btn-export btn-export-default" onclick="exportData('csv','unpaid')">
                    <i class="fas fa-file-csv"></i> Export en attente
                </button>
            </div>
            <div class="table-responsive">
                <table aria-label="Dépenses non payées">
                    <thead>
                        <tr>
                            <th scope="col">Catégorie</th>
                            <th scope="col">Nom</th>
                            <th scope="col" class="text-center">Qté</th>
                            <th scope="col" class="text-right">Prix unit.</th>
                            <th scope="col" class="text-right">Montant</th>
                            <th scope="col" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="unpaid-expenses-body">
                        <tr><td colspan="6" class="loading-row">
                            <i class="fas fa-spinner fa-spin"></i> Chargement…
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

</main>

<!-- Toast global -->

<!-- ════ MODAL — Dépense ════════════════════════════════════════ -->
<div id="expense-modal" class="modal" role="dialog" aria-modal="true"
     aria-labelledby="modal-title" style="display:none">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modal-title" class="modal-heading">Nouvelle dépense</h2>
            <button class="modal-close" onclick="closeModal()" aria-label="Fermer">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="expense-form" onsubmit="handleSubmit(event)" novalidate>
            <input type="hidden" id="expense-id">
            <div class="form-grid">
                <div class="form-group">
                    <label for="category-select">Catégorie <span class="required">*</span></label>
                    <select id="category-select" required>
                        <option value="">Sélectionner…</option>
                    </select>
                </div>
                <div class="form-group" id="new-category-group" style="display:none">
                    <label for="new-category">Nouvelle catégorie <span class="required">*</span></label>
                    <input type="text" id="new-category" placeholder="Ex : Honeymoon" maxlength="100">
                </div>
                <div class="form-group">
                    <label for="expense-name">Nom de la dépense <span class="required">*</span></label>
                    <input type="text" id="expense-name" required maxlength="255"
                        placeholder="Ex : Location salle">
                </div>
                <div class="form-group">
                    <label for="quantity">Quantité <span class="required">*</span></label>
                    <input type="number" id="quantity" min="1" value="1" required
                        oninput="updateModalTotal()">
                </div>
                <div class="form-group">
                    <label for="unit-price">Prix unitaire (FCFA) <span class="required">*</span></label>
                    <input type="number" id="unit-price" min="0" step="1" required
                        placeholder="0" oninput="updateModalTotal()">
                </div>
                <div class="form-group">
                    <label for="frequency">
                        Fréquence <span class="required">*</span>
                        <span class="field-hint" title="Nombre de répétitions de ce montant">(?)</span>
                    </label>
                    <input type="number" id="frequency" min="1" value="1" required
                        oninput="updateModalTotal()">
                </div>
                <div class="form-group">
                    <label for="payment-date">Date de paiement</label>
                    <input type="date" id="payment-date">
                </div>
                <div class="form-group form-group-full">
                    <label for="notes">Notes (optionnel)</label>
                    <input type="text" id="notes" maxlength="500"
                        placeholder="Informations complémentaires…">
                </div>
                <div class="form-group form-group-checkbox">
                    <label class="checkbox-label">
                        <input type="checkbox" id="paid"> Déjà payé
                    </label>
                </div>
            </div>
            <div class="modal-total" id="modal-total" style="display:none">
                <i class="fas fa-calculator"></i>
                Montant total : <strong id="modal-total-value">0 FCFA</strong>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-primary" id="submit-btn">
                    <i class="fas fa-save"></i> <span id="submit-btn-text">Ajouter</span>
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ════ MODAL — Date du mariage ════════════════════════════════ -->
<div id="wedding-date-modal" class="modal" role="dialog" aria-modal="true"
     aria-labelledby="wedding-modal-title" style="display:none">
    <div class="modal-content modal-narrow">
        <div class="modal-header">
            <h2 id="wedding-modal-title" class="modal-heading">
                <i class="fas fa-heart" style="color:#ff6b9d"></i> Date prévue pour le mariage
            </h2>
            <button class="modal-close" onclick="closeDateModal()" aria-label="Fermer">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="form-group">
            <label for="wedding-date-input">Sélectionnez la date <span class="required">*</span></label>
            <input type="date" id="wedding-date-input">
            <small style="color:var(--text-secondary);font-size:.85rem">
                Choisissez le grand jour !
            </small>
        </div>
        <div class="date-preview" id="date-preview">
            <div class="date-preview-icon"><i class="fas fa-church"></i></div>
            <div>
                <p class="date-preview-date" id="date-preview-text">Sélectionnez une date</p>
                <p class="date-preview-countdown" id="countdown-preview-text">—</p>
            </div>
        </div>
        <div class="modal-actions">
            <button class="btn btn-primary" onclick="saveWeddingDate()">
                <i class="fas fa-save"></i> Enregistrer
            </button>
            <button class="btn btn-secondary" onclick="closeDateModal()">
                <i class="fas fa-times"></i> Annuler
            </button>
        </div>
    </div>
</div>

<?php include ROOT_PATH . 'includes/footer.php'; ?>

<!-- FIX: Définir les constantes API en global AVANT les scripts -->
<script>
    window.API      = '<?= APP_URL ?>/api/api.php';
    window.AUTH_API = '<?= APP_URL ?>/api/auth_api.php';
    window.APP_URL  = '<?= APP_URL ?>';
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/charts.js" defer></script>
<script src="<?= APP_URL ?>/assets/js/script.js" defer></script>
</body>
</html>