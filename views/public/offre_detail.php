<?php
// views/public/offre_detail.php
global $pdo;

$offre_id = $_GET['id'] ?? null;
if (!$offre_id) {
    set_flash_message("ID de l'offre manquant.", "error");
    redirect("index.php?view=offres_emploi");
}

$sql = "SELECT o.*, e.nom_entreprise, e.description_entreprise, e.lien_logo AS logo_entreprise, e.site_web_url 
        FROM offres_emploi o
        JOIN entreprises e ON o.id_entreprise_utilisateur = e.id_utilisateur
        WHERE o.id = ? AND o.est_active = 1 AND o.statut_validation_admin = 'validee'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$offre_id]);
$offre = $stmt->fetch();

if (!$offre) {
    set_flash_message("Offre non trouvée ou non accessible.", "error");
    redirect("index.php?view=offres_emploi");
}

$deja_postule = false;
if (isStudent()) {
    $stmt_check_candidature = $pdo->prepare("SELECT id FROM candidatures WHERE id_offre = ? AND id_etudiant_utilisateur = ?");
    $stmt_check_candidature->execute([$offre_id, getUserId()]);
    if ($stmt_check_candidature->fetch()) {
        $deja_postule = true;
    }
}
?>

<section id="detail-offre">
    <div class="container">
        <div style="display:flex; flex-wrap: wrap; gap: 30px;">
            <div style="flex: 2; min-width: 300px;"> <!-- Contenu principal de l'offre -->
                <h1 class="section-title" style="text-align:left; margin-bottom:5px;"><?php echo esc_html($offre['titre_poste']); ?></h1>
                <p style="font-size: 1.2em; color: var(--secondary-color); margin-bottom:20px;">
                    <a href="#entreprise-details" style="color:var(--primary-color); text-decoration:none;"><?php echo esc_html($offre['nom_entreprise']); ?></a>
                    - <?php echo esc_html($offre['lieu']); ?>
                </p>

                <div class="offre-meta" style="margin-bottom:20px; padding-bottom:20px; border-bottom:1px solid #eee;">
                    <span style="margin-right:20px;"><i class="fas fa-briefcase"></i> Type: <strong><?php echo esc_html($offre['type_contrat']); ?></strong></span>
                    <span style="margin-right:20px;"><i class="fas fa-calendar-alt"></i> Publiée le: <strong><?php echo date('d/m/Y', strtotime($offre['date_publication'])); ?></strong></span>
                    <?php if ($offre['date_limite_candidature']): ?>
                    <span style="margin-right:20px;"><i class="fas fa-hourglass-end"></i> Date limite: <strong><?php echo date('d/m/Y', strtotime($offre['date_limite_candidature'])); ?></strong></span>
                    <?php endif; ?>
                </div>
                
                <h3 style="color:var(--primary-color); margin-bottom:10px;">Description du Poste</h3>
                <div style="line-height:1.7; margin-bottom:25px;"><?php echo nl2br(esc_html($offre['description_poste'])); ?></div>

                <?php if (!empty($offre['competences_requises'])): ?>
                <h3 style="color:var(--primary-color); margin-bottom:10px;">Compétences Requises</h3>
                <div style="line-height:1.7; margin-bottom:25px;">
                    <?php 
                        $competences = explode(',', $offre['competences_requises']);
                        echo '<ul>';
                        foreach($competences as $comp) {
                            echo '<li>' . esc_html(trim($comp)) . '</li>';
                        }
                        echo '</ul>';
                    ?>
                </div>
                <?php endif; ?>

                <div class="actions-offre" style="margin-top:30px;">
                    <?php if (isStudent()): ?>
                        <?php if ($deja_postule): ?>
                            <p class="btn btn-success" style="cursor:default;"><i class="fas fa-check-circle"></i> Vous avez déjà postulé</p>
                        <?php else: ?>
                            <a href="<?php echo SITE_URL; ?>etudiant.php?view=postuler_offre&id_offre=<?php echo $offre['id']; ?>" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Postuler Maintenant</a>
                        <?php endif; ?>
                    <?php elseif (!isLoggedIn()): ?>
                        <p>Pour postuler, veuillez <a href="<?php echo SITE_URL; ?>index.php?view=connexion&redirect_to=<?php echo urlencode(SITE_URL.'index.php?view=offre_detail&id='.$offre['id']); ?>">vous connecter</a> ou <a href="<?php echo SITE_URL; ?>index.php?view=inscription_etudiant">créer un compte étudiant</a>.</p>
                    <?php endif; ?>
                </div>
            </div>

            <aside style="flex: 1; min-width: 280px; background:var(--light-gray); padding:20px; border-radius:var(--border-radius); height:fit-content;" id="entreprise-details">
                <img src="<?php echo esc_html(!empty($offre['logo_entreprise']) ? $offre['logo_entreprise'] : DEFAULT_COMPANY_LOGO); ?>" alt="Logo <?php echo esc_html($offre['nom_entreprise']); ?>" style="max-width:150px; display:block; margin:0 auto 20px auto; border-radius:var(--border-radius);">
                <h3 style="text-align:center; color:var(--primary-color); margin-bottom:15px;"><?php echo esc_html($offre['nom_entreprise']); ?></h3>
                <?php if(!empty($offre['site_web_url'])): ?>
                    <p style="text-align:center; margin-bottom:15px;"><a href="<?php echo esc_html($offre['site_web_url']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm"><i class="fas fa-globe"></i> Site Web</a></p>
                <?php endif; ?>
                <h4 style="margin-bottom:8px; font-size:1.1em;">À propos de l'entreprise:</h4>
                <p style="font-size:0.9em; line-height:1.6; color:var(--secondary-color);"><?php echo truncate_text(esc_html(strip_tags($offre['description_entreprise'])), 300); ?></p>
                <!-- Peut-être ajouter d'autres offres de la même entreprise -->
            </aside>
        </div>
        <a href="<?php echo SITE_URL; ?>index.php?view=offres_emploi" class="btn btn-secondary" style="margin-top:30px;"><i class="fas fa-arrow-left"></i> Retour aux offres</a>
    </div>
</section>