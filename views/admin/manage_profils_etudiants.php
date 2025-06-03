<?php
// views/admin/manage_profils_etudiants.php
global $pdo;

$sql_profils_admin = "SELECT p.*, u.email, u.est_actif AS utilisateur_actif
                      FROM profils_etudiants p
                      JOIN utilisateurs u ON p.id_utilisateur = u.id
                      ORDER BY p.date_derniere_maj DESC";
$stmt_profils_admin = $pdo->query($sql_profils_admin);
$profils_etudiants_admin = $stmt_profils_admin->fetchAll();
?>
<h2><i class="fas fa-user-graduate"></i> Gérer les Profils Étudiants</h2>
<p>Visualisez et gérez les profils des étudiants inscrits.</p>

<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>ID Profil</th>
                <th>Nom Complet</th>
                <th>Email (Utilisateur)</th>
                <th>Établissement</th>
                <th>Domaine</th>
                <th>Profil Visible</th>
                <th>Compte Actif</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($profils_etudiants_admin) > 0): ?>
                <?php foreach($profils_etudiants_admin as $profil_etu_admin): ?>
                <tr>
                    <td><?php echo $profil_etu_admin['id']; ?></td>
                    <td><?php echo esc_html($profil_etu_admin['nom_complet']); ?></td>
                    <td><?php echo esc_html($profil_etu_admin['email']); ?></td>
                    <td><?php echo esc_html($profil_etu_admin['etablissement']); ?></td>
                    <td><?php echo esc_html($profil_etu_admin['domaine_etudes']); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $profil_etu_admin['est_visible'] ? 'success' : 'secondary'; ?>">
                            <?php echo $profil_etu_admin['est_visible'] ? 'Oui' : 'Non'; ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-<?php echo $profil_etu_admin['utilisateur_actif'] ? 'actif' : 'inactif'; ?>">
                            <?php echo $profil_etu_admin['utilisateur_actif'] ? 'Actif' : 'Inactif'; ?>
                        </span>
                    </td>
                    <td class="actions-column">
                        <a href="<?php echo SITE_URL; ?>entreprise.php?view=profil_detail&id_etudiant=<?php echo $profil_etu_admin['id_utilisateur']; ?>&admin_view=1" target="_blank" class="btn btn-sm btn-info" title="Voir Profil (Vue Entreprise)"><i class="fas fa-eye"></i></a>
                        <a href="<?php echo SITE_URL; ?>etudiant.php?view=gerer_profil&id_user_admin_edit=<?php echo $profil_etu_admin['id_utilisateur']; ?>" target="_blank" class="btn btn-sm btn-secondary" title="Modifier Profil (Simulation connexion étudiant)"><i class="fas fa-edit"></i></a>
                        <!-- Ajouter validation admin profil si la colonne est_valide_admin est utilisée -->
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8">Aucun profil étudiant trouvé.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<p><small>Note: La modification directe des profils par l'admin est généralement déconseillée. Préférez guider l'utilisateur ou utiliser les outils de gestion de compte utilisateur.</small></p>