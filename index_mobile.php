<?php
/**
 * index_mobile.php — Interface Mobile Native avec fonctionnalités améliorées
 * Version 2.2 - Ajout des parrains, commentaires et corrections de pourcentages
 */
declare(strict_types=1);
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . '/');
}
require_once ROOT_PATH . 'config.php';
require_once ROOT_PATH . 'AuthManager.php';

if (!AuthManager::isLoggedIn()) {
    header('Location: ' . APP_URL . '/auth/login.php');
    exit;
}
$user = AuthManager::getCurrentUser();
$userId = $user['id'] ?? 0;
$isAdmin = ($user['role'] ?? 'user') === 'admin';

// NOUVEAU : Récupérer les informations du mariage
$db = getDBConnection();
$weddingInfo = null;
$wedding_id = 0;
$weddingDate = null;
$fiance_nom_complet = '';
$fiancee_nom_complet = '';

try {
    $sql = "SELECT * FROM wedding_dates WHERE user_id = :user_id LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    $weddingInfo = $stmt->fetch();
    if ($weddingInfo) {
        $wedding_id = $weddingInfo['id'] ?? 0;
        $weddingDate = $weddingInfo['wedding_date'] ?? null;
        $fiance_nom_complet = $weddingInfo['fiance_nom_complet'] ?? '';
        $fiancee_nom_complet = $weddingInfo['fiancee_nom_complet'] ?? '';
    }
} catch (PDOException $e) {
    error_log("Erreur récupération wedding_dates: " . $e->getMessage());
}

// NOUVEAU : Récupérer les commentaires récents des parrains
$recentComments = [];
if ($wedding_id > 0) {
    try {
        $sql = "SELECT sc.*, ws.sponsor_nom_complet, ws.role 
                FROM sponsor_comments sc
                INNER JOIN wedding_sponsors ws ON sc.sponsor_id = ws.id
                WHERE sc.wedding_dates_id = :wedding_id AND sc.statut = 'public'
                ORDER BY sc.created_at DESC
                LIMIT 5";
        $stmt = $db->prepare($sql);
        $stmt->execute([':wedding_id' => $wedding_id]);
        $recentComments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Info: Tables de commentaires non disponibles - " . $e->getMessage());
    }
}

// Fonction pour formater les dates en français
function formatDateFrancais($date) {
    if (empty($date)) return '';
    $mois = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
    $timestamp = strtotime($date);
    return date('d', $timestamp) . ' ' . $mois[date('n', $timestamp) - 1] . ' ' . date('Y', $timestamp);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<meta name="theme-color" content="#4c1d95">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Budget Mariage">
<meta name="mobile-web-app-capable" content="yes">
<link rel="shortcut icon" href="assets/images/wedding.jpg" type="image/jpg">
<meta name="description" content="Gérez votre budget de mariage depuis votre mobile">
<link rel="manifest" href="<?= APP_URL ?>/manifest.json">
<link rel="apple-touch-icon" href="<?= APP_URL ?>/assets/images/wedding.jpg">
<title><?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400..700;1,400..700&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&family=Roboto+Serif:ital,opsz,wght@0,8..144,100..900;1,8..144,100..900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* ================================================
   DESIGN SYSTEM — BUDGET MARIAGE MOBILE v2.0.2
   Luxury Gold × Deep Violet · iOS & Android
================================================ */
:root {
  --gold: #c9a84c; --gold-l: #e8cc84; --gold-pale: #f9f0d8;
  --violet: #4c1d95; --violet-m: #6d28d9; --violet-l: #7c3aed; --violet-pale: #ede9fe;
  --rose: #be185d; --rose-l: #f9a8d4;
  --green: #065f46; --green-l: #6ee7b7;
  --amber: #92400e; --amber-l: #fde68a;
  --ink: #1a0a2e; --ink-m: #3d1c72; --ink-l: #6b4f9e;
  --mist: #f5f3ff; --white: #ffffff;
  --sh-sm: 0 2px 8px rgba(76,29,149,.08);
  --sh-md: 0 8px 24px rgba(76,29,149,.14);
  --sh-lg: 0 20px 60px rgba(76,29,149,.2);
  --sh-gold: 0 4px 20px rgba(201,168,76,.3);
  --hh: 62px; --nh: 70px;
  --r: 20px; --r-sm: 14px; --r-xs: 10px;
  --ease: cubic-bezier(.4,0,.2,1);
  --spring: cubic-bezier(.34,1.56,.64,1);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent;}
html{font-size:16px;-webkit-text-size-adjust:100%;overscroll-behavior:none;}
body{
  font-family:'DM Sans',sans-serif;background:var(--mist);color:var(--ink);
  min-height:100dvh;overflow-x:hidden;-webkit-font-smoothing:antialiased;
  overscroll-behavior:none;
}
button{font-family:inherit;cursor:pointer;border:none;background:none;}
input,select,textarea{font-family:inherit;-webkit-appearance:none;}
a{text-decoration:none;color:inherit;}
.serif{font-family:'Cormorant Garamond',serif;}
::-webkit-scrollbar{width:3px;}
::-webkit-scrollbar-thumb{background:var(--violet-l);border-radius:3px;}
/*==========widding_date===========*/
.wedding-banner-mobile {
    background: linear-gradient(135deg, var(--violet), var(--violet-l));
    color: white;
    padding: 24px 18px;
    margin: -20px -18px 20px -18px;
    box-shadow: var(--sh-md);
}

.wedding-banner-content {
    text-align: center;
}

.wedding-icon {
    font-size: 32px;
    margin-bottom: 12px;
    animation: heartbeat 1.5s ease-in-out infinite;
}

@keyframes heartbeat {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.couple-names {
    font-family: 'Cormorant Garamond', serif;
    font-size: 22px;
    font-weight: 600;
    margin: 0 0 12px 0;
    line-height: 1.3;
}

.wedding-date-display {
    font-size: 14px;
    opacity: 0.9;
    margin-bottom: 10px;
}

.wedding-countdown {
    font-size: 20px;
    font-weight: bold;
    color: var(--gold-l);
    margin: 12px 0;
    padding: 10px;
    background: rgba(255,255,255,0.1);
    border-radius: var(--r-sm);
}

.wedding-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-top: 16px;
    flex-wrap: wrap;
}

.wedding-action-btn {
    background: rgba(255,255,255,0.2);
    padding: 10px 18px;
    border-radius: var(--r-sm);
    text-decoration: none;
    color: white;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s var(--ease);
}

.wedding-action-btn:active {
    transform: scale(0.95);
    background: rgba(255,255,255,0.3);
}

/* ── APP SHELL ─────────────────────────────────────────── */
#app{max-width:430px;margin:0 auto;min-height:100dvh;position:relative;background:var(--white);box-shadow:0 0 80px rgba(0,0,0,.15);}

/* ── HEADER ─────────────────────────────────────────────── */
.app-header{
  position:fixed;top:0;left:50%;transform:translateX(-50%);
  width:100%;max-width:430px;height:var(--hh);
  background:linear-gradient(135deg,var(--violet) 0%,var(--violet-l) 100%);
  display:flex;align-items:center;justify-content:space-between;
  padding:0 18px;padding-top:env(safe-area-inset-top,0);
  z-index:100;box-shadow:var(--sh-md);
}
.app-header::after{
  content:'';position:absolute;bottom:-1px;left:0;right:0;
  height:1px;background:linear-gradient(90deg,transparent,var(--gold),transparent);
}
.header-logo{display:flex;align-items:center;gap:10px;}
.logo-icon{
  width:34px;height:34px;border-radius:10px;
  background:linear-gradient(135deg,var(--gold),var(--gold-l));
  display:flex;align-items:center;justify-content:center;
  font-size:17px;box-shadow:var(--sh-gold);
}
.header-logo h1{
  font-family:'Cormorant Garamond',serif;font-size:19px;font-weight:600;
  color:var(--white);letter-spacing:.3px;
}
.header-logo h1 span{color:var(--gold-l);}
.header-actions{display:flex;align-items:center;gap:6px;}
.header-btn{
  width:34px;height:34px;border-radius:10px;
  background:rgba(255,255,255,.15);
  display:flex;align-items:center;justify-content:center;
  color:var(--white);font-size:14px;transition:all .2s;position:relative;
}
.header-btn:active{transform:scale(.9);background:rgba(255,255,255,.25);}
.notif-dot{
  position:absolute;top:7px;right:7px;width:7px;height:7px;
  border-radius:50%;background:var(--gold);border:1.5px solid var(--violet);
}
.user-avatar{
  width:34px;height:34px;border-radius:10px;
  background:linear-gradient(135deg,var(--gold),var(--rose-l));
  display:flex;align-items:center;justify-content:center;
  font-family:'Cormorant Garamond',serif;font-weight:700;font-size:15px;
  color:var(--violet);border:2px solid rgba(255,255,255,.4);cursor:pointer;
}

/* ── CONTENT ─────────────────────────────────────────────── */
.app-content{padding-top:var(--hh);padding-bottom:calc(var(--nh)+12px);min-height:100dvh;}

/* ── PAGES ───────────────────────────────────────────────── */
.page{display:none;animation:pageIn .3s var(--ease) both;}
.page.active{display:block;}
@keyframes pageIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}

/* ── BOTTOM NAV ──────────────────────────────────────────── */
.bottom-nav{
  position:fixed;bottom:0;left:50%;transform:translateX(-50%);
  width:100%;max-width:430px;height:var(--nh);
  background:var(--white);display:flex;align-items:center;justify-content:space-around;
  border-top:1px solid rgba(76,29,149,.08);
  box-shadow:0 -6px 24px rgba(76,29,149,.1);
  z-index:100;
  padding-bottom:env(safe-area-inset-bottom,0);
}
.nav-btn{
  flex:1;height:100%;display:flex;flex-direction:column;
  align-items:center;justify-content:center;gap:4px;padding:8px 0;
  color:var(--ink-l);font-size:9.5px;font-weight:500;letter-spacing:.3px;
  text-transform:uppercase;transition:all .25s;position:relative;
}
.nav-icon{
  width:42px;height:30px;display:flex;align-items:center;justify-content:center;
  border-radius:var(--r-xs);font-size:17px;transition:all .3s var(--spring);
}
.nav-btn.active{color:var(--violet-l);}
.nav-btn.active .nav-icon{
  background:var(--violet-pale);color:var(--violet-l);
  transform:translateY(-4px) scale(1.1);box-shadow:var(--sh-sm);
}
.nav-btn.active::before{
  content:'';position:absolute;top:0;left:50%;transform:translateX(-50%);
  width:30px;height:3px;border-radius:0 0 4px 4px;
  background:linear-gradient(90deg,var(--violet),var(--gold));
}
.nav-btn:active .nav-icon{transform:scale(.88);}

