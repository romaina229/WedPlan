<?php
// includes/footer.php — Pied de page — Budget Mariage PJPM
// FIX: Vérifier avant de définir
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

$footStats = ['users' => 0, 'expenses' => 0];
try {
    require_once ROOT_PATH . '/config.php';
    require_once ROOT_PATH . '/AuthManager.php';
    require_once ROOT_PATH . '/ExpenseManager.php';
    if (AuthManager::isLoggedIn()) {
        $fAuth = new AuthManager();
        $fMgr  = new ExpenseManager();
        $fUsers = $fAuth->getAllUsers();
        $footStats['users'] = count($fUsers);
        foreach ($fUsers as $u) {
            $footStats['expenses'] += $fMgr->getStats($u['id'])['total_items'];
        }
    }
} catch (Throwable $e) { /* silencieux */ }

$verses = [
    ['text' => 'Ce que Dieu a uni, que l\'homme ne le sépare point.', 'ref' => 'Matthieu 19:6'],
    ['text' => 'Celui qui trouve une femme trouve le bonheur; c\'est une grâce qu\'il obtient de l\'Éternel.', 'ref' => 'Proverbes 18:22'],
    ['text' => 'L\'amour est patient, il est plein de bonté; l\'amour n\'est point envieux.', 'ref' => '1 Corinthiens 13:4'],
    ['text' => 'C\'est pourquoi l\'homme quittera son père et sa mère, et s\'attachera à sa femme, et ils deviendront une seule chair.', 'ref' => 'Genèse 2:24'],
    ['text' => 'Que le mariage soit honoré de tous, et le lit conjugal exempt de souillure.', 'ref' => 'Hébreux 13:4'],
    ['text' => 'L\'Éternel Dieu dit: Il n\'est pas bon que l\'homme soit seul; je lui ferai une aide semblable à lui.', 'ref' => 'Genèse 2:18'],
    ['text' => "Dieu créa l'homme à son image, il le créa à l'image de Dieu, il créa l'homme et la femme. Dieu les bénit, et Dieu leur dit: «Soyez féconds, multipliez, remplissez la terre.»", 'ref' => "Genèse 1:27-28"
    ],
    ['text' => "On peut hériter de ses pères une maison et des richesses, mais une femme intelligente est un don du Seigneur.",'ref' => "Proverbes 19:14"
    ],
    ['text' => "L'amour est patient, il est plein de bonté; l'amour n'est point envieux.",'ref' => "1 Corinthiens 13:4"
    ],
    ['text' => "Mieux vaut habiter à l'angle d'un toit, Que de partager la demeure d'une femme querelleuse.",'ref' => "Proverbes 21:9"
    ],
    ['text' => "Que chacun de vous, dans ses relations avec sa femme, sache garder la mesure qui convient par égard pour le Seigneur.",'ref' => "Colossiens 3:19"
    ],
];
$v = $verses[array_rand($verses)];
?>

