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
    <title>Politique de Confidentialité - <?= APP_NAME ?></title>
    <link rel="shortcut icon" href="../assets/images/wedding.jpg" type="image/jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/privacy.css">
</head>
<body>
    <div class="privacy-container">
        <div class="privacy-header">
            <h1><i class="fas fa-shield-alt"></i> Politique de Confidentialité</h1>
            <p>Dernière mise à jour : <?php echo date('d/m/Y'); ?></p>
        </div>

        <div class="privacy-content">
            <div class="section">
                <h2>1. Introduction</h2>
                <p>Bienvenue sur <?= APP_NAME ?>. Nous nous engageons à protéger votre vie privée et vos données personnelles. Cette politique de confidentialité explique comment nous collectons, utilisons, divulguons et protégeons vos informations.</p>
                
                <div class="highlight">
                    <p><strong>Note importante :</strong> En utilisant notre plateforme, vous acceptez les pratiques décrites dans cette politique de confidentialité.</p>
                </div>
            </div>

            <div class="section">
                <h2>2. Informations que nous collectons</h2>
                
                <h3>2.1 Informations que vous nous fournissez</h3>
                <ul>
                    <li><strong>Informations de compte :</strong> Nom d'utilisateur, adresse e-mail, mot de passe</li>
                    <li><strong>Informations de profil :</strong> Nom complet, photo de profil (optionnelle)</li>
                    <li><strong>Données financières :</strong> Dépenses de mariage, budgets, paiements</li>
                    <li><strong>Communications :</strong> Messages, feedback, demandes de support</li>
                </ul>

                <h3>2.2 Informations collectées automatiquement</h3>
                <ul>
                    <li><strong>Données d'utilisation :</strong> Pages visitées, durée des sessions</li>
                    <li><strong>Données techniques :</strong> Adresse IP, type de navigateur, appareil utilisé</li>
                    <li><strong>Cookies :</strong> Pour améliorer votre expérience utilisateur</li>
                </ul>
            </div>

            <div class="section">
                <h2>3. Utilisation de vos informations</h2>
                <p>Nous utilisons vos informations pour :</p>
                <ol>
                    <li>Fournir et maintenir notre service</li>
                    <li>Personnaliser votre expérience utilisateur</li>
                    <li>Communiquer avec vous (mises à jour, notifications)</li>
                    <li>Améliorer notre plateforme</li>
                    <li>Prévenir la fraude et garantir la sécurité</li>
                    <li>Se conformer aux obligations légales</li>
                </ol>
            </div>

            <div class="section">
                <h2>4. Partage de vos informations</h2>
                <p>Nous ne vendons, n'échangeons ni ne louons vos informations personnelles à des tiers. Cependant, nous pouvons partager vos informations dans les cas suivants :</p>
                
                <h3>4.1 Avec votre consentement</h3>
                <p>Lorsque vous nous donnez explicitement votre autorisation.</p>
                
                <h3>4.2 Pour des raisons légales</h3>
                <p>Si la loi l'exige ou pour répondre à des procédures légales.</p>
                
                <h3>4.3 Avec des prestataires de services</h3>
                <p>Pour des services comme l'hébergement, l'analyse de données ou le support client.</p>
            </div>

            <div class="section">
                <h2>5. Sécurité des données</h2>
                <p>Nous mettons en œuvre des mesures de sécurité appropriées pour protéger vos informations :</p>
                <ul>
                    <li>Chiffrement des données sensibles</li>
                    <li>Accès restreint aux données personnelles</li>
                    <li>Authentification sécurisée</li>
                    <li>Sauvegardes régulières</li>
                    <li>Surveillance continue</li>
                </ul>
            </div>

            <div class="section">
                <h2>6. Vos droits</h2>
                <p>Vous avez le droit de :</p>
                <ul>
                    <li>Accéder à vos données personnelles</li>
                    <li>Corriger des informations inexactes</li>
                    <li>Supprimer vos données personnelles</li>
                    <li>Vous opposer au traitement de vos données</li>
                    <li>Demander la portabilité de vos données</li>
                    <li>Retirer votre consentement à tout moment</li>
                </ul>
                
                <!--<p>Pour exercer ces droits, contactez-nous à <a href="mailto:privacy@budgetmariage.pjpm">privacy@budgetmariage.pjpm</a></p>-->
            </div>

            <div class="section">
                <h2>7. Conservation des données</h2>
                <p>Nous conservons vos données personnelles aussi longtemps que nécessaire pour :</p>
                <ul>
                    <li>Fournir nos services</li>
                    <li>Respecter nos obligations légales</li>
                    <li>Résoudre les litiges</li>
                    <li>Appliquer nos accords</li>
                </ul>
                <p>Les données sont supprimées automatiquement après 2 ans d'inactivité.</p>
            </div>

            <div class="section">
                <h2>8. Modifications de cette politique</h2>
                <p>Nous pouvons mettre à jour cette politique de confidentialité périodiquement. Nous vous informerons de tout changement important en publiant la nouvelle politique sur cette page et en vous envoyant une notification.</p>
            </div>

            <div class="contact-box">
                <h3>Questions ou préoccupations ?</h3>
                <p>Si vous avez des questions concernant cette politique de confidentialité, contactez-nous :</p>
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