/* ── FAB ─────────────────────────────────────────────────── */
.fab{
  position:fixed;bottom:calc(var(--nh)+14px);right:18px;
  width:54px;height:54px;border-radius:16px;
  background:linear-gradient(135deg,var(--violet-m),var(--violet-l));
  color:var(--white);font-size:22px;
  display:flex;align-items:center;justify-content:center;
  box-shadow:var(--sh-md),0 0 0 0 rgba(124,58,237,.4);
  z-index:99;transition:all .3s;
  animation:pulse-ring 3s infinite;
}
.fab:active{transform:scale(.9);}
@keyframes pulse-ring{
  0%,100%{box-shadow:var(--sh-md),0 0 0 0 rgba(124,58,237,.3);}
  50%{box-shadow:var(--sh-md),0 0 0 10px rgba(124,58,237,0);}
}

/* ── HERO BANNER ─────────────────────────────────────────── */
.hero-banner{
  margin:14px;border-radius:var(--r);
  background:linear-gradient(135deg,var(--violet) 0%,var(--violet-l) 60%,var(--rose) 100%);
  padding:22px 18px 18px;position:relative;overflow:hidden;
}
.hero-banner::before{
  content:'';position:absolute;top:-40px;right:-40px;
  width:150px;height:150px;border-radius:50%;
  background:radial-gradient(circle,rgba(201,168,76,.22),transparent 70%);
}
.hero-banner::after{
  content:'';position:absolute;bottom:-20px;left:-20px;
  width:90px;height:90px;border-radius:50%;
  background:radial-gradient(circle,rgba(255,255,255,.07),transparent 70%);
}
.hero-greeting{font-size:12px;color:rgba(255,255,255,.7);margin-bottom:3px;}
.hero-title{
  font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:700;
  color:var(--white);line-height:1.2;margin-bottom:14px;position:relative;z-index:1;
}
.hero-title span{color:var(--gold-l);font-style:italic;}
.countdown-widget{
  background:rgba(255,255,255,.12);backdrop-filter:blur(10px);
  border-radius:var(--r-sm);padding:12px 14px;
  display:flex;align-items:center;gap:12px;
  border:1px solid rgba(255,255,255,.15);position:relative;z-index:1;
}
.cd-icon{font-size:22px;}
.cd-info{flex:1;}
.cd-label{font-size:10px;color:rgba(255,255,255,.65);text-transform:uppercase;letter-spacing:.8px;}
.cd-date{font-family:'Cormorant Garamond',serif;font-size:15px;font-weight:600;color:var(--white);margin:1px 0;}
.cd-days{font-size:20px;font-weight:700;color:var(--gold-l);letter-spacing:-.5px;}
.cd-suf{font-size:10px;color:rgba(255,255,255,.65);}
.cd-edit-btn{
  width:30px;height:30px;border-radius:8px;
  background:rgba(255,255,255,.15);color:var(--white);
  display:flex;align-items:center;justify-content:center;font-size:12px;
}
.cd-edit-btn:active{transform:scale(.9);}

/* ── STATS GRID ──────────────────────────────────────────── */
.stats-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:0 14px;margin-top:14px;}
.stat-card{
  background:var(--white);border-radius:var(--r-sm);padding:14px;
  box-shadow:var(--sh-sm);border:1px solid rgba(76,29,149,.05);
  position:relative;overflow:hidden;
}
.stat-card::before{
  content:'';position:absolute;top:0;left:0;right:0;height:3px;
  border-radius:var(--r-sm) var(--r-sm) 0 0;
}
.stat-card.total::before{background:linear-gradient(90deg,var(--violet),var(--gold));}
.stat-card.paid::before{background:linear-gradient(90deg,var(--green),var(--green-l));}
.stat-card.pending::before{background:linear-gradient(90deg,var(--amber),var(--amber-l));}
.stat-card.items::before{background:linear-gradient(90deg,var(--rose),var(--rose-l));}
.stat-label{font-size:10px;color:var(--ink-l);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px;}
.stat-value{font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:700;color:var(--ink);line-height:1;}
.stat-value .cur{font-size:11px;font-weight:400;color:var(--ink-l);font-family:'DM Sans',sans-serif;}
.stat-sub{font-size:10px;color:var(--ink-l);margin-top:3px;}
.stat-icon-bg{position:absolute;top:12px;right:12px;font-size:18px;opacity:.1;}

/* ── PROGRESS ────────────────────────────────────────────── */
.progress-section{padding:14px 14px 4px;}
.progress-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;}
.progress-title{font-weight:600;font-size:13px;color:var(--ink);}
.progress-pct{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:700;color:var(--violet-l);}
.progress-bar{height:7px;background:var(--violet-pale);border-radius:7px;overflow:hidden;}
.progress-fill{
  height:100%;border-radius:7px;
  background:linear-gradient(90deg,var(--violet) 0%,var(--gold) 100%);
  transition:width 1.2s var(--ease);position:relative;
}
.progress-fill::after{
  content:'';position:absolute;inset:0;
  background:linear-gradient(90deg,transparent 40%,rgba(255,255,255,.4) 60%,transparent 80%);
  animation:shimmer 2s infinite;
}
@keyframes shimmer{from{transform:translateX(-100%)}to{transform:translateX(200%)}}

