<?php
// views/etudiant/postuler_offre.php
global $pdo, $user_id, $profil_etudiant; // $profil_etudiant est chargé dans etudiant.php

$id_offre = $_GET['id_offre'] ?? null;
if (!$id_offre) {
    set_flash_message("ID de l'offre manquant pour postuler.", "error");
    redirect("index.php?view=offres_emploi");
}

// Récupérer les infos de l'offre
$stmt_offre = $pdo->prepare(
    "SELECT o.titre_poste, e.nom_entreprise 
     FROM offres_emploi o
     JOIN entreprises e ON o.id_entreprise_utilisateur = e.id_utilisateur
     WHERE o.id = ? AND o.est_active = 1 AND o.statut_validation_admin = 'validee'"
);
$stmt_offre->execute([$id_offre]);
$offre_info = $stmt_offre->fetch();

if (!$offre_info) {
    set_flash_message("Offre non trouvée ou inaccessible.", "error");
    redirect("index.php?view=offres_emploi");
}

// Vérifier si l'étudiant a déjà postulé
$stmt_check_candidature = $pdo->prepare("SELECT id FROM candidatures WHERE id_offre = ? AND id_etudiant_utilisateur = ?");
$stmt_check_candidature->execute([$id_offre, $user_id]);
if ($stmt_check_candidature->fetch()) {
    set_flash_message("Vous avez déjà postulé à cette offre.", "info");
    redirect("etudiant.php?view=mes_candidatures");
}

if (!$profil_etudiant) { // S'assurer que le profil est chargé (devrait l'être par etudiant.php)
    echo "<p class='error'>Erreur: Profil étudiant non trouvé. Veuillez compléter votre profil.</p>";
    return;
}

$lien_cv_profil = $profil_etudiant['lien_cv'] ?? '';
$lien_lm_profil = $profil_etudiant['lien_lm'] ?? '';
?>
<h2><i class="fas fa-paper-plane"></i> Postuler à l'offre : <?php echo esc_html($offre_info['titre_poste']); ?></h2>
<p><strong>Entreprise :</strong> <?php echo esc_html($offre_info['nom_entreprise']); ?></p>
<hr>

<?php if (empty($lien_cv_profil)): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> Attention, vous n'avez pas encore renseigné de lien vers votre CV dans votre profil.
        Il est <strong>fortement recommandé</strong> de le faire pour augmenter vos chances.
        <a href="<?php echo SITE_URL; ?>etudiant.php?view=gerer_profil" class="btn btn-sm btn-warning" style="margin-left:10px;">Mettre à jour mon profil</a>
        <p style="margin-top:10px;">Vous pouvez néanmoins fournir un lien CV spécifique pour cette candidature ci-dessous.</p>
    </div>
<?php endif; ?>

<form action="<?php echo SITE_URL; ?>etudiant.php" method="POST" class="form-container">
    <input type="hidden" name="action" value="postuler_offre_submit">
    <input type="hidden" name="id_offre" value="<?php echo $id_offre; ?>">

    <div class="form-group">
        <label for="lien_cv_candidature">Lien vers votre CV pour cette candidature *</label>
        <input type="url" id="lien_cv_candidature" name="lien_cv_candidature" 
               value="<?php echo esc_html($lien_cv_profil); ?>" 
               placeholder="https://example.com/votre-cv-specifique.pdf" required>
        <small>Pré-rempli avec le CV de votre profil. Vous pouvez le modifier pour cette candidature.</small>
    </div>

    <div class="form-group">
        <label for="lien_lm_candidature">Lien vers votre Lettre de Motivation pour cette candidature (Optionnel)</label>
        <input type="url" id="lien_lm_candidature" name="lien_lm_candidature" 
               value="<?php echo esc_html($lien_lm_profil); ?>" 
               placeholder="https://example.com/votre-lettre-specifique.pdf">
        <small>Pré-rempli avec la LM de votre profil (si existante). Vous pouvez la modifier.</small>
    </div>

    <div class="form-group">
        <label for="message_candidature">Message à l'attention du recruteur (Optionnel)</label>
        <textarea id="message_candidature" name="message_candidature" rows="5" placeholder="Vous pouvez ajouter ici un court message personnalisé pour cette candidature."></textarea>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fas fa-check-circle"></i> Envoyer ma candidature</button>
    <a href="<?php echo SITE_URL; ?>index.php?view=offre_detail&id=<?php echo $id_offre; ?>" class="btn btn-secondary" style="margin-left:10px;">Annuler</a>
</form>