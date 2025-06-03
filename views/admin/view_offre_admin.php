<?php
// views/admin/view_offre_admin.php
global $pdo, $item_id; // $item_id est l'ID de l'offre

if (!$item_id) {
    set_flash_message("ID de l'offre manquant.", "error");
    redirect("admin.php?view=manage_offres");
}

$sql = "SELECT o.*, e.nom_entreprise, e.description_entreprise AS desc_entreprise_admin, e.lien_logo AS logo_entreprise_admin, e.site_web_url AS site_web_entreprise_admin
        FROM offres_emploi o
        JOIN entreprises e ON o.id_entreprise_utilisateur = e.id_utilisateur
        WHERE o.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$item_id]);
$offre_detail_admin = $stmt->fetch();

if (!$offre_detail_admin) {
    set_flash_message("Offre non trouvée.", "error");
    redirect("admin.php?view=manage_offres");
}
$statut_validation_admin_labels = [
    'en_attente' => ['label' => 'En attente', 'class' => 'badge-en-attente'],
    'validee' => ['label' => 'Validée', 'class' => 'badge-validee'],
    'refusee' => ['label' => 'Refusée', 'class' => 'badge-refusee'],
];
?>
<h2><i class="fas fa-clipboard-check"></i> Détails de l'Offre #<?php echo $offre_detail_admin['id']; ?> (Validation Admin)</h2>

<div style="display:flex; flex-wrap: wrap; gap: 30px;">
    <div style="flex: 2; min-width: 300px;">
        <h3 style="color:var(--primary-color); margin-bottom:5px;"><?php echo esc_html($offre_detail_admin['titre_poste']); ?></h3>
        <p style="font-size: 1.1em; color: var(--secondary-color); margin-bottom:15px;">
            Par : <?php echo esc_html($offre_detail_admin['nom_entreprise']); ?>
            - Lieu : <?php echo esc_html($offre_detail_admin['lieu']); ?>
        </p>
        <p><strong>Statut de Validation Actuel:</strong>
            <span class="badge <?php echo $statut_validation_admin_labels[$offre_detail_admin['statut_validation_admin']]['class'] ?? 'badge-secondary'; ?>">
                <?php echo $statut_validation_admin_labels[$offre_detail_admin['statut_validation_admin']]['label'] ?? ucfirst($offre_detail_admin['statut_validation_admin']); ?>
            </span>
        </p>
        <p><strong>Statut de l'Offre (par entreprise):</strong> <?php echo $offre_detail_admin['est_active'] ? 'Active' : 'Inactive'; ?></p>
        <hr>
        <p><strong>Type de Contrat:</strong> <?php echo esc_html($offre_detail_admin['type_contrat']); ?></p>
        <p><strong>Date de Publication:</strong> <?php echo date('d/m/Y H:i', strtotime($offre_detail_admin['date_publication'])); ?></p>
        <?php if ($offre_detail_admin['date_limite_candidature']): ?>
        <p><strong>Date Limite Candidature:</strong> <?php echo date('d/m/Y', strtotime($offre_detail_admin['date_limite_candidature'])); ?></p>
        <?php endif; ?>

        <h4 style="margin-top:20px; margin-bottom:10px;">Description du Poste</h4>
        <div style="background:#f9f9f9; padding:15px; border-radius:var(--border-radius); line-height:1.7;"><?php echo nl2br(esc_html($offre_detail_admin['description_poste'])); ?></div>

        <?php if (!empty($offre_detail_admin['competences_requises'])): ?>
        <h4 style="margin-top:20px; margin-bottom:10px;">Compétences Requises</h4>
        <p><?php echo esc_html($offre_detail_admin['competences_requises']); ?></p>
        <?php endif; ?>

        <div style="margin-top:30px;" class="actions-column">
            <?php if ($offre_detail_admin['statut_validation_admin'] !== 'validee'): ?>
            <form action="<?php echo SITE_URL; ?>admin.php" method="POST" style="display:inline;">
                <input type="hidden" name="action" value="validate_offre">
                <input type="hidden" name="id" value="<?php echo $offre_detail_admin['id']; ?>">
                <input type="hidden" name="validation_status" value="validee">
                <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Valider cette offre</button>
            </form>
            <?php endif; ?>
            <?php if ($offre_detail_admin['statut_validation_admin'] !== 'refusee'): ?>
            <form action="<?php echo SITE_URL; ?>admin.php" method="POST" style="display:inline;">
                <input type="hidden" name="action" value="validate_offre">
                <input type="hidden" name="id" value="<?php echo $offre_detail_admin['id']; ?>">
                <input type="hidden" name="validation_status" value="refusee">
                <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> Refuser cette offre</button>
            </form>
            <?php endif; ?>
            <a href="<?php echo SITE_URL; ?>entreprise.php?view=form_offre&mode=modifier&id_offre=<?php echo $offre_detail_admin['id']; ?>&admin_edit=1" target="_blank" class="btn btn-secondary"><i class="fas fa-edit"></i> Modifier l'offre</a>
        </div>

    </div>
    <aside style="flex: 1; min-width: 280px;">
        <div class="card">
            <div class="card-header"><h4 style="margin:0;">Informations Entreprise</h4></div>
            <div class="card-content">
                <?php if(!empty($offre_detail_admin['logo_entreprise_admin'])): ?>
                    <img src="<?php echo esc_html($offre_detail_admin['logo_entreprise_admin']); ?>" alt="Logo <?php echo esc_html($offre_detail_admin['nom_entreprise']); ?>" style="max-width:100px; display:block; margin:0 auto 15px auto; border-radius:var(--border-radius);">
                <?php endif; ?>
                <p><strong>Nom:</strong> <?php echo esc_html($offre_detail_admin['nom_entreprise']); ?></p>
                <?php if(!empty($offre_detail_admin['site_web_entreprise_admin'])): ?>
                <p><strong>Site Web:</strong> <a href="<?php echo esc_html($offre_detail_admin['site_web_entreprise_admin']); ?>" target="_blank"><?php echo esc_html($offre_detail_admin['site_web_entreprise_admin']); ?></a></p>
                <?php endif; ?>
                <p><strong>Description:</strong><br><small><?php echo truncate_text(esc_html($offre_detail_admin['desc_entreprise_admin']), 150); ?></small></p>
            </div>
        </div>
    </aside>
</div>
<hr>
<a href="<?php echo SITE_URL; ?>admin.php?view=manage_offres" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour à la gestion des offres</a>