/* ── CATEGORY CARDS ──────────────────────────────────────── */
.section-header{display:flex;justify-content:space-between;align-items:center;padding:6px 14px 10px;}
.section-title{font-family:'Cormorant Garamond',serif;font-size:19px;font-weight:600;color:var(--ink);}
.section-link{font-size:12px;color:var(--violet-l);font-weight:500;}
.category-list{padding:0 14px;display:flex;flex-direction:column;gap:9px;margin-bottom:14px;}
.cat-card{
  background:var(--white);border-radius:var(--r-sm);padding:13px 14px;
  box-shadow:var(--sh-sm);border:1px solid rgba(76,29,149,.05);
  display:flex;align-items:center;gap:11px;
  transition:transform .2s,box-shadow .2s;
}
.cat-card:active{transform:scale(.98);box-shadow:none;}
.cat-icon-wrap{
  width:40px;height:40px;border-radius:11px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;font-size:17px;
}
.cat-info{flex:1;min-width:0;}
.cat-name{font-weight:600;font-size:13px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.cat-bar{height:3px;border-radius:3px;background:rgba(76,29,149,.08);margin:4px 0 3px;overflow:hidden;}
.cat-bar-fill{height:100%;border-radius:3px;transition:width .8s var(--ease);}
.cat-amounts{display:flex;justify-content:space-between;font-size:10px;}
.cat-total{color:var(--ink-l);}
.cat-paid{font-weight:600;}
.cat-badge{padding:3px 9px;border-radius:20px;font-size:10px;font-weight:700;flex-shrink:0;}
.cat-badge.done{background:rgba(6,95,70,.1);color:var(--green);}
.cat-badge.partial{background:rgba(146,64,14,.1);color:var(--amber);}
.cat-badge.none{background:rgba(76,29,149,.08);color:var(--violet-m);}

/* ── EXPENSE LIST ────────────────────────────────────────── */
.search-bar{padding:10px 14px;}
.search-wrap{
  position:relative;display:flex;align-items:center;
  background:var(--white);border-radius:13px;
  box-shadow:var(--sh-sm);border:1.5px solid rgba(76,29,149,.08);
}
.search-wrap input{
  flex:1;padding:11px 42px 11px 38px;border:none;background:none;outline:none;
  font-size:13px;color:var(--ink);
}
.search-wrap input::placeholder{color:var(--ink-l);font-size:12px;}
.search-icon{position:absolute;left:13px;color:var(--ink-l);font-size:13px;}
.filter-btn button{
  position:absolute;right:10px;width:26px;height:26px;border-radius:7px;
  background:var(--violet-pale);color:var(--violet-l);
  display:flex;align-items:center;justify-content:center;font-size:11px;
}
.filter-chips{display:flex;gap:7px;padding:0 14px 10px;overflow-x:auto;scrollbar-width:none;}
.filter-chips::-webkit-scrollbar{display:none;}
.chip{
  flex-shrink:0;padding:5px 13px;border-radius:20px;font-size:11px;font-weight:500;
  border:1.5px solid var(--violet-pale);color:var(--ink-l);background:var(--white);
  transition:all .2s;
}
.chip.active{background:var(--violet-l);color:var(--white);border-color:var(--violet-l);}
.expense-list{padding:0 14px;display:flex;flex-direction:column;gap:7px;}
.expense-item{
  background:var(--white);border-radius:var(--r-sm);padding:13px 14px;
  box-shadow:var(--sh-sm);border:1px solid rgba(76,29,149,.05);
  display:flex;align-items:center;gap:11px;position:relative;overflow:hidden;
  transition:transform .15s,box-shadow .15s;
}
.expense-item:active{transform:scale(.99);}
.expense-item.paid-item{border-left:3px solid var(--green);}
.expense-item.unpaid-item{border-left:3px solid var(--amber);}
.expense-dot{width:38px;height:38px;border-radius:11px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:15px;}
.expense-info{flex:1;min-width:0;}
.expense-name{font-weight:600;font-size:13px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.expense-meta{font-size:10px;color:var(--ink-l);margin-top:2px;}
.expense-amount{text-align:right;flex-shrink:0;}
.expense-total{font-family:'Cormorant Garamond',serif;font-size:16px;font-weight:700;color:var(--ink);}
.expense-status{font-size:9.5px;font-weight:700;padding:2px 7px;border-radius:8px;display:inline-block;margin-top:2px;}
.status-paid{background:rgba(6,95,70,.1);color:var(--green);}
.status-unpaid{background:rgba(146,64,14,.1);color:var(--amber);}
.expense-actions{display:flex;gap:5px;margin-left:3px;}
.action-btn{
  width:28px;height:28px;border-radius:8px;
  display:flex;align-items:center;justify-content:center;font-size:11px;
  transition:all .15s;
}
.action-btn:active{transform:scale(.85);}
.btn-toggle{background:rgba(6,95,70,.1);color:var(--green);}
.btn-toggle.undo{background:rgba(146,64,14,.1);color:var(--amber);}
.btn-edit{background:var(--violet-pale);color:var(--violet-l);}
.btn-del{background:rgba(190,24,93,.1);color:var(--rose);}
.cat-section-label{padding:12px 14px 5px;display:flex;align-items:center;gap:7px;}
.cat-section-badge{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;padding:2px 9px;border-radius:7px;}
.cat-subtotal{font-size:11px;color:var(--ink-l);margin-left:auto;}

/* ── PAYMENTS PAGE ───────────────────────────────────────── */
.payments-summary{
  margin:14px;border-radius:var(--r);
  background:linear-gradient(135deg,var(--green) 0%,#047857 100%);
  padding:18px;color:var(--white);
}
.pay-sum-title{font-size:11px;text-transform:uppercase;letter-spacing:.8px;opacity:.7;margin-bottom:3px;}
.pay-sum-amount{font-family:'Cormorant Garamond',serif;font-size:30px;font-weight:700;}
.pay-sum-sub{font-size:11px;opacity:.7;margin-top:3px;}
.pay-meta{display:flex;gap:14px;margin-top:14px;}
.pay-meta-item{flex:1;background:rgba(255,255,255,.12);border-radius:9px;padding:9px 11px;}
.pay-meta-label{font-size:9.5px;text-transform:uppercase;letter-spacing:.5px;opacity:.7;}
.pay-meta-val{font-family:'Cormorant Garamond',serif;font-size:16px;font-weight:700;}
.payments-group{padding:0 14px;margin-bottom:18px;}
.group-title{
  font-family:'Cormorant Garamond',serif;font-size:17px;font-weight:600;
  color:var(--ink);margin:14px 0 9px;display:flex;align-items:center;gap:7px;
}
.group-dot{width:7px;height:7px;border-radius:50%;}
.pay-item{
  background:var(--white);border-radius:var(--r-sm);padding:12px 14px;
  box-shadow:var(--sh-sm);border:1px solid rgba(76,29,149,.05);
  display:flex;align-items:center;gap:11px;margin-bottom:7px;
}
.pay-item .pay-info{flex:1;min-width:0;}
.pay-item .pay-name{font-weight:600;font-size:13px;color:var(--ink);}
.pay-item .pay-cat{font-size:10px;color:var(--ink-l);margin-top:1px;}
.pay-item .pay-date{font-size:10px;color:var(--ink-l);margin-top:1px;}
.pay-item .pay-amount{font-family:'Cormorant Garamond',serif;font-size:15px;font-weight:700;text-align:right;flex-shrink:0;}
.pay-action-btn{
  width:30px;height:30px;border-radius:8px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;font-size:12px;
  transition:all .15s;
}
.pay-action-btn:active{transform:scale(.88);}

/* ── GUIDE PAGE ──────────────────────────────────────────── */
.guide-hero{
  margin:14px;border-radius:var(--r);
  background:linear-gradient(135deg,var(--ink) 0%,var(--violet-m) 100%);
  padding:26px 18px;text-align:center;position:relative;overflow:hidden;
}
.guide-hero-icon{font-size:44px;margin-bottom:10px;position:relative;z-index:1;}
.guide-hero-title{font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:700;color:var(--white);position:relative;z-index:1;margin-bottom:5px;}
.guide-hero-sub{font-size:12px;color:rgba(255,255,255,.6);position:relative;z-index:1;}
.guide-steps{padding:0 14px;}
.guide-step{position:relative;padding-left:50px;padding-bottom:22px;}
.guide-step:not(:last-child)::before{
  content:'';position:absolute;left:18px;top:38px;bottom:0;width:2px;
  background:linear-gradient(to bottom,var(--violet-l),transparent);
}
.step-num{
  position:absolute;left:0;top:0;width:38px;height:38px;border-radius:11px;
  background:linear-gradient(135deg,var(--violet),var(--violet-l));
  display:flex;align-items:center;justify-content:center;
  font-size:17px;font-weight:700;color:var(--white);box-shadow:var(--sh-sm);
}
.step-num.gold{background:linear-gradient(135deg,var(--gold),var(--gold-l));}
.step-card{background:var(--white);border-radius:var(--r-sm);padding:14px;box-shadow:var(--sh-sm);border:1px solid rgba(76,29,149,.05);}
.step-badge{font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;padding:2px 9px;border-radius:8px;background:var(--violet-pale);color:var(--violet-l);display:inline-block;margin-bottom:7px;}
.step-title{font-family:'Cormorant Garamond',serif;font-size:17px;font-weight:600;color:var(--ink);margin-bottom:5px;}
.step-desc{font-size:12px;color:var(--ink-l);line-height:1.6;margin-bottom:9px;}
.step-checks{list-style:none;}
.step-checks li{font-size:12px;color:var(--ink);padding:3px 0 3px 18px;position:relative;}
.step-checks li::before{content:'✓';position:absolute;left:0;color:var(--violet-l);font-weight:700;}
.step-time{font-size:10px;color:var(--ink-l);margin-top:8px;display:flex;align-items:center;gap:5px;}

/* ── PROFILE PAGE ────────────────────────────────────────── */
.profile-hero{
  background:linear-gradient(135deg,var(--violet) 0%,var(--violet-l) 60%,var(--gold) 100%);
  padding:30px 18px 22px;text-align:center;margin-bottom:-18px;
}
.profile-avatar-big{
  width:76px;height:76px;border-radius:22px;margin:0 auto 11px;
  background:linear-gradient(135deg,var(--gold),var(--gold-l));
  display:flex;align-items:center;justify-content:center;
  font-family:'Cormorant Garamond',serif;font-size:34px;font-weight:700;color:var(--violet);
  border:3px solid rgba(255,255,255,.5);box-shadow:var(--sh-gold);
}
.profile-name{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--white);}
.profile-role{font-size:11px;color:rgba(255,255,255,.65);margin-top:3px;}
.profile-cards{padding:26px 14px 14px;display:flex;flex-direction:column;gap:10px;}
.profile-card{background:var(--white);border-radius:var(--r-sm);padding:14px;box-shadow:var(--sh-sm);}
.profile-card-title{font-size:11px;text-transform:uppercase;letter-spacing:.8px;font-weight:700;color:var(--ink-l);margin-bottom:11px;}
.info-row{display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid rgba(76,29,149,.05);}
.info-row:last-child{border-bottom:none;}
.info-label{font-size:12px;color:var(--ink-l);display:flex;align-items:center;gap:7px;}
.info-label i{width:14px;color:var(--violet-l);}
.info-val{font-size:12px;font-weight:600;color:var(--ink);}
.profile-actions{display:flex;flex-direction:column;gap:9px;padding:0 14px 20px;}
.action-row{
  display:flex;align-items:center;justify-content:space-between;
  padding:13px 14px;border-radius:var(--r-sm);
  background:var(--white);box-shadow:var(--sh-sm);
  transition:all .15s;
}
.action-row:active{transform:scale(.98);}
.action-row-left{display:flex;align-items:center;gap:11px;}
.action-row-icon{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:15px;}
.action-title{font-size:13px;font-weight:600;color:var(--ink);}
.action-sub{font-size:10px;color:var(--ink-l);}
.action-row i.arrow{color:var(--ink-l);font-size:11px;}
.logout-row{background:rgba(190,24,93,.05);}
.logout-row .action-title{color:var(--rose);}
.logout-row .action-row-icon{background:rgba(190,24,93,.1);color:var(--rose);}

/* ── MODAL ───────────────────────────────────────────────── */
.modal-overlay{
  position:fixed;inset:0;background:rgba(10,0,20,.65);
  backdrop-filter:blur(4px);z-index:200;
  display:none;align-items:flex-end;justify-content:center;
}
.modal-overlay.open{display:flex;}
.modal-sheet{
  width:100%;max-width:430px;background:var(--white);
  border-radius:22px 22px 0 0;padding:0 0 env(safe-area-inset-bottom,16px);
  animation:sheetUp .35s var(--spring) both;
  max-height:92dvh;overflow-y:auto;
}
@keyframes sheetUp{from{transform:translateY(100%)}to{transform:translateY(0)}}
.modal-handle{width:38px;height:4px;border-radius:4px;background:rgba(76,29,149,.15);margin:11px auto 0;}
.modal-header{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid rgba(76,29,149,.06);}
.modal-title{font-family:'Cormorant Garamond',serif;font-size:19px;font-weight:600;color:var(--ink);}
.modal-close{width:30px;height:30px;border-radius:8px;background:var(--mist);display:flex;align-items:center;justify-content:center;font-size:13px;color:var(--ink-l);}
.modal-body{padding:18px;}
.form-group{margin-bottom:14px;}
.form-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--ink-l);margin-bottom:5px;display:block;}
.form-input{
  width:100%;padding:11px 14px;border-radius:11px;
  border:1.5px solid rgba(76,29,149,.12);font-size:13px;color:var(--ink);
  background:var(--white);outline:none;transition:border-color .2s;
}
.form-input:focus{border-color:var(--violet-l);box-shadow:0 0 0 3px rgba(124,58,237,.07);}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:11px;}
.form-check-wrap{
  display:flex;align-items:center;gap:9px;padding:11px 13px;
  border-radius:11px;background:var(--mist);border:1.5px solid transparent;cursor:pointer;
}
.form-check-wrap input[type=checkbox]{display:none;}
.custom-check{width:19px;height:19px;border-radius:6px;border:2px solid rgba(76,29,149,.2);display:flex;align-items:center;justify-content:center;transition:all .2s;flex-shrink:0;}
.form-check-wrap.checked .custom-check{background:var(--violet-l);border-color:var(--violet-l);color:var(--white);}
.form-check-wrap.checked{background:var(--violet-pale);border-color:var(--violet-l);}
.check-label{font-size:13px;color:var(--ink);}
.modal-footer{padding:0 18px 18px;display:flex;gap:9px;}
.btn-primary-modal{
  flex:1;padding:13px;border-radius:13px;
  background:linear-gradient(135deg,var(--violet-m),var(--violet-l));
  color:var(--white);font-size:14px;font-weight:600;box-shadow:var(--sh-sm);
}
.btn-primary-modal:active{transform:scale(.97);}
.btn-cancel-modal{padding:13px 18px;border-radius:13px;background:var(--mist);color:var(--ink-l);font-size:14px;font-weight:500;}
.btn-cancel-modal:active{transform:scale(.97);}
.modal-total-preview{
  background:var(--violet-pale);border-radius:11px;padding:11px 14px;
  display:flex;justify-content:space-between;align-items:center;
  margin:0 0 14px;
}
.mtp-label{font-size:11px;color:var(--violet-m);font-weight:600;text-transform:uppercase;}
.mtp-val{font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:700;color:var(--violet);}
.date-input-big{
  width:100%;padding:14px;border-radius:13px;
  border:2px solid var(--violet-pale);font-size:17px;text-align:center;
  font-family:'Cormorant Garamond',serif;color:var(--violet);outline:none;
  transition:border-color .2s;
}
.date-input-big:focus{border-color:var(--violet-l);}
.preview-box{background:var(--violet-pale);border-radius:13px;padding:14px;text-align:center;margin-top:11px;}
.preview-date{font-family:'Cormorant Garamond',serif;font-size:19px;font-weight:600;color:var(--violet);}
.preview-days{font-size:12px;color:var(--violet-m);margin-top:3px;}

