<?php
// views/etudiant/mes_candidatures.php
global $pdo, $user_id;

$stmt_candidatures = $pdo->prepare(
    "SELECT c.*, o.titre_poste, e.nom_entreprise 
     FROM candidatures c
     JOIN offres_emploi o ON c.id_offre = o.id
     JOIN entreprises e ON o.id_entreprise_utilisateur = e.id_utilisateur
     WHERE c.id_etudiant_utilisateur = ?
     ORDER BY c.date_candidature DESC"
);
$stmt_candidatures->execute([$user_id]);
$candidatures = $stmt_candidatures->fetchAll();

// Utiliser les mêmes labels que pour l'entreprise pour la cohérence
$decision_labels_etu = [
    'postulee' => ['label' => 'Candidature Envoyée', 'class' => 'badge-info', 'icon' => 'fa-paper-plane'],
    'vue_par_entreprise' => ['label' => 'Vue par l\'Entreprise', 'class' => 'badge-primary', 'icon' => 'fa-eye'],
    'en_cours_analyse' => ['label' => 'En Cours d\'Analyse', 'class' => 'badge-warning', 'icon' => 'fa-hourglass-half'],
    'entretien_planifie' => ['label' => 'Entretien Planifié', 'class' => 'badge-success', 'icon' => 'fa-calendar-check'],
    'acceptee' => ['label' => 'Candidature Acceptée !', 'class' => 'badge-success', 'icon' => 'fa-thumbs-up'],
    'refusee' => ['label' => 'Candidature Refusée', 'class' => 'badge-danger', 'icon' => 'fa-thumbs-down']
];
?>
<h2><i class="fas fa-file-alt"></i> Mes Candidatures</h2>

<?php if (count($candidatures) > 0): ?>
    <div class="card-grid">
        <?php foreach ($candidatures as $candidature): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title" style="margin-bottom:0;"><?php echo esc_html($candidature['titre_poste']); ?></h3>
                </div>
                <div class="card-content">
                    <p class="card-text"><strong>Entreprise:</strong> <?php echo esc_html($candidature['nom_entreprise']); ?></p>
                    <p class="card-text"><strong>Date de candidature:</strong> <?php echo date('d/m/Y H:i', strtotime($candidature['date_candidature'])); ?></p>
                    <p class="card-text"><strong>Statut:</strong> 
                        <span class="badge <?php echo $decision_labels_etu[$candidature['statut_candidature']]['class'] ?? 'badge-secondary'; ?>" style="font-size:0.95em;">
                            <i class="fas <?php echo $decision_labels_etu[$candidature['statut_candidature']]['icon'] ?? 'fa-question-circle'; ?>"></i>
                            <?php echo $decision_labels_etu[$candidature['statut_candidature']]['label'] ?? ucfirst(esc_html($candidature['statut_candidature'])); ?>
                        </span>
                    </p>
                    <?php if (!empty($candidature['decision_entreprise_commentaire'])): ?>
                        <div style="margin-top:10px; padding:10px; background-color: #f9f9f9; border-left: 3px solid var(--primary-color); border-radius:var(--border-radius);">
                            <p class="card-text" style="font-weight:bold; margin-bottom:5px;"><i class="fas fa-comment-alt text-primary"></i> Message de l'entreprise :</p>
                            <p class="card-text" style="font-size:0.9em; white-space: pre-wrap;"><?php echo esc_html($candidature['decision_entreprise_commentaire']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-actions" style="text-align:left;">
                    <a href="<?php echo SITE_URL; ?>index.php?view=offre_detail&id=<?php echo $candidature['id_offre']; ?>" class="btn btn-secondary btn-sm"><i class="fas fa-eye"></i> Revoir l'offre</a>
                    <!-- Possibilité d'ajouter "Retirer candidature" si statut le permet -->
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Vous n'avez soumis aucune candidature pour le moment.
        <a href="<?php echo SITE_URL; ?>index.php?view=offres_emploi" class="btn btn-primary btn-sm" style="margin-left:15px;">Trouver des offres</a>
    </div>
<?php endif; ?>