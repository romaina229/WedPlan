<?php
session_start();
require_once __DIR__ . '/../AuthManager.php';
require_once __DIR__ . '/../config.php';

$isLoggedIn = AuthManager::isLoggedIn();
$currentUser = $isLoggedIn ? AuthManager::getCurrentUser() : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentions Légales - <?= APP_NAME ?></title>
    <link rel="shortcut icon" href="../assets/images/wedding.jpg" type="image/jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/privacy.css">
</head>
<body>
    <div class="privacy-container">
        <div class="privacy-header">
            <h1><i class="fas fa-balance-scale"></i> Mentions Légales</h1>
            <p>Informations légales concernant <?= APP_NAME ?></p>
        </div>

        <div class="privacy-content">
            <div class="section">
                <h2>1. Éditeur du site</h2>
                <p><strong>Nom :</strong> <?= APP_NAME ?></p>
                <p><strong>Statut :</strong> Projet communautaire</p>
                <p><strong>Mission :</strong> Aider à la planification budgétaire des mariages</p>
                <p><strong>Email :</strong> liferopro@gmail.com</p>
                <p><strong>Téléphone :</strong> +229 01 94 59 25 67</p>
                <p><strong>Adresse :</strong> Abomey-Calavi, Bénin</p>
            </div>

            <div class="section">
                <h2>2. Hébergement</h2>
                <p><strong>Hébergeur :</strong> apllication puissante Render</p>
                <p><strong>Serveur :</strong> en ligne</p>
                <p><strong>Base de données :</strong> MySQL</p>
                <p><strong>Version PHP :</strong> <?php echo phpversion(); ?></p>
            </div>

            <div class="section">
                <h2>3. Propriété intellectuelle</h2>
                <p>Tous les éléments du site (textes, images, logos, design, code source) sont la propriété exclusive de <?= APP_NAME ?> ou font l'objet d'une autorisation d'utilisation.</p>
                <p>Toute reproduction, modification ou distribution sans autorisation préalable est interdite.</p>
            </div>

            <div class="section">
                <h2>4. Protection des données</h2>
                <p> Conformément à la <strong>Loi N° 2017-20 du 20 avril 2018</strong> portant code du numérique en République du Bénin et à la <strong>Loi N° 2009-09 du 22 mai 2009</strong> relative à la protection des données à caractère personnel, vous disposez des droits suivants concernant vos données :</p>
                <p>
                Pour exercer ces droits ou pour toute question, contactez l'Autorité de Protection des Données Personnelles (APDP) à l'adresse : 
                <a href="mailto:contact@apdp.bj">contact@apdp.bj</a> ou visitez leur site web :
                <a href="https://apdp.bj" target="_blank">site web apdp</a>
                </p>
                
                <p>
                    <small>
                        <i class="fas fa-balance-scale"></i>
                        Références légales : Loi N° 2017-20 du 20/04/2018 (Code du numérique) et Loi N° 2009-09 du 22/05/2009 (Protection des données)
                    </small>
                </p>
            </div>

            <div class="section">
                <h2>5. Limitation de responsabilité</h2>
                <p><?= APP_NAME ?> s'efforce d'assurer l'exactitude des informations publiées sur le site. Cependant, nous ne pouvons garantir :</p>
                <ul>
                    <li>L'exactitude, l'exhaustivité ou l'actualité des informations</li>
                    <li>La disponibilité ininterrompue du service</li>
                    <li>L'absence d'erreurs ou de virus</li>
                </ul>
                <p>L'utilisation des informations et services se fait sous votre entière responsabilité.</p>
            </div>

            <div class="section">
                <h2>6. Liens externes</h2>
                <p>Notre site peut contenir des liens vers des sites tiers. Nous ne contrôlons pas ces sites et déclinons toute responsabilité concernant leur contenu, leur politique de confidentialité ou leurs pratiques.</p>
            </div>

            <div class="section">
                <h2>7. Cookies</h2>
                <p>Notre site utilise des cookies pour :</p>
                <ul>
                    <li>Améliorer votre expérience de navigation</li>
                    <li>Mémoriser vos préférences</li>
                    <li>Analyser l'utilisation du site</li>
                    <li>Assurer la sécurité</li>
                </ul>
                <p>En utilisant notre site, vous consentez à l'utilisation de ces cookies.</p>
            </div>

            <div class="section">
                <h2>8. Modification des mentions légales</h2>
                <p>Nous nous réservons le droit de modifier ces mentions légales à tout moment. Les modifications prendront effet dès leur publication sur cette page.</p>
            </div>

            <div class="section">
                <h2>9. Contact</h2>
                <div class="highlight">
                    <p><strong>Pour toute question concernant ces mentions légales :</strong></p>
                    <p><i class="fas fa-envelope"></i> liferopro@gmail.com</p>
                    <p><i class="fas fa-phone"></i> +229 01 94 59 25 67</p>
                    <p><i class="fas fa-map-marker-alt"></i> Abomey-Calavi, Bénin</p>
                </div>
            </div>
        </div>
        <div style="margin-top: 20px;">
            <a href="../index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Retour à l'accueil
            </a>
        </div>
    </div>
</html>