/* ── TOAST ───────────────────────────────────────────────── */
.toast-wrap{position:fixed;top:calc(var(--hh)+10px);left:50%;transform:translateX(-50%);z-index:300;width:calc(100% - 28px);max-width:400px;pointer-events:none;}
.toast{
  background:var(--ink);color:var(--white);border-radius:13px;
  padding:11px 14px;display:flex;align-items:center;gap:9px;
  box-shadow:var(--sh-lg);font-size:12px;font-weight:500;
  transform:translateY(-14px) scale(.95);opacity:0;
  transition:all .3s var(--spring);pointer-events:auto;
}
.toast.show{transform:translateY(0) scale(1);opacity:1;}
.toast.success{background:var(--green);}
.toast.error{background:var(--rose);}
.toast.warning{background:var(--amber);}
.toast-icon{font-size:15px;flex-shrink:0;}

/* ── EMPTY STATE ──────────────────────────────────────────── */
.empty-state{text-align:center;padding:44px 20px;}
.empty-icon{font-size:52px;margin-bottom:14px;opacity:.45;}
.empty-title{font-family:'Cormorant Garamond',serif;font-size:21px;font-weight:600;color:var(--ink);margin-bottom:7px;}
.empty-sub{font-size:13px;color:var(--ink-l);line-height:1.6;}
.skeleton{background:linear-gradient(90deg,rgba(76,29,149,.06) 25%,rgba(76,29,149,.12) 50%,rgba(76,29,149,.06) 75%);background-size:200% 100%;animation:skel 1.4s infinite;border-radius:8px;}
@keyframes skel{from{background-position:200% 0}to{background-position:-200% 0}}
</style>
</head>
<body>
<div id="app">

  <!-- HEADER -->
  <header class="app-header">
    <div class="header-logo">
      <div class="logo-icon"><img src="assets/images/wedding.jpg" alt="logo du site wedplan" width="52px" height="52px" style="border-radius:50%;"></div>
      <h1 class="serif">Wed<span>Plan</span></h1>
    </div>
    <div class="header-actions">
      <button class="header-btn" onclick="App.showNotifications()" title="Notifications">
        <i class="fas fa-bell"></i>
        <span class="notif-dot" id="notif-dot" style="display:none"></span>
      </button>
      <div class="user-avatar" id="header-avatar" onclick="App.goTo('profile')">
        <?= mb_strtoupper(mb_substr($user['username'] ?? 'U', 0, 1)) ?>
      </div>
    </div>
  </header>

  <!-- MAIN CONTENT -->
  <main class="app-content">

    <!-- ===== DASHBOARD ===== -->
    <section id="page-dashboard" class="page active">
      <div class="hero-banner">
        <!-- ── SECTION BANNIÈRE MARIAGE (NOUVEAU) ────────────────────── -->
      <?php if ($weddingInfo && $weddingDate): ?>
      <div id="wedding-banner" class="wedding-banner-mobile">
          <div class="wedding-banner-content">
              <div class="wedding-icon">💑</div>
              <h2 class="couple-names"><?= htmlspecialchars($fiance_nom_complet . ' & ' . $fiancee_nom_complet) ?></h2>
              <div class="wedding-date-display">
                  <i class="fas fa-calendar-alt"></i> 
                  <span id="wedding-date-text"><?= formatDateFrancais($weddingDate) ?></span>
              </div>
              <div class="wedding-countdown" id="wedding-countdown">Calcul en cours...</div>
              
              <?php if ($isAdmin): ?>
              <div class="wedding-actions">
                  <a href="wedding_date.php" class="wedding-action-btn">
                      <i class="fas fa-edit"></i> Modifier
                  </a>
                  <a href="admin_sponsors.php" class="wedding-action-btn">
                      <i class="fas fa-users"></i> Parrains
                  </a>
              </div>
              <?php endif; ?>
          </div>
      </div>
      <?php endif; ?>

      <!-- ── SECTION COMMENTAIRES PARRAINS (NOUVEAU) ────────────────── -->
      <?php if ($wedding_id > 0 && !empty($recentComments)): ?>
      <div id="sponsor-comments-section" class="comments-section-mobile">
          <div class="section-header-comments">
              <h3>
                  <i class="fas fa-comments"></i>
                  Messages des Parrains
              </h3>
          </div>
          
          <div class="comments-list-mobile">
              <?php foreach ($recentComments as $comment): ?>
              <div class="comment-card-mobile">
                  <div class="comment-header-mobile">
                      <div class="comment-author">
                          <strong><?= htmlspecialchars($comment['sponsor_nom_complet']) ?></strong>
                          <span class="sponsor-role-badge">
                              <?= $comment['role'] === 'parrain' ? '👔 Parrain' : '🎓 Conseiller' ?>
                          </span>
                      </div>
                      <span class="comment-date-mobile">
                          <?= date('d/m/Y', strtotime($comment['created_at'])) ?>
                      </span>
                  </div>
                  <div class="comment-body-mobile">
                      <?= nl2br(htmlspecialchars($comment['commentaire'])) ?>
                  </div>
                  <?php if (!empty($comment['type_commentaire']) && $comment['type_commentaire'] !== 'general'): ?>
                  <div class="comment-type-badge">
                      <?php if ($comment['type_commentaire'] === 'suggestion'): ?>
                          💡 Suggestion
                      <?php elseif ($comment['type_commentaire'] === 'depense'): ?>
                          💰 Dépense
                      <?php endif; ?>
                  </div>
                  <?php endif; ?>
              </div>
              <?php endforeach; ?>
          </div>
          
          <div class="view-all-comments">
              <a href="wedding_date.php" class="view-all-link">
                  Voir tous les commentaires <i class="fas fa-arrow-right"></i>
              </a>
          </div>
      </div>
      <style>
      .comments-section-mobile {
          background: white;
          border-radius: var(--r);
          padding: 20px;
          margin-bottom: 20px;
          box-shadow: var(--sh-sm);
      }

      .section-header-comments h3 {
          display: flex;
          align-items: center;
          gap: 10px;
          color: var(--violet);
          font-size: 18px;
          margin: 0 0 16px 0;
      }

      .comments-list-mobile {
          display: flex;
          flex-direction: column;
          gap: 12px;
      }

      .comment-card-mobile {
          padding: 16px;
          background: var(--mist);
          border-radius: var(--r-sm);
          border-left: 3px solid var(--violet);
      }

      .comment-header-mobile {
          display: flex;
          justify-content: space-between;
          align-items: flex-start;
          margin-bottom: 12px;
          gap: 10px;
      }

      .comment-author {
          display: flex;
          flex-direction: column;
          gap: 4px;
      }

      .comment-author strong {
          color: var(--violet);
          font-size: 15px;
      }

      .sponsor-role-badge {
          font-size: 11px;
          color: var(--violet-l);
          font-weight: normal;
      }

      .comment-date-mobile {
          font-size: 11px;
          color: #666;
          white-space: nowrap;
      }

      .comment-body-mobile {
          color: var(--ink);
          line-height: 1.5;
          font-size: 14px;
      }

      .comment-type-badge {
          margin-top: 10px;
          display: inline-block;
          padding: 5px 12px;
          background: var(--gold-pale);
          color: var(--amber);
          border-radius: 12px;
          font-size: 11px;
          font-weight: 500;
      }

      .view-all-comments {
          text-align: center;
          margin-top: 16px;
          padding-top: 16px;
          border-top: 1px solid #eee;
      }

      .view-all-link {
          color: var(--violet);
          text-decoration: none;
          font-size: 14px;
          font-weight: 500;
          display: inline-flex;
          align-items: center;
          gap: 6px;
      }

      .view-all-link:active {
          color: var(--violet-l);
      }
      </style>
      <?php endif; ?>
      </div>

      <div class="stats-grid" id="stats-grid">
        <div class="stat-card total skeleton" style="height:88px"></div>
        <div class="stat-card paid skeleton" style="height:88px"></div>
        <div class="stat-card pending skeleton" style="height:88px"></div>
        <div class="stat-card items skeleton" style="height:88px"></div>
      </div>

      <div class="progress-section">
        <div class="progress-header">
          <span class="progress-title">Progression des paiements</span>
          <span class="progress-pct serif" id="progress-pct">0%</span>
        </div>
        <div class="progress-bar"><div class="progress-fill" id="progress-fill" style="width:0%"></div></div>
      </div>

      <div class="section-header">
        <h3 class="section-title serif">Par Catégorie</h3>
        <button class="section-link" onclick="App.goTo('expenses')">Voir tout →</button>
      </div>
      <div class="category-list" id="cat-summary-list"></div>
    </section>

    <!-- ===== EXPENSES ===== -->
    <section id="page-expenses" class="page">
      <div class="search-bar">
        <div class="search-wrap">
          <i class="fas fa-search search-icon"></i>
          <input type="search" id="search-input" placeholder="Rechercher une dépense…" oninput="App.applyFilters()">
          <div class="filter-btn"><button onclick="App.setFilter('all')" title="Réinitialiser"><i class="fas fa-times"></i></button></div>
        </div>
      </div>
      <div class="filter-chips" id="filter-chips">
        <button class="chip active" data-filter="all" onclick="App.setFilter('all')">Tous</button>
        <button class="chip" data-filter="unpaid" onclick="App.setFilter('unpaid')">À payer</button>
        <button class="chip" data-filter="paid" onclick="App.setFilter('paid')">Payés</button>
      </div>
      <div id="expense-list-container"></div>
    </section>

    <!-- ===== PAYMENTS ===== -->
    <section id="page-payments" class="page">
      <div class="payments-summary">
        <div class="pay-sum-title">Montant total payé</div>
        <div class="pay-sum-amount serif" id="pay-total-amount">0 FCFA</div>
        <div class="pay-sum-sub" id="pay-total-sub">0 paiement(s) effectué(s)</div>
        <div class="pay-meta">
          <div class="pay-meta-item">
            <div class="pay-meta-label">Reste</div>
            <div class="pay-meta-val serif" id="pay-remaining">—</div>
          </div>
          <div class="pay-meta-item">
            <div class="pay-meta-label">Progression</div>
            <div class="pay-meta-val serif" id="pay-pct">0%</div>
          </div>
        </div>
      </div>
      <div class="payments-group">
        <h3 class="group-title serif"><span class="group-dot" style="background:var(--green)"></span>Éléments Payés</h3>
        <div id="paid-list"></div>
      </div>
      <div class="payments-group">
        <h3 class="group-title serif"><span class="group-dot" style="background:var(--amber)"></span>À Régler</h3>
        <div id="unpaid-list"></div>
      </div>
    </section>

    <!-- ===== GUIDE ===== -->
    <section id="page-guide" class="page">
      <div class="guide-hero">
        <div class="guide-hero-icon">💒</div>
        <h2 class="guide-hero-title serif">Guide Complet du Mariage</h2>
        <p class="guide-hero-sub">Toutes les étapes pour un mariage réussi</p>
      </div>
      <div class="guide-steps" id="guide-steps"></div>
    </section>

    <!-- ===== PROFILE ===== -->
    <section id="page-profile" class="page">
      <div class="profile-hero">
        <div class="profile-avatar-big" id="profile-avatar-big">
          <?= mb_strtoupper(mb_substr($user['username'] ?? 'U', 0, 1)) ?>
        </div>
        <div class="profile-name serif" id="profile-name"><?= htmlspecialchars($user['username'] ?? 'Utilisateur') ?></div>
        <div class="profile-role" id="profile-role">
          <?= ($user['role'] ?? '') === 'admin' ? '👑 Administrateur' : '💍 Utilisateur' ?>
        </div>
      </div>
      <div class="profile-cards">
        <div class="profile-card">
          <div class="profile-card-title">Informations</div>
          <div class="info-row"><span class="info-label"><i class="fas fa-user"></i>Nom d'utilisateur</span><span class="info-val" id="info-username"><?= htmlspecialchars($user['username'] ?? '—') ?></span></div>
          <div class="info-row"><span class="info-label"><i class="fas fa-envelope"></i>Email</span><span class="info-val" id="info-email"><?= htmlspecialchars($user['email'] ?? '—') ?></span></div>
          <div class="info-row"><span class="info-label"><i class="fas fa-shield-alt"></i>Rôle</span><span class="info-val"><?= ($user['role'] ?? '') === 'admin' ? 'Administrateur' : 'Utilisateur' ?></span></div>
        </div>
        <div class="profile-card">
          <div class="profile-card-title">Mes Statistiques</div>
          <div class="info-row"><span class="info-label"><i class="fas fa-list"></i>Dépenses totales</span><span class="info-val" id="profile-stat-items">—</span></div>
          <div class="info-row"><span class="info-label"><i class="fas fa-check"></i>Payées</span><span class="info-val" id="profile-stat-paid">—</span></div>
          <div class="info-row"><span class="info-label"><i class="fas fa-wallet"></i>Budget total</span><span class="info-val" id="profile-stat-budget">—</span></div>
        </div>
      </div>
      <div class="profile-actions">
        <div class="action-row" onclick="App.openDateModal()">
          <div class="action-row-left">
            <div class="action-row-icon" style="background:var(--violet-pale);color:var(--violet-l)"><i class="fas fa-calendar-alt"></i></div>
            <div><div class="action-title">Date du Mariage</div><div class="action-sub">Définir ou modifier</div></div>
          </div>
          <i class="fas fa-chevron-right arrow"></i>
        </div>
        <div class="action-row" onclick="App.changePassword()">
          <div class="action-row-left">
            <div class="action-row-icon" style="background:rgba(146,64,14,.1);color:var(--amber)"><i class="fas fa-key"></i></div>
            <div><div class="action-title">Changer le mot de passe</div><div class="action-sub">Sécuriser votre compte</div></div>
          </div>
          <i class="fas fa-chevron-right arrow"></i>
        </div>
        <div class="action-row logout-row" onclick="App.logout()">
          <div class="action-row-left">
            <div class="action-row-icon"><i class="fas fa-sign-out-alt"></i></div>
            <div><div class="action-title">Se déconnecter</div><div class="action-sub">Quitter l'application</div></div>
          </div>
          <i class="fas fa-chevron-right arrow"></i>
        </div>
      </div>
    </section>
  </main>

  <!-- BOTTOM NAV -->
  <nav class="bottom-nav">
    <button class="nav-btn active" id="nav-dashboard" onclick="App.goTo('dashboard')">
      <div class="nav-icon"><i class="fas fa-home"></i></div><span>Accueil</span>
    </button>
    <button class="nav-btn" id="nav-expenses" onclick="App.goTo('expenses')">
      <div class="nav-icon"><i class="fas fa-list-alt"></i></div><span>Dépenses</span>
    </button>
    <button class="nav-btn" id="nav-payments" onclick="App.goTo('payments')">
      <div class="nav-icon"><i class="fas fa-money-check-alt"></i></div><span>Paiements</span>
    </button>
    <button class="nav-btn" id="nav-guide" onclick="App.goTo('guide')">
      <div class="nav-icon"><i class="fas fa-book-open"></i></div><span>Guide</span>
    </button>
    <button class="nav-btn" id="nav-profile" onclick="App.goTo('profile')">
      <div class="nav-icon"><i class="fas fa-user-circle"></i></div><span>Profil</span>
    </button>
  </nav>

  <!-- FAB -->
  <button class="fab" id="fab-btn" onclick="App.openExpenseModal()" title="Ajouter une dépense">
    <i class="fas fa-plus"></i>
  </button>

  <!-- TOAST -->
  <div class="toast-wrap">
    <div class="toast" id="toast"><span class="toast-icon" id="toast-icon">✓</span><span id="toast-msg"></span></div>
  </div>

  <!-- MODAL: Dépense -->
  <div class="modal-overlay" id="expense-modal">
    <div class="modal-sheet">
      <div class="modal-handle"></div>
      <div class="modal-header">
        <h3 class="modal-title serif" id="expense-modal-title">Nouvelle Dépense</h3>
        <button class="modal-close" onclick="App.closeExpenseModal()"><i class="fas fa-times"></i></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit-expense-id">
        <div class="form-group">
          <label class="form-label">Catégorie *</label>
          <select class="form-input" id="form-category" required>
            <option value="">Sélectionner une catégorie…</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Nom de la dépense *</label>
          <input type="text" class="form-input" id="form-name" placeholder="Ex : Robe de mariée" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Quantité</label>
            <input type="number" class="form-input" id="form-qty" value="1" min="1" oninput="App.updateTotal()">
          </div>
          <div class="form-group">
            <label class="form-label">Fréquence</label>
            <input type="number" class="form-input" id="form-freq" value="1" min="1" oninput="App.updateTotal()">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Prix Unitaire (FCFA) *</label>
          <input type="number" class="form-input" id="form-price" placeholder="0" min="0" required oninput="App.updateTotal()">
        </div>
        <div id="modal-total-preview" class="modal-total-preview" style="display:none">
          <span class="mtp-label">Total calculé</span>
          <span class="mtp-val serif" id="mtp-val">0 FCFA</span>
        </div>
        <div class="form-group">
          <label class="form-label">Date de paiement</label>
          <input type="date" class="form-input" id="form-date">
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <input type="text" class="form-input" id="form-notes" placeholder="Commentaire optionnel…">
        </div>
        <div class="form-group">
          <label class="form-check-wrap" id="paid-check-wrap" onclick="App.togglePaidCheck()">
            <input type="checkbox" id="form-paid">
            <div class="custom-check" id="custom-check"></div>
            <span class="check-label">Marquer comme payé</span>
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-cancel-modal" onclick="App.closeExpenseModal()">Annuler</button>
        <button class="btn-primary-modal" onclick="App.saveExpense()" id="save-expense-btn">
          <i class="fas fa-save"></i> Enregistrer
        </button>
      </div>
    </div>
  </div>

  <!-- MODAL: Date -->
  <div class="modal-overlay" id="date-modal">
    <div class="modal-sheet">
      <div class="modal-handle"></div>
      <div class="modal-header">
        <h3 class="modal-title serif">📅 Date du Mariage</h3>
        <button class="modal-close" onclick="App.closeDateModal()"><i class="fas fa-times"></i></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Choisir la date</label>
          <input type="date" class="date-input-big" id="date-input" onchange="App.updateDatePreview()">
        </div>
        <div class="preview-box" id="date-preview-box" style="display:none">
          <div class="preview-date serif" id="date-preview-text">—</div>
          <div class="preview-days" id="date-preview-days">—</div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-cancel-modal" onclick="App.closeDateModal()">Annuler</button>
        <button class="btn-primary-modal" onclick="App.saveDateModal()">
          <i class="fas fa-save"></i> Enregistrer
        </button>
      </div>
    </div>
  </div>
