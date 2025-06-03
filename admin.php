<?php
// admin.php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/functions.php';

// --- Protection de la page ---
if (!isAdmin()) {
    set_flash_message("Accès non autorisé. Cette section est réservée aux administrateurs.", "error");
    if (isLoggedIn()) {
        if (isStudent()) redirect("etudiant.php");
        elseif (isCompany()) redirect("entreprise.php");
        else redirect("index.php?view=accueil");
    } else {
        redirect("index.php?view=connexion&redirect_to=" . urlencode(SITE_URL . "admin.php"));
    }
}

$user_id_admin = getUserId();

// --- Routage interne au dashboard admin ---
$admin_view = $_GET['view'] ?? 'dashboard'; // Vue par défaut
$action = $_POST['action'] ?? $_GET['action'] ?? null;
$item_id = $_GET['id'] ?? $_POST['id'] ?? null; // ID générique pour modification/suppression

// --- Traitement des actions POST spécifiques à l'admin ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Gestion des Utilisateurs ---
    if ($action === 'toggle_user_status' && $item_id) {
        try {
            $stmt_check_user = $pdo->prepare("SELECT est_actif FROM utilisateurs WHERE id = ?");
            $stmt_check_user->execute([$item_id]);
            $current_status = $stmt_check_user->fetchColumn();

            if ($current_status !== false) {
                $new_status = ($current_status == 1) ? 0 : 1;
                $stmt_update = $pdo->prepare("UPDATE utilisateurs SET est_actif = ? WHERE id = ?");
                $stmt_update->execute([$new_status, $item_id]);
                set_flash_message("Statut de l'utilisateur (ID: $item_id) mis à jour.", "success");
            } else {
                set_flash_message("Utilisateur (ID: $item_id) non trouvé.", "error");
            }
        } catch (PDOException $e) {
            set_flash_message("Erreur lors de la mise à jour du statut: " . $e->getMessage(), "error");
        }
        redirect("admin.php?view=manage_users");
    }
    elseif ($action === 'change_user_role' && $item_id && isset($_POST['new_role'])) {
        $new_role = $_POST['new_role'];
        if (in_array($new_role, ['etudiant', 'entreprise', 'admin'])) {
            try {
                if ($item_id == $user_id_admin && $new_role !== 'admin') {
                    set_flash_message("Vous ne pouvez pas modifier votre propre rôle pour qu'il ne soit plus admin de cette manière.", "error");
                } else {
                    $stmt_count_admins = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE role = 'admin'");
                    $stmt_count_admins->execute();
                    $admin_count = $stmt_count_admins->fetchColumn();

                    $stmt_current_role = $pdo->prepare("SELECT role FROM utilisateurs WHERE id = ?");
                    $stmt_current_role->execute([$item_id]);
                    $current_role_user = $stmt_current_role->fetchColumn();

                    if ($admin_count <= 1 && $current_role_user === 'admin' && $new_role !== 'admin') {
                        set_flash_message("Impossible de modifier le rôle du dernier administrateur.", "error");
                    } else {
                        $stmt_update = $pdo->prepare("UPDATE utilisateurs SET role = ? WHERE id = ?");
                        $stmt_update->execute([$new_role, $item_id]);
                        set_flash_message("Rôle de l'utilisateur (ID: $item_id) mis à jour en '$new_role'.", "success");
                    }
                }
            } catch (PDOException $e) {
                set_flash_message("Erreur lors du changement de rôle: " . $e->getMessage(), "error");
            }
        } else {
            set_flash_message("Rôle invalide spécifié.", "error");
        }
        redirect("admin.php?view=manage_users");
    }
    elseif ($action === 'delete_user' && $item_id) {
        if ($item_id == $user_id_admin) {
            set_flash_message("Vous ne pouvez pas supprimer votre propre compte administrateur.", "error");
        } else {
            $stmt_current_role = $pdo->prepare("SELECT role FROM utilisateurs WHERE id = ?");
            $stmt_current_role->execute([$item_id]);
            $role_to_delete = $stmt_current_role->fetchColumn();

            $stmt_count_admins = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE role = 'admin'");
            $stmt_count_admins->execute();
            $admin_count_before_delete = $stmt_count_admins->fetchColumn();

            if ($role_to_delete === 'admin' && $admin_count_before_delete <= 1) {
                set_flash_message("Impossible de supprimer le dernier compte administrateur.", "error");
            } else {
                try {
                    $pdo->beginTransaction();
                    $stmt_del_user = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
                    $stmt_del_user->execute([$item_id]);
                    // Les suppressions en cascade devraient s'occuper des profils, offres, etc.
                    // Si ce n'est pas le cas (FK non configurées avec ON DELETE CASCADE),
                    // il faudrait ajouter des DELETE explicites ici pour :
                    // - profils_etudiants
                    // - entreprises
                    // - offres_emploi (pour les offres de l'entreprise supprimée)
                    // - candidatures (pour les candidatures de l'étudiant supprimé ou aux offres supprimées)
                    // - offres_favorites_etudiants
                    $pdo->commit();
                    set_flash_message("Utilisateur (ID: $item_id) et données associées supprimés.", "success");
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    set_flash_message("Erreur lors de la suppression de l'utilisateur: " . $e->getMessage(), "error");
                    error_log("Erreur suppression utilisateur: " . $e->getMessage());
                }
            }
        }
        redirect("admin.php?view=manage_users");
    }
    // --- Gestion des Offres d'Emploi ---
    elseif ($action === 'validate_offre' && $item_id && isset($_POST['validation_status'])) {
        $validation_status = $_POST['validation_status'];
        if (in_array($validation_status, ['validee', 'refusee'])) {
            try {
                $stmt = $pdo->prepare("UPDATE offres_emploi SET statut_validation_admin = ? WHERE id = ?");
                $stmt->execute([$validation_status, $item_id]);
                set_flash_message("Statut de l'offre (ID: $item_id) mis à jour en '$validation_status'.", "success");
            } catch (PDOException $e) {
                set_flash_message("Erreur validation offre: " . $e->getMessage(), "error");
            }
        } else {
            set_flash_message("Statut de validation invalide.", "error");
        }
        redirect("admin.php?view=manage_offres");
    }
    // --- Gestion des Profils Entreprise (Validation) ---
    elseif ($action === 'validate_entreprise_profil' && isset($_POST['id_utilisateur_entreprise']) && isset($_POST['validation_status'])) {
        $id_utilisateur_ent_valid = $_POST['id_utilisateur_entreprise'];
        $new_validation_status = ((int)$_POST['validation_status'] == 1) ? 1 : 0; // S'assurer que c'est 0 ou 1

        try {
            $stmt = $pdo->prepare("UPDATE entreprises SET est_valide_admin = ? WHERE id_utilisateur = ?");
            $stmt->execute([$new_validation_status, $id_utilisateur_ent_valid]);
            $message_status = $new_validation_status ? 'validé' : 'dévalidé (mis en attente)';
            set_flash_message("Profil de l'entreprise (Utilisateur ID: $id_utilisateur_ent_valid) $message_status.", "success");
            // TODO: Envoyer une notification à l'entreprise si son profil est validé/refusé
        } catch (PDOException $e) {
            set_flash_message("Erreur lors de la mise à jour du statut de validation du profil entreprise: " . $e->getMessage(), "error");
            error_log("Erreur validation profil entreprise: " . $e->getMessage());
        }
        redirect("admin.php?view=manage_entreprises");
    }
     // --- Gestion des Profils Etudiants (Validation si implémentée) ---
    elseif ($action === 'validate_etudiant_profil' && isset($_POST['id_utilisateur_etudiant']) && isset($_POST['validation_status_etu'])) {
        $id_utilisateur_etu_valid = $_POST['id_utilisateur_etudiant'];
        $new_validation_status_etu = ((int)$_POST['validation_status_etu'] == 1) ? 1 : 0;

        try {
            $stmt = $pdo->prepare("UPDATE profils_etudiants SET est_valide_admin = ? WHERE id_utilisateur = ?");
            $stmt->execute([$new_validation_status_etu, $id_utilisateur_etu_valid]);
            $message_status_etu = $new_validation_status_etu ? 'validé' : 'dévalidé (mis en attente)';
            set_flash_message("Profil de l'étudiant (Utilisateur ID: $id_utilisateur_etu_valid) $message_status_etu.", "success");
        } catch (PDOException $e) {
            set_flash_message("Erreur validation profil étudiant: " . $e->getMessage(), "error");
            error_log("Erreur validation profil étudiant: " . $e->getMessage());
        }
        redirect("admin.php?view=manage_profils_etudiants");
    }
    // --- Gestion du Contenu du Site ---
    elseif ($action === 'update_site_content' && isset($_POST['contenu'])) {
        $contenus_a_updater = $_POST['contenu'];
        try {
            $pdo->beginTransaction();
            foreach ($contenus_a_updater as $cle => $valeurs) {
                $texte_val = $valeurs['texte'] ?? null;
                $lien_val = $valeurs['lien'] ?? null;
                if (preg_match('/^[a-zA-Z0-9_.-]+$/', $cle)) {
                     $stmt = $pdo->prepare("UPDATE contenu_site SET valeur_contenu_texte = ?, valeur_contenu_lien = ? WHERE cle_contenu = ?");
                     $stmt->execute([$texte_val, $lien_val, $cle]);
                } else {
                    error_log("Clé de contenu invalide tentée lors de la MAJ: " . $cle);
                }
            }
            $pdo->commit();
            set_flash_message("Contenu du site mis à jour avec succès.", "success");
        } catch (PDOException $e) {
            $pdo->rollBack();
            set_flash_message("Erreur lors de la mise à jour du contenu: " . $e->getMessage(), "error");
            error_log("Erreur MAJ contenu site: " . $e->getMessage());
        }
        redirect("admin.php?view=manage_site_content");
    }
    // --- Gestion des Partenaires ---
    elseif ($action === 'add_partner') {
        $nom_partenaire = trim($_POST['nom_partenaire']);
        $lien_logo_partenaire = filter_var(trim($_POST['lien_logo_partenaire']), FILTER_VALIDATE_URL);
        $lien_site_partenaire = filter_var(trim($_POST['lien_site_partenaire']), FILTER_VALIDATE_URL) ? trim($_POST['lien_site_partenaire']) : null;
        $ordre_affichage = (int)($_POST['ordre_affichage'] ?? 0);

        if (!empty($nom_partenaire) && $lien_logo_partenaire) {
            try {
                $stmt = $pdo->prepare("INSERT INTO partenaires (nom_partenaire, lien_logo_partenaire, lien_site_partenaire, ordre_affichage) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nom_partenaire, $lien_logo_partenaire, $lien_site_partenaire, $ordre_affichage]);
                set_flash_message("Partenaire ajouté avec succès.", "success");
            } catch (PDOException $e) {
                set_flash_message("Erreur ajout partenaire: " . $e->getMessage(), "error");
            }
        } else {
            set_flash_message("Nom du partenaire et lien du logo (URL valide) sont requis.", "error");
        }
        redirect("admin.php?view=manage_partners");
    }
    elseif ($action === 'update_partner' && $item_id) {
        $nom_partenaire = trim($_POST['nom_partenaire']);
        $lien_logo_partenaire = filter_var(trim($_POST['lien_logo_partenaire']), FILTER_VALIDATE_URL);
        $lien_site_partenaire = filter_var(trim($_POST['lien_site_partenaire']), FILTER_VALIDATE_URL) ? trim($_POST['lien_site_partenaire']) : null;
        $ordre_affichage = (int)($_POST['ordre_affichage'] ?? 0);
        $est_actif_part = isset($_POST['est_actif_part']) ? 1 : 0;

         if (!empty($nom_partenaire) && $lien_logo_partenaire) {
            try {
                $stmt = $pdo->prepare("UPDATE partenaires SET nom_partenaire = ?, lien_logo_partenaire = ?, lien_site_partenaire = ?, ordre_affichage = ?, est_actif = ? WHERE id = ?");
                $stmt->execute([$nom_partenaire, $lien_logo_partenaire, $lien_site_partenaire, $ordre_affichage, $est_actif_part, $item_id]);
                set_flash_message("Partenaire (ID: $item_id) mis à jour.", "success");
            } catch (PDOException $e) {
                set_flash_message("Erreur MAJ partenaire: " . $e->getMessage(), "error");
            }
        } else {
            set_flash_message("Nom et lien logo (URL valide) requis.", "error");
        }
        redirect("admin.php?view=manage_partners");
    }
     elseif ($action === 'delete_partner' && $item_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM partenaires WHERE id = ?");
            $stmt->execute([$item_id]);
            set_flash_message("Partenaire (ID: $item_id) supprimé.", "success");
        } catch (PDOException $e) {
            set_flash_message("Erreur suppression partenaire: " . $e->getMessage(), "error");
        }
        redirect("admin.php?view=manage_partners");
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .container-dashboard { width: 95%; max-width: 1600px; margin: 20px auto; padding: 0; display: flex; gap: 20px; flex-grow: 1; }
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
            flex: 0 0 280px; background-color: #fff; padding: 20px;
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

        .stat-card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card {
            background-color: #fff; color: var(--text-color);
            padding: 20px; border-radius: var(--border-radius);
            box-shadow: var(--box-shadow); display: flex;
            align-items: center; justify-content: space-between;
            border-left: 5px solid var(--primary-color); 
        }
        .stat-card .stat-icon { font-size: 2.5em; color: var(--primary-color); margin-right: 15px; opacity: 0.8; }
        .stat-card .stat-info h4 { font-size: 1.8em; margin: 0 0 5px 0; font-weight: 600; color: var(--dark-gray); }
        .stat-card .stat-info p { margin: 0; font-size: 0.95em; color: var(--secondary-color); }
        .stat-card.info { border-left-color: #17a2b8; } .stat-card.info .stat-icon { color: #17a2b8; }
        .stat-card.success { border-left-color: var(--accent-color); } .stat-card.success .stat-icon { color: var(--accent-color); }
        .stat-card.warning { border-left-color: #ffc107; } .stat-card.warning .stat-icon { color: #ffc107; }
        .stat-card.danger { border-left-color: #dc3545; } .stat-card.danger .stat-icon { color: #dc3545; }

        .form-container { box-shadow: none; padding: 0; max-width: none; margin:0; }
        .form-container h2 { font-size: 1.6em; margin-bottom: 20px; text-align:left; border-bottom:0;}
        .form-group { margin-bottom: 20px; }
        .form-group label { font-weight: 600; }
        .form-group input[type="text"], .form-group input[type="email"], .form-group input[type="password"],
        .form-group input[type="url"], .form-group input[type="tel"], .form-group input[type="number"], .form-group input[type="color"],
        .form-group select, .form-group textarea { width:100%; padding: 10px; font-size: 0.95em; border:1px solid #ccc; border-radius:var(--border-radius); }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--primary-color); box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); outline:none; }
        .form-container .btn { padding: 10px 20px; font-size: 1em; width: auto; margin-top:10px;}
        .form-check input[type="checkbox"] { margin-right: 10px; width: auto; }
        .form-check label { margin-bottom: 0; font-weight: normal; }

        .table-responsive { overflow-x: auto; }
        .table { width: 100%; margin-bottom: 1rem; color: var(--text-color); border-collapse: collapse; }
        .table th, .table td { padding: .75rem; vertical-align: middle; border-top: 1px solid #dee2e6; }
        .table thead th { vertical-align: bottom; border-bottom: 2px solid #dee2e6; background-color: var(--light-gray); font-weight:600; }
        .table tbody tr:nth-of-type(odd) { background-color: rgba(0,0,0,.03); }
        .table .actions-column .btn, .table .actions-column form { margin-right: 5px; display:inline-block; margin-bottom: 5px;}
        .table .actions-column .btn-sm { padding: .25rem .5rem; font-size: .8em; }
        .table .actions-column select { padding: .25rem .5rem; font-size: .8em; border-radius: var(--border-radius); border:1px solid #ccc;}

        .badge { display: inline-block; padding: .35em .65em; font-size: .75em; font-weight: 700; line-height: 1; color: #fff; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: .25rem; }
        .badge-success { background-color: var(--accent-color); }
        .badge-info { background-color: #17a2b8; }
        .badge-warning { background-color: #ffc107; color: #212529;}
        .badge-danger { background-color: #dc3545; }
        .badge-secondary { background-color: var(--secondary-color); }
        .badge-primary { background-color: var(--primary-color); }
        .badge-etudiant { background-color: var(--primary-color); }
        .badge-entreprise { background-color: var(--accent-color); }
        .badge-admin { background-color: var(--dark-gray); }
        .badge-actif { background-color: var(--accent-color); }
        .badge-inactif { background-color: var(--secondary-color); }
        .badge-en-attente { background-color: #ffc107; color: #212529;}
        .badge-validee { background-color: var(--accent-color);}
        .badge-refusee { background-color: #dc3545;}

        .flash-message { padding: 15px 20px; margin: 0 0 20px 0; border-radius: var(--border-radius); text-align: left; font-weight: 500; border: 1px solid transparent; }
        .flash-message.success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .flash-message.error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .flash-message.info { background-color: #d1ecf1; color: #0c5460; border-color: #bee5eb; }
        
        .content-item { margin-bottom:25px; padding-bottom:15px; border-bottom:1px dashed #eee;}
        .content-item label { font-weight:bold; display:block; margin-bottom:5px;}
        .content-item small { color:var(--secondary-color); display:block; margin-bottom:8px;}

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
                 <a href="<?php echo SITE_URL; ?>index.php?view=accueil">
                    <img src="<?php echo get_site_content('logo_site_url', $pdo); ?>" alt="<?php echo SITE_NAME; ?> Logo">
                </a>
            </div>
            <nav>
                <ul>
                    <li><a href="<?php echo SITE_URL; ?>index.php?view=accueil" title="Aller au site public">Site Public</a></li>
                    <li class="nav-dropdown">
                        <a href="javascript:void(0)" class="active">
                            <i class="fas fa-user-shield"></i> <?php echo esc_html($_SESSION['user_nom_utilisateur'] ?? 'Admin'); ?> <i class="fas fa-caret-down fa-xs"></i>
                        </a>
                        <div class="nav-dropdown-content">
                            <a href="<?php echo SITE_URL; ?>admin.php?view=dashboard"><i class="fas fa-tachometer-alt"></i> Tableau de Bord</a>
                            <a href="<?php echo SITE_URL; ?>index.php?action=deconnexion"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container-dashboard">
        <aside class="dashboard-sidebar">
            <h3><i class="fas fa-cogs"></i> Administration</h3>
            <ul>
                <li><a href="<?php echo SITE_URL; ?>admin.php?view=dashboard" class="<?php echo ($admin_view === 'dashboard') ? 'active' : ''; ?>"><i class="fas fa-chart-pie"></i> Tableau de Bord</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin.php?view=manage_users" class="<?php echo ($admin_view === 'manage_users') ? 'active' : ''; ?>"><i class="fas fa-users-cog"></i> Gérer Utilisateurs</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin.php?view=manage_offres" class="<?php echo ($admin_view === 'manage_offres' || $admin_view === 'view_offre_admin') ? 'active' : ''; ?>"><i class="fas fa-briefcase-medical"></i> Valider Offres</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin.php?view=manage_profils_etudiants" class="<?php echo ($admin_view === 'manage_profils_etudiants') ? 'active' : ''; ?>"><i class="fas fa-user-graduate"></i> Gérer Profils Étudiants</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin.php?view=manage_entreprises" class="<?php echo ($admin_view === 'manage_entreprises' || $admin_view === 'view_entreprise_profil') ? 'active' : ''; ?>"><i class="fas fa-building-shield"></i> Gérer Profils Entreprises</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin.php?view=manage_site_content" class="<?php echo ($admin_view === 'manage_site_content') ? 'active' : ''; ?>"><i class="fas fa-edit"></i> Gérer Contenu Site</a></li>
                 <li><a href="<?php echo SITE_URL; ?>admin.php?view=manage_partners" class="<?php echo ($admin_view === 'manage_partners' || $admin_view === 'form_partner') ? 'active' : ''; ?>"><i class="fas fa-handshake"></i> Gérer Partenaires</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin.php?view=newsletter_subscribers" class="<?php echo ($admin_view === 'newsletter_subscribers') ? 'active' : ''; ?>"><i class="fas fa-envelope-open-text"></i> Inscrits Newsletter</a></li>
            </ul>
        </aside>

        <main class="dashboard-main-content">
            <?php display_flash_message(); ?>

            <?php
            // --- Chargement de la sous-vue du dashboard admin ---
            $allowed_admin_views = [
                'dashboard', 'manage_users', 'manage_offres', 
                'manage_profils_etudiants', 'manage_entreprises',
                'manage_site_content', 'manage_partners', 'form_partner',
                'newsletter_subscribers', 'view_offre_admin', 
                'view_entreprise_profil' 
            ];
            if (in_array($admin_view, $allowed_admin_views)) {
                include __DIR__ . '/views/admin/' . $admin_view . '.php';
            } else {
                set_flash_message("Vue d'administration '$admin_view' non valide. Affichage du tableau de bord par défaut.", "warning");
                include __DIR__ . '/views/admin/dashboard.php'; 
            }
            ?>
        </main>
    </div>

    <footer style="text-align:center; padding:20px; background-color:var(--dark-gray); color:#ccc; font-size:0.9em; margin-top:auto;">
        <p><?php echo str_replace('%YEAR%', date('Y'), get_site_content('footer_texte', $pdo)); ?></p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const confirmDeleteUserForms = document.querySelectorAll('.confirm-delete-user');
            confirmDeleteUserForms.forEach(form => {
                form.addEventListener('submit', function(event) {
                    if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Ses profils et données associées (offres, candidatures si entreprise) seront aussi supprimés. Cette action est irréversible.')) {
                        event.preventDefault();
                    }
                });
            });
            const confirmDeletePartnerForms = document.querySelectorAll('.confirm-delete-partner');
            confirmDeletePartnerForms.forEach(form => {
                form.addEventListener('submit', function(event) {
                    if (!confirm('Êtes-vous sûr de vouloir supprimer ce partenaire ?')) {
                        event.preventDefault();
                    }
                });
            });
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