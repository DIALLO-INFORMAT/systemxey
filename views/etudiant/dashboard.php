<?php
// views/etudiant/dashboard.php
global $pdo, $user_id, $profil_etudiant; // $profil_etudiant est chargé si nécessaire
if (!$profil_etudiant) { // S'assurer qu'il est chargé
    $stmt_profil = $pdo->prepare("SELECT * FROM profils_etudiants WHERE id_utilisateur = ?");
    $stmt_profil->execute([$user_id]);
    $profil_etudiant = $stmt_profil->fetch();
}

// Statistiques pour l'étudiant
$stmt_candidatures_count = $pdo->prepare("SELECT COUNT(*) FROM candidatures WHERE id_etudiant_utilisateur = ?");
$stmt_candidatures_count->execute([$user_id]);
$candidatures_count = $stmt_candidatures_count->fetchColumn();

$stmt_favorites_count = $pdo->prepare("SELECT COUNT(*) FROM offres_favorites_etudiants WHERE id_etudiant_utilisateur = ?");
$stmt_favorites_count->execute([$user_id]);
$favorites_count = $stmt_favorites_count->fetchColumn();

// Notifications (simulées pour l'instant)
$notifications_simulees = [
    ['type' => 'info', 'message' => 'Bienvenue sur votre tableau de bord, ' . esc_html($_SESSION['user_nom_utilisateur'] ?? '') . '! Complétez votre profil pour augmenter vos chances.', 'date' => date('Y-m-d H:i:s', strtotime('-1 hour'))],
    // ['type' => 'success', 'message' => 'Votre candidature pour "Développeur Web" a été vue par SystemXey Corp.', 'date' => date('Y-m-d H:i:s', strtotime('-2 days'))],
];
if (empty($profil_etudiant['lien_cv']) || empty($profil_etudiant['description_personnelle'])) {
    $notifications_simulees[] = ['type' => 'warning', 'message' => 'Pensez à ajouter votre CV et une description à votre profil.', 'date' => date('Y-m-d H:i:s')];
}

?>
<h2><i class="fas fa-tachometer-alt"></i> Vue d'ensemble</h2>

<div class="card-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); margin-bottom:30px;">
    <div class="card" style="background-color: var(--primary-color); color:white; text-align:center;">
        <div class="card-content">
            <i class="fas fa-file-alt fa-3x" style="margin-bottom:10px;"></i>
            <h3 style="font-size:2em;"><?php echo $candidatures_count; ?></h3>
            <p>Candidatures Envoyées</p>
            <a href="<?php echo SITE_URL; ?>etudiant.php?view=mes_candidatures" class="btn btn-sm" style="background:rgba(255,255,255,0.2); color:white; margin-top:10px;">Voir mes candidatures</a>
        </div>
    </div>
    <div class="card" style="background-color: var(--accent-color); color:white; text-align:center;">
        <div class="card-content">
            <i class="fas fa-heart fa-3x" style="margin-bottom:10px;"></i>
            <h3 style="font-size:2em;"><?php echo $favorites_count; ?></h3>
            <p>Offres Favorites</p>
            <a href="<?php echo SITE_URL; ?>etudiant.php?view=offres_favorites" class="btn btn-sm" style="background:rgba(255,255,255,0.2); color:white; margin-top:10px;">Voir mes favoris</a>
        </div>
    </div>
     <div class="card" style="background-color: var(--secondary-color); color:white; text-align:center;">
        <div class="card-content">
            <i class="fas fa-user-check fa-3x" style="margin-bottom:10px;"></i>
            <?php 
                $completion = 0;
                if ($profil_etudiant) {
                    if(!empty($profil_etudiant['nom_complet'])) $completion += 10;
                    if(!empty($profil_etudiant['titre_profil'])) $completion += 10;
                    if(!empty($profil_etudiant['lien_photo'])) $completion += 10;
                    if(!empty($profil_etudiant['lien_cv'])) $completion += 15;
                    if(!empty($profil_etudiant['description_personnelle'])) $completion += 15;
                    if(!empty($profil_etudiant['competences_cles'])) $completion += 10;
                    if(!empty($profil_etudiant['domaine_etudes'])) $completion += 10;
                    if(!empty($profil_etudiant['niveau_etudes'])) $completion += 10;
                    if(!empty($profil_etudiant['disponibilite'])) $completion += 5;
                    if(!empty($profil_etudiant['telephone1'])) $completion += 5;
                }
                $completion = min(100, $completion); // Plafonner à 100
            ?>
            <h3 style="font-size:2em;"><?php echo $completion; ?>%</h3>
            <p>Profil Complété</p>
            <a href="<?php echo SITE_URL; ?>etudiant.php?view=gerer_profil" class="btn btn-sm" style="background:rgba(255,255,255,0.2); color:white; margin-top:10px;">Compléter mon profil</a>
        </div>
    </div>
</div>

<h3><i class="fas fa-bell"></i> Notifications Récentes</h3>
<?php if (!empty($notifications_simulees)): ?>
    <?php foreach ($notifications_simulees as $notif): ?>
        <div class="alert alert-<?php echo $notif['type']; ?>" role="alert">
            <strong><?php echo ucfirst($notif['type']); ?>!</strong> <?php echo $notif['message']; ?>
            <small class="float-right text-muted"><?php echo date('d/m/Y H:i', strtotime($notif['date'])); ?></small>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>Aucune nouvelle notification.</p>
<?php endif; ?>

<div style="margin-top:30px;">
    <h4><i class="fas fa-lightbulb"></i> Actions rapides :</h4>
    <a href="<?php echo SITE_URL; ?>etudiant.php?view=gerer_profil" class="btn btn-primary"><i class="fas fa-edit"></i> Mettre à jour mon profil</a>
    <a href="<?php echo SITE_URL; ?>index.php?view=offres_emploi" class="btn btn-secondary" style="margin-left:10px;"><i class="fas fa-search"></i> Voir les nouvelles offres</a>
</div>

