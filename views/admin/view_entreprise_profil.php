<?php
// views/admin/view_entreprise_profil.php
global $pdo, $item_id; // $item_id sera l'ID utilisateur de l'entreprise

if (!$item_id) {
    set_flash_message("ID de l'entreprise manquant.", "error");
    redirect("admin.php?view=manage_entreprises");
}

// Récupérer les informations du profil entreprise et de l'utilisateur associé
$stmt_profil_ent_admin = $pdo->prepare(
    "SELECT e.*, u.email AS email_utilisateur, u.nom_utilisateur, u.date_inscription, u.est_actif AS compte_utilisateur_actif
     FROM entreprises e
     JOIN utilisateurs u ON e.id_utilisateur = u.id
     WHERE e.id_utilisateur = ?"
);
$stmt_profil_ent_admin->execute([$item_id]);
$profil_entreprise_admin = $stmt_profil_ent_admin->fetch();

if (!$profil_entreprise_admin) {
    set_flash_message("Profil entreprise non trouvé ou ID utilisateur invalide.", "error");
    redirect("admin.php?view=manage_entreprises");
}

// Récupérer les offres de cette entreprise (pour un aperçu)
$stmt_offres_entreprise = $pdo->prepare(
    "SELECT id, titre_poste, statut_validation_admin, est_active 
     FROM offres_emploi 
     WHERE id_entreprise_utilisateur = ? 
     ORDER BY date_publication DESC LIMIT 5" // 5 dernières offres
);
$stmt_offres_entreprise->execute([$item_id]);
$offres_de_lentreprise = $stmt_offres_entreprise->fetchAll();

$statut_validation_labels_offre = [
    'en_attente' => ['label' => 'En attente', 'class' => 'badge-en-attente'],
    'validee' => ['label' => 'Validée', 'class' => 'badge-validee'],
    'refusee' => ['label' => 'Refusée', 'class' => 'badge-refusee'],
];

?>
<h2><i class="fas fa-building-magnifying-glass"></i> Profil de l'Entreprise : <?php echo esc_html($profil_entreprise_admin['nom_entreprise']); ?></h2>
<a href="<?php echo SITE_URL; ?>admin.php?view=manage_entreprises" class="btn btn-secondary btn-sm" style="margin-bottom:20px;"><i class="fas fa-arrow-left"></i> Retour à la liste des entreprises</a>

