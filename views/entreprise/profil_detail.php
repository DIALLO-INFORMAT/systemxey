<?php
// views/entreprise/profil_detail.php
global $pdo, $user_id_entreprise;

$id_etudiant_param = $_GET['id_etudiant'] ?? null;
if (!$id_etudiant_param) {
    set_flash_message("ID de l'étudiant manquant.", "error");
    redirect("entreprise.php?view=rechercher_profils");
}

$stmt_profil_etu_complet = $pdo->prepare(
    "SELECT p.*, u.email AS email_etudiant, u.nom_utilisateur AS nom_utilisateur_etudiant
     FROM profils_etudiants p
     JOIN utilisateurs u ON p.id_utilisateur = u.id
     WHERE p.id_utilisateur = ? AND p.est_visible = 1 AND u.est_actif = 1 AND u.role = 'etudiant'"
);
$stmt_profil_etu_complet->execute([$id_etudiant_param]);
$profil_etudiant_complet = $stmt_profil_etu_complet->fetch();

if (!$profil_etudiant_complet) {
    set_flash_message("Profil étudiant non trouvé ou inaccessible.", "error");
    redirect("entreprise.php?view=rechercher_profils");
}
?>
<h2><i class="fas fa-user-tie"></i> Profil de <?php echo esc_html($profil_etudiant_complet['nom_complet']); ?></h2>

