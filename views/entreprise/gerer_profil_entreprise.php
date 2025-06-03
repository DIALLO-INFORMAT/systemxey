<?php
// views/entreprise/gerer_profil_entreprise.php
global $pdo, $user_id_entreprise, $profil_entreprise; // $profil_entreprise est chargé dans entreprise.php
if (!$profil_entreprise) {
    echo "<p class='error'>Erreur: Profil entreprise non trouvé.</p>";
    return;
}
?>
<h2><i class="fas fa-id-card-alt"></i> Gérer le Profil de l'Entreprise</h2>
<p>Un profil complet et bien présenté attire plus de candidats qualifiés.</p>

<form action="<?php echo SITE_URL; ?>entreprise.php?view=gerer_profil_entreprise" method="POST" class="form-container">
    <input type="hidden" name="action" value="update_profil_entreprise">

    <div class="form-group">
        <label for="nom_entreprise">Nom de l'Entreprise *</label>
        <input type="text" id="nom_entreprise" name="nom_entreprise" value="<?php echo esc_html($profil_entreprise['nom_entreprise'] ?? ''); ?>" required>
    </div>
    <div class="form-group">
        <label for="secteur_activite">Secteur d'activité *</label>
        <input type="text" id="secteur_activite" name="secteur_activite" value="<?php echo esc_html($profil_entreprise['secteur_activite'] ?? ''); ?>" required>
    </div>
    <div class="form-group">
        <label for="lien_logo">Lien vers le Logo de l'Entreprise (Google Drive, ImgBB, etc.)</label>
        <input type="url" id="lien_logo" name="lien_logo" value="<?php echo esc_html($profil_entreprise['lien_logo'] ?? ''); ?>" placeholder="https://example.com/votre-logo.png">
        <?php if(!empty($profil_entreprise['lien_logo'])): ?>
            <img src="<?php echo esc_html($profil_entreprise['lien_logo']); ?>" alt="Aperçu logo" style="max-width:150px; max-height:100px; margin-top:10px; border:1px solid #eee; padding:5px; border-radius:var(--border-radius); background:#f9f9f9;">
        <?php endif; ?>
    </div>
    <div class="form-group">
        <label for="site_web_url">Site Web de l'Entreprise</label>
        <input type="url" id="site_web_url" name="site_web_url" value="<?php echo esc_html($profil_entreprise['site_web_url'] ?? ''); ?>" placeholder="https://www.votre-entreprise.com">
    </div>
     <div class="form-group">
        <label for="adresse">Adresse / Localisation (Ville, Pays)</label>
        <input type="text" id="adresse" name="adresse" value="<?php echo esc_html($profil_entreprise['adresse'] ?? ''); ?>" placeholder="Ex: Dakar, Sénégal">
    </div>
    <div class="form-group">
        <label for="description_entreprise">Description de l'entreprise (activités, culture, etc.)</label>
        <textarea id="description_entreprise" name="description_entreprise" rows="8" placeholder="Présentez votre entreprise, ses valeurs, sa mission..."><?php echo esc_html($profil_entreprise['description_entreprise'] ?? ''); ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer les modifications</button>
</form>