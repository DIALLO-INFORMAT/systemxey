<?php
// views/entreprise/dashboard.php
global $pdo, $user_id_entreprise;

// Statistiques pour l'entreprise
$stmt_offres_count = $pdo->prepare("SELECT COUNT(*) FROM offres_emploi WHERE id_entreprise_utilisateur = ?");
$stmt_offres_count->execute([$user_id_entreprise]);
$offres_publiees_count = $stmt_offres_count->fetchColumn();

$stmt_candidatures_recues = $pdo->prepare(
    "SELECT COUNT(c.id) FROM candidatures c
     JOIN offres_emploi o ON c.id_offre = o.id
     WHERE o.id_entreprise_utilisateur = ?"
);
$stmt_candidatures_recues->execute([$user_id_entreprise]);
$candidatures_recues_count = $stmt_candidatures_recues->fetchColumn();

$stmt_candidatures_non_vues = $pdo->prepare(
    "SELECT COUNT(c.id) FROM candidatures c
     JOIN offres_emploi o ON c.id_offre = o.id
     WHERE o.id_entreprise_utilisateur = ? AND c.statut_candidature = 'postulee'" // 'postulee' signifie non encore vue
);
$stmt_candidatures_non_vues->execute([$user_id_entreprise]);
$candidatures_non_vues_count = $stmt_candidatures_non_vues->fetchColumn();

?>
<h2><i class="fas fa-chart-line"></i> Vue d'ensemble Entreprise</h2>

<div class="card-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); margin-bottom:30px;">
    <div class="card" style="background-color: var(--primary-color); color:white; text-align:center;">
        <div class="card-content">
            <i class="fas fa-list-alt fa-3x" style="margin-bottom:10px;"></i>
            <h3 style="font-size:2em;"><?php echo $offres_publiees_count; ?></h3>
            <p>Offres Publiées (Total)</p>
            <a href="<?php echo SITE_URL; ?>entreprise.php?view=mes_offres" class="btn btn-sm" style="background:rgba(255,255,255,0.2); color:white; margin-top:10px;">Gérer mes offres</a>
        </div>
    </div>
    <div class="card" style="background-color: var(--accent-color); color:white; text-align:center;">
        <div class="card-content">
            <i class="fas fa-users fa-3x" style="margin-bottom:10px;"></i>
            <h3 style="font-size:2em;"><?php echo $candidatures_recues_count; ?></h3>
            <p>Candidatures Reçues (Total)</p>
        </div>
    </div>
     <div class="card" style="background-color: #ff8c00; color:white; text-align:center;"> <!-- Orange pour nouvelles candidatures -->
        <div class="card-content">
            <i class="fas fa-envelope-open-text fa-3x" style="margin-bottom:10px;"></i>
            <h3 style="font-size:2em;"><?php echo $candidatures_non_vues_count; ?></h3>
            <p>Nouvelles Candidatures</p>
             <?php if($candidatures_non_vues_count > 0): ?>
            <a href="<?php echo SITE_URL; ?>entreprise.php?view=mes_offres" class="btn btn-sm" style="background:rgba(255,255,255,0.2); color:white; margin-top:10px;">Voir les candidatures</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<h3><i class="fas fa-bullhorn"></i> Actions rapides</h3>
<p>
    <a href="<?php echo SITE_URL; ?>entreprise.php?view=form_offre&mode=creer" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Publier une nouvelle offre</a>
    <a href="<?php echo SITE_URL; ?>entreprise.php?view=rechercher_profils" class="btn btn-secondary" style="margin-left:10px;"><i class="fas fa-search"></i> Rechercher des profils étudiants</a>
</p>

<div style="margin-top:30px;">
    <h4><i class="fas fa-info-circle"></i> Rappels importants :</h4>
    <ul>
        <li>Assurez-vous que le <a href="<?php echo SITE_URL; ?>entreprise.php?view=gerer_profil_entreprise">profil de votre entreprise</a> est complet et attractif.</li>
        <li>Les offres que vous publiez sont soumises à la validation de nos administrateurs avant d'être visibles publiquement.</li>
        <li>Répondez rapidement aux candidatures pour une meilleure expérience candidat.</li>
    </ul>
</div>
