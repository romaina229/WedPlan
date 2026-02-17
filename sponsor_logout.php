<?php
/**
 * Page de déconnexion pour les parrains/conseillers
 * 
 * @package WeddingManagement
 * @subpackage SponsorSystem
 */

require_once __DIR__ . '/config_da.php';

// Déconnexion du parrain
logoutSponsor();

// Rediriger vers la page de connexion
header("Location: sponsor_login.php");
exit();
