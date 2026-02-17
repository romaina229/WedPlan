<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../AuthManager.php';

AuthManager::startSession();

if (AuthManager::isLoggedIn()) {
    header('Location: ' . APP_URL . '/index_mobile.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<meta name="theme-color" content="#4c1d95">
<title>Inscription <?= APP_NAME ?></title>
<link rel="shortcut icon" href="../assets/images/wedding.jpg" type="image/jpg">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent;}
body{
  font-family:'DM Sans',sans-serif;
  /*background:linear-gradient(135deg,#4c1d95 0%,#7c3aed 60%,#be185d 100%);*/
  min-height:100dvh;display:flex;align-items:center;justify-content:center;
  padding:20px;padding-bottom:calc(20px + env(safe-area-inset-bottom,0));
}
.card{background:#fff;border-radius:24px;box-shadow:0 20px 60px rgba(76,29,149,.3);width:100%;max-width:420px;overflow:hidden;}
.card-header{background:linear-gradient(135deg,#4c1d95,#7c3aed);padding:28px;text-align:center;}
.logo-wrap{width:56px;height:56px;border-radius:16px;margin:0 auto 12px;background:linear-gradient(135deg,#c9a84c,#e8cc84);display:flex;align-items:center;justify-content:center;font-size:26px;}
.card-header h1{font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:700;color:#fff;margin-bottom:4px;}
.card-header p{font-size:12px;color:rgba(255,255,255,.7);}
.card-body{padding:24px;}
.alert{padding:11px 13px;border-radius:11px;margin-bottom:16px;font-size:13px;font-weight:500;display:none;}
.alert.show{display:block;}
.alert-success{background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;}
.alert-danger{background:#fce7f3;color:#be185d;border:1px solid #f9a8d4;}
.form-group{margin-bottom:15px;}
.form-label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6b4f9e;margin-bottom:5px;}
.input-wrap{position:relative;}
.input-wrap i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#6b4f9e;font-size:13px;}
.form-input{width:100%;padding:12px 13px 12px 40px;border-radius:11px;border:1.5px solid rgba(76,29,149,.15);font-size:13px;color:#1a0a2e;outline:none;transition:border-color .2s;font-family:'DM Sans',sans-serif;background:#fff;-webkit-appearance:none;}
.form-input:focus{border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.1);}
.btn-submit{width:100%;padding:13px;border-radius:13px;border:none;cursor:pointer;background:linear-gradient(135deg,#6d28d9,#7c3aed);color:#fff;font-size:14px;font-weight:600;font-family:'DM Sans',sans-serif;transition:all .2s;margin-top:4px;}
.btn-submit:active{transform:scale(.97);}
.btn-submit:disabled{opacity:.65;cursor:not-allowed;}
.link-row{text-align:center;font-size:13px;color:#6b4f9e;margin-top:16px;}
.link-row a{color:#7c3aed;font-weight:600;text-decoration:none;}
</style>
</head>
<body>
<div class="card">
  <div class="card-header">
    <div class="logo-wrap">💍</div>
    <h1>Créer un compte</h1>
    <p>Commencez à planifier votre mariage</p>
  </div>
  <div class="card-body">
    <div id="alert" class="alert"></div>
    <form id="register-form" autocomplete="on">
      <div class="form-group">
        <label class="form-label" for="username">Nom d'utilisateur *</label>
        <div class="input-wrap">
          <i class="fas fa-user"></i>
          <input type="text" class="form-input" id="username" placeholder="min. 3 caractères" required autocomplete="username">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="email">Adresse email *</label>
        <div class="input-wrap">
          <i class="fas fa-envelope"></i>
          <input type="email" class="form-input" id="email" placeholder="votre@email.com" required autocomplete="email">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="fullname">Nom complet</label>
        <div class="input-wrap">
          <i class="fas fa-id-card"></i>
          <input type="text" class="form-input" id="fullname" placeholder="Prénom Nom" autocomplete="name">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Mot de passe *</label>
        <div class="input-wrap">
          <i class="fas fa-lock"></i>
          <input type="password" class="form-input" id="password" placeholder="min. 6 caractères" required autocomplete="new-password">
        </div>
      </div>
      <button type="submit" class="btn-submit" id="reg-btn">
        <i class="fas fa-user-plus"></i> Créer mon compte
      </button>
    </form>
    <div class="link-row">
      Déjà un compte ? <a href="login.php">Se connecter</a>
    </div>
    <div>
      <p style="color:black;margin-top:20px;text-align:center; font-size:12px;">Ici les Parrains/Conseillers ne peuvent pas créer de compte, leurs compte est généré sur l'espace des futurs mariés</p>
    </div>
  </div>
</div>
<script>
document.getElementById('register-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn   = document.getElementById('reg-btn');
    const alert = document.getElementById('alert');
    alert.className = 'alert';
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création...';
    try {
        const res = await fetch('<?= APP_URL ?>/api/auth_api.php?action=register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                username: document.getElementById('username').value.trim(),
                email:    document.getElementById('email').value.trim(),
                password: document.getElementById('password').value,
                fullname: document.getElementById('fullname').value.trim() || null,
            })
        });
        const r = await res.json();
        if (r.success) {
            alert.className = 'alert alert-success show';
            alert.textContent = '✓ ' + r.message;
            setTimeout(() => window.location.href = 'login.php', 1500);
        } else {
            alert.className = 'alert alert-danger show';
            alert.textContent = r.message || 'Erreur lors de l\'inscription.';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-user-plus"></i> Créer mon compte';
        }
    } catch {
        alert.className = 'alert alert-danger show';
        alert.textContent = 'Erreur de connexion au serveur.';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-user-plus"></i> Créer mon compte';
    }
});
</script>
</body>
</html>
