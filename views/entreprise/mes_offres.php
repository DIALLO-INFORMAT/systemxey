<?php
// views/entreprise/mes_offres.php
global $pdo, $user_id_entreprise;

$stmt_mes_offres = $pdo->prepare(
    "SELECT o.*, 
     (SELECT COUNT(*) FROM candidatures c WHERE c.id_offre = o.id) AS nb_candidatures,
     (SELECT COUNT(*) FROM candidatures c WHERE c.id_offre = o.id AND c.statut_candidature = 'postulee') AS nb_nouvelles_candidatures
     FROM offres_emploi o
     WHERE o.id_entreprise_utilisateur = ?
     ORDER BY o.date_publication DESC"
);
$stmt_mes_offres->execute([$user_id_entreprise]);
$mes_offres = $stmt_mes_offres->fetchAll();

$statut_validation_labels = [
    'en_attente' => ['label' => 'En attente de validation', 'class' => 'badge-en-attente'],
    'validee' => ['label' => 'Validée et Publique', 'class' => 'badge-validee'],
    'refusee' => ['label' => 'Refusée par l\'admin', 'class' => 'badge-refusee'],
];
?>
<h2><i class="fas fa-list-alt"></i> Mes Offres d'Emploi</h2>
<p>Gérez ici les offres que vous avez publiées.</p>
<a href="<?php echo SITE_URL; ?>entreprise.php?view=form_offre&mode=creer" class="btn btn-primary" style="margin-bottom:20px;"><i class="fas fa-plus-circle"></i> Publier une Nouvelle Offre</a>

<?php if (count($mes_offres) > 0): ?>
    <div class="card-grid">
        <?php foreach ($mes_offres as $offre): ?>
            <div class="card">
                 <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                    <h3 class="card-title" style="margin-bottom:0; font-size:1.1em;"><?php echo esc_html($offre['titre_poste']); ?></h3>
                    <span class="badge <?php echo $statut_validation_labels[$offre['statut_validation_admin']]['class'] ?? 'badge-secondary'; ?>">
                        <?php echo $statut_validation_labels[$offre['statut_validation_admin']]['label'] ?? ucfirst($offre['statut_validation_admin']); ?>
                    </span>
                </div>
                <div class="card-content">
                    <p class="card-text"><strong>Lieu:</strong> <?php echo esc_html($offre['lieu']); ?></p>
                    <p class="card-text"><strong>Contrat:</strong> <?php echo esc_html($offre['type_contrat']); ?></p>
                    <p class="card-text"><strong>Publiée le:</strong> <?php echo date('d/m/Y', strtotime($offre['date_publication'])); ?></p>
                    <p class="card-text"><strong>Candidatures:</strong> <?php echo $offre['nb_candidatures']; ?> 
                        <?php if ($offre['nb_nouvelles_candidatures'] > 0): ?>
                            <span class="badge badge-danger"><?php echo $offre['nb_nouvelles_candidatures']; ?> Nouvelle(s)</span>
                        <?php endif; ?>
                    </p>
                    <p class="card-text"><strong>Statut de l'offre:</strong> <?php echo $offre['est_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>'; ?></p>
                </div>
                <div class="card-actions">
                    <a href="<?php echo SITE_URL; ?>entreprise.php?view=candidatures_offre&id_offre=<?php echo $offre['id']; ?>" class="btn btn-info btn-sm" title="Voir les candidatures"><i class="fas fa-users"></i> Candidatures</a>
                    <a href="<?php echo SITE_URL; ?>entreprise.php?view=form_offre&mode=modifier&id_offre=<?php echo $offre['id']; ?>" class="btn btn-secondary btn-sm" title="Modifier"><i class="fas fa-edit"></i></a>
                    <form action="<?php echo SITE_URL; ?>entreprise.php?view=mes_offres" method="POST" style="display:inline;" class="confirm-delete-offre">
                        <input type="hidden" name="action" value="supprimer_offre">
                        <input type="hidden" name="id_offre" value="<?php echo $offre['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm" title="Supprimer"><i class="fas fa-trash-alt"></i></button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Vous n'avez publié aucune offre pour le moment.
    </div>
<?php endif; ?>