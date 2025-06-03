<?php
// views/admin/manage_offres.php
global $pdo;

// Filtres
$filter_statut_validation = $_GET['statut_validation'] ?? '';
$search_offre = trim($_GET['q_offre'] ?? '');
$filter_entreprise_offre = trim($_GET['entreprise_offre'] ?? '');


$sql_offres_admin = "SELECT o.*, e.nom_entreprise 
                     FROM offres_emploi o
                     JOIN entreprises e ON o.id_entreprise_utilisateur = e.id_utilisateur
                     WHERE 1=1"; // Toujours vrai pour commencer
$params_offres_admin = [];

if (!empty($filter_statut_validation)) {
    $sql_offres_admin .= " AND o.statut_validation_admin = :statut_validation";
    $params_offres_admin[':statut_validation'] = $filter_statut_validation;
}
if (!empty($search_offre)) {
    $sql_offres_admin .= " AND (o.titre_poste LIKE :search_offre OR o.description_poste LIKE :search_offre)";
    $params_offres_admin[':search_offre'] = '%' . $search_offre . '%';
}
if (!empty($filter_entreprise_offre)) {
    $sql_offres_admin .= " AND e.nom_entreprise LIKE :nom_entreprise_offre";
    $params_offres_admin[':nom_entreprise_offre'] = '%' . $filter_entreprise_offre . '%';
}

$sql_offres_admin .= " ORDER BY o.date_publication DESC";

$stmt_offres_list_admin = $pdo->prepare($sql_offres_admin);
$stmt_offres_list_admin->execute($params_offres_admin);
$offres_list_admin = $stmt_offres_list_admin->fetchAll();

$statut_validation_admin_labels = [
    'en_attente' => ['label' => 'En attente', 'class' => 'badge-en-attente'],
    'validee' => ['label' => 'Validée', 'class' => 'badge-validee'],
    'refusee' => ['label' => 'Refusée', 'class' => 'badge-refusee'],
];
?>
<h2><i class="fas fa-briefcase-medical"></i> Gérer et Valider les Offres d'Emploi</h2>

<form action="<?php echo SITE_URL; ?>admin.php" method="GET" class="filters-bar">
    <input type="hidden" name="view" value="manage_offres">
    <div class="filter-group">
        <label for="q_offre_admin">Recherche (Titre, Desc.)</label>
        <input type="text" id="q_offre_admin" name="q_offre" value="<?php echo esc_html($search_offre); ?>">
    </div>
    <div class="filter-group">
        <label for="entreprise_offre_filter">Entreprise</label>
        <input type="text" id="entreprise_offre_filter" name="entreprise_offre" value="<?php echo esc_html($filter_entreprise_offre); ?>" placeholder="Nom entreprise">
    </div>
    <div class="filter-group">
        <label for="statut_validation_filter">Statut Validation</label>
        <select id="statut_validation_filter" name="statut_validation">
            <option value="">Tous</option>
            <option value="en_attente" <?php echo ($filter_statut_validation === 'en_attente') ? 'selected' : ''; ?>>En attente</option>
            <option value="validee" <?php echo ($filter_statut_validation === 'validee') ? 'selected' : ''; ?>>Validée</option>
            <option value="refusee" <?php echo ($filter_statut_validation === 'refusee') ? 'selected' : ''; ?>>Refusée</option>
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
                <th>ID</th>
                <th>Titre du Poste</th>
                <th>Entreprise</th>
                <th>Date Publ.</th>
                <th>Statut Actuel</th>
                <th>Statut Validation</th>
                <th style="width:20%;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($offres_list_admin) > 0): ?>
                <?php foreach ($offres_list_admin as $offre_item): ?>
                <tr>
                    <td><?php echo $offre_item['id']; ?></td>
                    <td>
                        <a href="<?php echo SITE_URL; ?>admin.php?view=view_offre_admin&id=<?php echo $offre_item['id']; ?>" title="Voir détails de l'offre">
                            <?php echo esc_html($offre_item['titre_poste']); ?>
                        </a>
                    </td>
                    <td><?php echo esc_html($offre_item['nom_entreprise']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($offre_item['date_publication'])); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $offre_item['est_active'] ? 'actif' : 'inactif'; ?>">
                            <?php echo $offre_item['est_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td>
                         <span class="badge <?php echo $statut_validation_admin_labels[$offre_item['statut_validation_admin']]['class'] ?? 'badge-secondary'; ?>">
                            <?php echo $statut_validation_admin_labels[$offre_item['statut_validation_admin']]['label'] ?? ucfirst($offre_item['statut_validation_admin']); ?>
                        </span>
                    </td>
                    <td class="actions-column">
                        <?php if ($offre_item['statut_validation_admin'] === 'en_attente'): ?>
                            <form action="<?php echo SITE_URL; ?>admin.php" method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="validate_offre">
                                <input type="hidden" name="id" value="<?php echo $offre_item['id']; ?>">
                                <input type="hidden" name="validation_status" value="validee">
                                <button type="submit" class="btn btn-sm btn-success" title="Valider cette offre"><i class="fas fa-check"></i> Valider</button>
                            </form>
                            <form action="<?php echo SITE_URL; ?>admin.php" method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="validate_offre">
                                <input type="hidden" name="id" value="<?php echo $offre_item['id']; ?>">
                                <input type="hidden" name="validation_status" value="refusee">
                                <button type="submit" class="btn btn-sm btn-danger" title="Refuser cette offre"><i class="fas fa-times"></i> Refuser</button>
                            </form>
                        <?php elseif ($offre_item['statut_validation_admin'] === 'validee'): ?>
                             <form action="<?php echo SITE_URL; ?>admin.php" method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="validate_offre">
                                <input type="hidden" name="id" value="<?php echo $offre_item['id']; ?>">
                                <input type="hidden" name="validation_status" value="refusee"> <!-- Ou en_attente pour dépublier -->
                                <button type="submit" class="btn btn-sm btn-warning" title="Dépublier/Refuser"><i class="fas fa-undo"></i> Réfuser</button>
                            </form>
                        <?php elseif ($offre_item['statut_validation_admin'] === 'refusee'): ?>
                             <form action="<?php echo SITE_URL; ?>admin.php" method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="validate_offre">
                                <input type="hidden" name="id" value="<?php echo $offre_item['id']; ?>">
                                <input type="hidden" name="validation_status" value="validee">
                                <button type="submit" class="btn btn-sm btn-success" title="Re-valider cette offre"><i class="fas fa-redo"></i> Valider</button>
                            </form>
                        <?php endif; ?>
                         <!-- L'admin peut aussi modifier/supprimer directement -->
                        <a href="<?php echo SITE_URL; ?>entreprise.php?view=form_offre&mode=modifier&id_offre=<?php echo $offre_item['id']; ?>&admin_edit=1" target="_blank" class="btn btn-sm btn-secondary" title="Modifier (en tant qu'admin)"><i class="fas fa-edit"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7">Aucune offre trouvée avec ces critères.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>