</div>

<script>
'use strict';
const API      = '<?= APP_URL ?>/api/api.php';
const AUTH_API = '<?= APP_URL ?>/api/auth_api.php';

const CAT_THEMES = [
  {bg:'#ede9fe',text:'#6d28d9'},{bg:'#fce7f3',text:'#be185d'},
  {bg:'#dbeafe',text:'#1d4ed8'},{bg:'#d1fae5',text:'#065f46'},
  {bg:'#fef3c7',text:'#92400e'},{bg:'#ffedd5',text:'#c2410c'},
  {bg:'#f1f5f9',text:'#475569'},{bg:'#fdf4ff',text:'#7c3aed'},
  {bg:'#ecfdf5',text:'#047857'},{bg:'#fff7ed',text:'#c2410c'},
  {bg:'#f0fdf4',text:'#15803d'},{bg:'#fef9c3',text:'#854d0e'},
];
const getCatTheme = i => CAT_THEMES[i % CAT_THEMES.length];

const State = {
  user: null, expenses: [], categories: [], weddingDate: null,
  filter: 'all', searchQuery: '', currentPage: 'dashboard',
  editingId: null, paidCheck: false,
};

// ── Utils ──────────────────────────────────────────────────
const fmtN = n => new Intl.NumberFormat('fr-FR').format(Math.round(parseFloat(n)||0));
const fmtC = n => fmtN(n) + ' FCFA';
const fmtD = d => d ? new Date(d).toLocaleDateString('fr-FR',{day:'2-digit',month:'long',year:'numeric'}) : '—';
const fmtDs = d => d ? new Date(d).toLocaleDateString('fr-FR',{day:'2-digit',month:'2-digit',year:'numeric'}) : '';
const calcT = e => (parseFloat(e.quantity)||1)*(parseFloat(e.unit_price)||0)*(parseFloat(e.frequency)||1);
const esc = s => { const d=document.createElement('div');d.textContent=s;return d.innerHTML; };

