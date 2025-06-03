<?php
// views/etudiant/offres_favorites.php
global $pdo, $user_id;

$stmt_favorites = $pdo->prepare(
    "SELECT o.*, e.nom_entreprise, e.lien_logo AS logo_entreprise
     FROM offres_favorites_etudiants ofe
     JOIN offres_emploi o ON ofe.id_offre = o.id
     JOIN entreprises e ON o.id_entreprise_utilisateur = e.id_utilisateur
     WHERE ofe.id_etudiant_utilisateur = ? AND o.est_active = 1 AND o.statut_validation_admin = 'validee'
     ORDER BY ofe.date_ajout DESC"
);
$stmt_favorites->execute([$user_id]);
$offres_favorites = $stmt_favorites->fetchAll();
?>
<h2><i class="fas fa-heart"></i> Mes Offres Favorites</h2>

<?php if (count($offres_favorites) > 0): ?>
    <div class="card-grid">
        <?php foreach ($offres_favorites as $offre): ?>
            <div class="card">
                <div class="card-image-container" style="background-color: #eee;">
                    <img src="<?php echo esc_html(!empty($offre['logo_entreprise']) ? $offre['logo_entreprise'] : DEFAULT_COMPANY_LOGO); ?>" alt="Logo <?php echo esc_html($offre['nom_entreprise']); ?>">
                </div>
                <div class="card-content">
                    <h3 class="card-title"><?php echo esc_html($offre['titre_poste']); ?></h3>
                    <p class="card-text"><strong>Entreprise:</strong> <?php echo esc_html($offre['nom_entreprise']); ?></p>
                    <p class="card-text"><strong>Lieu:</strong> <?php echo esc_html($offre['lieu']); ?></p>
                    <p class="card-text"><strong>Contrat:</strong> <?php echo esc_html($offre['type_contrat']); ?></p>
                    <div class="card-actions">
                        <a href="<?php echo SITE_URL; ?>index.php?view=offre_detail&id=<?php echo $offre['id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i> Voir Détails</a>
                        <form action="<?php echo SITE_URL; ?>etudiant.php" method="POST" style="display:inline-block;">
                            <input type="hidden" name="action" value="toggle_offre_favorite">
                            <input type="hidden" name="id_offre" value="<?php echo $offre['id']; ?>">
                            <input type="hidden" name="redirect_back_url" value="<?php echo SITE_URL; ?>etudiant.php?view=offres_favorites">
                            <button type="submit" class="btn btn-danger btn-sm" title="Retirer des favoris"><i class="fas fa-trash-alt"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
     <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Vous n'avez aucune offre dans vos favoris pour le moment.
        <a href="<?php echo SITE_URL; ?>index.php?view=offres_emploi" class="btn btn-primary btn-sm" style="margin-left:15px;">Parcourir les offres</a>
    </div>
<?php endif; ?>