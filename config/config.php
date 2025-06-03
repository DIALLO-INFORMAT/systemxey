<?php
// config/config.php

// --- Configuration et Initialisation ---
error_reporting(E_ALL);
ini_set('display_errors', 1); // Mettre à 0 en production
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Constantes de la base de données ---
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Utilisateur XAMPP par défaut
define('DB_PASS', '');     // Mot de passe XAMPP par défaut (vide)
define('DB_NAME', 'systemxey_db');

// --- Constantes du site ---
define('SITE_URL', 'http://localhost/systemxey/'); // Adaptez si nécessaire
define('SITE_NAME', 'SystemXey.sn');
define('DEFAULT_PROFILE_PIC', SITE_URL . 'img/default_profile.png'); // Créez cette image placeholder
define('DEFAULT_COMPANY_LOGO', SITE_URL . 'img/default_company.png'); // Créez cette image placeholder

// --- Connexion à la base de données (PDO) ---
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En production, logguer l'erreur et afficher un message générique
    die("ERREUR CRITIQUE : Impossible de se connecter à la base de données. " . $e->getMessage());
}
?>