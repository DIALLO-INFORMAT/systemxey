<?php
// index.php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/functions.php';

// --- Routage et Gestion des Actions ---
$view = $_GET['view'] ?? 'accueil'; // Page par défaut
$action = $_POST['action'] ?? $_GET['action'] ?? null;

// --- Traitement des actions POST (Connexion, Inscription, Newsletter) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'connexion') {
        $email_or_username = trim($_POST['email_or_username']);
        $password = $_POST['password'];

        if (empty($email_or_username) || empty($password)) {
            set_flash_message("Email/Nom d'utilisateur et mot de passe requis.", "error");
        } else {
            $stmt = $pdo->prepare("SELECT id, email, nom_utilisateur, mot_de_passe_hash, role, est_actif FROM utilisateurs WHERE email = :identifier OR nom_utilisateur = :identifier");
            $stmt->execute(['identifier' => $email_or_username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['mot_de_passe_hash'])) {
                if ($user['est_actif'] == 0) {
                    set_flash_message("Votre compte est désactivé. Veuillez contacter l'administrateur.", "error");
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_nom_utilisateur'] = $user['nom_utilisateur'];
                    set_flash_message("Connexion réussie !", "success");
                    
                    if ($user['role'] === 'admin') redirect("admin.php");
                    elseif ($user['role'] === 'etudiant') redirect("etudiant.php");
                    elseif ($user['role'] === 'entreprise') redirect("entreprise.php");
                    else redirect("index.php?view=accueil"); // Fallback
                }
            } else {
                set_flash_message("Identifiants incorrects.", "error");
            }
        }
        // Si erreur de connexion, $view est réaffecté pour rester sur la page de connexion.
        // Il est important de s'assurer que $view reste défini.
        // La ligne suivante est correcte pour ce cas.
        $view = 'connexion'; 
    }
    elseif ($action === 'inscrire_etudiant') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $password_confirm = $_POST['password_confirm'];
        $nom_complet = trim($_POST['nom_complet']);
        $etablissement = trim($_POST['etablissement']);
        $valid_inscription = true;

        if (empty($email) || empty($password) || empty($nom_complet) || empty($etablissement)) {
            set_flash_message("Tous les champs obligatoires doivent être remplis.", "error");
            $valid_inscription = false;
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash_message("Adresse email invalide.", "error");
            $valid_inscription = false;
        } elseif (strlen($password) < 6) {
            set_flash_message("Le mot de passe doit faire au moins 6 caractères.", "error");
            $valid_inscription = false;
        } elseif ($password !== $password_confirm) {
            set_flash_message("Les mots de passe ne correspondent pas.", "error");
            $valid_inscription = false;
        } 
        
        if ($valid_inscription) {
            $stmt_check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
            $stmt_check->execute([$email]);
            if ($stmt_check->fetch()) {
                set_flash_message("Cet email est déjà utilisé.", "error");
                $valid_inscription = false;
            }
        }

        if ($valid_inscription) {
            try {
                $pdo->beginTransaction();
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt_user = $pdo->prepare("INSERT INTO utilisateurs (email, mot_de_passe_hash, role, nom_utilisateur) VALUES (?, ?, 'etudiant', ?)");
                $nom_utilisateur_base = explode('@', $email)[0] . rand(10,99); 
                $stmt_user->execute([$email, $password_hash, $nom_utilisateur_base]);
                $user_id = $pdo->lastInsertId();

                $stmt_profile = $pdo->prepare("INSERT INTO profils_etudiants (id_utilisateur, nom_complet, etablissement, domaine_etudes) VALUES (?, ?, ?, ?)");
                $domaine_etudes_post = trim($_POST['domaine_etudes'] ?? 'Non spécifié');
                $stmt_profile->execute([$user_id, $nom_complet, $etablissement, $domaine_etudes_post]);
                
                $pdo->commit();
                set_flash_message("Inscription étudiant réussie ! Vous pouvez vous connecter.", "success");
                redirect("index.php?view=connexion");
            } catch (PDOException $e) {
                $pdo->rollBack();
                set_flash_message("Erreur lors de l'inscription: " . $e->getMessage(), "error");
                error_log("Erreur inscription étudiant: " . $e->getMessage());
            }
        }
        // Si l'inscription échoue (validation ou BDD), $view est réaffecté
        // pour réafficher le formulaire d'inscription.
        $view = 'inscription_etudiant'; 
    }
    elseif ($action === 'inscrire_entreprise') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $password_confirm = $_POST['password_confirm'];
        $nom_entreprise = trim($_POST['nom_entreprise']);
        $secteur_activite = trim($_POST['secteur_activite']);
        $valid_inscription_ent = true;

        if (empty($email) || empty($password) || empty($nom_entreprise) || empty($secteur_activite)) {
            set_flash_message("Tous les champs obligatoires doivent être remplis.", "error");
            $valid_inscription_ent = false;
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash_message("Adresse email invalide.", "error");
            $valid_inscription_ent = false;
        } elseif (strlen($password) < 6) {
            set_flash_message("Le mot de passe doit faire au moins 6 caractères.", "error");
            $valid_inscription_ent = false;
        } elseif ($password !== $password_confirm) {
            set_flash_message("Les mots de passe ne correspondent pas.", "error");
            $valid_inscription_ent = false;
        }

        if ($valid_inscription_ent) {
            $stmt_check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
            $stmt_check->execute([$email]);
            if ($stmt_check->fetch()) {
                set_flash_message("Cet email est déjà utilisé.", "error");
                $valid_inscription_ent = false;
            }
        }
        
        if ($valid_inscription_ent) {
             try {
                $pdo->beginTransaction();
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt_user = $pdo->prepare("INSERT INTO utilisateurs (email, mot_de_passe_hash, role, nom_utilisateur) VALUES (?, ?, 'entreprise', ?)");
                $nom_utilisateur_base = preg_replace('/[^a-zA-Z0-9]/', '', $nom_entreprise) . rand(10,99);
                $stmt_user->execute([$email, $password_hash, $nom_utilisateur_base]);
                $user_id = $pdo->lastInsertId();

                $stmt_profile = $pdo->prepare("INSERT INTO entreprises (id_utilisateur, nom_entreprise, secteur_activite) VALUES (?, ?, ?)");
                $stmt_profile->execute([$user_id, $nom_entreprise, $secteur_activite]);
                
                $pdo->commit();
                set_flash_message("Inscription entreprise réussie ! Vous pouvez vous connecter.", "success");
                redirect("index.php?view=connexion");
            } catch (PDOException $e) {
                $pdo->rollBack();
                set_flash_message("Erreur lors de l'inscription: " . $e->getMessage(), "error");
                error_log("Erreur inscription entreprise: " . $e->getMessage());
            }
        }
        $view = 'inscription_entreprise';
    }
    elseif ($action === 'subscribe_newsletter') {
        $newsletter_email = filter_var(trim($_POST['newsletter_email']), FILTER_VALIDATE_EMAIL);
        if ($newsletter_email) {
            try {
                $stmt = $pdo->prepare("INSERT INTO newsletter_inscrits (email) VALUES (?) ON DUPLICATE KEY UPDATE email=VALUES(email)"); // Correction pour ON DUPLICATE KEY
                $stmt->execute([$newsletter_email]);
                set_flash_message("Merci pour votre inscription à notre newsletter !", "success");
            } catch (PDOException $e) {
                set_flash_message("Erreur lors de l'inscription à la newsletter.", "error");
                error_log("Erreur newsletter: " . $e->getMessage());
            }
        } else {
            set_flash_message("Veuillez fournir une adresse email valide.", "error");
        }
        // La redirection suivante ne devrait pas annuler $view avant la génération HTML
        // car redirect() fait un exit. Si la redirection n'a pas lieu, $view reste ce qu'il était.
        redirect("index.php?view=" . ($_POST['current_view'] ?? 'accueil')); 
    }
}