<div style="display:flex; flex-wrap:wrap; gap:30px;">
    <div style="flex:2; min-width:350px;"> <!-- Colonne principale -->
        <?php if (!empty($profil_etudiant_complet['titre_profil'])): ?>
            <p style="font-size:1.3em; color:var(--primary-color); margin-bottom:15px; font-style:italic;"><?php echo esc_html($profil_etudiant_complet['titre_profil']); ?></p>
        <?php endif; ?>

        <h4 style="margin-top:20px; margin-bottom:10px; color:var(--dark-gray); border-bottom:1px solid #eee; padding-bottom:5px;">Informations Académiques</h4>
        <p><strong><i class="fas fa-school"></i> Établissement :</strong> <?php echo esc_html($profil_etudiant_complet['etablissement'] ?? 'N/A'); ?></p>
        <p><strong><i class="fas fa-graduation-cap"></i> Domaine :</strong> <?php echo esc_html($profil_etudiant_complet['domaine_etudes'] ?? 'N/A'); ?></p>
        <p><strong><i class="fas fa-layer-group"></i> Niveau :</strong> <?php echo esc_html($profil_etudiant_complet['niveau_etudes'] ?? 'N/A'); ?></p>
        
        <h4 style="margin-top:20px; margin-bottom:10px; color:var(--dark-gray); border-bottom:1px solid #eee; padding-bottom:5px;">Contact et Disponibilité</h4>
        <p><strong><i class="fas fa-envelope"></i> Email :</strong> <a href="mailto:<?php echo esc_html($profil_etudiant_complet['email_etudiant']); ?>"><?php echo esc_html($profil_etudiant_complet['email_etudiant']); ?></a></p>
        <?php if(!empty($profil_etudiant_complet['telephone1'])): ?>
        <p><strong><i class="fas fa-phone"></i> Téléphone 1 :</strong> <?php echo esc_html($profil_etudiant_complet['telephone1']); ?></p>
        <?php endif; ?>
         <?php if(!empty($profil_etudiant_complet['telephone2'])): ?>
        <p><strong><i class="fas fa-mobile-alt"></i> Téléphone 2 :</strong> <?php echo esc_html($profil_etudiant_complet['telephone2']); ?></p>
        <?php endif; ?>
        <p><strong><i class="fas fa-calendar-alt"></i> Disponibilité :</strong> <?php echo esc_html($profil_etudiant_complet['disponibilite'] ?? 'N/A'); ?></p>
        <p><strong><i class="fas fa-file-contract"></i> Contrat recherché :</strong> <?php echo esc_html($profil_etudiant_complet['type_contrat_recherche'] ?? 'N/A'); ?></p>

        <?php if(!empty($profil_etudiant_complet['description_personnelle'])): ?>
        <h4 style="margin-top:20px; margin-bottom:10px; color:var(--dark-gray); border-bottom:1px solid #eee; padding-bottom:5px;">Présentation</h4>
        <div style="line-height:1.7;"><?php echo nl2br(esc_html($profil_etudiant_complet['description_personnelle'])); ?></div>
        <?php endif; ?>

        <?php if(!empty($profil_etudiant_complet['competences_cles'])): ?>
        <h4 style="margin-top:20px; margin-bottom:10px; color:var(--dark-gray); border-bottom:1px solid #eee; padding-bottom:5px;">Compétences Clés</h4>
        <div>
            <?php
            $competences = explode(',', $profil_etudiant_complet['competences_cles']);
            foreach($competences as $comp) {
                echo '<span class="badge badge-secondary" style="margin:3px; font-size:0.9em;">' . esc_html(trim($comp)) . '</span>';
            }
            ?>
        </div>
        <?php endif; ?>
    </div>

    <aside style="flex:1; min-width:250px;"> <!-- Colonne latérale -->
        <div style="text-align:center; margin-bottom:20px;">
             <img src="<?php echo esc_html(!empty($profil_etudiant_complet['lien_photo']) ? $profil_etudiant_complet['lien_photo'] : DEFAULT_PROFILE_PIC); ?>" alt="Photo de <?php echo esc_html($profil_etudiant_complet['nom_complet']); ?>" style="width:180px; height:180px; border-radius:50%; object-fit:cover; border: 4px solid var(--primary-color); padding:3px; background:white;">
        </div>
        <div class="card" style="padding:15px;">
            <h4 style="margin-bottom:10px; color:var(--primary-color);">Documents & Liens</h4>
            <?php if(!empty($profil_etudiant_complet['lien_cv'])): ?>
            <p><a href="<?php echo esc_html($profil_etudiant_complet['lien_cv']); ?>" target="_blank" class="btn btn-primary btn-block" style="width:100%; margin-bottom:10px; text-align:left;"><i class="fas fa-file-pdf"></i> Voir le CV</a></p>
            <?php else: ?> <p><small>CV non fourni.</small></p> <?php endif; ?>
            <?php if(!empty($profil_etudiant_complet['lien_lm'])): ?>
            <p><a href="<?php echo esc_html($profil_etudiant_complet['lien_lm']); ?>" target="_blank" class="btn btn-secondary btn-block" style="width:100%; margin-bottom:10px; text-align:left;"><i class="fas fa-file-alt"></i> Voir Lettre de Motivation</a></p>
            <?php endif; ?>
            <?php if(!empty($profil_etudiant_complet['lien_linkedin'])): ?>
            <p><a href="<?php echo esc_html($profil_etudiant_complet['lien_linkedin']); ?>" target="_blank" class="btn btn-info btn-block" style="width:100%; margin-bottom:10px; text-align:left; background-color:#0077b5; border-color:#0077b5;"><i class="fab fa-linkedin"></i> Profil LinkedIn</a></p>
            <?php endif; ?>
            <?php if(!empty($profil_etudiant_complet['lien_portfolio'])): ?>
            <p><a href="<?php echo esc_html($profil_etudiant_complet['lien_portfolio']); ?>" target="_blank" class="btn btn-dark btn-block" style="width:100%; text-align:left; background-color:var(--dark-gray); border-color:var(--dark-gray);"><i class="fas fa-briefcase"></i> Portfolio/GitHub</a></p>
            <?php endif; ?>
        </div>
         <!-- Actions (contacter, sauvegarder - à implémenter) -->
        <div style="margin-top:20px;">
            <button class="btn btn-success" style="width:100%;"><i class="fas fa-envelope"></i> Contacter (Bientôt)</button>
            <button class="btn btn-outline-primary" style="width:100%; margin-top:10px;"><i class="fas fa-bookmark"></i> Sauvegarder Profil (Bientôt)</button>
        </div>
    </aside>
</div>
<hr style="margin:30px 0;">
<a href="<?php echo SITE_URL; ?>entreprise.php?view=rechercher_profils" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour à la recherche</a>