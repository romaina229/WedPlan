<?php
declare(strict_types=1);
// ================================================================
// auth/login.php — CORRIGÉ
// ================================================================

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../AuthManager.php';

AuthManager::startSession();

// FIX: Rediriger vers la version appropriée selon l'User-Agent
if (AuthManager::isLoggedIn()) {
    $isMobile = preg_match('/(android|iphone|ipad|ipod|blackberry|mobile)/i', $_SERVER['HTTP_USER_AGENT'] ?? '');
    $redirect = $isMobile ? APP_URL . '/index_mobile.php' : APP_URL . '/index.php';
    header('Location: ' . $redirect);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<meta name="theme-color" content="#4c1d95">
<title>Login <?= APP_NAME ?></title>
<link rel="shortcut icon" href="../assets/images/wedding.jpg" type="image/jpg">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent;}
html{font-size:16px;-webkit-text-size-adjust:100%;}
body{
  font-family:'DM Sans',sans-serif;
  /*background:linear-gradient(135deg,#4c1d95 0%,#7c3aed 60%,#be185d 100%);*/
  min-height:100dvh;display:flex;align-items:center;justify-content:center;
  padding:20px;padding-bottom:calc(20px + env(safe-area-inset-bottom,0));
}
.card{
  background:#fff;border-radius:24px;
  box-shadow:0 20px 60px rgba(76,29,149,.3);
  width:100%;max-width:420px;overflow:hidden;
}
.card-header{
  background:linear-gradient(135deg,#4c1d95,#7c3aed);
  padding:36px 28px 28px;text-align:center;position:relative;overflow:hidden;
}
.card-header::before{
  content:'';position:absolute;top:-50px;right:-30px;
  width:180px;height:180px;border-radius:50%;
  background:radial-gradient(circle,rgba(201,168,76,.2),transparent 70%);
}
.logo-wrap{
  width:64px;height:64px;border-radius:18px;margin:0 auto 14px;
  background:linear-gradient(135deg,#c9a84c,#e8cc84);
  display:flex;align-items:center;justify-content:center;font-size:30px;
  box-shadow:0 4px 20px rgba(201,168,76,.4);position:relative;z-index:1;
}
.card-header h1{
  font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;
  color:#fff;margin-bottom:6px;position:relative;z-index:1;
}
.card-header p{font-size:13px;color:rgba(255,255,255,.7);position:relative;z-index:1;}
.card-body{padding:28px;}
.alert{
  padding:12px 14px;border-radius:12px;margin-bottom:18px;
  font-size:13px;font-weight:500;display:none;
  animation:fadeIn .3s ease;
}
.alert.show{display:block;}
.alert-success{background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;}
.alert-danger{background:#fce7f3;color:#be185d;border:1px solid #f9a8d4;}
@keyframes fadeIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}
.form-group{margin-bottom:18px;}
.form-label{
  display:block;font-size:11px;font-weight:700;text-transform:uppercase;
  letter-spacing:.5px;color:#6b4f9e;margin-bottom:6px;
}
.input-wrap{position:relative;}
.input-wrap i{
  position:absolute;left:14px;top:50%;transform:translateY(-50%);
  color:#6b4f9e;font-size:14px;
}
.form-input{
  width:100%;padding:13px 14px 13px 42px;border-radius:12px;
  border:1.5px solid rgba(76,29,149,.15);
  font-size:14px;color:#1a0a2e;outline:none;
  transition:border-color .2s,box-shadow .2s;
  font-family:'DM Sans',sans-serif;background:#fff;
  -webkit-appearance:none;
}
.form-input:focus{
  border-color:#7c3aed;
  box-shadow:0 0 0 3px rgba(124,58,237,.1);
}
.btn-submit{
  width:100%;padding:14px;border-radius:14px;border:none;cursor:pointer;
  background:linear-gradient(135deg,#6d28d9,#7c3aed);
  color:#fff;font-size:15px;font-weight:600;
  font-family:'DM Sans',sans-serif;
  box-shadow:0 4px 16px rgba(124,58,237,.3);
  transition:all .2s;margin-top:4px;
}
.btn-submit:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(124,58,237,.4);}
.btn-submit:active{transform:scale(.97);}
.btn-submit:disabled{opacity:.65;cursor:not-allowed;transform:none;}
.divider{
  text-align:center;margin:20px 0;color:#6b4f9e;font-size:12px;
  display:flex;align-items:center;gap:12px;
}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:rgba(76,29,149,.1);}
.link-row{text-align:center;font-size:13px;color:#6b4f9e;}
.link-row a{color:#7c3aed;font-weight:600;text-decoration:none;}
.link-row a:hover{text-decoration:underline;}
</style>
</head>
<body>
<div class="card">
  <div class="card-header">
    <div class="logo-wrap">💍</div>
    <h1 class="serif">WedPlan</h1>
    <p>Connectez-vous à votre espace</p>
  </div>
  <div class="card-body">
    <div id="alert" class="alert"></div>
    <form id="login-form" autocomplete="on">
      <div class="form-group">
        <label class="form-label" for="username">Nom d'utilisateur ou Email</label>
        <div class="input-wrap">
          <i class="fas fa-user"></i>
          <input type="text" class="form-input" id="username" name="username"
                 placeholder="votre_nom ou email@exemple.com" required autofocus autocomplete="username">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Mot de passe</label>
        <div class="input-wrap">
          <i class="fas fa-lock"></i>
          <input type="password" class="form-input" id="password" name="password"
                 placeholder="••••••••" required autocomplete="current-password">
        </div>
      </div>
      <button type="submit" class="btn-submit" id="login-btn">
        <i class="fas fa-sign-in-alt"></i> Se connecter
      </button>
    </form>
    <div class="divider">OU</div>
    <div class="link-row">
      Connectez-vous en que Parrains/Conseillers <a href="../sponsor_login.php">Cliquez ici</a>
    </div><br>
    <div class="link-row">
      Pas encore de compte ? <a href="register.php">S'inscrire</a>
    </div>
    
  </div>
</div>

<script>
document.getElementById('login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn   = document.getElementById('login-btn');
    const alert = document.getElementById('alert');
    alert.className = 'alert';

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connexion...';

    try {
        // FIX: URL absolue via APP_URL
        const res = await fetch('<?= APP_URL ?>/api/auth_api.php?action=login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                username: document.getElementById('username').value.trim(),
                password: document.getElementById('password').value,
            })
        });

        const r = await res.json();

        if (r.success) {
            alert.className = 'alert alert-success show';
            alert.textContent = '✓ ' + (r.message || 'Connexion réussie !');
            
            // FIX: Utiliser la redirection fournie par l'API ou détecter l'appareil
            const redirectUrl = r.redirect || (<?= json_encode(preg_match('/(mobile|android|iphone)/i', $_SERVER['HTTP_USER_AGENT'] ?? '')) ?> 
                ? '<?= APP_URL ?>/index_mobile.php' 
                : '<?= APP_URL ?>/index.php');
            
            setTimeout(() => {
                window.location.href = redirectUrl;
            }, 800);
        } else {
            alert.className = 'alert alert-danger show';
            alert.textContent = r.message || 'Identifiants incorrects.';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Se connecter';
        }
    } catch (err) {
        alert.className = 'alert alert-danger show';
        alert.textContent = 'Erreur de connexion au serveur. Vérifiez votre connexion.';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Se connecter';
    }
});
</script>
</body>
</html>