<?php
// views/public/profil_etudiant_public_detail.php
global $pdo;

$profil_user_id = $_GET['id'] ?? null;
if (!$profil_user_id) {
    set_flash_message("ID de profil manquant.", "error");
    redirect("index.php?view=profils_etudiants");
}

$sql = "SELECT p.*, u.email 
        FROM profils_etudiants p
        JOIN utilisateurs u ON p.id_utilisateur = u.id
        WHERE p.id_utilisateur = ? AND p.est_visible = 1 AND u.est_actif = 1 AND u.role = 'etudiant'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$profil_user_id]);
$profil = $stmt->fetch();

if (!$profil) {
    set_flash_message("Profil étudiant non trouvé ou non accessible.", "error");
    redirect("index.php?view=profils_etudiants");
}
?>

<section id="detail-profil-public">
    <div class="container">
        <div class="card" style="max-width:800px; margin:auto; padding:30px;">
            <div style="text-align:center; margin-bottom:20px;">
                <img src="<?php echo esc_html(!empty($profil['lien_photo']) ? $profil['lien_photo'] : DEFAULT_PROFILE_PIC); ?>" alt="Photo de <?php echo esc_html($profil['nom_complet']); ?>" style="width:150px; height:150px; border-radius:50%; object-fit:cover; border: 3px solid var(--primary-color);">
            </div>
            <h1 class="section-title" style="margin-bottom:5px;"><?php echo esc_html($profil['nom_complet']); ?></h1>
            <?php if (!empty($profil['titre_profil'])): ?>
                <p style="text-align:center; font-size:1.2em; color:var(--primary-color); font-style:italic; margin-bottom:20px;"><?php echo esc_html($profil['titre_profil']); ?></p>
            <?php endif; ?>
            
            <div class="profile-info" style="margin-bottom:20px;">
                <p><strong><i class="fas fa-graduation-cap"></i> Domaine d'études :</strong> <?php echo esc_html($profil['domaine_etudes'] ?? 'Non spécifié'); ?></p>
                <p><strong><i class="fas fa-layer-group"></i> Niveau d'études :</strong> <?php echo esc_html($profil['niveau_etudes'] ?? 'Non spécifié'); ?></p>
                <p><strong><i class="fas fa-school"></i> Établissement :</strong> <?php echo esc_html($profil['etablissement'] ?? 'Non spécifié'); ?></p>
                <p><strong><i class="fas fa-calendar-check"></i> Disponibilité :</strong> <?php echo esc_html($profil['disponibilite'] ?? 'Non spécifié'); ?></p>
                <p><strong><i class="fas fa-file-signature"></i> Type de contrat recherché :</strong> <?php echo esc_html($profil['type_contrat_recherche'] ?? 'Non spécifié'); ?></p>
            </div>

            <?php if (!empty($profil['description_personnelle'])): ?>
            <h3 style="color:var(--primary-color); margin-top:20px; margin-bottom:10px;">À propos de moi</h3>
            <p style="line-height:1.7;"><?php echo nl2br(esc_html($profil['description_personnelle'])); ?></p>
            <?php endif; ?>

            <?php if (!empty($profil['competences_cles'])): ?>
            <h3 style="color:var(--primary-color); margin-top:20px; margin-bottom:10px;">Compétences Clés</h3>
            <div>
                <?php
                $competences = explode(',', $profil['competences_cles']);
                foreach ($competences as $comp) {
                    echo '<span style="background-color:var(--light-gray); color:var(--dark-gray); padding:5px 10px; border-radius:15px; margin-right:5px; margin-bottom:5px; display:inline-block; font-size:0.9em;">' . esc_html(trim($comp)) . '</span>';
                }
                ?>
            </div>
            <?php endif; ?>

            <?php if (!isCompany()): ?>
            <div style="margin-top:30px; padding:20px; background-color:var(--light-gray); border:1px solid var(--primary-color); border-radius:var(--border-radius); text-align:center;">
                <h4><i class="fas fa-lock" style="color:var(--primary-color);"></i> Informations Réservées</h4>
                <p>Pour voir le profil complet, les coordonnées, le CV et contacter cet étudiant, veuillez vous connecter en tant qu'entreprise.</p>
                <a href="<?php echo SITE_URL; ?>index.php?view=connexion&redirect_to=<?php echo urlencode(SITE_URL.'entreprise.php?view=profil_detail&id='.$profil['id_utilisateur']); ?>" class="btn btn-primary" style="margin-top:10px;">Connexion Entreprise</a>
                <a href="<?php echo SITE_URL; ?>index.php?view=inscription_entreprise" class="btn btn-secondary" style="margin-left:10px; margin-top:10px;">S'inscrire (Entreprise)</a>
            </div>
            <?php endif; ?>

        </div>
        <div style="text-align:center; margin-top:30px;">
             <a href="<?php echo SITE_URL; ?>index.php?view=profils_etudiants" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour aux profils</a>
        </div>
    </div>
</section>