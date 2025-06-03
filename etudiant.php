<?php
// etudiant.php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/functions.php';

// --- Protection de la page ---
if (!isStudent()) {
    set_flash_message("Accès non autorisé. Veuillez vous connecter en tant qu'étudiant.", "error");
    if (isLoggedIn()) { // Si connecté mais pas étudiant, rediriger vers son dashboard ou accueil
        if (isAdmin()) redirect("admin.php");
        elseif (isCompany()) redirect("entreprise.php");
        else redirect("index.php?view=accueil");
    } else { // Si pas connecté du tout
        redirect("index.php?view=connexion&redirect_to=" . urlencode(SITE_URL . "etudiant.php"));
    }
}

$user_id = getUserId(); // ID de l'utilisateur étudiant connecté

// --- Routage interne au dashboard étudiant ---
$student_view = $_GET['view'] ?? 'dashboard'; // Vue par défaut du dashboard étudiant
$action = $_POST['action'] ?? $_GET['action'] ?? null;

// --- Traitement des actions POST spécifiques à l'étudiant ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'update_profil_etudiant') {
        // Récupération et validation des données du formulaire
        $nom_complet = trim($_POST['nom_complet']);
        $titre_profil = trim($_POST['titre_profil']);
        $lien_photo = filter_var(trim($_POST['lien_photo']), FILTER_VALIDATE_URL) ? trim($_POST['lien_photo']) : null;
        $lien_cv = filter_var(trim($_POST['lien_cv']), FILTER_VALIDATE_URL) ? trim($_POST['lien_cv']) : null;
        $lien_lm = filter_var(trim($_POST['lien_lm']), FILTER_VALIDATE_URL) ? trim($_POST['lien_lm']) : null;
        $etablissement = trim($_POST['etablissement']);
        $domaine_etudes = trim($_POST['domaine_etudes']);
        $niveau_etudes = trim($_POST['niveau_etudes']);
        $description_personnelle = trim($_POST['description_personnelle']);
        $competences_cles = trim($_POST['competences_cles']); // Peut être une liste séparée par des virgules
        $lien_linkedin = filter_var(trim($_POST['lien_linkedin']), FILTER_VALIDATE_URL) ? trim($_POST['lien_linkedin']) : null;
        $lien_portfolio = filter_var(trim($_POST['lien_portfolio']), FILTER_VALIDATE_URL) ? trim($_POST['lien_portfolio']) : null;
        $telephone1 = trim($_POST['telephone1']);
        $telephone2 = trim($_POST['telephone2']);
        $disponibilite = trim($_POST['disponibilite']);
        $type_contrat_recherche = trim($_POST['type_contrat_recherche']);
        $est_visible = isset($_POST['est_visible']) ? 1 : 0;

        // Validation basique (à améliorer si nécessaire)
        if (empty($nom_complet) || empty($etablissement)) {
            set_flash_message("Le nom complet et l'établissement sont requis.", "error");
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE profils_etudiants SET 
                    nom_complet = :nom_complet, titre_profil = :titre_profil, lien_photo = :lien_photo, 
                    lien_cv = :lien_cv, lien_lm = :lien_lm, etablissement = :etablissement, 
                    domaine_etudes = :domaine_etudes, niveau_etudes = :niveau_etudes, 
                    description_personnelle = :description_personnelle, competences_cles = :competences_cles, 
                    lien_linkedin = :lien_linkedin, lien_portfolio = :lien_portfolio, 
                    telephone1 = :telephone1, telephone2 = :telephone2, disponibilite = :disponibilite, 
                    type_contrat_recherche = :type_contrat_recherche, est_visible = :est_visible
                    WHERE id_utilisateur = :id_utilisateur");
                
                $stmt->execute([
                    ':nom_complet' => $nom_complet, ':titre_profil' => $titre_profil, ':lien_photo' => $lien_photo,
                    ':lien_cv' => $lien_cv, ':lien_lm' => $lien_lm, ':etablissement' => $etablissement,
                    ':domaine_etudes' => $domaine_etudes, ':niveau_etudes' => $niveau_etudes,
                    ':description_personnelle' => $description_personnelle, ':competences_cles' => $competences_cles,
                    ':lien_linkedin' => $lien_linkedin, ':lien_portfolio' => $lien_portfolio,
                    ':telephone1' => $telephone1, ':telephone2' => $telephone2, ':disponibilite' => $disponibilite,
                    ':type_contrat_recherche' => $type_contrat_recherche, ':est_visible' => $est_visible,
                    ':id_utilisateur' => $user_id
                ]);
                set_flash_message("Profil mis à jour avec succès !", "success");
            } catch (PDOException $e) {
                set_flash_message("Erreur lors de la mise à jour du profil: " . $e->getMessage(), "error");
                error_log("Erreur MAJ profil étudiant: " . $e->getMessage());
            }
        }
        redirect("etudiant.php?view=gerer_profil");

    } elseif ($action === 'postuler_offre_submit') {
        $id_offre_postuler = $_POST['id_offre'] ?? null;
        $lien_cv_candidature = filter_var(trim($_POST['lien_cv_candidature']), FILTER_VALIDATE_URL);
        $lien_lm_candidature = filter_var(trim($_POST['lien_lm_candidature']), FILTER_VALIDATE_URL) ? trim($_POST['lien_lm_candidature']) : null;
        $message_candidature = trim($_POST['message_candidature']);

        if (!$id_offre_postuler) {
            set_flash_message("ID de l'offre manquant.", "error");
            redirect("index.php?view=offres_emploi");
        }
        if (empty($lien_cv_candidature)) {
            set_flash_message("Le lien vers votre CV est obligatoire pour postuler.", "error");
            redirect("etudiant.php?view=postuler_offre&id_offre=" . $id_offre_postuler);
        } else {
            try {
                // Vérifier si l'étudiant a déjà postulé
                $stmt_check = $pdo->prepare("SELECT id FROM candidatures WHERE id_offre = ? AND id_etudiant_utilisateur = ?");
                $stmt_check->execute([$id_offre_postuler, $user_id]);
                if ($stmt_check->fetch()) {
                    set_flash_message("Vous avez déjà postulé à cette offre.", "info");
                } else {
                    $stmt_insert_candidature = $pdo->prepare("INSERT INTO candidatures 
                        (id_offre, id_etudiant_utilisateur, lien_cv_candidature, lien_lm_candidature, message_candidature, statut_candidature) 
                        VALUES (?, ?, ?, ?, ?, 'postulee')");
                    $stmt_insert_candidature->execute([$id_offre_postuler, $user_id, $lien_cv_candidature, $lien_lm_candidature, $message_candidature]);
                    set_flash_message("Votre candidature a été soumise avec succès !", "success");
                    redirect("etudiant.php?view=mes_candidatures");
                }
            } catch (PDOException $e) {
                 // Vérifier si c'est une erreur de clé dupliquée (unicite_candidature)
                if ($e->getCode() == '23000') { // Code SQLSTATE pour violation de contrainte d'intégrité
                     set_flash_message("Vous avez déjà postulé à cette offre.", "info");
                } else {
                    set_flash_message("Erreur lors de la soumission de la candidature: " . $e->getMessage(), "error");
                    error_log("Erreur postulation: " . $e->getMessage());
                }
            }
            redirect("etudiant.php?view=mes_candidatures");
        }
    } elseif ($action === 'toggle_offre_favorite') {
        $id_offre_fav = $_POST['id_offre'] ?? null;
        if ($id_offre_fav) {
            try {
                $stmt_check_fav = $pdo->prepare("SELECT * FROM offres_favorites_etudiants WHERE id_etudiant_utilisateur = ? AND id_offre = ?");
                $stmt_check_fav->execute([$user_id, $id_offre_fav]);
                if ($stmt_check_fav->fetch()) {
                    // Déjà en favori, donc supprimer
                    $stmt_remove_fav = $pdo->prepare("DELETE FROM offres_favorites_etudiants WHERE id_etudiant_utilisateur = ? AND id_offre = ?");
                    $stmt_remove_fav->execute([$user_id, $id_offre_fav]);
                    set_flash_message("Offre retirée des favoris.", "success");
                } else {
                    // Ajouter aux favoris
                    $stmt_add_fav = $pdo->prepare("INSERT INTO offres_favorites_etudiants (id_etudiant_utilisateur, id_offre) VALUES (?, ?)");
                    $stmt_add_fav->execute([$user_id, $id_offre_fav]);
                    set_flash_message("Offre ajoutée aux favoris.", "success");
                }
            } catch (PDOException $e) {
                set_flash_message("Erreur lors de la gestion des favoris: " . $e->getMessage(), "error");
                error_log("Erreur favoris: " . $e->getMessage());
            }
        }
        // Rediriger vers la page d'où vient la requête, ou une page par défaut
        $redirect_url = $_POST['redirect_back_url'] ?? "etudiant.php?view=offres_favorites";
        // S'assurer que redirect_back_url est une URL locale et sûre
        if (strpos($redirect_url, SITE_URL) !== 0 && strpos($redirect_url, 'index.php') !==0 && strpos($redirect_url, 'etudiant.php') !==0) {
            $redirect_url = "etudiant.php?view=offres_favorites"; // Fallback sécurisé
        }
        header("Location: " . $redirect_url); // Utiliser header directement car redirect() ajoute SITE_URL
        exit;
    }
}