<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><h3 style="margin:0;">Informations Générales de l'Entreprise</h3></div>
    <div class="card-content">
        <div style="display:flex; flex-wrap:wrap; gap:20px;">
            <div style="flex:1; text-align:center;">
                <?php if(!empty($profil_entreprise_admin['lien_logo'])): ?>
                    <img src="<?php echo esc_html($profil_entreprise_admin['lien_logo']); ?>" alt="Logo <?php echo esc_html($profil_entreprise_admin['nom_entreprise']); ?>" style="max-width:180px; max-height:120px; margin-bottom:15px; border:1px solid #eee; padding:5px; border-radius:var(--border-radius); background:#f9f9f9;">
                <?php else: ?>
                    <div style="width:180px; height:120px; background:#f0f0f0; color:#aaa; display:flex; align-items:center; justify-content:center; margin:0 auto 15px auto; border-radius:var(--border-radius);">Aucun Logo</div>
                <?php endif; ?>
            </div>
            <div style="flex:2;">
                <p><strong>Nom de l'entreprise :</strong> <?php echo esc_html($profil_entreprise_admin['nom_entreprise']); ?></p>
                <p><strong>Secteur d'activité :</strong> <?php echo esc_html($profil_entreprise_admin['secteur_activite'] ?? 'N/A'); ?></p>
                <p><strong>Adresse / Localisation :</strong> <?php echo esc_html($profil_entreprise_admin['adresse'] ?? 'N/A'); ?></p>
                <p><strong>Site Web :</strong> 
                    <?php if(!empty($profil_entreprise_admin['site_web_url'])): ?>
                        <a href="<?php echo esc_html($profil_entreprise_admin['site_web_url']); ?>" target="_blank"><?php echo esc_html($profil_entreprise_admin['site_web_url']); ?></a>
                    <?php else: echo 'N/A'; endif; ?>
                </p>
                <p><strong>Profil Validé par Admin :</strong> 
                    <span class="badge badge-<?php echo $profil_entreprise_admin['est_valide_admin'] ? 'success' : 'warning'; ?>">
                        <?php echo $profil_entreprise_admin['est_valide_admin'] ? 'Oui' : 'En attente/Non'; ?>
                    </span>
                    <!-- Ajouter action de validation/dévalidation si nécessaire -->
                </p>
            </div>
        </div>
        <?php if(!empty($profil_entreprise_admin['description_entreprise'])): ?>
        <h4 style="margin-top:20px; margin-bottom:10px; color:var(--dark-gray);">Description de l'entreprise :</h4>
        <div style="background:#f9f9f9; padding:15px; border-radius:var(--border-radius); line-height:1.7;">
            <?php echo nl2br(esc_html($profil_entreprise_admin['description_entreprise'])); ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><h3 style="margin:0;">Informations du Compte Utilisateur Associé</h3></div>
    <div class="card-content">
        <p><strong>ID Utilisateur :</strong> <?php echo $profil_entreprise_admin['id_utilisateur']; ?></p>
        <p><strong>Email du compte :</strong> <?php echo esc_html($profil_entreprise_admin['email_utilisateur']); ?></p>
        <p><strong>Nom d'utilisateur :</strong> <?php echo esc_html($profil_entreprise_admin['nom_utilisateur'] ?? 'N/A'); ?></p>
        <p><strong>Date d'inscription :</strong> <?php echo date('d/m/Y H:i', strtotime($profil_entreprise_admin['date_inscription'])); ?></p>
        <p><strong>Statut du compte :</strong> 
            <span class="badge badge-<?php echo $profil_entreprise_admin['compte_utilisateur_actif'] ? 'actif' : 'inactif'; ?>">
                <?php echo $profil_entreprise_admin['compte_utilisateur_actif'] ? 'Actif' : 'Inactif'; ?>
            </span>
        </p>
        <div style="margin-top:15px;">
            <a href="<?php echo SITE_URL; ?>admin.php?view=manage_users&q_user=<?php echo urlencode($profil_entreprise_admin['email_utilisateur']); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-user-cog"></i> Gérer ce compte utilisateur</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 style="margin:0;">Dernières Offres Publiées (Aperçu)</h3></div>
    <div class="card-content">
        <?php if(count($offres_de_lentreprise) > 0): ?>
            <ul style="list-style:none; padding-left:0;">
            <?php foreach($offres_de_lentreprise as $offre_ent): ?>
                <li style="padding:8px 0; border-bottom:1px dashed #eee;">
                    <a href="<?php echo SITE_URL; ?>admin.php?view=view_offre_admin&id=<?php echo $offre_ent['id']; ?>">
                        <?php echo esc_html($offre_ent['titre_poste']); ?>
                    </a> 
                    <span class="badge <?php echo $statut_validation_labels_offre[$offre_ent['statut_validation_admin']]['class'] ?? 'badge-secondary'; ?>" style="margin-left:10px;">
                        <?php echo $statut_validation_labels_offre[$offre_ent['statut_validation_admin']]['label'] ?? ucfirst($offre_ent['statut_validation_admin']); ?>
                    </span>
                    <span class="badge badge-<?php echo $offre_ent['est_active'] ? 'success' : 'secondary'; ?>" style="margin-left:5px;">
                        <?php echo $offre_ent['est_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                </li>
            <?php endforeach; ?>
            </ul>
            <a href="<?php echo SITE_URL; ?>admin.php?view=manage_offres&entreprise_offre=<?php echo urlencode($profil_entreprise_admin['nom_entreprise']); ?>" class="btn btn-sm btn-info" style="margin-top:10px;"><i class="fas fa-list"></i> Voir toutes les offres de cette entreprise</a>
        <?php else: ?>
            <p>Cette entreprise n'a pas encore publié d'offres.</p>
        <?php endif; ?>
    </div>
</div>

<div style="margin-top:30px;">
    <a href="<?php echo SITE_URL; ?>entreprise.php?view=gerer_profil_entreprise&id_user_admin_edit=<?php echo $profil_entreprise_admin['id_utilisateur']; ?>" target="_blank" class="btn btn-secondary"><i class="fas fa-edit"></i> Modifier ce profil (en tant qu'entreprise)</a>
    <!-- Autres actions admin spécifiques si besoin -->
</div>