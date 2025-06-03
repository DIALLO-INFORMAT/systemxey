<?php
// views/etudiant/gerer_profil.php
global $pdo, $user_id, $profil_etudiant; // $profil_etudiant est chargé dans etudiant.php
if (!$profil_etudiant) {
    // Ce cas ne devrait pas arriver si la logique dans etudiant.php est correcte
    echo "<p class='error'>Erreur: Profil étudiant non trouvé.</p>";
    return;
}
?>
<h2><i class="fas fa-user-edit"></i> Gérer mon Profil Étudiant</h2>
<p>Assurez-vous que votre profil est complet et à jour pour maximiser votre visibilité auprès des recruteurs.</p>

<form action="<?php echo SITE_URL; ?>etudiant.php?view=gerer_profil" method="POST" class="form-container">
    <input type="hidden" name="action" value="update_profil_etudiant">

    <div class="row" style="display:flex; flex-wrap:wrap; gap:20px;">
        <div style="flex:1; min-width:300px;">
            <div class="form-group">
                <label for="nom_complet">Nom Complet *</label>
                <input type="text" id="nom_complet" name="nom_complet" value="<?php echo esc_html($profil_etudiant['nom_complet'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="titre_profil">Titre du Profil (ex: Étudiant en Marketing Digital, Développeur Web Junior)</label>
                <input type="text" id="titre_profil" name="titre_profil" value="<?php echo esc_html($profil_etudiant['titre_profil'] ?? ''); ?>">
            </div>
             <div class="form-group">
                <label for="telephone1">Téléphone 1</label>
                <input type="tel" id="telephone1" name="telephone1" value="<?php echo esc_html($profil_etudiant['telephone1'] ?? ''); ?>" placeholder="+221 XX XXX XX XX">
            </div>
            <div class="form-group">
                <label for="telephone2">Téléphone 2 (Optionnel)</label>
                <input type="tel" id="telephone2" name="telephone2" value="<?php echo esc_html($profil_etudiant['telephone2'] ?? ''); ?>">
            </div>
        </div>
        <div style="flex:1; min-width:300px;">
            <div class="form-group">
                <label for="etablissement">Établissement Actuel *</label>
                <input type="text" id="etablissement" name="etablissement" value="<?php echo esc_html($profil_etudiant['etablissement'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="domaine_etudes">Domaine d'études Principal</label>
                <input type="text" id="domaine_etudes" name="domaine_etudes" value="<?php echo esc_html($profil_etudiant['domaine_etudes'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="niveau_etudes">Niveau d'études (ex: Licence 3, Master 1, BTS)</label>
                <input type="text" id="niveau_etudes" name="niveau_etudes" value="<?php echo esc_html($profil_etudiant['niveau_etudes'] ?? ''); ?>">
            </div>
        </div>
    </div>

    <hr style="margin: 20px 0;">
    <h3 style="margin-bottom:15px; font-size:1.3em;">Liens Professionnels et Documents</h3>
     <div class="row" style="display:flex; flex-wrap:wrap; gap:20px;">
        <div style="flex:1; min-width:300px;">
            <div class="form-group">
                <label for="lien_photo">Lien vers votre Photo de Profil (Google Drive, ImgBB, etc.)</label>
                <input type="url" id="lien_photo" name="lien_photo" value="<?php echo esc_html($profil_etudiant['lien_photo'] ?? ''); ?>" placeholder="https://example.com/votre-photo.jpg">
                <?php if(!empty($profil_etudiant['lien_photo'])): ?>
                    <img src="<?php echo esc_html($profil_etudiant['lien_photo']); ?>" alt="Aperçu photo" style="max-width:100px; max-height:100px; margin-top:10px; border-radius:var(--border-radius);">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="lien_cv">Lien vers votre CV (Google Drive, Dropbox, PDF hébergé) *</label>
                <input type="url" id="lien_cv" name="lien_cv" value="<?php echo esc_html($profil_etudiant['lien_cv'] ?? ''); ?>" placeholder="https://example.com/mon-cv.pdf" required>
                 <?php if(!empty($profil_etudiant['lien_cv'])): ?> <small><a href="<?php echo esc_html($profil_etudiant['lien_cv']); ?>" target="_blank">Voir CV actuel</a></small><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="lien_lm">Lien vers une Lettre de Motivation type (Optionnel)</label>
                <input type="url" id="lien_lm" name="lien_lm" value="<?php echo esc_html($profil_etudiant['lien_lm'] ?? ''); ?>" placeholder="https://example.com/ma-lettre.pdf">
            </div>
        </div>
         <div style="flex:1; min-width:300px;">
             <div class="form-group">
                <label for="lien_linkedin">Lien Profil LinkedIn</label>
                <input type="url" id="lien_linkedin" name="lien_linkedin" value="<?php echo esc_html($profil_etudiant['lien_linkedin'] ?? ''); ?>" placeholder="https://linkedin.com/in/votrenom">
            </div>
            <div class="form-group">
                <label for="lien_portfolio">Lien Portfolio/GitHub/Behance</label>
                <input type="url" id="lien_portfolio" name="lien_portfolio" value="<?php echo esc_html($profil_etudiant['lien_portfolio'] ?? ''); ?>" placeholder="https://github.com/votrenom">
            </div>
         </div>
    </div>

    <hr style="margin: 20px 0;">
    <h3 style="margin-bottom:15px; font-size:1.3em;">Présentation et Compétences</h3>
    <div class="form-group">
        <label for="description_personnelle">Description Personnelle / Objectifs Professionnels</label>
        <textarea id="description_personnelle" name="description_personnelle" rows="6" placeholder="Décrivez brièvement votre parcours, vos aspirations, ce qui vous motive..."><?php echo esc_html($profil_etudiant['description_personnelle'] ?? ''); ?></textarea>
    </div>
    <div class="form-group">
        <label for="competences_cles">Compétences Clés (séparées par des virgules)</label>
        <input type="text" id="competences_cles" name="competences_cles" value="<?php echo esc_html($profil_etudiant['competences_cles'] ?? ''); ?>" placeholder="Ex: Microsoft Office, Photoshop, Programmation Java, Gestion de projet">
    </div>

    <hr style="margin: 20px 0;">
    <h3 style="margin-bottom:15px; font-size:1.3em;">Disponibilité et Préférences</h3>
     <div class="row" style="display:flex; flex-wrap:wrap; gap:20px;">
        <div style="flex:1; min-width:300px;">
            <div class="form-group">
                <label for="disponibilite">Disponibilité</label>
                <input type="text" id="disponibilite" name="disponibilite" placeholder="Ex: Immédiate, À partir de Septembre 2024, Temps partiel" value="<?php echo esc_html($profil_etudiant['disponibilite'] ?? ''); ?>">
            </div>
        </div>
        <div style="flex:1; min-width:300px;">
            <div class="form-group">
                <label for="type_contrat_recherche">Type de contrat recherché</label>
                <input type="text" id="type_contrat_recherche" name="type_contrat_recherche" placeholder="Ex: Stage, CDI, Alternance, Freelance" value="<?php echo esc_html($profil_etudiant['type_contrat_recherche'] ?? ''); ?>">
            </div>
        </div>
    </div>
    
    <div class="form-check" style="margin-top:20px;">
        <input type="checkbox" id="est_visible" name="est_visible" value="1" <?php echo (isset($profil_etudiant['est_visible']) && $profil_etudiant['est_visible'] == 1) ? 'checked' : ''; ?>>
        <label for="est_visible">Rendre mon profil visible par les recruteurs</label>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer les modifications</button>
    <a href="<?php echo SITE_URL; ?>index.php?view=profil_etudiant_public_detail&id=<?php echo $user_id; ?>" target="_blank" class="btn btn-secondary" style="margin-left:10px;"><i class="fas fa-eye"></i> Voir mon profil public</a>
</form>