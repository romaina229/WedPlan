<?php
/**
 * export_api.php — Export CSV / HTML-PDF — Budget Mariage PJPM v2.0
 */
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__) . '/');
require_once ROOT_PATH . 'config.php';
require_once ROOT_PATH . 'AuthManager.php';
require_once ROOT_PATH . 'ExpenseManager.php';

// Authentification obligatoire
if (!AuthManager::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$currentUser = AuthManager::getCurrentUser();
$userId      = (int)$currentUser['id'];
$format      = $_GET['format'] ?? 'csv';   // csv | pdf
$type        = $_GET['type']   ?? 'all';   // all | paid | unpaid | category

$manager = new ExpenseManager();
$expenses = $manager->getAllExpenses($userId);
$stats    = $manager->getStats($userId);

// Filtrage selon le type
$filtered = match ($type) {
    'paid'     => array_filter($expenses, fn($e) => (int)$e['paid'] === 1),
    'unpaid'   => array_filter($expenses, fn($e) => (int)$e['paid'] === 0),
    default    => $expenses,
};
$filtered = array_values($filtered);

$filename = 'budget_mariage_' . date('Y-m-d_His');

/* ------------------------------------------------------------------ */
/*  FORMAT CSV                                                          */
/* ------------------------------------------------------------------ */
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
    header('Cache-Control: no-cache, no-store, must-revalidate');
    // BOM UTF-8 pour Excel
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');

    // En-têtes
    fputcsv($out, [
        'Catégorie', 'Nature de la dépense', 'Quantité',
        'Prix unitaire (FCFA)', 'Fréquence', 'Montant total (FCFA)',
        'Statut', 'Date de paiement', 'Notes'
    ], ';');

    $currentCat  = '';
    $catTotal    = 0;
    $grandTotal  = 0;
    $paidTotal   = 0;

    foreach ($filtered as $i => $e) {
        // Ligne de séparation de catégorie
        if ($e['category_name'] !== $currentCat) {
            if ($currentCat !== '') {
                fputcsv($out, ['', '>>> Sous-total ' . $currentCat, '', '', '', number_format($catTotal, 0, ',', ' '), '', '', ''], ';');
                fputcsv($out, [], ';');
            }
            $currentCat = $e['category_name'];
            $catTotal   = 0;
        }

        $total = (float)$e['quantity'] * (float)$e['unit_price'] * (float)$e['frequency'];
        $catTotal   += $total;
        $grandTotal += $total;
        if ((int)$e['paid'] === 1) $paidTotal += $total;

        fputcsv($out, [
            $e['category_name'],
            $e['name'],
            $e['quantity'],
            number_format((float)$e['unit_price'], 0, ',', ' '),
            $e['frequency'],
            number_format($total, 0, ',', ' '),
            (int)$e['paid'] ? 'Payé' : 'Non payé',
            $e['payment_date'] ?? '',
            $e['notes'] ?? '',
        ], ';');
    }

    // Dernier sous-total
    if ($currentCat !== '') {
        fputcsv($out, ['', '>>> Sous-total ' . $currentCat, '', '', '', number_format($catTotal, 0, ',', ' '), '', '', ''], ';');
        fputcsv($out, [], ';');
    }

    // Récapitulatif
    fputcsv($out, ['=== RÉCAPITULATIF ===', '', '', '', '', '', '', '', ''], ';');
    fputcsv($out, ['Budget total', '', '', '', '', number_format($grandTotal, 0, ',', ' '), '', '', ''], ';');
    fputcsv($out, ['Montant payé', '', '', '', '', number_format($paidTotal, 0, ',', ' '), '', '', ''], ';');
    fputcsv($out, ['Reste à payer', '', '', '', '', number_format($grandTotal - $paidTotal, 0, ',', ' '), '', '', ''], ';');
    $pct = $grandTotal > 0 ? round(($paidTotal / $grandTotal) * 100, 1) : 0;
    fputcsv($out, ['Progression', '', '', '', '', $pct . '%', '', '', ''], ';');
    fputcsv($out, ['Export généré le', '', '', '', '', date('d/m/Y à H:i'), '', '', ''], ';');

    fclose($out);
    exit;
}

/* ------------------------------------------------------------------ */
/*  FORMAT PDF (rendu HTML → impression navigateur)                    */
/* ------------------------------------------------------------------ */
$grandTotal = 0;
$paidTotal  = 0;
foreach ($expenses as $e) {
    $t = (float)$e['quantity'] * (float)$e['unit_price'] * (float)$e['frequency'];
    $grandTotal += $t;
    if ((int)$e['paid'] === 1) $paidTotal += $t;
}
$unpaidTotal = $grandTotal - $paidTotal;
$pct = $grandTotal > 0 ? round(($paidTotal / $grandTotal) * 100, 1) : 0;

