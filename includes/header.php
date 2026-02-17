<?php
// includes/header.php — Navigation principale — Budget Mariage PJPM
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/AuthManager.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (AuthManager::isLoggedIn() && !AuthManager::checkSessionTimeout()) {
    header('Location: ' . APP_URL . '/auth/login.php?expired=1');
    exit;
}
$isLoggedIn  = AuthManager::isLoggedIn();
$currentUser = $isLoggedIn ? AuthManager::getCurrentUser() : null;
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=5">
<meta name="theme-color" content="#8b4f8d">
<meta name="description" content="<?= APP_NAME ?> — Planifiez le mariage de vos rêves sans stress.">
<link rel="shortcut icon" href="<?= APP_URL ?>/assets/images/wedding.jpg" type="image/jpeg">
<title><?= APP_NAME ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400..700;1,400..700&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&family=Roboto+Serif:ital,opsz,wght@0,8..144,100..900;1,8..144,100..900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
<style>
/* ─── HEADER ─────────────────────────────────────── */
.fixed-header{position:fixed;top:0;left:0;width:100%;z-index:1000;
  background:linear-gradient(135deg,#8b4f8d 0%,#5d2f5f 100%);
  box-shadow:0 4px 20px rgba(0,0,0,.15);}
.hdr{max-width:1400px;margin:0 auto;padding:0 20px;
  display:flex;align-items:center;justify-content:space-between;height:120px;}
.logo{display:flex;align-items:center;gap:10px;text-decoration:none;color:#fff;}
.logo img{width:72px;height:72px;border-radius:50%;object-fit:cover;
  border:2px solid rgba(255,255,255,.3);}
.logo-title{font-family:'Playfair Display',serif;font-size:1.5rem;margin:0;white-space:nowrap;}

/* NAV */
.main-nav{display:flex;align-items:center;gap:6px;}
.nav-link,.nav-btn{color:rgba(255,255,255,.88);text-decoration:none;padding:8px 13px;
  border-radius:7px;font-weight:600;font-size:.9rem;display:flex;align-items:center;
  gap:6px;transition:all .25s;white-space:nowrap;background:none;border:none;
  cursor:pointer;font-family:'segoi ui',sans-serif;}
.nav-link:hover,.nav-link.active,.nav-btn:hover{background:rgba(255,255,255,.18);color:#fff;}

/* DROPDOWN */
.user-drop{position:relative;}
.user-btn{display:flex;align-items:center;gap:8px;
  background:rgba(255,255,255,.12);border:2px solid rgba(255,255,255,.2);
  color:#fff;padding:7px 13px;border-radius:8px;cursor:pointer;
  font-family:'Lato',sans-serif;font-size:.9rem;font-weight:600;transition:all .25s;}
.user-btn:hover{background:rgba(255,255,255,.22);}
.chevron{transition:transform .3s;font-size:.75rem;}
.user-btn.open .chevron{transform:rotate(180deg);}
.drop-menu{position:absolute;top:calc(100% + 10px);right:0;background:#fff;
  min-width:215px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.15);
  border:1px solid #e8e3dd;display:none;z-index:1001;overflow:hidden;}
.drop-menu.open{display:block;animation:dropIn .2s ease;}
@keyframes dropIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
.drop-menu::before{content:'';position:absolute;top:-9px;right:18px;
  border-left:9px solid transparent;border-right:9px solid transparent;
  border-bottom:9px solid #fff;filter:drop-shadow(0 -2px 2px rgba(0,0,0,.06));}
.di{display:flex;align-items:center;gap:10px;padding:11px 16px;color:#2d2d2d;
  text-decoration:none;font-size:.92rem;border-bottom:1px solid #f0ecea;transition:all .2s;}
.di:last-child{border-bottom:none;}
.di i{width:18px;color:#8b4f8d;}
.di:hover{background:#faf8f5;color:#8b4f8d;padding-left:20px;}
.di.danger{color:#e53935;}.di.danger i{color:#e53935;}
.di.danger:hover{background:#fff5f5;}
.d-sep{height:1px;background:#f0ecea;margin:4px 0;}
.abadge{background:#8b4f8d;color:#fff;font-size:.65rem;padding:2px 6px;
  border-radius:4px;font-weight:700;}

/* BURGER */
.burger{display:none;background:none;border:none;color:#fff;
  font-size:1.4rem;cursor:pointer;padding:8px;}
.nav-ovl{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:998;}

/* APP OFFSET */
body{padding-top:68px;}

/* ─── RESPONSIVE ─────────────────────────────────── */
@media(max-width:900px){
  .burger{display:block;}
  .main-nav{position:fixed;top:0;right:-100%;width:280px;height:100vh;
    background:linear-gradient(160deg,#8b4f8d 0%,#5d2f5f 100%);
    flex-direction:column;align-items:stretch;padding:74px 16px 30px;
    z-index:999;transition:right .3s;overflow-y:auto;gap:4px;}
  .main-nav.open{right:0;}
  .nav-link,.nav-btn{font-size:1rem;padding:12px 16px;}
  .user-drop{margin-top:12px;}
  .user-btn{width:100%;justify-content:space-between;padding:12px 16px;}
  .drop-menu{position:static;display:none!important;}
  .drop-menu.open{display:block!important;animation:none;}
  .drop-menu::before{display:none;}
  .di{color:rgba(255,255,255,.9);border-color:rgba(255,255,255,.1);}
  .di i{color:#d4af37;}
  .di:hover{background:rgba(255,255,255,.1);color:#fff;}
  .di.danger{color:#ff8a80;}
  .nav-ovl.open{display:block;}
}
@media(max-width:480px){
  .logo-title{display:none;}
  .nav-link span,.nav-btn span{display:none;}
}
</style>
</head>
<body>
<header class="fixed-header" role="banner">
  <div class="hdr">
    <a href="<?= APP_URL ?>/index.php" class="logo" aria-label="Accueil <?= APP_NAME ?>">
      <img src="<?= APP_URL ?>/assets/images/wedding.jpg" alt="Logo" onerror="this.style.display='none'">
      <h1 class="logo-title"><?= APP_NAME ?></h1>
    </a>

    <button class="burger" id="burgerBtn" aria-label="Ouvrir le menu" aria-expanded="false">
      <i class="fas fa-bars" id="burgerIcon"></i>
    </button>
    <div class="nav-ovl" id="navOvl"></div>

    <nav class="main-nav" id="mainNav" role="navigation" aria-label="Navigation principale">
      <?php if ($isLoggedIn): ?>
        <a href="<?= APP_URL ?>/index.php"
           class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
          <i class="fas fa-home"></i><span>Accueil</span>
        </a>
        <button class="nav-btn" onclick="switchTab('dashboard')">
          <i class="fas fa-chart-bar"></i><span>Dashboard</span>
        </button>
        <button class="nav-btn" onclick="switchTab('details')">
          <i class="fas fa-list-alt"></i><span>Dépenses</span>
        </button>
        <button class="nav-btn" onclick="switchTab('payments')">
          <i class="fas fa-money-check-alt"></i><span>Paiements</span>
        </button>
        <button class="nav-btn" onclick="switchTab('stats')" aria-controls="stats-tab" id="tab-stats">
          <i class="fas fa-chart-pie" aria-hidden="true"></i> Statistiques
        </button>
        <a href="<?= APP_URL ?>/guide.php"
           class="nav-link <?= $currentPage === 'guide.php' ? 'active' : '' ?>">
          <i class="fas fa-book"></i><span>Guide</span>
        </a>

        <div class="user-drop" id="userDrop">
          <button class="user-btn" id="userBtn" onclick="toggleDrop(event)"
                  aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-user-circle"></i>
            <span><?= htmlspecialchars($currentUser['full_name'] ?: $currentUser['username']) ?></span>
            <?php if ($currentUser['role'] === 'admin'): ?>
              <span class="abadge">Admin</span>
            <?php endif; ?>
            <i class="fas fa-chevron-down chevron"></i>
          </button>
          <div class="drop-menu" id="dropMenu" role="menu">
            <a href="<?= APP_URL ?>/admin/profile.php" class="di" role="menuitem">
              <i class="fas fa-user"></i> Mon Profil
            </a>
            <a href="<?= APP_URL ?>/wedding_date.php" class="di" role="menuitem">
              <i class="fas fa-calendar-alt"></i> Date du Mariage
            </a>
            <a href="<?= APP_URL ?>/admin_sponsors.php" class="di" role="menuitem">
                <i class="fas fa-users"></i>Gérer les Parrains
            </a>
            <a href="<?= APP_URL ?>/admin/settings.php" class="di" role="menuitem">
              <i class="fas fa-cog"></i> Paramètres
            </a>
            <?php if ($currentUser['role'] === 'admin'): ?>
              <div class="d-sep"></div>
              <a href="<?= APP_URL ?>/admin/admin.php" class="di" role="menuitem">
                <i class="fas fa-shield-alt"></i> Administration
              </a>
            <?php endif; ?>
            <div class="d-sep"></div>
            <a href="#" class="di danger" onclick="doLogout();return false;" role="menuitem">
              <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
          </div>
        </div>

      <?php else: ?>
        <a href="<?= APP_URL ?>/index.php"
           class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
          <i class="fas fa-home"></i><span>Accueil</span>
        </a>
        <a href="<?= APP_URL ?>/guide.php"
           class="nav-link <?= $currentPage === 'guide.php' ? 'active' : '' ?>">
          <i class="fas fa-book"></i><span>Guide</span>
        </a>
        <a href="<?= APP_URL ?>/auth/login.php" class="nav-link">
          <i class="fas fa-sign-in-alt"></i><span>Connexion</span>
        </a>
        <a href="<?= APP_URL ?>/auth/register.php" class="nav-link">
          <i class="fas fa-user-plus"></i><span>Inscription</span>
        </a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<script>
// ── Burger menu ──────────────────────────────────────────────
const burger   = document.getElementById('burgerBtn');
const bIcon    = document.getElementById('burgerIcon');
const navEl    = document.getElementById('mainNav');
const ovl      = document.getElementById('navOvl');

function openNav() {
    navEl.classList.add('open'); ovl.classList.add('open');
    burger.setAttribute('aria-expanded','true');
    bIcon.className = 'fas fa-times';
}
function closeNav() {
    navEl.classList.remove('open'); ovl.classList.remove('open');
    burger.setAttribute('aria-expanded','false');
    bIcon.className = 'fas fa-bars';
}
burger.addEventListener('click', () => navEl.classList.contains('open') ? closeNav() : openNav());
ovl.addEventListener('click', closeNav);
document.addEventListener('keydown', e => e.key === 'Escape' && closeNav());

// ── Dropdown user ────────────────────────────────────────────
function toggleDrop(e) {
    e && e.stopPropagation();
    const menu = document.getElementById('dropMenu');
    const btn  = document.getElementById('userBtn');
    if (!menu) return;
    const willOpen = !menu.classList.contains('open');
    menu.classList.toggle('open', willOpen);
    btn.classList.toggle('open', willOpen);
    btn.setAttribute('aria-expanded', willOpen);
    if (willOpen) setTimeout(() => document.addEventListener('click', outsideDrop), 0);
}
function outsideDrop(e) {
    const d = document.getElementById('userDrop');
    if (d && !d.contains(e.target)) {
        document.getElementById('dropMenu')?.classList.remove('open');
        document.getElementById('userBtn')?.classList.remove('open');
        document.removeEventListener('click', outsideDrop);
    }
}

// ── Déconnexion ──────────────────────────────────────────────
async function doLogout() {
    if (!confirm('Voulez-vous vraiment vous déconnecter ?')) return;
    try {
        const r = await fetch('<?= APP_URL ?>/api/auth_api.php?action=logout');
        const d = await r.json();
        window.location.href = d.success
            ? '<?= APP_URL ?>/auth/login.php'
            : '<?= APP_URL ?>/auth/login.php';
    } catch(e) { window.location.href = '<?= APP_URL ?>/auth/login.php'; }
}

// ── switchTab fallback (sera override par script.js) ─────────
if (typeof switchTab === 'undefined') {
    window.switchTab = function(name) {
        document.querySelectorAll('.tab-content').forEach(t => { t.style.display = 'none'; });
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        const el  = document.getElementById(name + '-tab');
        const btn = document.querySelector('[data-tab="'+name+'"]');
        if (el)  el.style.display = 'block';
        if (btn) btn.classList.add('active');
    };
}
</script>
