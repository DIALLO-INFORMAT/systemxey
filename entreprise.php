<?php
// entreprise.php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/functions.php';

// --- Protection de la page ---
if (!isCompany()) {
    set_flash_message("Accès non autorisé. Veuillez vous connecter en tant qu'entreprise.", "error");
    if (isLoggedIn()) {
        if (isAdmin()) redirect("admin.php");
        elseif (isStudent()) redirect("etudiant.php");
        else redirect("index.php?view=accueil");
    } else {
        redirect("index.php?view=connexion&redirect_to=" . urlencode(SITE_URL . "entreprise.php"));
    }
}

$user_id_entreprise = getUserId(); 

// --- Routage interne au dashboard entreprise ---
$company_view = $_GET['view'] ?? 'dashboard'; 
$action = $_POST['action'] ?? $_GET['action'] ?? null;
$id_offre_param = $_GET['id_offre'] ?? $_POST['id_offre'] ?? null; 
$id_candidature_param = $_GET['id_candidature'] ?? $_POST['id_candidature'] ?? null;


// --- Traitement des actions POST spécifiques à l'entreprise ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'update_profil_entreprise') {
        $nom_entreprise = trim($_POST['nom_entreprise']);
        $secteur_activite = trim($_POST['secteur_activite']);
        $description_entreprise = trim($_POST['description_entreprise']);
        $lien_logo = filter_var(trim($_POST['lien_logo']), FILTER_VALIDATE_URL) ? trim($_POST['lien_logo']) : null;
        $site_web_url = filter_var(trim($_POST['site_web_url']), FILTER_VALIDATE_URL) ? trim($_POST['site_web_url']) : null;
        $adresse = trim($_POST['adresse']);

        if (empty($nom_entreprise) || empty($secteur_activite)) {
            set_flash_message("Le nom de l'entreprise et le secteur d'activité sont requis.", "error");
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE entreprises SET 
                    nom_entreprise = :nom_entreprise, secteur_activite = :secteur_activite, 
                    description_entreprise = :description_entreprise, lien_logo = :lien_logo, 
                    site_web_url = :site_web_url, adresse = :adresse
                    WHERE id_utilisateur = :id_utilisateur");
                $stmt->execute([
                    ':nom_entreprise' => $nom_entreprise, ':secteur_activite' => $secteur_activite,
                    ':description_entreprise' => $description_entreprise, ':lien_logo' => $lien_logo,
                    ':site_web_url' => $site_web_url, ':adresse' => $adresse,
                    ':id_utilisateur' => $user_id_entreprise
                ]);
                set_flash_message("Profil d'entreprise mis à jour avec succès !", "success");
            } catch (PDOException $e) {
                set_flash_message("Erreur lors de la mise à jour du profil: " . $e->getMessage(), "error");
                error_log("Erreur MAJ profil entreprise: " . $e->getMessage());
            }
        }
        redirect("entreprise.php?view=gerer_profil_entreprise");

    } elseif ($action === 'creer_offre' || $action === 'modifier_offre') {
        $titre_poste = trim($_POST['titre_poste']);
        $description_poste = trim($_POST['description_poste']);
        $type_contrat = trim($_POST['type_contrat']);
        $lieu = trim($_POST['lieu']);
        $competences_requises = trim($_POST['competences_requises']);
        $date_limite_candidature = !empty($_POST['date_limite_candidature']) ? trim($_POST['date_limite_candidature']) : null;
        $est_active_offre = isset($_POST['est_active_offre']) ? 1 : 0;

        if (empty($titre_poste) || empty($description_poste) || empty($type_contrat) || empty($lieu)) {
            set_flash_message("Titre, description, type de contrat et lieu sont requis pour une offre.", "error");
            $_SESSION['_form_data'] = $_POST; 
            $company_view = ($action === 'creer_offre') ? 'form_offre&mode=creer' : ('form_offre&mode=modifier&id_offre=' . ($id_offre_param ?? ''));

        } else {
            try {
                if ($action === 'creer_offre') {
                    $stmt = $pdo->prepare("INSERT INTO offres_emploi 
                        (id_entreprise_utilisateur, titre_poste, description_poste, type_contrat, lieu, competences_requises, date_limite_candidature, est_active, statut_validation_admin) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')");
                    $stmt->execute([
                        $user_id_entreprise, $titre_poste, $description_poste, $type_contrat, $lieu,
                        $competences_requises, $date_limite_candidature, $est_active_offre
                    ]);
                    set_flash_message("Nouvelle offre d'emploi créée avec succès. Elle sera visible après validation par un administrateur.", "success");
                } elseif ($action === 'modifier_offre' && $id_offre_param) {
                    $stmt_check = $pdo->prepare("SELECT id FROM offres_emploi WHERE id = ? AND id_entreprise_utilisateur = ?");
                    $stmt_check->execute([$id_offre_param, $user_id_entreprise]);
                    if ($stmt_check->fetch()) {
                        $stmt = $pdo->prepare("UPDATE offres_emploi SET 
                            titre_poste = ?, description_poste = ?, type_contrat = ?, lieu = ?, 
                            competences_requises = ?, date_limite_candidature = ?, est_active = ?,
                            statut_validation_admin = 'en_attente'
                            WHERE id = ? AND id_entreprise_utilisateur = ?");
                        $stmt->execute([
                            $titre_poste, $description_poste, $type_contrat, $lieu,
                            $competences_requises, $date_limite_candidature, $est_active_offre,
                            $id_offre_param, $user_id_entreprise
                        ]);
                        set_flash_message("Offre d'emploi mise à jour avec succès. Elle sera visible après re-validation par un administrateur.", "success");
                    } else {
                         set_flash_message("Tentative de modification d'une offre non autorisée.", "error");
                    }
                }
                 redirect("entreprise.php?view=mes_offres"); 
            } catch (PDOException $e) {
                set_flash_message("Erreur lors de la gestion de l'offre: " . $e->getMessage(), "error");
                error_log("Erreur offre entreprise: " . $e->getMessage());
                $_SESSION['_form_data'] = $_POST;
                $company_view = ($action === 'creer_offre') ? 'form_offre&mode=creer' : ('form_offre&mode=modifier&id_offre=' . ($id_offre_param ?? ''));
            }
        }

    } elseif ($action === 'supprimer_offre' && $id_offre_param) {
        try {
            $stmt_check = $pdo->prepare("SELECT id FROM offres_emploi WHERE id = ? AND id_entreprise_utilisateur = ?");
            $stmt_check->execute([$id_offre_param, $user_id_entreprise]);
            if ($stmt_check->fetch()) {
                $pdo->beginTransaction();
                $stmt_del_cand = $pdo->prepare("DELETE FROM candidatures WHERE id_offre = ?");
                $stmt_del_cand->execute([$id_offre_param]);
                $stmt_del_fav = $pdo->prepare("DELETE FROM offres_favorites_etudiants WHERE id_offre = ?");
                $stmt_del_fav->execute([$id_offre_param]);
                $stmt = $pdo->prepare("DELETE FROM offres_emploi WHERE id = ? AND id_entreprise_utilisateur = ?");
                $stmt->execute([$id_offre_param, $user_id_entreprise]);
                $pdo->commit();
                set_flash_message("Offre d'emploi supprimée avec succès.", "success");
            } else {
                 set_flash_message("Tentative de suppression d'une offre non autorisée.", "error");
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            set_flash_message("Erreur lors de la suppression de l'offre: " . $e->getMessage(), "error");
            error_log("Erreur suppression offre: " . $e->getMessage());
        }
        redirect("entreprise.php?view=mes_offres");

    } 
    elseif ($action === 'update_statut_candidature' && $id_candidature_param) {
        // --- DÉBUT DÉBOGAGE ---
        echo "<pre style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc; margin: 10px;'>Données POST reçues pour update_statut_candidature:\n";
        print_r($_POST);
        echo "</pre>";
        
        $nouveau_statut_brut = $_POST['decision_statut'] ?? 'NON_DEFINI_DANS_POST';
        echo "<pre style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc; margin: 10px;'>Valeur brute de \$_POST['decision_statut']: ";
        var_dump($nouveau_statut_brut);
        echo "</pre>";
        // --- FIN DÉBOGAGE ---

        $nouveau_statut = $_POST['decision_statut'] ?? null; 
        $message_pour_candidat = trim($_POST['message_pour_candidat'] ?? ''); 
        $id_offre_redirect = $_POST['id_offre_pour_redirect'] ?? null;

        $statuts_decision_valides = ['acceptee', 'refusee', 'entretien_planifie', 'vue_par_entreprise', 'en_cours_analyse'];
        
        // --- DÉBUT DÉBOGAGE DE LA CONDITION ---
        echo "<pre style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc; margin: 10px;'>Vérification de la condition pour update_statut_candidature:\n";
        echo "  \$nouveau_statut ('" . esc_html(strval($nouveau_statut)) . "') est-il non vide ? " . (!empty($nouveau_statut) ? 'Oui' : 'Non') . "\n";
        echo "  \$nouveau_statut ('" . esc_html(strval($nouveau_statut)) . "') est-il dans \$statuts_decision_valides ? " . (in_array($nouveau_statut, $statuts_decision_valides) ? 'Oui' : 'Non') . "\n";
        echo "  Tableau \$statuts_decision_valides: \n";
        print_r($statuts_decision_valides);
        echo "</pre>";
        // --- FIN DÉBOGAGE DE LA CONDITION ---
        // die("Fin du débogage de l'action update_statut_candidature avant la condition IF. Vérifiez les valeurs ci-dessus."); // Décommentez pour arrêter ici

        if (!empty($nouveau_statut) && in_array($nouveau_statut, $statuts_decision_valides)) {
            try {
                $stmt_check = $pdo->prepare(
                    "SELECT c.id FROM candidatures c 
                     JOIN offres_emploi o ON c.id_offre = o.id
                     WHERE c.id = ? AND o.id_entreprise_utilisateur = ?"
                );
                $stmt_check->execute([$id_candidature_param, $user_id_entreprise]);

                if ($stmt_check->fetch()) {
                    $stmt_update = $pdo->prepare(
                        "UPDATE candidatures SET 
                            statut_candidature = ?, 
                            decision_entreprise_commentaire = ?, 
                            date_decision_entreprise = NOW()    
                         WHERE id = ?"
                    );
                    $stmt_update->execute([$nouveau_statut, $message_pour_candidat, $id_candidature_param]);
                    set_flash_message("Décision et message pour la candidature mis à jour.", "success");

                } else {
                    set_flash_message("Action non autorisée sur cette candidature.", "error");
                }
            } catch (PDOException $e) {
                set_flash_message("Erreur lors de la mise à jour de la décision: " . $e->getMessage(), "error");
                error_log("Erreur MAJ statut/décision cand: " . $e->getMessage());
            }
        } else {
            if (empty($nouveau_statut)) { 
                set_flash_message("Veuillez sélectionner une décision valide pour la candidature.", "error");
            } else { 
                set_flash_message("Décision de candidature invalide ('" . esc_html($nouveau_statut) . "').", "error");
            }
        }
        
        // La redirection se fera après l'affichage des messages de débogage si die() est commenté
        if($id_offre_redirect){
             redirect("entreprise.php?view=candidatures_offre&id_offre=" . $id_offre_redirect);
        } else {
             redirect("entreprise.php?view=mes_offres"); 
        }
    }
}

// --- Récupération des données pour les vues du dashboard entreprise ---
// ... (le reste du code PHP reste identique)
if ($company_view === 'gerer_profil_entreprise' || $company_view === 'form_offre') {
    $stmt_profil_ent = $pdo->prepare("SELECT * FROM entreprises WHERE id_utilisateur = ?");
    $stmt_profil_ent->execute([$user_id_entreprise]);
    $profil_entreprise = $stmt_profil_ent->fetch();
     if (!$profil_entreprise) { 
        $pdo->prepare("INSERT INTO entreprises (id_utilisateur, nom_entreprise) VALUES (?, (SELECT SUBSTRING_INDEX(email, '@', 1) FROM utilisateurs WHERE id = ?))")->execute([$user_id_entreprise, $user_id_entreprise]);
        $stmt_profil_ent->execute([$user_id_entreprise]);
        $profil_entreprise = $stmt_profil_ent->fetch();
    }
}
if ($company_view === 'form_offre' || $company_view === 'candidatures_offre') {
    if (isset($_GET['mode']) && $_GET['mode'] === 'modifier' && !$id_offre_param) {
        set_flash_message("ID de l'offre manquant pour modification.", "error");
        redirect("entreprise.php?view=mes_offres");
    }

    if ($id_offre_param) { 
        $stmt_offre_details = $pdo->prepare("SELECT * FROM offres_emploi WHERE id = ? AND id_entreprise_utilisateur = ?");
        $stmt_offre_details->execute([$id_offre_param, $user_id_entreprise]);
        $offre_a_modifier = $stmt_offre_details->fetch(); 

        if (!$offre_a_modifier && $company_view === 'form_offre' && isset($_GET['mode']) && $_GET['mode'] === 'modifier') {
            set_flash_message("Offre non trouvée ou non autorisée pour modification.", "error");
            redirect("entreprise.php?view=mes_offres");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Entreprise - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root { 
            --primary-color: #007bff; --secondary-color: #6c757d; --accent-color: #28a745;
            --light-gray: #f8f9fa; --dark-gray: #343a40; --text-color: #212529;
            --border-radius: 8px; --box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            --max-width-container: 1200px;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Open Sans', sans-serif; line-height: 1.6; color: var(--text-color);
            background-color: var(--light-gray); padding-top: 70px; display: flex;
            flex-direction: column; min-height: 100vh;
        }
        .container-dashboard { width: 95%; max-width: 1400px; margin: 20px auto; padding: 0; display: flex; gap: 20px; flex-grow: 1; }
        header { 
            background: linear-gradient(90deg, var(--primary-color) 0%, #0056b3 100%);
            color: white; padding: 10px 0; position: fixed; top: 0; left: 0;
            width: 100%; z-index: 1000; box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        header .container-header { width: 90%; max-width: var(--max-width-container); margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .logo a { font-size: 1.6em; font-weight: 700; text-decoration: none; color: white; display:flex; align-items:center; }
        .logo img { height: 40px; margin-right: 10px;}
        nav ul { list-style: none; display: flex; align-items: center;}
        nav ul li { margin-left: 15px; }
        nav ul li a { color: white; text-decoration: none; padding: 8px 12px; border-radius: var(--border-radius); transition: background-color 0.3s ease; font-size: 0.95em;}
        nav ul li a:hover, nav ul li a.active { background-color: rgba(255,255,255,0.2); }
        .nav-dropdown { position: relative; }
        .nav-dropdown-content { display: none; position: absolute; background-color: var(--primary-color); min-width: 220px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 1; border-radius: 0 0 var(--border-radius) var(--border-radius); right: 0; top:100%;}
        .nav-dropdown-content a { color: white; padding: 10px 15px; text-decoration: none; display: block; white-space: nowrap; font-size:0.9em;}
        .nav-dropdown-content a:hover { background-color: #0056b3; }
        .nav-dropdown:hover .nav-dropdown-content { display: block; }

        .dashboard-sidebar { 
            flex: 0 0 260px; background-color: #fff; padding: 20px;
            border-radius: var(--border-radius); box-shadow: var(--box-shadow); height: fit-content;
        }
        .dashboard-sidebar h3 { font-size: 1.3em; color: var(--primary-color); margin-bottom: 10px; padding-bottom: 10px; border-bottom: 2px solid var(--primary-color); display: flex; align-items: center; }
        .dashboard-sidebar h3 i { margin-right: 10px; }
        .dashboard-sidebar ul { list-style: none; }
        .dashboard-sidebar ul li a { display: flex; align-items: center; padding: 12px 15px; color: var(--dark-gray); text-decoration: none; border-radius: var(--border-radius); margin-bottom: 8px; font-weight: 500; transition: background-color 0.2s ease, color 0.2s ease; }
        .dashboard-sidebar ul li a i { margin-right: 12px; width: 20px; text-align: center; }
        .dashboard-sidebar ul li a:hover, .dashboard-sidebar ul li a.active { background-color: var(--primary-color); color: white; }
        .dashboard-sidebar ul li a.active { font-weight: 600; }

        .dashboard-main-content { 
            flex-grow: 1; background-color: #fff; padding: 25px;
            border-radius: var(--border-radius); box-shadow: var(--box-shadow);
        }
        .dashboard-main-content h2 { font-size: 1.8em; color: var(--primary-color); margin-bottom: 25px; padding-bottom: 10px; border-bottom: 1px solid #eee; display:flex; align-items:center; }
         .dashboard-main-content h2 i { margin-right:10px; }

        .form-container { box-shadow: none; padding: 0; max-width: none; margin:0; }
        .form-container h2 { font-size: 1.6em; margin-bottom: 20px; text-align:left; border-bottom:0;}
        .form-group { margin-bottom: 20px; }
        .form-group label { font-weight: 600; }
        .form-group input[type="text"], .form-group input[type="email"], .form-group input[type="password"],
        .form-group input[type="url"], .form-group input[type="tel"], .form-group input[type="date"],
        .form-group select, .form-group textarea { width:100%; padding: 10px; font-size: 0.95em; border:1px solid #ccc; border-radius:var(--border-radius); }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--primary-color); box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); outline:none; }
        .form-group textarea { min-height: 120px; }
        .form-container .btn { padding: 10px 20px; font-size: 1em; width: auto; margin-top:10px;}
        .form-check { display: flex; align-items: center; margin-bottom: 15px; }
        .form-check input[type="checkbox"] { margin-right: 10px; width: auto; }
        .form-check label { margin-bottom: 0; font-weight: normal; }

        .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .card { background-color: #fff; border-radius: var(--border-radius); box-shadow: var(--box-shadow); overflow: hidden; display: flex; flex-direction: column; }
        .card-header { padding: 15px 20px; background-color:var(--light-gray); border-bottom:1px solid #eee;}
        .card-image-container { width: 100%; height: 180px; overflow: hidden; background-color: var(--light-gray); }
        .card-image-container img { width: 100%; height: 100%; object-fit: cover; }
        .card-content { padding: 20px; flex-grow: 1; }
        .card-title { font-size: 1.2em; color: var(--primary-color); margin-bottom: 10px; font-weight: 600; }
        .card-text { font-size: 0.9em; color: var(--secondary-color); margin-bottom: 8px; }
        .card-text strong { color: var(--dark-gray); }
        .card-actions { margin-top: auto; padding: 15px 20px; border-top: 1px solid #eee; text-align:right;}
        .card-actions .btn { font-size: 0.9em; padding: 6px 12px; margin-left:5px;}
        
        .badge { display: inline-block; padding: .35em .65em; font-size: .75em; font-weight: 700; line-height: 1; color: #fff; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: .25rem; }
        .badge-success { background-color: var(--accent-color); }
        .badge-info { background-color: #17a2b8; }
        .badge-warning { background-color: #ffc107; color: #212529;}
        .badge-danger { background-color: #dc3545; }
        .badge-secondary { background-color: var(--secondary-color); }
        .badge-primary { background-color: var(--primary-color); }
        .badge-en-attente { background-color: #ffc107; color: #212529;}
        .badge-validee { background-color: var(--accent-color);}
        .badge-refusee { background-color: #dc3545;}

        .table-responsive { overflow-x: auto; }
        .table { width: 100%; margin-bottom: 1rem; color: var(--text-color); border-collapse: collapse; }
        .table th, .table td { padding: .75rem; vertical-align: top; border-top: 1px solid #dee2e6; }
        .table thead th { vertical-align: bottom; border-bottom: 2px solid #dee2e6; background-color: var(--light-gray); }
        .table tbody tr:nth-of-type(odd) { background-color: rgba(0,0,0,.03); }
        .table .actions-column .btn { margin-bottom:5px; } 
        .table .actions-column form { display:inline-block; margin-right:5px; } 


        .flash-message { padding: 15px 20px; margin: 0 0 20px 0; border-radius: var(--border-radius); text-align: left; font-weight: 500; border: 1px solid transparent; }
        .flash-message.success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .flash-message.error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .flash-message.info { background-color: #d1ecf1; color: #0c5460; border-color: #bee5eb; }

        .filters-bar {
            background-color: var(--light-gray); padding: 15px; margin-bottom: 30px;
            border-radius: var(--border-radius); display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;
        }
        .filter-group { display: flex; flex-direction: column; min-width: 180px; }
        .filter-group label { font-size: 0.85em; margin-bottom: 5px; color: var(--dark-gray); font-weight: 600; }
        .filter-group select, .filter-group input[type="text"] { padding: 8px; border-radius: var(--border-radius); border: 1px solid #ccc; font-size: 0.9em; }
        .filter-group .btn { padding: 8px 15px; font-size: 0.9em; }

        @media (max-width: 992px) { .container-dashboard { flex-direction: column; } .dashboard-sidebar { flex: 0 0 auto; width: 100%; margin-bottom: 20px; } }
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
                <a href="<?php echo SITE_URL; ?>index.php?view=accueil" title="<?php echo SITE_NAME; ?> - Accueil">
                    <?php
                    $logo_display_info = get_site_logo_info($pdo); 
                    if ($logo_display_info['type'] === 'image') :
                    ?>
                        <img src="<?php echo esc_html($logo_display_info['value']); ?>" alt="<?php echo SITE_NAME; ?> Logo" style="height: 40px; max-width: 180px; object-fit: contain;">
                    <?php else: ?>
                        <span style="font-size: 1.6em; font-weight: 700; color: white;"><?php echo esc_html($logo_display_info['value']); ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <nav>
                <ul>
                    <li><a href="<?php echo SITE_URL; ?>index.php?view=accueil">Accueil</a></li>
                    <li><a href="<?php echo SITE_URL; ?>index.php?view=offres_emploi">Offres d'Emploi</a></li>
                    <li><a href="<?php echo SITE_URL; ?>index.php?view=profils_etudiants">Profils Étudiants</a></li>
                    <li class="nav-dropdown">
                        <a href="javascript:void(0)" class="active">
                            <i class="fas fa-building"></i> <?php echo esc_html($_SESSION['user_nom_utilisateur'] ?? explode('@', $_SESSION['user_email'])[0]); ?> <i class="fas fa-caret-down fa-xs"></i>
                        </a>
                        <div class="nav-dropdown-content">
                            <a href="<?php echo SITE_URL; ?>entreprise.php?view=dashboard"><i class="fas fa-tachometer-alt"></i> Mon Tableau de Bord</a>
                            <a href="<?php echo SITE_URL; ?>entreprise.php?view=gerer_profil_entreprise"><i class="fas fa-industry"></i> Gérer Profil Entreprise</a>
                            <a href="<?php echo SITE_URL; ?>index.php?action=deconnexion"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container-dashboard">
        <aside class="dashboard-sidebar">
            <h3><i class="fas fa-briefcase"></i> Espace Entreprise</h3>
            <ul>
                <li><a href="<?php echo SITE_URL; ?>entreprise.php?view=dashboard" class="<?php echo ($company_view === 'dashboard') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Vue d'ensemble</a></li>
                <li><a href="<?php echo SITE_URL; ?>entreprise.php?view=gerer_profil_entreprise" class="<?php echo ($company_view === 'gerer_profil_entreprise') ? 'active' : ''; ?>"><i class="fas fa-id-card-alt"></i> Gérer Profil Entreprise</a></li>
                <li><a href="<?php echo SITE_URL; ?>entreprise.php?view=rechercher_profils" class="<?php echo ($company_view === 'rechercher_profils' || $company_view === 'profil_detail') ? 'active' : ''; ?>"><i class="fas fa-users"></i> Rechercher Profils</a></li>
                <li><a href="<?php echo SITE_URL; ?>entreprise.php?view=mes_offres" class="<?php echo ($company_view === 'mes_offres' || $company_view === 'form_offre' || $company_view === 'candidatures_offre') ? 'active' : ''; ?>"><i class="fas fa-list-alt"></i> Mes Offres</a></li>
                <li><a href="<?php echo SITE_URL; ?>entreprise.php?view=form_offre&mode=creer" class="btn btn-primary" style="color:white; text-align:center; margin-top:10px; padding: 12px 15px; width:100%; display:flex; justify-content:center; align-items:center;"><i class="fas fa-plus-circle" style="margin-right:8px;"></i> Publier une Offre</a></li>
            </ul>
        </aside>

        <main class="dashboard-main-content">
            <?php display_flash_message(); ?>

            <?php
            $allowed_company_views = [
                'dashboard', 'gerer_profil_entreprise', 'rechercher_profils', 
                'profil_detail', 'mes_offres', 'form_offre', 'candidatures_offre'
            ];
            if (in_array($company_view, $allowed_company_views)) {
                $form_data_entreprise = $_SESSION['_form_data'] ?? []; 
                unset($_SESSION['_form_data']); 
                
                include __DIR__ . '/views/entreprise/' . $company_view . '.php';
            } else {
                set_flash_message("Vue entreprise '$company_view' non valide. Affichage du tableau de bord.", "warning");
                include __DIR__ . '/views/entreprise/dashboard.php'; 
            }
            ?>
        </main>
    </div>

    <footer style="text-align:center; padding:20px; background-color:var(--dark-gray); color:#ccc; font-size:0.9em; margin-top:auto;">
        <p>© <?php echo date("Y"); ?> <?php echo SITE_NAME; ?>. Tous droits réservés.</p>
        <p>Développé par Salimatou SABALY</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const confirmDeleteForms = document.querySelectorAll('.confirm-delete-offre'); 
            confirmDeleteForms.forEach(form => {
                form.addEventListener('submit', function(event) { 
                    if (!confirm('Êtes-vous sûr de vouloir supprimer cette offre ? Cette action est irréversible et supprimera aussi les candidatures et favoris associés.')) {
                        event.preventDefault();
                    }
                });
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
        });
    </script>
</body>
</html>