<!-- ╔═══════════════════════════════════════════════════╗ -->
<!-- ║  FOOTER — Budget Mariage PJPM                    ║ -->
<!-- ╚═══════════════════════════════════════════════════╝ -->
<footer class="site-footer" role="contentinfo">
  <div class="footer-grid">

    <!-- Col 1 : Marque + verset -->
    <div class="fc">
      <div class="flogo">
        <i class="fas fa-ring" aria-hidden="true"></i>
        <span><?= APP_NAME ?></span>
      </div>
      <p class="ftagline">Organisez le mariage de vos rêves sans stress. Budget, dépenses et paiements en un seul outil.</p>
      <blockquote class="fverse" aria-label="Verset biblique">
        <i class="fas fa-bible" aria-hidden="true"></i>
        <p>"<?= htmlspecialchars($v['text']) ?>"</p>
        <cite><?= htmlspecialchars($v['ref']) ?></cite>
      </blockquote>
    </div>

    <!-- Col 2 : Navigation -->
    <div class="fc">
      <h4 class="fhead">Navigation</h4>
      <ul class="flist">
        <li><a href="<?= APP_URL ?>/index.php"><i class="fas fa-chart-bar"></i> Tableau de bord</a></li>
        <li><a href="<?= APP_URL ?>/guide.php"><i class="fas fa-book"></i> Guide du mariage</a></li>
        <li><a href="<?= APP_URL ?>/wedding_date.php"><i class="fas fa-calendar-alt"></i> Date du mariage</a></li>
        <li><a href="<?= APP_URL ?>/auth/login.php"><i class="fas fa-sign-in-alt"></i> Connexion</a></li>
      </ul>
      <h4 class="fhead" style="margin-top:20px">Pages légales</h4>
      <ul class="flist">
        <li><a href="<?= APP_URL ?>/termes/privacy.php"><i class="fas fa-shield-alt"></i> Confidentialité</a></li>
        <li><a href="<?= APP_URL ?>/termes/terms.php"><i class="fas fa-file-contract"></i> Conditions</a></li>
        <li><a href="<?= APP_URL ?>/termes/legal.php"><i class="fas fa-balance-scale"></i> Mentions légales</a></li>
      </ul>
    </div>

    <!-- Col 3 : Stats + Contact -->
    <div class="fc">
      <h4 class="fhead">Statistiques</h4>
      <div class="fstats">
        <div class="fstat">
          <span class="fstat-val" id="ftUsers"><?= $footStats['users'] ?></span>
          <span class="fstat-lbl">Utilisateurs</span>
        </div>
        <div class="fstat">
          <span class="fstat-val" id="ftExp"><?= $footStats['expenses'] ?></span>
          <span class="fstat-lbl">Dépenses</span>
        </div>
      </div>

      <h4 class="fhead" style="margin-top:22px">Contact</h4>
      <ul class="fcontact">
        <li><i class="fas fa-envelope"></i><a href="mailto:liferopro@gmail.com">liferopro@gmail.com</a></li>
        <li><i class="fas fa-phone"></i><a href="tel:+22994592567">+229 01 94 59 25 67</a></li>
        <li><i class="fas fa-map-marker-alt"></i><span>Abomey-Calavi, Bénin</span></li>
      </ul>

      <div class="fsocial" role="list" aria-label="Réseaux sociaux">
        <a href="https://facebook.com/Romain.AKPO" class="fsoc fb" target="_blank" rel="noopener" role="listitem" aria-label="Facebook">
          <i class="fab fa-facebook-f"></i>
        </a>
        <a href="https://linkedin.com/in/romain-akpo-2ab8802a8" class="fsoc ig" target="_blank" rel="noopener" role="listitem" aria-label="Instagram">
          <i class="fab fa-instagram"></i>
        </a>
        <a href="https://wa.me/22994592567" class="fsoc wa" target="_blank" rel="noopener" role="listitem" aria-label="WhatsApp">
          <i class="fab fa-whatsapp"></i>
        </a>
        <a href="http://www.youtube.com/@lifero5180" class="fsoc yt" target="_blank" rel="noopener" role="listitem" aria-label="YouTube">
          <i class="fab fa-youtube"></i>
        </a>
      </div>
    </div>
  </div>

  <div class="footer-bottom">
    <p>&copy; <?= date('Y') ?> <?= APP_NAME ?> v<?= APP_VERSION ?>— Tous droits réservés</p>
    <p class="fmotto"><i class="fas fa-heart" aria-hidden="true"></i> Construire des foyers solides sur le roc de Jésus-Christ</p>
  </div>
</footer>

<!-- Toast global -->
<div id="toast" class="toast" role="alert" aria-live="polite"></div>

