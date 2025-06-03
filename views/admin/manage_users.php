<?php
// views/admin/manage_users.php
global $pdo;

// Filtres
$filter_role = $_GET['role'] ?? '';
$filter_status = $_GET['status'] ?? ''; // 'actif', 'inactif'
$search_user = trim($_GET['q_user'] ?? '');

$sql_users = "SELECT id, email, nom_utilisateur, role, date_inscription, est_actif FROM utilisateurs WHERE 1=1";
$params_users = [];

if (!empty($filter_role)) {
    $sql_users .= " AND role = :role";
    $params_users[':role'] = $filter_role;
}
if ($filter_status === 'actif') {
    $sql_users .= " AND est_actif = 1";
} elseif ($filter_status === 'inactif') {
    $sql_users .= " AND est_actif = 0";
}
if (!empty($search_user)) {
    $sql_users .= " AND (email LIKE :search_user OR nom_utilisateur LIKE :search_user)";
    $params_users[':search_user'] = '%' . $search_user . '%';
}
$sql_users .= " ORDER BY date_inscription DESC";

$stmt_users_list = $pdo->prepare($sql_users);
$stmt_users_list->execute($params_users);
$users_list = $stmt_users_list->fetchAll();
?>
<h2><i class="fas fa-users-cog"></i> Gérer les Utilisateurs</h2>

<form action="<?php echo SITE_URL; ?>admin.php" method="GET" class="filters-bar">
    <input type="hidden" name="view" value="manage_users">
    <div class="filter-group">
        <label for="q_user_filter">Recherche (Email, Nom)</label>
        <input type="text" id="q_user_filter" name="q_user" value="<?php echo esc_html($search_user); ?>">
    </div>
    <div class="filter-group">
        <label for="role_filter">Rôle</label>
        <select id="role_filter" name="role">
            <option value="">Tous</option>
            <option value="etudiant" <?php echo ($filter_role === 'etudiant') ? 'selected' : ''; ?>>Étudiant</option>
            <option value="entreprise" <?php echo ($filter_role === 'entreprise') ? 'selected' : ''; ?>>Entreprise</option>
            <option value="admin" <?php echo ($filter_role === 'admin') ? 'selected' : ''; ?>>Admin</option>
        </select>
    </div>
    <div class="filter-group">
        <label for="status_filter">Statut</label>
        <select id="status_filter" name="status">
            <option value="">Tous</option>
            <option value="actif" <?php echo ($filter_status === 'actif') ? 'selected' : ''; ?>>Actif</option>
            <option value="inactif" <?php echo ($filter_status === 'inactif') ? 'selected' : ''; ?>>Inactif</option>
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
                <th>Email</th>
                <th>Nom d'utilisateur</th>
                <th>Rôle</th>
                <th>Inscrit le</th>
                <th>Statut</th>
                <th style="width:25%;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($users_list) > 0): ?>
                <?php foreach ($users_list as $user_item): ?>
                <tr>
                    <td><?php echo $user_item['id']; ?></td>
                    <td><?php echo esc_html($user_item['email']); ?></td>
                    <td><?php echo esc_html($user_item['nom_utilisateur'] ?? 'N/A'); ?></td>
                    <td>
                        <span class="badge badge-<?php echo esc_html($user_item['role']); ?>">
                            <?php echo ucfirst(esc_html($user_item['role'])); ?>
                        </span>
                    </td>
                    <td><?php echo date('d/m/Y H:i', strtotime($user_item['date_inscription'])); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $user_item['est_actif'] ? 'actif' : 'inactif'; ?>">
                            <?php echo $user_item['est_actif'] ? 'Actif' : 'Inactif'; ?>
                        </span>
                    </td>
                    <td class="actions-column">
                        <form action="<?php echo SITE_URL; ?>admin.php" method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="toggle_user_status">
                            <input type="hidden" name="id" value="<?php echo $user_item['id']; ?>">
                            <button type="submit" class="btn btn-sm <?php echo $user_item['est_actif'] ? 'btn-warning' : 'btn-success'; ?>" title="<?php echo $user_item['est_actif'] ? 'Désactiver' : 'Activer'; ?>">
                                <i class="fas <?php echo $user_item['est_actif'] ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                            </button>
                        </form>
                        <form action="<?php echo SITE_URL; ?>admin.php" method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="change_user_role">
                            <input type="hidden" name="id" value="<?php echo $user_item['id']; ?>">
                            <select name="new_role" onchange="if(confirm('Changer le rôle de cet utilisateur ?')) { this.form.submit(); }" title="Changer le rôle">
                                <option value="etudiant" <?php if($user_item['role'] == 'etudiant') echo 'selected'; ?>>Étudiant</option>
                                <option value="entreprise" <?php if($user_item['role'] == 'entreprise') echo 'selected'; ?>>Entreprise</option>
                                <option value="admin" <?php if($user_item['role'] == 'admin') echo 'selected'; ?>>Admin</option>
                            </select>
                        </form>
                        <?php if ($user_item['id'] != $user_id_admin): // Empêcher l'admin de se supprimer ?>
                        <form action="<?php echo SITE_URL; ?>admin.php" method="POST" class="confirm-delete-user" style="display:inline;">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="id" value="<?php echo $user_item['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger" title="Supprimer"><i class="fas fa-trash-alt"></i></button>
                        </form>
                        <?php endif; ?>
                         <!-- Ajouter lien pour voir profil étudiant/entreprise -->
                        <?php if ($user_item['role'] === 'etudiant'): ?>
                            <a href="<?php echo SITE_URL; ?>entreprise.php?view=profil_detail&id_etudiant=<?php echo $user_item['id']; ?>" target="_blank" class="btn btn-sm btn-info" title="Voir profil étudiant"><i class="fas fa-eye"></i> Ét.</a>
                        <?php elseif ($user_item['role'] === 'entreprise'): ?>
                             <!-- Créer une vue admin pour profil entreprise ou utiliser celle publique -->
                            <a href="#" class="btn btn-sm btn-info" title="Voir profil entreprise (à implémenter)"><i class="fas fa-eye"></i> Ent.</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7">Aucun utilisateur trouvé avec ces critères.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>