let _toastTimer;
function toast(msg, type='success') {
  const t = document.getElementById('toast');
  const icons = {success:'✓',error:'✕',warning:'⚠',info:'ℹ'};
  document.getElementById('toast-icon').textContent = icons[type]||'ℹ';
  document.getElementById('toast-msg').textContent = msg;
  t.className = `toast show ${type}`;
  clearTimeout(_toastTimer);
  _toastTimer = setTimeout(()=>t.className='toast', 3200);
}

async function apiFetch(url, opts={}) {
  try {
    const r = await fetch(url, {
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',   // FIX : envoyer le cookie de session PHP
      ...opts
    });
    // Détecter une redirection vers login (réponse HTML au lieu de JSON)
    const ct = r.headers.get('content-type') || '';
    if (!ct.includes('application/json')) {
      // Serveur a renvoyé HTML (souvent une page de login) → session expirée
      window.location.href = '<?= APP_URL ?>/auth/login.php';
      return { success: false, message: 'Session expirée.' };
    }
    const json = await r.json();
    // Redirection si 401
    if (r.status === 401) {
      window.location.href = '<?= APP_URL ?>/auth/login.php';
      return json;
    }
    return json;
  } catch (err) {
    console.error('[apiFetch]', err);
    return { success: false, message: 'Erreur réseau. Vérifiez votre connexion.' };
  }
}

