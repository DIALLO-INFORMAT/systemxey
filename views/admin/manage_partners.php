<?php
// views/admin/manage_partners.php
global $pdo;

$stmt_partners = $pdo->query("SELECT * FROM partenaires ORDER BY ordre_affichage ASC, nom_partenaire ASC");
$partenaires = $stmt_partners->fetchAll();
?>
<h2><i class="fas fa-handshake"></i> Gérer les Partenaires</h2>

<a href="<?php echo SITE_URL; ?>admin.php?view=form_partner&mode=creer" class="btn btn-success" style="margin-bottom:20px;"><i class="fas fa-plus"></i> Ajouter un Partenaire</a>

<?php if(count($partenaires) > 0): ?>
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Ordre</th>
                <th>Logo</th>
                <th>Nom du Partenaire</th>
                <th>Site Web</th>
                <th>Statut</th>
                <th style="width:15%;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($partenaires as $part): ?>
            <tr>
                <td><?php echo esc_html($part['ordre_affichage']); ?></td>
                <td><img src="<?php echo esc_html($part['lien_logo_partenaire']); ?>" alt="Logo <?php echo esc_html($part['nom_partenaire']); ?>" style="max-height:40px; max-width:100px; background:#f0f0f0; padding:3px; border-radius:var(--border-radius);"></td>
                <td><?php echo esc_html($part['nom_partenaire']); ?></td>
                <td>
                    <?php if(!empty($part['lien_site_partenaire'])): ?>
                        <a href="<?php echo esc_html($part['lien_site_partenaire']); ?>" target="_blank"><?php echo esc_html($part['lien_site_partenaire']); ?></a>
                    <?php else: echo 'N/A'; endif; ?>
                </td>
                <td>
                    <span class="badge badge-<?php echo $part['est_actif'] ? 'actif' : 'inactif'; ?>">
                        <?php echo $part['est_actif'] ? 'Actif' : 'Inactif'; ?>
                    </span>
                </td>
                <td class="actions-column">
                    <a href="<?php echo SITE_URL; ?>admin.php?view=form_partner&mode=modifier&id=<?php echo $part['id']; ?>" class="btn btn-sm btn-secondary" title="Modifier"><i class="fas fa-edit"></i></a>
                    <form action="<?php echo SITE_URL; ?>admin.php?view=manage_partners" method="POST" class="confirm-delete-partner" style="display:inline;">
                        <input type="hidden" name="action" value="delete_partner">
                        <input type="hidden" name="id" value="<?php echo $part['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger" title="Supprimer"><i class="fas fa-trash-alt"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<p>Aucun partenaire n'a été ajouté pour le moment.</p>
<?php endif; ?>