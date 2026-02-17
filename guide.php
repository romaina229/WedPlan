<?php
declare(strict_types=1);
// FIX: Vérifier avant de définir
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . '/');
}
require_once ROOT_PATH . 'config.php';
require_once ROOT_PATH . 'AuthManager.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

$isLoggedIn  = AuthManager::isLoggedIn();
$currentUser = $isLoggedIn ? AuthManager::getCurrentUser() : null;
?>
<?php include ROOT_PATH . 'includes/header.php'; ?>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/guide.css">
<div class="guide-container">
  <div class="guide-hero">
    <h1>💍Guide de Planification complète du Mariage</h1>
    <p>De la demande en mariage à la cérémonie : Toutes les étapes pour un mariage réussi</p>
  </div>

  <div class="timeline">
    <!-- Étape 0 : Avant le comité d'église -->
    <div class="tl-item">
      <div class="tl-marker"><i class="fas fa-church"></i></div>
      <div class="tl-content">
        <span class="step-badge">Étape clé</span>
        <h2 class="step-title">Préparatifs avant le comité d'église</h2>
        <p class="step-desc"><strong>6 mois avant le mariage civil.</strong> Démarches indispensables à effectuer avant de se présenter au comité d'église.</p>
        <ul class="step-list">
          <li>Informer le président de la JAD (Jeunesse de l'Assemblée de Dieu)</li>
          <li>Prévenir les responsables du département(s) (EDL, chorale, groupe musical, évangelisation, sécurité…) dans lequel(s) vous êtes impliqué(s)</li>
          <li>Prévenir les pasteurs avant toute démarche officielle</li>
          <li>Soumettre une demande écrite au comité d'église</li>
          <li>Participer aux séances de préparation au mariage</li>
          <li>Assister aux mariages célébrés dans l'église</li>
          <li>Obtenir : certificat de baptême, attestation de célibat, attestation de bonne conduite</li>
          <li>Planifier les rencontres avec le pasteur ou le conseiller conjugal</li>
          <li>Préparer votre témoignage de conversion et d'engagement</li>
        </ul>
        <div class="step-tip"><strong>💡 Conseil :</strong> Le comité d'église se réunit généralement une fois par mois. Prévoyez <strong>minimum 6 mois</strong> d'avance pour que votre dossier soit examiné.</div>
        <div class="step-duration"><i class="fas fa-clock"></i> 6 mois minimum avant le mariage civil</div>
      </div>
    </div>

    <!-- Étape 1 -->
    <div class="tl-item">
      <div class="tl-marker"><i class="fas fa-ring"></i></div>
      <div class="tl-content">
        <span class="step-badge">Étape 1</span>
        <h2 class="step-title">La Demande en Mariage</h2>
        <p class="step-desc">Première étape officielle : demander la main de votre bien-aimée. Cette étape doit être préparée avec soin et sincérité.</p>
        <ul class="step-list">
          <li>Préparer une bague de fiançailles</li>
          <li>Choisir le moment et le lieu parfaits</li>
          <li>Obtenir la bénédiction des familles</li>
          <li>Faire la demande officielle</li>
        </ul>
        <div class="step-duration"><i class="fas fa-clock"></i> 1 à 2 mois avant les démarches dans le cas échéant</div>
      </div>
    </div>

    <!-- Étape 2 -->
    <div class="tl-item">
      <div class="tl-marker"><i class="fas fa-handshake"></i></div>
      <div class="tl-content">
        <span class="step-badge">Étape 2</span>
        <h2 class="step-title">Prise de contact avec la belle-famille</h2>
        <p class="step-desc">Rencontre formelle avec la famille de la future épouse pour demander officiellement sa main et discuter des arrangements.</p>
        <ul class="step-list">
          <li>Préparer une enveloppe symbolique</li>
          <li>Apporter des présents (boissons, etc.)</li>
          <li>Prévoir les frais de déplacement</li>
          <li>Se faire accompagner par des membres de sa propre famille</li>
          <li>Fixer la date de la dot</li>
        </ul>
        <div class="step-duration"><i class="fas fa-clock"></i> 1 mois avant la dot dans le cas échéant</div>
      </div>
    </div>

    <!-- Étape 3 -->
    <div class="tl-item">
      <div class="tl-marker"><i class="fas fa-gift"></i></div>
      <div class="tl-content">
        <span class="step-badge">Étape 3</span>
        <h2 class="step-title">La Dot — Cérémonie Traditionnelle</h2>
        <p class="step-desc">Cérémonie où le futur marié présente la dot à la famille de la mariée selon les coutumes locales.</p>
        <ul class="step-list">
          <li>Rassembler tous les éléments de la dot</li>
          <li>Préparer la valise et les pagnes</li>
          <li>Ustensiles de cuisine complets</li>
          <li>Enveloppes (fille, famille, frères et sœurs)</li>
          <li>Boissons et collations</li>
          <li>Organiser le cortège familial</li>
        </ul>
        <div class="step-duration"><i class="fas fa-clock"></i> 2 à 3 semaines avant le mariage civil</div>
      </div>
    </div>

    <!-- Étape 4 -->
    <div class="tl-item">
      <div class="tl-marker"><i class="fas fa-landmark"></i></div>
      <div class="tl-content">
        <span class="step-badge">Étape 4</span>
        <h2 class="step-title">Mariage Civil à la Mairie</h2>
        <p class="step-desc">Légalisation de votre union devant l'officier d'état civil. Cette étape est <strong>obligatoire</strong> légalement.</p>
        <ul class="step-list">
          <li>Constituer le dossier de mariage complet</li>
          <li>Publier les bans</li>
          <li>Réunir les témoins (2 minimum)</li>
          <li>Réserver la salle de célébration</li>
          <li>Préparer une petite réception</li>
          <li>Prévoir les tenues civiles</li>
        </ul>
        <div class="step-duration"><i class="fas fa-clock"></i> 1 à 2 semaines avant la bénédiction</div>
      </div>
    </div>

    <!-- Étape 5 -->
    <div class="tl-item">
      <div class="tl-marker"><i class="fas fa-church"></i></div>
      <div class="tl-content">
        <span class="step-badge">Étape 5</span>
        <h2 class="step-title">Célébration religieuse — Bénédiction nuptiale</h2>
        <p class="step-desc">Bénédiction de votre union devant Dieu, en présence de la communauté religieuse et de vos proches.</p>
        <ul class="step-list">
          <li><span style="color :red">Vérifier que votre acte de mariage est bien déposé sans lequel votre mariage sera suspendu</span></li>
          <li>Suivre les séances de préparation au mariage</li>
          <li>Louer ou acheter la robe de mariée</li>
          <li>Acheter le costume du marié</li>
          <li>Choisir les témoins et le cortège</li>
          <li>Préparer les tenues pour le cortège</li>
          <li>Commander et récupérer les alliances</li>
        </ul>
        <div class="step-duration"><i class="fas fa-clock"></i> Le jour J</div>
      </div>
    </div>

    <!-- Étape 6 -->
    <div class="tl-item">
      <div class="tl-marker"><i class="fas fa-glass-cheers"></i></div>
      <div class="tl-content">
        <span class="step-badge">Étape 6</span>
        <h2 class="step-title">Réception et Fête</h2>
        <p class="step-desc">Célébration avec vos invités : repas, animations et moments de joie partagée avec famille et amis.</p>
        <ul class="step-list">
          <li>Réserver la salle de réception</li>
          <li>Prévoir le traiteur et les boissons</li>
          <li>Organiser la décoration</li>
          <li>Réserver les animations (DJ, orchestre...) </li>
          <li>Commander le gâteau de mariage</li>
          <li>Planifier le menu</li>
          <li>Gérer la liste des invités</li>
        </ul>
        <div class="step-duration"><i class="fas fa-clock"></i> Après l'église — Le jour J</div>
      </div>
    </div>

    <!-- Étape 7 -->
    <div class="tl-item">
      <div class="tl-marker"><i class="fas fa-truck"></i></div>
      <div class="tl-content">
        <span class="step-badge">Étape 7</span>
        <h2 class="step-title">Logistique et Organisation</h2>
        <p class="step-desc">Coordination de tous les aspects pratiques pour assurer le bon déroulement de la journée.</p>
        <ul class="step-list">
          <li>Louer les véhicules de transport</li>
          <li>Engager un photographe et un vidéaste</li>
          <li>Prévoir la sonorisation complète</li>
          <li>Imprimer les faire-part et programmes</li>
          <li>Organiser les répétitions</li>
          <li>Coordonner les horaires précis</li>
        </ul>
        <div class="step-duration"><i class="fas fa-clock"></i> Tout au long de la préparation</div>
      </div>
    </div>

    <!-- Étape 8 -->
    <div class="tl-item">
      <div class="tl-marker"><i class="fas fa-heart"></i></div>
      <div class="tl-content">
        <span class="step-badge">Étape Finale</span>
        <h2 class="step-title">Après le Mariage</h2>
        <p class="step-desc">Les formalités et moments qui suivent la célébration pour bien démarrer votre vie commune.</p>
        <ul class="step-list">
          <li>Récupérer les photos et vidéos</li>
          <li>Envoyer les remerciements aux invités</li>
          <li>Retirer le livret de famille à la mairie</li>
          <li>Installer et aménager le foyer</li>
        </ul>
        <div class="step-duration"><i class="fas fa-clock"></i> Dans les semaines suivant le mariage</div>
      </div>
    </div>

  </div><!-- /timeline -->

  <div style="text-align:center;margin:50px 0 30px">
    <a href="<?= APP_URL ?>/index.php" class="btn-cta">
      <i class="fas fa-calculator"></i> Gérer mon Budget
    </a>
    <a href="<?= APP_URL ?>/wedding_date.php" class="btn-cta btn-cta-sec">
      <i class="fas fa-calendar-heart"></i> Fixer la date
    </a>
  </div>

</div><!-- /guide-container -->

<?php include ROOT_PATH . 'includes/footer.php'; ?>
