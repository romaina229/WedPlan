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
    <title>Conditions d'Utilisation - <?= APP_NAME ?></title>
    <link rel="shortcut icon" href="../assets/images/wedding.jpg" type="image/jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/privacy.css">
</head>
<body>
    <div class="privacy-container">
        <div class="privacy-header">
            <h1><i class="fas fa-file-contract"></i> Conditions d'Utilisation</h1>
            <p>Dernière mise à jour : <?php echo date('d/m/Y'); ?></p>
        </div>

        <div class="privacy-content">
            <div class="section">
                <h2>1. Acceptation des conditions</h2>
                <p>En accédant et en utilisant <?= APP_NAME ?>, vous acceptez d'être lié par ces conditions d'utilisation. Si vous n'acceptez pas ces conditions, veuillez ne pas utiliser notre service.</p>
            </div>

            <div class="section">
                <h2>2. Description du service</h2>
                <p><?= APP_NAME ?> est une plateforme de gestion de budget de mariage qui permet aux utilisateurs de :</p>
                <ul>
                    <li>Créer et gérer des budgets de mariage</li>
                    <li>Suivre les dépenses et paiements</li>
                    <li>Organiser les différentes étapes du mariage</li>
                    <li>Consulter des guides et ressources</li>
                    <li>Collaborer sur la planification</li>
                </ul>
            </div>

            <div class="section">
                <h2>3. Compte utilisateur</h2>
                
                <h3>3.1 Création de compte</h3>
                <p>Pour utiliser nos services, vous devez créer un compte avec des informations exactes et complètes.</p>
                
                <h3>3.2 Sécurité du compte</h3>
                <p>Vous êtes responsable de :</p>
                <ul>
                    <li>Maintenir la confidentialité de vos identifiants</li>
                    <li>Toutes les activités sur votre compte</li>
                    <li>Signaler immédiatement toute utilisation non autorisée</li>
                </ul>
                
                <h3>3.3 Age minimum</h3>
                <p>Vous devez avoir au moins 18 ans pour utiliser notre service.</p>
            </div>

            <div class="section">
                <h2>4. Contenu utilisateur</h2>
                
                <h3>4.1 Responsabilité</h3>
                <p>Vous êtes seul responsable du contenu que vous publiez sur notre plateforme.</p>
                
                <h3>4.2 Restrictions de contenu</h3>
                <p>Vous ne devez pas publier de contenu qui :</p>
                <ul>
                    <li>Est illégal, frauduleux ou trompeur</li>
                    <li>Enfreint les droits d'autrui</li>
                    <li>Est diffamatoire, obscène ou offensant</li>
                    <li>Contient des virus ou code malveillant</li>
                    <li>Promouvoit la violence ou la haine</li>
                </ul>
                
                <h3>4.3 Licence</h3>
                <p>En publiant du contenu, vous nous accordez une licence pour l'utiliser, le reproduire et le modifier pour fournir nos services.</p>
            </div>

            <div class="section">
                <h2>5. Propriété intellectuelle</h2>
                <p>Tous les droits de propriété intellectuelle relatifs à notre plateforme (logos, design, code source) sont notre propriété exclusive ou celle de nos concédants de licence.</p>
            </div>

            <div class="section">
                <h2>6. Limitation de responsabilité</h2>
                <p>Notre service est fourni "tel quel" sans garantie d'aucune sorte. Nous ne sommes pas responsables :</p>
                <ul>
                    <li>Des erreurs ou interruptions de service</li>
                    <li>Des pertes de données</li>
                    <li>Des décisions financières prises sur la base de nos services</li>
                    <li>Des dommages indirects ou consécutifs</li>
                </ul>
            </div>

            <div class="section">
                <h2>7. Suspension et résiliation</h2>
                <p>Nous pouvons suspendre ou résilier votre accès si :</p>
                <ul>
                    <li>Vous violez ces conditions</li>
                    <li>Vous créez un risque ou une responsabilité légale</li>
                    <li>Votre compte est inactif pendant plus de 2 ans</li>
                </ul>
            </div>

            <div class="section">
                <h2>8. Modifications des conditions</h2>
                <p>Nous nous réservons le droit de modifier ces conditions à tout moment. Les modifications prendront effet dès leur publication sur cette page.</p>
            </div>

            <div class="section">
                <h2>9. Droit applicable</h2>
                <p>Ces conditions sont régies par les lois de la République de Bénin. Tout litige relève de la compétence des tribunaux d'Abomey-Calavi.</p>
            </div>

            <div class="contact-box">
                <h3>Contact pour les questions légales</h3>
                <p><i class="fas fa-envelope"></i> liferopro@gmail.com</p>
                <p><i class="fas fa-phone"></i> +229 01 94 59 25 67</p>
            </div>
        </div>
        <div style="margin-top: 20px;">
            <a href="../index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Retour à l'accueil
            </a>
        </div>
    </div>
</body>
</html>