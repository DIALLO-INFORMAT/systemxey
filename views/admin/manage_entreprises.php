<?php
// views/admin/manage_entreprises.php
global $pdo;

// Filtres
$filter_validation_status_ent = $_GET['validation_status_ent'] ?? '';
$search_entreprise_admin = trim($_GET['q_entreprise_admin'] ?? '');

$sql_entreprises_admin = "SELECT e.*, u.email, u.est_actif AS utilisateur_actif
                          FROM entreprises e
                          JOIN utilisateurs u ON e.id_utilisateur = u.id
                          WHERE 1=1";
$params_entreprises_admin = [];

if ($filter_validation_status_ent === 'valide') {
    $sql_entreprises_admin .= " AND e.est_valide_admin = 1";
} elseif ($filter_validation_status_ent === 'non_valide') {
    $sql_entreprises_admin .= " AND e.est_valide_admin = 0";
}
if (!empty($search_entreprise_admin)) {
    $sql_entreprises_admin .= " AND (e.nom_entreprise LIKE :search_ent OR u.email LIKE :search_ent OR e.secteur_activite LIKE :search_ent)";
    $params_entreprises_admin[':search_ent'] = '%' . $search_entreprise_admin . '%';
}

$sql_entreprises_admin .= " ORDER BY e.nom_entreprise ASC";

$stmt_entreprises_admin = $pdo->prepare($sql_entreprises_admin);
$stmt_entreprises_admin->execute($params_entreprises_admin);
$profils_entreprises_admin = $stmt_entreprises_admin->fetchAll();
?>
<h2><i class="fas fa-building-shield"></i> Gérer les Profils Entreprises</h2>
<p>Visualisez, validez et gérez les profils des entreprises inscrites.</p>

<form action="<?php echo SITE_URL; ?>admin.php" method="GET" class="filters-bar">
    <input type="hidden" name="view" value="manage_entreprises">
    <div class="filter-group">
        <label for="q_entreprise_admin_filter">Recherche (Nom, Email, Secteur)</label>
        <input type="text" id="q_entreprise_admin_filter" name="q_entreprise_admin" value="<?php echo esc_html($search_entreprise_admin); ?>">
    </div>
    <div class="filter-group">
        <label for="validation_status_ent_filter">Statut Validation Profil</label>
        <select id="validation_status_ent_filter" name="validation_status_ent">
            <option value="">Tous</option>
            <option value="valide" <?php echo ($filter_validation_status_ent === 'valide') ? 'selected' : ''; ?>>Validé</option>
            <option value="non_valide" <?php echo ($filter_validation_status_ent === 'non_valide') ? 'selected' : ''; ?>>Non Validé / En attente</option>
        </select>
    </div>
    <div class="filter-group">
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrer</button>
    </div>
</form>

<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>ID Utilisateur</th>
                <th>Nom Entreprise</th>
                <th>Email (Compte)</th>
                <th>Secteur</th>
                <th>Profil Validé</th>
                <th>Compte Actif</th>
                <th style="width:28%;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($profils_entreprises_admin) > 0): ?>
                <?php foreach($profils_entreprises_admin as $profil_ent_admin): ?>
                <tr>
                    <td><?php echo $profil_ent_admin['id_utilisateur']; ?></td>
                    <td><?php echo esc_html($profil_ent_admin['nom_entreprise']); ?></td>
                    <td><?php echo esc_html($profil_ent_admin['email']); ?></td>
                    <td><?php echo esc_html($profil_ent_admin['secteur_activite']); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $profil_ent_admin['est_valide_admin'] ? 'success' : 'warning'; ?>">
                            <?php echo $profil_ent_admin['est_valide_admin'] ? 'Oui' : 'En attente'; ?>
                        </span>
                    </td>
                     <td>
                        <span class="badge badge-<?php echo $profil_ent_admin['utilisateur_actif'] ? 'actif' : 'inactif'; ?>">
                            <?php echo $profil_ent_admin['utilisateur_actif'] ? 'Actif' : 'Inactif'; ?>
                        </span>
                    </td>
                    <td class="actions-column">
                        <a href="<?php echo SITE_URL; ?>admin.php?view=view_entreprise_profil&id=<?php echo $profil_ent_admin['id_utilisateur']; ?>" class="btn btn-sm btn-primary" title="Voir Profil Entreprise (Admin)"><i class="fas fa-eye"></i> Profil</a>
                        
                        <?php if (!$profil_ent_admin['est_valide_admin']): // Si pas encore validé ?>
                        <form action="<?php echo SITE_URL; ?>admin.php?view=manage_entreprises" method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="validate_entreprise_profil">
                            <input type="hidden" name="id_utilisateur_entreprise" value="<?php echo $profil_ent_admin['id_utilisateur']; ?>">
                            <input type="hidden" name="validation_status" value="1"> <!-- 1 pour valider -->
                            <button type="submit" class="btn btn-sm btn-success" title="Valider ce profil entreprise"><i class="fas fa-check-circle"></i> Valider</button>
                        </form>
                        <?php else: ?>
                        <form action="<?php echo SITE_URL; ?>admin.php?view=manage_entreprises" method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="validate_entreprise_profil">
                            <input type="hidden" name="id_utilisateur_entreprise" value="<?php echo $profil_ent_admin['id_utilisateur']; ?>">
                            <input type="hidden" name="validation_status" value="0"> <!-- 0 pour dévalider/mettre en attente -->
                            <button type="submit" class="btn btn-sm btn-warning" title="Dévalider ce profil entreprise"><i class="fas fa-times-circle"></i> Dévalider</button>
                        </form>
                        <?php endif; ?>

                        <a href="<?php echo SITE_URL; ?>entreprise.php?view=gerer_profil_entreprise&id_user_admin_edit=<?php echo $profil_ent_admin['id_utilisateur']; ?>" target="_blank" class="btn btn-sm btn-secondary" title="Modifier Profil (Via vue entreprise)"><i class="fas fa-edit"></i> Modifier</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7">Aucun profil entreprise trouvé avec ces critères.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<p><small>Note : "Modifier" ouvre la vue de gestion de profil de l'entreprise (comme si l'entreprise était connectée) dans un nouvel onglet, permettant à l'admin d'effectuer des changements si nécessaire.</small></p>