// --- Traitement des actions GET (Déconnexion) ---
if ($action === 'deconnexion') {
    session_destroy();
    redirect("index.php?view=accueil"); // Fait un exit, donc pas de souci pour $view ici
}

// --- Protection des pages de dashboard (si qqn essaie d'y accéder via index.php) ---
$dashboard_views_check = ['dashboard_admin', 'dashboard_etudiant', 'dashboard_entreprise']; // Renommé pour éviter conflit
if (in_array($view, $dashboard_views_check)) { // $view est bien celui défini en haut
    if (!isLoggedIn()) {
        set_flash_message("Vous devez être connecté.", "error");
        redirect("index.php?view=connexion");
    }
    if ($view === 'dashboard_admin' && isAdmin()) redirect("admin.php");
    if ($view === 'dashboard_etudiant' && isStudent()) redirect("etudiant.php");
    if ($view === 'dashboard_entreprise' && isCompany()) redirect("entreprise.php");
    redirect("index.php?view=accueil"); // Fallback si rôle incorrect mais $view est un dashboard
}

// À ce stade, $view est DÉFINITIVEMENT défini, soit par $_GET['view'], soit par 'accueil' par défaut,
// soit réaffecté dans les blocs if/elseif du traitement POST.

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo ucfirst(str_replace('_', ' ', $view)); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* --- Styles CSS (Identiques à ceux fournis précédemment) --- */
        :root {
            --primary-color: #007bff; /* Bleu SystemXey */
            --secondary-color: #6c757d; /* Gris neutre */
            --accent-color: #28a745; /* Vert pour succès/validation */
            --light-gray: #f8f9fa;
            --dark-gray: #343a40;
            --text-color: #212529;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            --max-width-container: 1200px;
        }
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Open Sans', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: #fff;
            padding-top: 70px; /* Hauteur du header sticky */
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .container {
            width: 90%;
            max-width: var(--max-width-container);
            margin: 0 auto;
            padding: 20px 0;
        }
        /* Header */
        header {
            background: linear-gradient(90deg, var(--primary-color) 0%, #0056b3 100%);
            color: white;
            padding: 10px 0; /* Réduit pour un look plus fin */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        header .container-header { /* Classe spécifique pour le container du header pour éviter conflits */
            width: 90%;
            max-width: var(--max-width-container);
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo a {
            font-size: 1.6em; /* Légèrement réduit */
            font-weight: 700;
            text-decoration: none;
            color: white;
            display: flex;
            align-items: center;
        }
        .logo img {
            height: 40px; /* Taille du logo image */
            margin-right: 10px;
        }
        nav ul {
            list-style: none;
            display: flex;
            align-items: center;
        }
        nav ul li {
            margin-left: 15px; /* Espacement réduit */
        }
        nav ul li a {
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: var(--border-radius);
            transition: background-color 0.3s ease;
            font-size: 0.95em;
        }
        nav ul li a:hover, nav ul li a.active {
            background-color: rgba(255,255,255,0.2);
        }
        .nav-dropdown {
            position: relative;
        }
        .nav-dropdown-content {
            display: none;
            position: absolute;
            background-color: var(--primary-color); /* Solide pour meilleure lisibilité */
            min-width: 200px; /* Plus large */
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            right: 0;
            top: 100%; /* S'ouvre en dessous */
        }
        .nav-dropdown-content a {
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            display: block;
            white-space: nowrap;
            font-size: 0.9em;
        }
        .nav-dropdown-content a:hover {
            background-color: #0056b3;
        }
        .nav-dropdown:hover .nav-dropdown-content {
            display: block;
        }

        /* Main Content */
        main {
            flex-grow: 1;
        }
        section {
            padding: 50px 0;
        }
        section.hero-section { /* Classe spécifique pour la section héros */
            background-size: cover;
            background-position: center center;
            color: white;
            text-align: center;
            position: relative;
            padding: 80px 20px; /* Augmenté pour plus d'impact */
            min-height: 60vh; /* Plus de hauteur */
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(0, 40, 80, 0.65); /* Overlay plus sombre */
        }
        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
        }
        .hero-content h1 {
            font-size: 3em; /* Augmenté */
            margin-bottom: 20px;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        .hero-content p {
            font-size: 1.3em; /* Augmenté */
            margin-bottom: 30px;
            font-weight: 300;
        }
        .btn {
            display: inline-block;
            background-color: var(--accent-color);
            color: white;
            padding: 12px 28px; /* Plus large */
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
            border: none;
            cursor: pointer;
            font-size: 1em;
        }
        .btn:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }
        .btn-primary { background-color: var(--primary-color); }
        .btn-primary:hover { background-color: #0056b3; }
        .btn-secondary { background-color: var(--secondary-color); }
        .btn-secondary:hover { background-color: #545b62; }

        .section-title {
            text-align: center;
            font-size: 2.2em; /* Légèrement réduit */
            margin-bottom: 15px;
            color: var(--primary-color);
            font-weight: 600;
        }
        .section-subtitle {
            text-align: center;
            font-size: 1.1em;
            color: var(--secondary-color);
            margin-bottom: 40px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        .section-title-underline {
            width: 80px; /* Réduit */
            height: 3px; /* Plus fin */
            background-color: var(--accent-color);
            margin: 0 auto 30px auto;
            border-radius: 2px;
        }

        /* Formulaires */
        .form-container {
            background-color: #fff;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            max-width: 500px;
            margin: 40px auto; /* Plus d'espace */
        }
        .form-container h2 {
            text-align: center;
            margin-bottom: 25px;
            color: var(--primary-color);
            font-size: 1.8em;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-gray);
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="url"],
        .form-group input[type="tel"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: var(--border-radius);
            font-size: 1em;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
            outline: none;
        }
        .form-container .btn { width: 100%; padding: 12px; font-size: 1.1em; margin-top: 10px; }
        .form-text { text-align: center; margin-top: 20px; font-size: 0.9em; }
        .form-text a { color: var(--primary-color); text-decoration: none; }
        .form-text a:hover { text-decoration: underline; }

        /* Messages Flash */
        .flash-message {
            padding: 15px 20px;
            margin: 20px auto; /* Changé pour être au-dessus du contenu principal de la vue */
            border-radius: var(--border-radius);
            text-align: left; 
            font-weight: 500; 
            max-width: var(--max-width-container);
            border: 1px solid transparent;
        }
        .flash-message.success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .flash-message.error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .flash-message.info { background-color: #d1ecf1; color: #0c5460; border-color: #bee5eb; }

        /* Partenaires Carousel */
        .partners-section { background-color: var(--light-gray); }
        .carousel-container {
            overflow: hidden;
            width: 100%;
            position: relative;
        }
        .carousel-track {
            display: flex;
            transition: transform 0.5s ease-in-out;
        }
        .carousel-item {
            min-width: 20%; 
            flex-shrink: 0;
            padding: 15px;
            text-align: center;
        }
        .carousel-item img {
            max-width: 100%;
            height: 80px; 
            object-fit: contain; 
            display: block;
            margin: 0 auto 10px auto;
            filter: grayscale(50%);
            transition: filter 0.3s ease;
        }
        .carousel-item img:hover {
            filter: grayscale(0%);
        }
        .carousel-item p { font-size: 0.9em; color: var(--secondary-color); }
        .carousel-nav {
            text-align: center;
            margin-top: 15px;
        }
        .carousel-nav button {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 12px;
            margin: 0 5px;
            border-radius: var(--border-radius);
            cursor: pointer;
        }
        .carousel-nav button:hover { background: #0056b3; }
        
        /* Newsletter */
        .newsletter-section {
            background-color: var(--primary-color);
            color: white;
            padding: 40px 0;
        }
        .newsletter-section .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .newsletter-section h3 {
            font-size: 1.8em;
            margin-bottom: 10px;
        }
        .newsletter-section p {
            margin-bottom: 20px;
            font-size: 1.1em;
            max-width: 600px;
        }
        .newsletter-form {
            display: flex;
            gap: 10px;
            max-width: 500px;
            width: 100%;
        }
        .newsletter-form input[type="email"] {
            flex-grow: 1;
            padding: 12px;
            border-radius: var(--border-radius);
            border: 1px solid #ccc;
            font-size: 1em;
        }
        .newsletter-form button {
            background-color: var(--accent-color);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 600;
        }
        .newsletter-form button:hover { background-color: #218838; }

        /* Cartes pour profils et offres */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }
        .card {
            background-color: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.12);
        }
        .card-image-container {
            width: 100%;
            height: 180px; 
            overflow: hidden;
            background-color: var(--light-gray);
        }
        .card-image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover; 
        }
        .card-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .card-title {
            font-size: 1.3em;
            color: var(--primary-color);
            margin-bottom: 10px;
            font-weight: 600;
        }
        .card-text {
            font-size: 0.9em;
            color: var(--secondary-color);
            margin-bottom: 8px;
            line-height: 1.5;
        }
        .card-text strong { color: var(--dark-gray); }
        .card-actions {
            margin-top: auto; 
            padding-top: 15px;
        }
        .card-actions .btn { font-size: 0.9em; padding: 8px 18px; }

        /* Filtres */
        .filters-bar {
            background-color: var(--light-gray);
            padding: 15px;
            margin-bottom: 30px;
            border-radius: var(--border-radius);
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            min-width: 180px; 
        }
        .filter-group label {
            font-size: 0.85em;
            margin-bottom: 5px;
            color: var(--dark-gray);
            font-weight: 600;
        }
        .filter-group select, .filter-group input[type="text"] {
            padding: 8px;
            border-radius: var(--border-radius);
            border: 1px solid #ccc;
            font-size: 0.9em;
        }
        .filter-group .btn { padding: 8px 15px; font-size: 0.9em; }


        /* Footer */
        footer.footer-main { 
            background-color: var(--dark-gray);
            color: #ccc;
            text-align: center;
            padding: 30px 0; 
            margin-top: auto; 
        }
        footer.footer-main p {
            margin: 8px 0; 
            font-size: 0.9em;
        }
        footer.footer-main a {
            color: var(--primary-color);
            text-decoration: none;
        }
        footer.footer-main a:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .hero-content h1 { font-size: 2.5em; }
            .hero-content p { font-size: 1.1em; }
            .carousel-item { min-width: 33.33%; } 
        }
        @media (max-width: 768px) {
            body { padding-top: 60px; } 
            header .container-header { flex-direction: column; align-items: flex-start; }
            .logo { margin-bottom: 10px; }
            nav ul { flex-direction: column; width: 100%; margin-top: 10px; align-items: flex-start; }
            nav ul li { margin-left: 0; margin-bottom: 5px; width: 100%;}
            nav ul li a { display: block; }
            .nav-dropdown-content { position: static; box-shadow: none; background-color: #0056b3; }
            .nav-dropdown-content a { padding-left: 20px; } 
            .hero-content h1 { font-size: 2em; }
            .hero-content p { font-size: 1em; }
            .filters-bar { flex-direction: column; gap: 10px; }
            .filter-group { width: 100%; }
            .newsletter-form { flex-direction: column; }
            .carousel-item { min-width: 50%; } 
            .card-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 576px) {
            .carousel-item { min-width: 100%; } 
        }
    </style>
</head>
<body>
    <header>
        <div class="container-header">
            <div class="logo">
                <a href="<?php echo SITE_URL; ?>index.php?view=accueil">
                    <img src="<?php echo get_site_content('logo_site_url', $pdo); ?>" alt="<?php echo SITE_NAME; ?> Logo">
                </a>
            </div>
            <nav>
                <ul>
                    <li><a href="<?php echo SITE_URL; ?>index.php?view=accueil" class="<?php echo ($view === 'accueil') ? 'active' : ''; ?>">Accueil</a></li>
                    <li><a href="<?php echo SITE_URL; ?>index.php?view=offres_emploi" class="<?php echo ($view === 'offres_emploi' || $view === 'offre_detail') ? 'active' : ''; ?>">Offres d'Emploi</a></li>
                    <li><a href="<?php echo SITE_URL; ?>index.php?view=profils_etudiants" class="<?php echo ($view === 'profils_etudiants' || $view === 'profil_etudiant_public_detail') ? 'active' : ''; ?>">Profils Étudiants</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-dropdown">
                            <a href="javascript:void(0)">
                                <i class="fas fa-user-circle"></i> <?php echo esc_html($_SESSION['user_nom_utilisateur'] ?? explode('@', $_SESSION['user_email'])[0]); ?> <i class="fas fa-caret-down fa-xs"></i>
                            </a>
                            <div class="nav-dropdown-content">
                                <?php if (isAdmin()): ?>
                                    <a href="<?php echo SITE_URL; ?>admin.php"><i class="fas fa-tachometer-alt"></i> Tableau de Bord Admin</a>
                                <?php elseif (isStudent()): ?>
                                    <a href="<?php echo SITE_URL; ?>etudiant.php"><i class="fas fa-user-graduate"></i> Tableau de Bord Étudiant</a>
                                <?php elseif (isCompany()): ?>
                                    <a href="<?php echo SITE_URL; ?>entreprise.php"><i class="fas fa-building"></i> Tableau de Bord Entreprise</a>
                                <?php endif; ?>
                                <a href="<?php echo SITE_URL; ?>index.php?action=deconnexion"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                            </div>
                        </li>
                    <?php else: ?>
                        <li><a href="<?php echo SITE_URL; ?>index.php?view=connexion" class="btn btn-primary" style="padding: 8px 15px; background-color: var(--accent-color);">Connexion</a></li>
                        <li class="nav-dropdown">
                            <a href="javascript:void(0)" class="btn" style="padding: 8px 15px;">S'inscrire <i class="fas fa-caret-down fa-xs"></i></a>
                            <div class="nav-dropdown-content">
                                <a href="<?php echo SITE_URL; ?>index.php?view=inscription_etudiant">Inscription Étudiant</a>
                                <a href="<?php echo SITE_URL; ?>index.php?view=inscription_entreprise">Inscription Entreprise</a>
                            </div>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <?php 
        // Affichage du message flash juste avant le contenu de la vue
        display_flash_message(); 
        ?>

        <?php
        // --- Chargement de la vue demandée pour les pages publiques ---
        $public_views_directory = __DIR__ . '/views/public/';
        $auth_views_directory = __DIR__ . '/views/auth/';
        
        $view_file_path = '';

        // On s'assure que $view est bien définie avant de l'utiliser ici.
        // Normalement, elle l'est toujours grâce à l'initialisation en haut du script.
        // Mais pour être doublement sûr, on peut ajouter une vérification :
        $current_view_for_include = $view ?? 'accueil'; 

        if (file_exists($public_views_directory . $current_view_for_include . '.php')) {
            $view_file_path = $public_views_directory . $current_view_for_include . '.php';
        } elseif (file_exists($auth_views_directory . $current_view_for_include . '.php')) {
            $view_file_path = $auth_views_directory . $current_view_for_include . '.php';
        }

        if (!empty($view_file_path)) {
            include $view_file_path;
        } else {
            // Si $view est définie mais que le fichier n'existe pas, ou si $view est vide/invalide
            set_flash_message("La page demandée ('" . esc_html($current_view_for_include) . "') n'a pas pu être trouvée.", "error");
            include $public_views_directory . '404.php'; 
        }
        ?>
    </main>

    <footer class="footer-main"> 
        <div class="container">
            <p>© <?php echo date("Y"); ?> <?php echo SITE_NAME; ?>. Tous droits réservés.</p>
            <p>Développé par Boubacar DIALLO-WKB</p>
        </div>
    </footer>

    <script>
        const carousels = document.querySelectorAll('.carousel-container');
        carousels.forEach(carousel => {
            const track = carousel.querySelector('.carousel-track');
            if (!track) return; 
            const items = Array.from(track.children);
            if (items.length === 0) return; 

            const itemWidth = items[0].getBoundingClientRect().width;
            let currentIndex = 0;
            let itemsToShow = Math.floor(track.parentElement.offsetWidth / itemWidth);
            if (itemsToShow < 1) itemsToShow = 1; 

            const prevButton = carousel.querySelector('.carousel-prev');
            const nextButton = carousel.querySelector('.carousel-next');

            const updateCarousel = () => {
                if (track) {
                    track.style.transform = `translateX(-${currentIndex * itemWidth}px)`;
                }
            };
            
            if (nextButton) {
                nextButton.addEventListener('click', () => {
                    if (currentIndex < items.length - itemsToShow ) {
                        currentIndex++;
                    } else {
                         currentIndex = 0; // Boucle simple pour l'instant
                    }
                    updateCarousel();
                });
            }

            if (prevButton) {
                prevButton.addEventListener('click', () => {
                    if (currentIndex > 0) {
                        currentIndex--;
                    } else {
                        currentIndex = items.length - itemsToShow; // Boucle simple
                         if(currentIndex < 0) currentIndex = 0;
                    }
                    updateCarousel();
                });
            }
             // Auto-cycle optionnel, peut nécessiter une logique de clonage plus avancée pour une boucle infinie parfaite
            // if (items.length > itemsToShow) {
            //     setInterval(() => {
            //         if (nextButton) nextButton.click();
            //     }, 5000);
            // }
        });

        const flashMessageGlobal = document.querySelector('.flash-message');
        if (flashMessageGlobal) {
            setTimeout(() => {
                if (flashMessageGlobal) { 
                    flashMessageGlobal.style.transition = 'opacity 0.5s ease';
                    flashMessageGlobal.style.opacity = '0';
                    setTimeout(() => flashMessageGlobal.remove(), 500);
                }
            }, 7000);
        }
        
        const sectionsToAnimateGlobal = document.querySelectorAll('section:not(.hero-section)');
        const animateOnScrollGlobal = () => {
            sectionsToAnimateGlobal.forEach(section => {
                const sectionTop = section.getBoundingClientRect().top;
                const windowHeight = window.innerHeight;
                if (sectionTop < windowHeight * 0.90) {
                    section.style.opacity = '1';
                    section.style.transform = 'translateY(0)';
                }
            });
        };

        sectionsToAnimateGlobal.forEach(section => {
            section.style.opacity = '0';
            section.style.transform = 'translateY(30px)';
            section.style.transition = 'opacity 0.7s ease-out, transform 0.7s ease-out';
        });
        
        document.addEventListener('DOMContentLoaded', () => {
            animateOnScrollGlobal();
            window.addEventListener('scroll', animateOnScrollGlobal);

            const currentUrlGlobal = window.location.href;
            const navLinksGlobal = document.querySelectorAll('header nav ul li a');
            navLinksGlobal.forEach(link => {
                // Pour une correspondance exacte
                if (link.href === currentUrlGlobal) {
                    link.classList.add('active');
                }
                // Cas spécial pour la page d'accueil si l'URL est juste le domaine ou index.php
                const siteBaseUrl = "<?php echo SITE_URL; ?>";
                if ((currentUrlGlobal === siteBaseUrl || currentUrlGlobal === siteBaseUrl + 'index.php' || currentUrlGlobal === siteBaseUrl + 'index.php?view=accueil') && link.href.includes('index.php?view=accueil')) {
                     link.classList.add('active');
                }
                // Gérer l'activation pour les pages de détail
                if (currentUrlGlobal.includes('offre_detail') && link.href.includes('offres_emploi')) {
                    link.classList.add('active');
                }
                if (currentUrlGlobal.includes('profil_etudiant_public_detail') && link.href.includes('profils_etudiants')) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>