// ── APP ────────────────────────────────────────────────────
const App = {

  async init() {
    // Fermer modals sur overlay click
    document.querySelectorAll('.modal-overlay').forEach(o => {
      o.addEventListener('click', e => { if(e.target===o) o.classList.remove('open'); });
    });
    await this.loadData();
    this.loadWeddingDate();
    this.renderDashboard();
    this.renderGuide();
    // Enregistrer SW
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('<?= APP_URL ?>/sw.js').catch(()=>{});
    }
  },

  goTo(page) {
    document.querySelectorAll('.page').forEach(p=>p.classList.remove('active'));
    document.querySelectorAll('.nav-btn').forEach(b=>b.classList.remove('active'));
    const el = document.getElementById(`page-${page}`);
    const nav = document.getElementById(`nav-${page}`);
    if(el) el.classList.add('active');
    if(nav) nav.classList.add('active');
    State.currentPage = page;
    document.getElementById('fab-btn').style.display =
      (page==='dashboard'||page==='expenses') ? 'flex':'none';
    if(page==='dashboard') this.renderDashboard();
    else if(page==='expenses') this.renderExpenses();
    else if(page==='payments') this.renderPayments();
    window.scrollTo({top:0,behavior:'smooth'});
  },

  async loadData() {
    const [eR,cR] = await Promise.all([
      apiFetch(`${API}?action=get_all`),
      apiFetch(`${API}?action=get_categories`)
    ]);
    if(eR.success) State.expenses = eR.data||[];
    if(cR.success) State.categories = cR.data||[];
    this.populateCategorySelect();
    this.updateFilterChips();
  },

  populateCategorySelect() {
    const sel = document.getElementById('form-category');
    sel.innerHTML = '<option value="">Sélectionner…</option>' +
      State.categories.map(c=>`<option value="${c.id}">${esc(c.name)}</option>`).join('');
  },

  updateFilterChips() {
    const el = document.getElementById('filter-chips');
    if(!el) return;
    el.innerHTML =
      `<button class="chip ${State.filter==='all'?'active':''}" data-filter="all" onclick="App.setFilter('all')">Tous</button>
       <button class="chip ${State.filter==='unpaid'?'active':''}" data-filter="unpaid" onclick="App.setFilter('unpaid')">À payer</button>
       <button class="chip ${State.filter==='paid'?'active':''}" data-filter="paid" onclick="App.setFilter('paid')">Payés</button>` +
      State.categories.map(c=>`<button class="chip ${State.filter==='cat_'+c.id?'active':''}" data-filter="cat_${c.id}" onclick="App.setFilter('cat_${c.id}')">${esc(c.name)}</button>`).join('');
  },

  // ── Wedding Date ─────────────────────────────────────────
  async loadWeddingDate() {
    const r = await apiFetch(`${API}?action=get_wedding_date`);
    if(r.success && r.data && r.data.date) {
      State.weddingDate = r.data.date;
      this.renderCountdown();
    } else {
      document.getElementById('cd-days').textContent = '—';
      document.getElementById('cd-suf').textContent = ' Définissez votre date !';
      document.getElementById('cd-date-text').textContent = 'Aucune date définie';
    }
  },

  renderCountdown() {
    if(!State.weddingDate) return;
    const wed = new Date(State.weddingDate); wed.setHours(0,0,0,0);
    const now = new Date(); now.setHours(0,0,0,0);
    const diff = Math.floor((wed-now)/86400000);
    document.getElementById('cd-date-text').textContent = fmtD(State.weddingDate);
    if(diff>0) {
      document.getElementById('cd-days').textContent = diff;
      const mo=Math.floor(diff/30),d=diff%30;
      document.getElementById('cd-suf').textContent = mo>0?` j → ${mo} mois ${d}j`:' jours restants';
    } else if(diff===0) {
      document.getElementById('cd-days').textContent='🎉';
      document.getElementById('cd-suf').textContent=" C'est aujourd'hui !";
    } else {
      document.getElementById('cd-days').textContent=Math.abs(diff);
      document.getElementById('cd-suf').textContent=' jours écoulés';
    }
  },

  openDateModal() {
    if(State.weddingDate) document.getElementById('date-input').value=State.weddingDate;
    document.getElementById('date-modal').classList.add('open');
    this.updateDatePreview();
  },
  closeDateModal() { document.getElementById('date-modal').classList.remove('open'); },
  updateDatePreview() {
    const val = document.getElementById('date-input').value;
    if(!val) return;
    const d=new Date(val),now=new Date(); now.setHours(0,0,0,0);
    const diff=Math.floor((d-now)/86400000);
    document.getElementById('date-preview-text').textContent = fmtD(val);
    document.getElementById('date-preview-days').textContent =
      diff>0?`Dans ${diff} jour${diff>1?'s':''}`:(diff===0?"🎉 C'est aujourd'hui !":`⚠️ Date passée (${Math.abs(diff)} jours)`);
    document.getElementById('date-preview-box').style.display='block';
  },
  async saveDateModal() {
    const val = document.getElementById('date-input').value;
    if(!val){toast('Veuillez sélectionner une date','error');return;}
    const r = await apiFetch(`${API}?action=save_wedding_date`,{method:'POST',body:JSON.stringify({date:val})});
    if(r.success){State.weddingDate=val;this.renderCountdown();this.closeDateModal();toast('Date enregistrée ! 💍','success');}
    else toast(r.message||'Erreur','error');
  },

  // ── Dashboard ────────────────────────────────────────────
  renderDashboard() {
    const exps = State.expenses;
    const total = exps.reduce((s,e)=>s+calcT(e),0);
    const paid  = exps.filter(e=>e.paid==1).reduce((s,e)=>s+calcT(e),0);
    const pend  = total-paid;
    const pct   = total>0?(paid/total*100).toFixed(1):0;
    const pi    = exps.filter(e=>e.paid==1).length;
    const ui    = exps.filter(e=>e.paid==0).length;
    const activeCats = State.categories.filter(c=>exps.some(e=>e.category_id==c.id)).length;

    document.getElementById('stats-grid').innerHTML = `
      <div class="stat-card total">
        <i class="fas fa-wallet stat-icon-bg"></i>
        <div class="stat-label">Budget Total</div>
        <div class="stat-value serif" style="font-size:18px">${fmtN(total)}<span class="cur"> FCFA</span></div>
        <div class="stat-sub">${exps.length} article${exps.length!==1?'s':''}</div>
      </div>
      <div class="stat-card paid">
        <i class="fas fa-check-circle stat-icon-bg"></i>
        <div class="stat-label">Déjà Payé</div>
        <div class="stat-value serif" style="font-size:18px;color:#065f46">${fmtN(paid)}<span class="cur" style="color:#065f46"> FCFA</span></div>
        <div class="stat-sub">${pi} réglé${pi!==1?'s':''}</div>
      </div>
      <div class="stat-card pending">
        <i class="fas fa-hourglass-half stat-icon-bg"></i>
        <div class="stat-label">Reste à Payer</div>
        <div class="stat-value serif" style="font-size:18px;color:#92400e">${fmtN(pend)}<span class="cur" style="color:#92400e"> FCFA</span></div>
        <div class="stat-sub">${ui} en attente</div>
      </div>
      <div class="stat-card items">
        <i class="fas fa-layer-group stat-icon-bg"></i>
        <div class="stat-label">Catégories</div>
        <div class="stat-value serif" style="font-size:28px;color:#be185d">${activeCats||'—'}</div>
        <div class="stat-sub">catégories actives</div>
      </div>`;

    document.getElementById('progress-pct').textContent = `${pct}%`;
    setTimeout(()=>{ document.getElementById('progress-fill').style.width=`${pct}%`; },100);

    // Profile stats
    document.getElementById('profile-stat-items').textContent = exps.length;
    document.getElementById('profile-stat-paid').textContent = pi;
    document.getElementById('profile-stat-budget').textContent = fmtC(total);

    const list = document.getElementById('cat-summary-list');
    if(!State.categories.length){
      list.innerHTML='<div class="empty-state"><div class="empty-icon">📊</div><div class="empty-title">Aucune donnée</div><div class="empty-sub">Ajoutez des dépenses pour voir le récapitulatif.</div></div>';
      return;
    }
    list.innerHTML = State.categories.map((cat,i)=>{
      const ce = exps.filter(e=>e.category_id==cat.id);
      if(!ce.length) return '';
      const t=ce.reduce((s,e)=>s+calcT(e),0);
      const p=ce.filter(e=>e.paid==1).reduce((s,e)=>s+calcT(e),0);
      const bp=t>0?(p/t*100).toFixed(0):0;
      const th=getCatTheme(i);
      const bc=bp==100?'done':bp>0?'partial':'none';
      const bl=bp==100?'Complet':bp>0?`${bp}%`:'En attente';
      const iconColor=cat.color||th.text;
      return `<div class="cat-card" onclick="App.filterByCat(${cat.id})">
        <div class="cat-icon-wrap" style="background:${th.bg};color:${th.text}">
          ${cat.icon?`<i class="${esc(cat.icon)}" style="color:${iconColor}"></i>`:th.icon||'📁'}
        </div>
        <div class="cat-info">
          <div class="cat-name">${esc(cat.name)}</div>
          <div class="cat-bar"><div class="cat-bar-fill" style="width:${bp}%;background:${iconColor}"></div></div>
          <div class="cat-amounts"><span class="cat-total">${fmtC(t)}</span><span class="cat-paid" style="color:${iconColor}">${fmtC(p)} payé</span></div>
        </div>
        <span class="cat-badge ${bc}">${bl}</span>
      </div>`;
    }).join('');
  },

  filterByCat(id) { this.goTo('expenses'); this.setFilter(`cat_${id}`); },

  // ── Expenses ─────────────────────────────────────────────
  setFilter(f) {
    State.filter = f;
    document.querySelectorAll('.chip').forEach(c=>c.classList.toggle('active',c.dataset.filter===f));
    this.renderExpenses();
  },
  applyFilters() { this.renderExpenses(); },

  getFiltered() {
    let ex = [...State.expenses];
    const q = (document.getElementById('search-input')?.value||'').toLowerCase().trim();
    if(State.filter==='paid')       ex=ex.filter(e=>e.paid==1);
    else if(State.filter==='unpaid') ex=ex.filter(e=>e.paid==0);
    else if(State.filter.startsWith('cat_')){ const ci=State.filter.replace('cat_',''); ex=ex.filter(e=>e.category_id==ci); }
    if(q) ex=ex.filter(e=>e.name.toLowerCase().includes(q)||(e.notes||'').toLowerCase().includes(q));
    return ex;
  },

  renderExpenses() {
    const exps=this.getFiltered();
    const c=document.getElementById('expense-list-container');
    if(!exps.length){
      c.innerHTML=`<div class="empty-state"><div class="empty-icon">💸</div><div class="empty-title serif">Aucune dépense</div><div class="empty-sub">Ajoutez votre première dépense<br>avec le bouton <strong>+</strong> ci-dessous.</div></div>`;
      return;
    }
    const groups={};
    const catIdx={};
    State.categories.forEach((c,i)=>catIdx[c.id]=i);
    exps.forEach(e=>{
      const k=e.category_id||'other';
      if(!groups[k]) groups[k]={name:e.category_name||'Autre',items:[],total:0,idx:catIdx[k]??0};
      groups[k].items.push(e);
      groups[k].total+=calcT(e);
    });
    let html='';
    Object.entries(groups).forEach(([cid,g])=>{
      const th=getCatTheme(g.idx);
      const cat=State.categories.find(c=>c.id==cid);
      html+=`<div class="cat-section-label">
        <span class="cat-section-badge" style="background:${th.bg};color:${th.text}">${esc(g.name)}</span>
        <span class="cat-subtotal">${fmtC(g.total)}</span>
      </div><div class="expense-list">`;
      g.items.forEach(e=>{
        const tot=calcT(e), paid=e.paid==1;
        html+=`<div class="expense-item ${paid?'paid-item':'unpaid-item'}">
          <div class="expense-dot" style="background:${th.bg};color:${th.text}">
            ${cat?.icon?`<i class="${esc(cat.icon)}"></i>`:th.icon||'📁'}
          </div>
          <div class="expense-info">
            <div class="expense-name">${esc(e.name)}</div>
            <div class="expense-meta">${e.quantity}×${fmtN(e.unit_price)} FCFA${e.frequency>1?` ×${e.frequency}`:''}</div>
          </div>
          <div class="expense-amount">
            <div class="expense-total">${fmtC(tot)}</div>
            <span class="expense-status ${paid?'status-paid':'status-unpaid'}">${paid?'Payé':'En attente'}</span>
          </div>
          <div class="expense-actions">
            <button class="action-btn btn-toggle ${paid?'undo':''}" onclick="App.togglePaid(${e.id})" title="${paid?'Annuler':'Payer'}">
              <i class="fas fa-${paid?'undo':'check'}"></i>
            </button>
            <button class="action-btn btn-edit" onclick="App.openEditModal(${e.id})" title="Modifier">
              <i class="fas fa-pen"></i>
            </button>
            <button class="action-btn btn-del" onclick="App.deleteExpense(${e.id})" title="Supprimer">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>`;
      });
      html+='</div>';
    });
    c.innerHTML=html;
  },

  // ── Payments ─────────────────────────────────────────────
  renderPayments() {
    const paidExps  = State.expenses.filter(e=>e.paid==1);
    const unpaidExps = State.expenses.filter(e=>e.paid==0);
    const paidTotal  = paidExps.reduce((s,e)=>s+calcT(e),0);
    const totalAll   = State.expenses.reduce((s,e)=>s+calcT(e),0);
    const pct        = totalAll>0?(paidTotal/totalAll*100).toFixed(1):0;

    document.getElementById('pay-total-amount').textContent = fmtC(paidTotal);
    document.getElementById('pay-total-sub').textContent = `${paidExps.length} paiement${paidExps.length!==1?'s':''} effectué${paidExps.length!==1?'s':''}`;
    document.getElementById('pay-remaining').textContent = fmtC(totalAll-paidTotal);
    document.getElementById('pay-pct').textContent = `${pct}%`;

    const pl=document.getElementById('paid-list');
    const ul=document.getElementById('unpaid-list');
    if(!paidExps.length){
      pl.innerHTML='<div class="empty-state" style="padding:24px"><div class="empty-icon" style="font-size:36px">💳</div><div class="empty-sub">Aucun paiement effectué</div></div>';
    } else {
      pl.innerHTML=paidExps.map(e=>`
        <div class="pay-item">
          <div class="pay-info">
            <div class="pay-name">${esc(e.name)}</div>
            <div class="pay-cat">${esc(e.category_name||'—')}</div>
            ${e.payment_date?`<div class="pay-date"><i class="fas fa-calendar-check" style="color:var(--green)"></i> ${fmtDs(e.payment_date)}</div>`:''}
          </div>
          <div class="pay-amount" style="color:var(--green)">${fmtC(calcT(e))}</div>
          <button class="pay-action-btn" style="background:rgba(146,64,14,.1);color:var(--amber)" onclick="App.togglePaid(${e.id})" title="Annuler paiement">
            <i class="fas fa-undo"></i>
          </button>
        </div>`).join('');
    }
    if(!unpaidExps.length){
      ul.innerHTML='<div class="empty-state" style="padding:24px"><div class="empty-icon" style="font-size:36px">🎉</div><div class="empty-title serif" style="font-size:17px">Tout est payé !</div><div class="empty-sub">Félicitations !</div></div>';
    } else {
      ul.innerHTML=unpaidExps.map(e=>`
        <div class="pay-item">
          <div class="pay-info">
            <div class="pay-name">${esc(e.name)}</div>
            <div class="pay-cat">${esc(e.category_name||'—')}</div>
          </div>
          <div class="pay-amount" style="color:var(--amber)">${fmtC(calcT(e))}</div>
          <button class="pay-action-btn" style="background:rgba(6,95,70,.1);color:var(--green)" onclick="App.togglePaid(${e.id})" title="Marquer payé">
            <i class="fas fa-check"></i>
          </button>
        </div>`).join('');
    }
  },

  // ── Guide ─────────────────────────────────────────────────
  renderGuide() {
  const steps = [
    {
      icon:'⛪',
      badge:'Étape clé',
      title:'Préparatifs avant le comité d\'église',
      desc:'6 mois avant le mariage civil. Démarches indispensables à effectuer avant de se présenter au comité d\'église.',
      checks:[
        'Informer le président de la JAD (Jeunesse de l\'Assemblée de Dieu)',
        'Prévenir les responsables du département(s) (EDL, chorale, groupe musical, évangelisation, sécurité…) dans lequel(s) vous êtes impliqué(s)',
        'Prévenir les pasteurs avant toute démarche officielle',
        'Soumettre une demande écrite au comité d\'église',
        'Participer aux séances de préparation au mariage',
        'Assister aux mariages célébrés dans l\'église',
        'Obtenir : certificat de baptême, attestation de célibat, attestation de bonne conduite',
        'Planifier les rencontres avec le pasteur ou le conseiller conjugal',
        'Préparer votre témoignage de conversion et d\'engagement'
      ],
      tip:'Le comité d\'église se réunit généralement une fois par mois. Prévoyez minimum 6 mois d\'avance.',
      time:'6 mois minimum avant le mariage civil'
    },
    {
      icon:'💍',
      badge:'Étape 1',
      title:'La Demande en Mariage',
      desc:'Première étape officielle : demander la main de votre bien-aimée. Cette étape doit être préparée avec soin et sincérité.',
      checks:[
        'Préparer une bague de fiançailles',
        'Choisir le moment et le lieu parfaits',
        'Obtenir la bénédiction des familles',
        'Faire la demande officielle'
      ],
      time:'1 à 2 mois avant les démarches'
    },
    {
      icon:'🤝',
      badge:'Étape 2',
      title:'Prise de contact avec la belle-famille',
      desc:'Rencontre formelle avec la famille de la future épouse pour demander officiellement sa main et discuter des arrangements.',
      checks:[
        'Préparer une enveloppe symbolique',
        'Apporter des présents (boissons, etc.)',
        'Prévoir les frais de déplacement',
        'Se faire accompagner par des membres de sa propre famille',
        'Fixer la date de la dot'
      ],
      time:'1 mois avant la dot'
    },
    {
      icon:'🎁',
      badge:'Étape 3',
      title:'La Dot — Cérémonie Traditionnelle',
      desc:'Cérémonie où le futur marié présente la dot à la famille de la mariée selon les coutumes locales.',
      checks:[
        'Rassembler tous les éléments de la dot',
        'Préparer la valise et les pagnes',
        'Ustensiles de cuisine complets',
        'Enveloppes (fille, famille, frères et sœurs)',
        'Boissons et collations',
        'Organiser le cortège familial'
      ],
      time:'2 à 3 semaines avant le mariage civil'
    },
    {
      icon:'🏛️',
      badge:'Étape 4',
      title:'Mariage Civil à la Mairie',
      desc:'Légalisation de votre union devant l\'officier d\'état civil. Cette étape est obligatoire légalement.',
      checks:[
        'Constituer le dossier de mariage complet',
        'Publier les bans',
        'Réunir les témoins (2 minimum)',
        'Réserver la salle de célébration',
        'Préparer une petite réception',
        'Prévoir les tenues civiles'
      ],
      time:'1 à 2 semaines avant la bénédiction'
    },
    {
      icon:'⛪',
      badge:'Étape 5',
      title:'Célébration religieuse — Bénédiction nuptiale',
      desc:'Bénédiction de votre union devant Dieu, en présence de la communauté religieuse et de vos proches.',
      checks:[
        'Vérifier que votre acte de mariage est bien déposé sans lequel votre mariage sera suspendu',
        'Suivre les séances de préparation au mariage',
        'Louer ou acheter la robe de mariée',
        'Acheter le costume du marié',
        'Choisir les témoins et le cortège',
        'Préparer les tenues pour le cortège',
        'Commander et récupérer les alliances'
      ],
      time:'Le jour J'
    },
    {
      icon:'🥂',
      badge:'Étape 6',
      title:'Réception et Fête',
      desc:'Célébration avec vos invités : repas, animations et moments de joie partagée.',
      checks:[
        'Réserver la salle de réception',
        'Prévoir le traiteur et les boissons',
        'Organiser la décoration',
        'Réserver les animations (DJ, orchestre...)',
        'Commander le gâteau de mariage',
        'Planifier le menu',
        'Gérer la liste des invités'
      ],
      time:'Après l\'église — Le jour J'
    },
    {
      icon:'🚛',
      badge:'Étape 7',
      title:'Logistique et Organisation',
      desc:'Coordination de tous les aspects pratiques pour assurer le bon déroulement.',
      checks:[
        'Louer les véhicules de transport',
        'Engager un photographe et un vidéaste',
        'Prévoir la sonorisation complète',
        'Imprimer les faire-part et programmes',
        'Organiser les répétitions',
        'Coordonner les horaires précis'
      ],
      time:'Tout au long de la préparation'
    },
    {
      icon:'❤️',
      badge:'Étape Finale',
      title:'Après le Mariage',
      desc:'Les formalités et moments qui suivent la célébration.',
      checks:[
        'Récupérer les photos et vidéos',
        'Envoyer les remerciements aux invités',
        'Retirer le livret de famille à la mairie',
        'Installer et aménager le foyer'
      ],
      gold:true,
      time:'Dans les semaines suivant le mariage'
    }
  ];

    document.getElementById('guide-steps').innerHTML=steps.map(s=>`
      <div class="guide-step">
        <div class="step-num${s.gold?' gold':''}">${s.icon}</div>
        <div class="step-card">
          <span class="step-badge">${s.badge}</span>
          <div class="step-title serif">${s.title}</div>
          <div class="step-desc">${s.desc}</div>
          <ul class="step-checks">${s.checks.map(c=>`<li>${c}</li>`).join('')}</ul>
          <div class="step-time"><i class="fas fa-clock"></i> ${s.time}</div>
        </div>
      </div>`).join('');
  },

  // ── Expense CRUD ─────────────────────────────────────────
  openExpenseModal() {
    State.editingId=null;
    document.getElementById('edit-expense-id').value='';
    document.getElementById('expense-modal-title').textContent='Nouvelle Dépense';
    document.getElementById('save-expense-btn').innerHTML='<i class="fas fa-save"></i> Enregistrer';
    ['form-name','form-notes'].forEach(id=>{document.getElementById(id).value='';});
    document.getElementById('form-price').value='';
    document.getElementById('form-qty').value='1';
    document.getElementById('form-freq').value='1';
    document.getElementById('form-date').value='';
    document.getElementById('form-category').value='';
    document.getElementById('modal-total-preview').style.display='none';
    State.paidCheck=false; this.updatePaidCheck();
    document.getElementById('expense-modal').classList.add('open');
  },
  closeExpenseModal() { document.getElementById('expense-modal').classList.remove('open'); },

  openEditModal(id) {
    const e=State.expenses.find(x=>x.id==id);
    if(!e) return;
    State.editingId=id;
    document.getElementById('edit-expense-id').value=id;
    document.getElementById('expense-modal-title').textContent='Modifier la Dépense';
    document.getElementById('save-expense-btn').innerHTML='<i class="fas fa-sync"></i> Mettre à jour';
    document.getElementById('form-name').value=e.name||'';
    document.getElementById('form-price').value=e.unit_price||'';
    document.getElementById('form-qty').value=e.quantity||1;
    document.getElementById('form-freq').value=e.frequency||1;
    document.getElementById('form-notes').value=e.notes||'';
    document.getElementById('form-date').value=e.payment_date||'';
    document.getElementById('form-category').value=e.category_id||'';
    State.paidCheck=e.paid==1; this.updatePaidCheck();
    this.updateTotal();
    document.getElementById('expense-modal').classList.add('open');
  },

  updateTotal() {
    const q=parseFloat(document.getElementById('form-qty').value)||0;
    const p=parseFloat(document.getElementById('form-price').value)||0;
    const f=parseFloat(document.getElementById('form-freq').value)||0;
    const t=q*p*f;
    const el=document.getElementById('modal-total-preview');
    if(t>0){el.style.display='flex';document.getElementById('mtp-val').textContent=fmtC(t);}
    else el.style.display='none';
  },

  togglePaidCheck() {
    State.paidCheck=!State.paidCheck; this.updatePaidCheck();
  },
  updatePaidCheck() {
    const w=document.getElementById('paid-check-wrap');
    const c=document.getElementById('custom-check');
    document.getElementById('form-paid').checked=State.paidCheck;
    w.classList.toggle('checked',State.paidCheck);
    c.innerHTML=State.paidCheck?'<i class="fas fa-check" style="font-size:10px"></i>':'';
  },

  async saveExpense() {
    const id=document.getElementById('edit-expense-id').value;
    const data={
      category_id:document.getElementById('form-category').value,
      name:document.getElementById('form-name').value.trim(),
      quantity:parseInt(document.getElementById('form-qty').value)||1,
      unit_price:parseFloat(document.getElementById('form-price').value)||0,
      frequency:parseInt(document.getElementById('form-freq').value)||1,
      paid:State.paidCheck?1:0,
      payment_date:document.getElementById('form-date').value||null,
      notes:document.getElementById('form-notes').value.trim()||null,
    };
    if(!data.name){toast('Veuillez saisir un nom','error');return;}
    if(!data.category_id){toast('Veuillez sélectionner une catégorie','error');return;}
    if(!data.unit_price){toast('Veuillez saisir un prix','error');return;}
    const btn=document.getElementById('save-expense-btn');
    btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>';
    const url=id?`${API}?action=update&id=${id}`:`${API}?action=add`;
    const r=await apiFetch(url,{method:'POST',body:JSON.stringify(data)});
    btn.disabled=false;
    if(r.success){
      toast(id?'Dépense modifiée ✓':'Dépense ajoutée ✓','success');
      this.closeExpenseModal();
      await this.loadData();
      this.renderDashboard();
      if(State.currentPage==='expenses') this.renderExpenses();
      else if(State.currentPage==='payments') this.renderPayments();
    } else {
      toast(r.message||'Erreur','error');
      btn.innerHTML=id?'<i class="fas fa-sync"></i> Mettre à jour':'<i class="fas fa-save"></i> Enregistrer';
    }
  },

  async togglePaid(id) {
    const r=await apiFetch(`${API}?action=toggle_paid&id=${id}`);
    if(r.success){
      await this.loadData();
      this.renderDashboard();
      if(State.currentPage==='expenses') this.renderExpenses();
      else if(State.currentPage==='payments') this.renderPayments();
      toast('Statut mis à jour ✓','success');
    } else toast(r.message||'Erreur','error');
  },

  async deleteExpense(id) {
    if(!confirm('Supprimer cette dépense ? Action irréversible.')) return;
    const r=await apiFetch(`${API}?action=delete&id=${id}`);
    if(r.success){
      toast('Dépense supprimée','warning');
      await this.loadData();
      this.renderDashboard();
      if(State.currentPage==='expenses') this.renderExpenses();
    } else toast(r.message||'Erreur','error');
  },

  // ── Auth ─────────────────────────────────────────────────
  async logout() {
    if(!confirm('Voulez-vous vous déconnecter ?')) return;
    await apiFetch(`${AUTH_API}?action=logout`);
    toast('Déconnecté avec succès','success');
    setTimeout(()=>window.location.href='<?= APP_URL ?>/auth/login.php',1000);
  },

  async changePassword() {
    const old=prompt('Ancien mot de passe :');
    if(!old) return;
    const nw=prompt('Nouveau mot de passe (min. 6 caractères) :');
    if(!nw||nw.length<6){toast('Mot de passe trop court','error');return;}
    const cf=prompt('Confirmer le nouveau mot de passe :');
    if(nw!==cf){toast('Mots de passe différents','error');return;}
    const r=await apiFetch(`${AUTH_API}?action=change_password`,{method:'POST',body:JSON.stringify({old_password:old,new_password:nw})});
    toast(r.success?'Mot de passe modifié ✓':(r.message||'Erreur'),r.success?'success':'error');
  },

  showNotifications() { toast('Aucune nouvelle notification','info'); },
};