// Regrouper par catégorie
$byCategory = [];
foreach ($filtered as $e) {
    $byCategory[$e['category_name']][] = $e;
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<link rel="shortcut icon" href="../assets/images/wedding.jpg" type="image/jpg">
<title>Budget Mariage — Récapitulatif — <?= date('d/m/Y') ?></title>
<style>
  @page { margin: 15mm 12mm; }
  * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',Arial,sans-serif; }
  body { font-size:11px; color:#222; background:#fff; }

  .cover { text-align:center; padding:40px 0 30px; border-bottom:3px solid #8b4f8d; margin-bottom:20px; }
  .cover h1 { font-size:28px; color:#8b4f8d; font-weight:700; letter-spacing:1px; }
  .cover .subtitle { font-size:14px; color:#666; margin-top:6px; }
  .cover .date { font-size:11px; color:#999; margin-top:8px; }

  .summary-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:20px; }
  .summary-card { border-radius:8px; padding:12px; text-align:center; }
  .summary-card .val { font-size:16px; font-weight:700; }
  .summary-card .lbl { font-size:9px; text-transform:uppercase; letter-spacing:.5px; margin-top:3px; color:#555; }
  .card-total  { background:#f3e8f5; color:#8b4f8d; }
  .card-paid   { background:#e8f5e9; color:#2e7d32; }
  .card-unpaid { background:#fff3e0; color:#e65100; }
  .card-pct    { background:#e3f2fd; color:#1565c0; }

  .progress-bar-wrap { background:#e0e0e0; border-radius:6px; overflow:hidden; height:14px; margin-bottom:20px; position:relative; }
  .progress-bar-fill { height:100%; background:linear-gradient(90deg,#8b4f8d,#b87bb8); border-radius:6px; transition:width .4s; }
  .progress-bar-label { position:absolute; right:6px; top:0; line-height:14px; font-size:10px; font-weight:700; color:#fff; }

  .section-title { font-size:13px; font-weight:700; color:#8b4f8d; padding:8px 10px; background:#f9f5fa; border-left:4px solid #8b4f8d; margin:16px 0 8px; border-radius:0 4px 4px 0; }

  table { width:100%; border-collapse:collapse; margin-bottom:10px; }
  thead { background:linear-gradient(135deg,#8b4f8d,#b87bb8); color:#fff; }
  th { padding:7px 8px; font-size:10px; text-align:left; letter-spacing:.3px; }
  td { padding:6px 8px; font-size:10px; border-bottom:1px solid #f0edf4; }
  tr:nth-child(even) td { background:#fdf8ff; }
  .text-right { text-align:right; }
  .text-center { text-align:center; }

  .badge { display:inline-block; padding:2px 7px; border-radius:10px; font-size:9px; font-weight:700; }
  .badge-paid   { background:#e8f5e9; color:#2e7d32; }
  .badge-unpaid { background:#fff3e0; color:#e65100; }

  .subtotal-row td { background:#f3e8f5 !important; font-weight:700; color:#5d2f5f; font-size:10px; }
  .total-row    td { background:linear-gradient(135deg,#5d2f5f,#8b4f8d) !important; color:#fff !important; font-weight:700; font-size:11px; }

  .footer-note { text-align:center; font-size:9px; color:#aaa; border-top:1px solid #eee; padding-top:10px; margin-top:20px; }

  @media print {
    body { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .no-print { display:none; }
    table { page-break-inside:auto; }
    tr { page-break-inside:avoid; page-break-after:auto; }
    .section-title { page-break-before:auto; }
  }
</style>
</head>
<body>

<div class="no-print" style="padding:12px;background:#8b4f8d;color:#fff;text-align:center;">
  <strong>Aperçu avant impression</strong> —
  <button onclick="window.print()" style="padding:6px 18px;background:#fff;color:#8b4f8d;border:none;border-radius:4px;cursor:pointer;font-weight:700;margin-left:10px;">🖨️ Imprimer / Sauvegarder PDF</button>
  <button onclick="window.close()" style="padding:6px 12px;background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.4);border-radius:4px;cursor:pointer;margin-left:6px;">✕ Fermer</button>
</div>

<!-- Couverture -->
<div class="cover">
  <h1>💍 Tableau de Bord</h1>
  <div class="subtitle"><?= htmlspecialchars($currentUser['full_name'] ?: $currentUser['username'], ENT_QUOTES) ?></div>
  <div class="date">Rapport généré le <?= date('d/m/Y à H:i') ?></div>
</div>

<!-- Cartes résumé -->
<div class="summary-grid">
  <div class="summary-card card-total">
    <div class="val"><?= formatCurrency($grandTotal) ?></div>
    <div class="lbl">Budget prévisionnel</div>
  </div>
  <div class="summary-card card-paid">
    <div class="val"><?= formatCurrency($paidTotal) ?></div>
    <div class="lbl">Dépensé</div>
  </div>
  <div class="summary-card card-unpaid">
    <div class="val"><?= formatCurrency($unpaidTotal) ?></div>
    <div class="lbl">Reste</div>
  </div>
  <div class="summary-card card-pct">
    <div class="val"><?= $pct ?>%</div>
    <div class="lbl">Progression</div>
  </div>
</div>

<!-- Barre de progression -->
<div class="progress-bar-wrap">
  <div class="progress-bar-fill" style="width:<?= $pct ?>%"></div>
  <span class="progress-bar-label"><?= $pct ?>%</span>
</div>

<!-- Récapitulatif par catégorie -->
<div class="section-title">📊 Récapitulatif par catégorie</div>
<table>
  <thead>
    <tr>
      <th>Catégorie</th>
      <th class="text-right">Montant total</th>
      <th class="text-right">Montant payé</th>
      <th class="text-right">Reste</th>
      <th class="text-center">Progression</th>
    </tr>
  </thead>
  <tbody>
  <?php
    $categories = $manager->getAllCategories();
    $gtotal = $gPaid = 0;
    foreach ($categories as $cat):
        $catTot  = $manager->getCategoryTotal((int)$cat['id'], $userId);
        $catPaid = $manager->getCategoryPaidTotal((int)$cat['id'], $userId);
        if ($catTot == 0) continue;
        $catRem = $catTot - $catPaid;
        $catPct = $catTot > 0 ? round(($catPaid / $catTot) * 100) : 0;
        $gtotal += $catTot; $gPaid += $catPaid;
  ?>
    <tr>
      <td><?= htmlspecialchars($cat['name']) ?></td>
      <td class="text-right"><?= formatCurrency($catTot) ?></td>
      <td class="text-right"><?= formatCurrency($catPaid) ?></td>
      <td class="text-right"><?= formatCurrency($catRem) ?></td>
      <td class="text-center">
        <div style="background:#e0e0e0;border-radius:4px;overflow:hidden;height:8px;width:80px;margin:auto;">
          <div style="background:<?= $catPct>=100 ? '#2e7d32' : '#8b4f8d' ?>;height:100%;width:<?= $catPct ?>%;"></div>
        </div>
        <?= $catPct ?>%
      </td>
    </tr>
  <?php endforeach; ?>
    <tr class="total-row">
      <td><strong>TOTAL GÉNÉRAL</strong></td>
      <td class="text-right"><strong><?= formatCurrency($gtotal) ?></strong></td>
      <td class="text-right"><strong><?= formatCurrency($gPaid) ?></strong></td>
      <td class="text-right"><strong><?= formatCurrency($gtotal - $gPaid) ?></strong></td>
      <td class="text-center"><strong><?= $gtotal > 0 ? round(($gPaid / $gtotal) * 100) : 0 ?>%</strong></td>
    </tr>
  </tbody>
</table>

<!-- Détail par catégorie -->
<?php foreach ($byCategory as $catName => $items): ?>
<?php
    $catT = $catP = 0;
    foreach ($items as $it) {
        $t = (float)$it['quantity'] * (float)$it['unit_price'] * (float)$it['frequency'];
        $catT += $t;
        if ((int)$it['paid']) $catP += $t;
    }
?>
<div class="section-title">📂 <?= htmlspecialchars($catName) ?> — <?= formatCurrency($catT) ?></div>
<table>
  <thead>
    <tr>
      <th>Nature de la dépense</th>
      <th class="text-center">Qté</th>
      <th class="text-right">Prix unit.</th>
      <th class="text-center">Fréq.</th>
      <th class="text-right">Montant</th>
      <th class="text-center">Statut</th>
      <th>Date paiement</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($items as $e):
    $total = (float)$e['quantity'] * (float)$e['unit_price'] * (float)$e['frequency'];
  ?>
    <tr>
      <td><?= htmlspecialchars($e['name']) ?></td>
      <td class="text-center"><?= $e['quantity'] ?></td>
      <td class="text-right"><?= formatCurrency((float)$e['unit_price']) ?></td>
      <td class="text-center"><?= $e['frequency'] ?></td>
      <td class="text-right"><strong><?= formatCurrency($total) ?></strong></td>
      <td class="text-center">
        <span class="badge <?= (int)$e['paid'] ? 'badge-paid' : 'badge-unpaid' ?>">
          <?= (int)$e['paid'] ? '✓ Payé' : '⏳ En attente' ?>
        </span>
      </td>
      <td><?= $e['payment_date'] ? date('d/m/Y', strtotime($e['payment_date'])) : '—' ?></td>
    </tr>
  <?php endforeach; ?>
    <tr class="subtotal-row">
      <td colspan="4">Sous-total <?= htmlspecialchars($catName) ?></td>
      <td class="text-right"><?= formatCurrency($catT) ?></td>
      <td class="text-center"><?= formatCurrency($catP) ?> payé</td>
      <td></td>
    </tr>
  </tbody>
</table>
<?php endforeach; ?>

<div class="footer-note">
  <?php echo APP_NAME ?> — Rapport généré le <?= date('d/m/Y à H:i') ?> —
  <?= htmlspecialchars($currentUser['username']) ?>
</div>

</body>
</html>