// --- Récupération des données pour les vues du dashboard étudiant ---
if ($student_view === 'gerer_profil' || $student_view === 'postuler_offre') { // Ou si nécessaire pour le header
    $stmt_profil = $pdo->prepare("SELECT * FROM profils_etudiants WHERE id_utilisateur = ?");
    $stmt_profil->execute([$user_id]);
    $profil_etudiant = $stmt_profil->fetch();
    if (!$profil_etudiant) { // Si le profil n'existe pas encore (cas rare après inscription)
        // Créer un profil vide pour éviter les erreurs
        $pdo->prepare("INSERT INTO profils_etudiants (id_utilisateur) VALUES (?)")->execute([$user_id]);
        $stmt_profil->execute([$user_id]); // Ré-exécuter pour récupérer le profil nouvellement créé
        $profil_etudiant = $stmt_profil->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Étudiant - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* --- Styles CSS (similaires à index.php, mais peuvent être affinés pour le dashboard) --- */
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --accent-color: #28a745; /* Vert pour succès/validation */
            --light-gray: #f8f9fa;
            --dark-gray: #343a40;
            --text-color: #212529;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            --max-width-container: 1200px;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Open Sans', sans-serif; line-height: 1.6; color: var(--text-color);
            background-color: var(--light-gray); /* Fond différent pour dashboard */
            padding-top: 70px; display: flex; flex-direction: column; min-height: 100vh;
        }
        .container-dashboard { /* Spécifique au dashboard */
            width: 95%;
            max-width: 1400px; /* Plus large pour les dashboards */
            margin: 20px auto;
            padding: 0; /* Le padding sera dans sidebar/content */
            display: flex;
            gap: 20px;
            flex-grow: 1;
        }
        /* Header (identique à index.php, peut être partagé via un include plus tard) */
        header {
            background: linear-gradient(90deg, var(--primary-color) 0%, #0056b3 100%);
            color: white; padding: 10px 0; position: fixed; top: 0; left: 0;
            width: 100%; z-index: 1000; box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        header .container-header {
            width: 90%; max-width: var(--max-width-container); margin: 0 auto;
            display: flex; justify-content: space-between; align-items: center;
        }
        .logo a { font-size: 1.6em; font-weight: 700; text-decoration: none; color: white; display:flex; align-items:center; }
        .logo img { height: 40px; margin-right: 10px;}
        nav ul { list-style: none; display: flex; align-items: center;}
        nav ul li { margin-left: 15px; }
        nav ul li a { color: white; text-decoration: none; padding: 8px 12px; border-radius: var(--border-radius); transition: background-color 0.3s ease; font-size: 0.95em;}
        nav ul li a:hover, nav ul li a.active { background-color: rgba(255,255,255,0.2); }
        .nav-dropdown { position: relative; }
        .nav-dropdown-content { display: none; position: absolute; background-color: var(--primary-color); min-width: 200px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 1; border-radius: 0 0 var(--border-radius) var(--border-radius); right: 0; top:100%;}
        .nav-dropdown-content a { color: white; padding: 10px 15px; text-decoration: none; display: block; white-space: nowrap; font-size:0.9em;}
        .nav-dropdown-content a:hover { background-color: #0056b3; }
        .nav-dropdown:hover .nav-dropdown-content { display: block; }

        /* Dashboard Sidebar */
        .dashboard-sidebar {
            flex: 0 0 260px; /* Largeur fixe */
            background-color: #fff;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            height: fit-content; /* S'adapte au contenu */
        }
        .dashboard-sidebar h3 {
            font-size: 1.3em; color: var(--primary-color); margin-bottom: 10px;
            padding-bottom: 10px; border-bottom: 2px solid var(--primary-color);
            display: flex; align-items: center;
        }
        .dashboard-sidebar h3 i { margin-right: 10px; }
        .dashboard-sidebar ul { list-style: none; }
        .dashboard-sidebar ul li a {
            display: flex; align-items: center;
            padding: 12px 15px; color: var(--dark-gray); text-decoration: none;
            border-radius: var(--border-radius); margin-bottom: 8px;
            font-weight: 500; transition: background-color 0.2s ease, color 0.2s ease;
        }
        .dashboard-sidebar ul li a i { margin-right: 12px; width: 20px; text-align: center; }
        .dashboard-sidebar ul li a:hover, .dashboard-sidebar ul li a.active {
            background-color: var(--primary-color); color: white;
        }
        .dashboard-sidebar ul li a.active { font-weight: 600; }

        /* Dashboard Content Area */
        .dashboard-main-content {
            flex-grow: 1;
            background-color: #fff;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        .dashboard-main-content h2 {
            font-size: 1.8em; color: var(--primary-color); margin-bottom: 25px;
            padding-bottom: 10px; border-bottom: 1px solid #eee;
            display:flex; align-items:center;
        }
        .dashboard-main-content h2 i { margin-right:10px; }

        /* Formulaires dans le dashboard (réutilisation et ajustements) */
        .form-container { /* Appliqué par défaut par le style global */
             box-shadow: none; /* Pas d'ombre supplémentaire si déjà dans un conteneur ombré */
             padding: 0; /* Géré par .dashboard-main-content */
             max-width: none; /* Prend toute la largeur dispo */
             margin:0;
        }
        .form-container h2 { font-size: 1.6em; margin-bottom: 20px; text-align:left; border-bottom:0;} /* Titre plus petit pour les formulaires internes */
        .form-group { margin-bottom: 20px; }
        .form-group label { font-weight: 600; }
        .form-group input[type="text"], .form-group input[type="email"], .form-group input[type="password"],
        .form-group input[type="url"], .form-group input[type="tel"], .form-group select, .form-group textarea {
            padding: 10px; font-size: 0.95em;
        }
        .form-group textarea { min-height: 120px; }
        .form-container .btn { padding: 10px 20px; font-size: 1em; width: auto; margin-top:10px;} /* Boutons non pleine largeur par défaut */
        .form-check { display: flex; align-items: center; margin-bottom: 15px; }
        .form-check input[type="checkbox"] { margin-right: 10px; width: auto; }
        .form-check label { margin-bottom: 0; font-weight: normal; }

        /* Cartes pour offres, candidatures */
        .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .card { background-color: #fff; border-radius: var(--border-radius); box-shadow: var(--box-shadow); overflow: hidden; display: flex; flex-direction: column; }
        .card-content { padding: 20px; flex-grow: 1; }
        .card-title { font-size: 1.2em; color: var(--primary-color); margin-bottom: 10px; font-weight: 600; }
        .card-text { font-size: 0.9em; color: var(--secondary-color); margin-bottom: 8px; }
        .card-text strong { color: var(--dark-gray); }
        .card-actions { margin-top: auto; padding-top: 15px; border-top: 1px solid #eee; }
        .card-actions .btn { font-size: 0.9em; padding: 6px 12px; margin-right:5px;}
        .badge {
            display: inline-block;
            padding: .35em .65em;
            font-size: .75em;
            font-weight: 700;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: .25rem;
        }
        .badge-success { background-color: var(--accent-color); }
        .badge-info { background-color: #17a2b8; }
        .badge-warning { background-color: #ffc107; color: #212529;}
        .badge-danger { background-color: #dc3545; }
        .badge-secondary { background-color: var(--secondary-color); }
        .badge-primary { background-color: var(--primary-color); }

        /* Alertes / Notifications dans dashboard */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: var(--border-radius);
        }
        .alert-info { color: #0c5460; background-color: #d1ecf1; border-color: #bee5eb; }
        .alert-warning { color: #856404; background-color: #fff3cd; border-color: #ffeeba; }

        /* Messages Flash (identique à index.php) */
        .flash-message { padding: 15px 20px; margin: 0 0 20px 0; border-radius: var(--border-radius); text-align: left; font-weight: 500; border: 1px solid transparent; }
        .flash-message.success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .flash-message.error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .flash-message.info { background-color: #d1ecf1; color: #0c5460; border-color: #bee5eb; }

        /* Responsive pour Dashboard */
        @media (max-width: 992px) {
            .container-dashboard { flex-direction: column; }
            .dashboard-sidebar { flex: 0 0 auto; width: 100%; margin-bottom: 20px; }
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
                    <li><a href="<?php echo SITE_URL; ?>index.php?view=accueil">Accueil</a></li>
                    <li><a href="<?php echo SITE_URL; ?>index.php?view=offres_emploi">Offres d'Emploi</a></li>
                    <li><a href="<?php echo SITE_URL; ?>index.php?view=profils_etudiants">Profils Étudiants</a></li>
                    <li class="nav-dropdown">
                        <a href="javascript:void(0)" class="active"> <!-- Lien Dashboard toujours actif ici -->
                            <i class="fas fa-user-circle"></i> <?php echo esc_html($_SESSION['user_nom_utilisateur'] ?? explode('@', $_SESSION['user_email'])[0]); ?> <i class="fas fa-caret-down fa-xs"></i>
                        </a>
                        <div class="nav-dropdown-content">
                            <a href="<?php echo SITE_URL; ?>etudiant.php?view=dashboard"><i class="fas fa-tachometer-alt"></i> Mon Tableau de Bord</a>
                            <a href="<?php echo SITE_URL; ?>etudiant.php?view=gerer_profil"><i class="fas fa-edit"></i> Gérer mon Profil</a>
                            <a href="<?php echo SITE_URL; ?>index.php?action=deconnexion"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container-dashboard">
        <aside class="dashboard-sidebar">
            <h3><i class="fas fa-user-graduate"></i> Espace Étudiant</h3>
            <ul>
                <li><a href="<?php echo SITE_URL; ?>etudiant.php?view=dashboard" class="<?php echo ($student_view === 'dashboard') ? 'active' : ''; ?>"><i class="fas fa-home"></i> Vue d'ensemble</a></li>
                <li><a href="<?php echo SITE_URL; ?>etudiant.php?view=gerer_profil" class="<?php echo ($student_view === 'gerer_profil') ? 'active' : ''; ?>"><i class="fas fa-user-edit"></i> Gérer mon Profil</a></li>
                <li><a href="<?php echo SITE_URL; ?>index.php?view=offres_emploi" class="<?php echo ($student_view === 'rechercher_offres') ? 'active' : ''; ?>"><i class="fas fa-search"></i> Rechercher des Offres</a></li> <!-- Lien vers la page publique -->
                <li><a href="<?php echo SITE_URL; ?>etudiant.php?view=mes_candidatures" class="<?php echo ($student_view === 'mes_candidatures') ? 'active' : ''; ?>"><i class="fas fa-file-alt"></i> Mes Candidatures</a></li>
                <li><a href="<?php echo SITE_URL; ?>etudiant.php?view=offres_favorites" class="<?php echo ($student_view === 'offres_favorites') ? 'active' : ''; ?>"><i class="fas fa-heart"></i> Offres Favorites</a></li>
                <li><a href="<?php echo SITE_URL; ?>etudiant.php?view=notifications" class="<?php echo ($student_view === 'notifications') ? 'active' : ''; ?>"><i class="fas fa-bell"></i> Notifications <span class="badge badge-danger" style="font-size:0.7em;padding:3px 6px;">3</span></a></li> <!-- Exemple de badge -->
                 <li><a href="<?php echo SITE_URL; ?>etudiant.php?view=rechercher_entreprises" class="<?php echo ($student_view === 'rechercher_entreprises') ? 'active' : ''; ?>"><i class="fas fa-building"></i> Rechercher Entreprises</a></li>
            </ul>
        </aside>

        <main class="dashboard-main-content">
            <?php display_flash_message(); ?>

            <?php
            // --- Chargement de la sous-vue du dashboard étudiant ---
            $allowed_student_views = [
                'dashboard', 'gerer_profil', 'mes_candidatures', 
                'offres_favorites', 'notifications', 'postuler_offre', 'rechercher_entreprises'
            ];
            if (in_array($student_view, $allowed_student_views)) {
                include __DIR__ . '/views/etudiant/' . $student_view . '.php';
            } else {
                include __DIR__ . '/views/etudiant/dashboard.php'; // Vue par défaut sécurisée
            }
            ?>
        </main>
    </div>

    <footer style="text-align:center; padding:20px; background-color:var(--dark-gray); color:#ccc; font-size:0.9em; margin-top:auto;">
        <p><?php echo str_replace('%YEAR%', date('Y'), get_site_content('footer_texte', $pdo)); ?></p>
    </footer>

    <script>
        // Scripts JS spécifiques au dashboard étudiant si nécessaire
        // Par exemple, confirmation avant suppression, etc.
        document.addEventListener('DOMContentLoaded', () => {
            // Active link in dashboard sidebar
            const currentUrlStudent = window.location.href;
            const sidebarLinks = document.querySelectorAll('.dashboard-sidebar ul li a');
            sidebarLinks.forEach(link => {
                if (link.href === currentUrlStudent) {
                    link.classList.add('active');
                }
            });
             // Fermer les messages flash (dupliqué de index.php, pourrait être factorisé)
            const flashMessage = document.querySelector('.flash-message');
            if (flashMessage) {
                setTimeout(() => {
                    flashMessage.style.transition = 'opacity 0.5s ease';
                    flashMessage.style.opacity = '0';
                    setTimeout(() => flashMessage.remove(), 500);
                }, 7000);
            }
        });
    </script>
</body>
</html>