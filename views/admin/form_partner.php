<?php
// views/admin/form_partner.php
global $pdo, $item_id; // $item_id sera l'ID du partenaire en mode modification

$mode_partner = $_GET['mode'] ?? 'creer';
$partner_data = null;
$form_action_partner = ($mode_partner === 'modifier' && $item_id) ? 'update_partner' : 'add_partner';
$form_title_partner = ($mode_partner === 'modifier' && $item_id) ? 'Modifier le Partenaire' : 'Ajouter un Nouveau Partenaire';

if ($mode_partner === 'modifier' && $item_id) {
    $stmt = $pdo->prepare("SELECT * FROM partenaires WHERE id = ?");
    $stmt->execute([$item_id]);
    $partner_data = $stmt->fetch();
    if (!$partner_data) {
        set_flash_message("Partenaire non trouvé.", "error");
        redirect("admin.php?view=manage_partners");
    }
}

$nom_val = $_POST['nom_partenaire'] ?? ($partner_data['nom_partenaire'] ?? '');
$logo_url_val = $_POST['lien_logo_partenaire'] ?? ($partner_data['lien_logo_partenaire'] ?? '');
$site_url_val = $_POST['lien_site_partenaire'] ?? ($partner_data['lien_site_partenaire'] ?? '');
$ordre_val = $_POST['ordre_affichage'] ?? ($partner_data['ordre_affichage'] ?? 0);
$actif_val_part = isset($_POST['est_actif_part']) ? $_POST['est_actif_part'] : ($partner_data['est_actif'] ?? 1);

?>
<h2><i class="fas <?php echo ($mode_partner === 'modifier') ? 'fa-edit' : 'fa-plus'; ?>"></i> <?php echo $form_title_partner; ?></h2>

<form action="<?php echo SITE_URL; ?>admin.php?view=manage_partners" method="POST" class="form-container" style="max-width:700px; margin-left:0;">
    <input type="hidden" name="action" value="<?php echo $form_action_partner; ?>">
    <?php if ($mode_partner === 'modifier' && $item_id): ?>
        <input type="hidden" name="id" value="<?php echo $item_id; ?>">
    <?php endif; ?>

    <div class="form-group">
        <label for="nom_partenaire">Nom du Partenaire *</label>
        <input type="text" id="nom_partenaire" name="nom_partenaire" value="<?php echo esc_html($nom_val); ?>" required>
    </div>
    <div class="form-group">
        <label for="lien_logo_partenaire">Lien vers le Logo (URL directe de l'image) *</label>
        <input type="url" id="lien_logo_partenaire" name="lien_logo_partenaire" value="<?php echo esc_html($logo_url_val); ?>" required placeholder="https://example.com/logo.png">
        <?php if(!empty($logo_url_val)): ?> <img src="<?php echo esc_html($logo_url_val); ?>" alt="Aperçu logo" style="max-height:50px; margin-top:5px; border:1px solid #ddd;"><?php endif; ?>
    </div>
    <div class="form-group">
        <label for="lien_site_partenaire">Lien vers le Site Web du Partenaire (Optionnel)</label>
        <input type="url" id="lien_site_partenaire" name="lien_site_partenaire" value="<?php echo esc_html($site_url_val); ?>" placeholder="https://www.partenaire.com">
    </div>
    <div class="form-group">
        <label for="ordre_affichage">Ordre d'Affichage (0 pour premier)</label>
        <input type="number" id="ordre_affichage" name="ordre_affichage" value="<?php echo esc_html($ordre_val); ?>" min="0" class="form-control" style="width:150px;">
    </div>
    <?php if ($mode_partner === 'modifier'): ?>
    <div class="form-check">
        <input type="checkbox" id="est_actif_part" name="est_actif_part" value="1" <?php echo ($actif_val_part == 1) ? 'checked' : ''; ?>>
        <label for="est_actif_part">Partenaire actif (visible sur le site)</label>
    </div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo ($mode_partner === 'modifier') ? 'Mettre à jour' : 'Ajouter Partenaire'; ?></button>
    <a href="<?php echo SITE_URL; ?>admin.php?view=manage_partners" class="btn btn-secondary" style="margin-left:10px;">Annuler</a>
</form>