<style>
/* ─── FOOTER ─────────────────────────────────────── */
body {margin: 0; padding: 0;font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f9f9f9; color: #333;}
.site-footer{background:linear-gradient(135deg,#5d2f5f 0%,#8b4f8d 100%);
  color:#fff;padding:50px 20px 20px;margin-top:60px; position:relative;overflow:hidden;}
.footer-grid{max-width:1400px;margin:0 auto;
  display:grid;grid-template-columns:repeat(3,1fr);gap:40px;margin-bottom:30px;}

.flogo{display:flex;align-items:center;gap:10px;font-family:'Playfair Display',serif;
  font-size:1.9rem;color:#d4af37;margin-bottom:12px;}
.flogo i{font-size:1.9rem;}
.ftagline{opacity:.88;line-height:1.7;font-size:.95rem;margin-bottom:16px;}

.fverse{background:rgba(255,255,255,.08);border-left:4px solid #d4af37;
  border-radius:0 8px 8px 0;padding:14px 16px;margin:0;position:relative;}
.fverse i{color:#d4af37;font-size:1.6rem;opacity:.35;position:absolute;top:8px;right:12px;}
.fverse p{font-style:italic;font-size:.95rem;line-height:1.6;margin:0 0 8px;}
.fverse cite{font-size:.85rem;color:#d4af37;font-weight:700;font-style:normal;}

.fhead{font-size:1rem;font-weight:700;color:#d4af37;margin:0 0 14px;
  padding-bottom:8px;border-bottom:1px solid rgba(255,255,255,.15);}
.flist{list-style:none;padding:0;margin:0;}
.flist li{margin-bottom:10px;}
.flist a{color:rgba(255,255,255,.88);text-decoration:none;
  display:flex;align-items:center;gap:9px;font-size:.93rem;
  transition:all .2s;}
.flist a i{width:16px;color:#d4af37;font-size:.85rem;}
.flist a:hover{color:#fff;padding-left:5px;}

.fstats{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.fstat{background:rgba(255,255,255,.08);padding:16px;border-radius:10px;text-align:center;
  transition:background .25s;}
.fstat:hover{background:rgba(255,255,255,.14);}
.fstat-val{display:block;font-size:1.8rem;font-weight:700;color:#d4af37;line-height:1;}
.fstat-lbl{font-size:.8rem;opacity:.8;text-transform:uppercase;letter-spacing:.5px;}

.fcontact{list-style:none;padding:0;margin:0;}
.fcontact li{display:flex;align-items:center;gap:10px;margin-bottom:10px;font-size:.9rem;}
.fcontact i{width:18px;color:#d4af37;}
.fcontact a{color:rgba(255,255,255,.88);text-decoration:none;}
.fcontact a:hover{color:#fff;}

.fsocial{display:flex;gap:10px;margin-top:16px;flex-wrap:wrap;}
.fsoc{width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.1);
  color:#fff;display:flex;align-items:center;justify-content:center;
  font-size:1.1rem;text-decoration:none;transition:all .25s;}
.fsoc:hover{transform:translateY(-3px) scale(1.1);}
.fsoc.fb:hover{background:#1877f2;}
.fsoc.ig:hover{background:linear-gradient(45deg,#405de6,#833ab4,#e1306c,#fd1d1d);}
.fsoc.wa:hover{background:#25d366;}
.fsoc.yt:hover{background:#ff0000;}

.footer-bottom{max-width:1400px;margin:0 auto;
  padding-top:20px;border-top:1px solid rgba(255,255,255,.15);text-align:center;}
.footer-bottom p{font-size:.88rem;opacity:.75;margin-bottom:6px;}
.fmotto{color:#d4af37!important;opacity:1!important;font-style:italic;font-weight:600;}
.fmotto i{margin-right:6px;}

/* TOAST */
.toast{position:fixed;bottom:24px;right:24px;padding:14px 22px;border-radius:10px;
  color:#fff;font-weight:600;font-size:.95rem;z-index:9999;
  opacity:0;transform:translateY(20px);transition:all .35s;pointer-events:none;
  box-shadow:0 6px 20px rgba(0,0,0,.2);}
.toast.show{opacity:1;transform:translateY(0);pointer-events:auto;}
.toast.success{background:#4caf50;}
.toast.error{background:#e53935;}
.toast.info{background:#1976d2;}

@media(max-width:900px){
  .footer-grid{grid-template-columns:1fr 1fr;gap:28px;}
}
@media(max-width:600px){
  .footer-grid{grid-template-columns:1fr;gap:24px;}
  .fstats{grid-template-columns:1fr 1fr;}
}
</style>

<script>
/* Service Worker PWA */
if ("serviceWorker" in navigator) {
  window.addEventListener("load", () => {
    navigator.serviceWorker.register('<?= APP_URL ?>/sw.js')
      .catch(e => console.warn("[SW]", e));
  });
}
</script>
</body>
</html>