document.addEventListener('DOMContentLoaded', ()=>App.init());
// ══════════════════════════════════════════════════════════════
// NOUVEAU : Compte à rebours du mariage
// ══════════════════════════════════════════════════════════════
<?php if ($weddingDate): ?>
(function() {
    const weddingDate = new Date('<?= $weddingDate ?>');
    
    function updateCountdown() {
        const now = new Date();
        const timeDiff = weddingDate.getTime() - now.getTime();
        const countdownEl = document.getElementById('wedding-countdown');
        
        if (!countdownEl) return;
        
        if (timeDiff <= 0) {
            countdownEl.innerHTML = '🎉 Aujourd\'hui !';
            countdownEl.style.color = 'var(--gold-l)';
            return;
        }
        
        const days = Math.floor(timeDiff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((timeDiff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60));
        
        let countdownText = '';
        
        if (days > 30) {
            const months = Math.floor(days / 30);
            const remainingDays = days % 30;
            countdownText = `${months} mois ${remainingDays} jours`;
        } else if (days > 0) {
            countdownText = `${days}j ${hours}h ${minutes}m`;
        } else {
            countdownText = `${hours}h ${minutes}m`;
        }
        
        countdownEl.textContent = countdownText;
        
        // Changer la couleur selon l'urgence
        if (days < 7) {
            countdownEl.style.color = '#ff6b6b';
        } else if (days < 30) {
            countdownEl.style.color = '#ffa726';
        } else {
            countdownEl.style.color = 'var(--gold-l)';
        }
    }
    
    // Mettre à jour immédiatement et toutes les minutes
    updateCountdown();
    setInterval(updateCountdown, 60000);
})();
<?php endif; ?>
</script>
</body>
</html>
