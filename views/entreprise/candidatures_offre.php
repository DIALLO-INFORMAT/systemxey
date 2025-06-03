<?php
// views/entreprise/candidatures_offre.php
global $pdo, $user_id_entreprise, $offre_a_modifier; // $offre_a_modifier contient les infos de l'offre si ID valide

if (!$offre_a_modifier) { // $offre_a_modifier est chargé dans entreprise.php si id_offre est présent et valide
    set_flash_message("Offre non trouvée ou accès non autorisé.", "error");
    redirect("entreprise.php?view=mes_offres");
}

$id_offre_courante = $offre_a_modifier['id'];

$stmt_candidatures = $pdo->prepare(
    "SELECT c.*, pe.nom_complet AS nom_etudiant, pe.titre_profil AS titre_profil_etudiant, u.email AS email_etudiant
     FROM candidatures c
     JOIN profils_etudiants pe ON c.id_etudiant_utilisateur = pe.id_utilisateur
     JOIN utilisateurs u ON pe.id_utilisateur = u.id
     WHERE c.id_offre = ?
     ORDER BY c.date_candidature DESC"
);
$stmt_candidatures->execute([$id_offre_courante]);
$candidatures_list = $stmt_candidatures->fetchAll();

$statut_labels_candidature = [
    'postulee' => ['label' => 'Nouvelle', 'class' => 'badge-info', 'icon' => 'fa-envelope'],
    'vue_par_entreprise' => ['label' => 'Vue', 'class' => 'badge-primary', 'icon' => 'fa-eye'],
    'en_cours_analyse' => ['label' => 'En analyse', 'class' => 'badge-warning', 'icon' => 'fa-hourglass-half'],
    'acceptee' => ['label' => 'Acceptée', 'class' => 'badge-success', 'icon' => 'fa-check-circle'],
    'refusee' => ['label' => 'Refusée', 'class' => 'badge-danger', 'icon' => 'fa-times-circle'],
    'entretien_planifie' => ['label' => 'Entretien', 'class' => 'badge-success', 'icon' => 'fa-calendar-check']
];
$options_statut_select = [
    'vue_par_entreprise' => 'Marquer comme Vue',
    'en_cours_analyse' => 'Mettre En cours d\'analyse',
    'entretien_planifie' => 'Planifier un Entretien',
    'acceptee' => 'Accepter la candidature',
    'refusee' => 'Refuser la candidature'
];
?>
<h2><i class="fas fa-users"></i> Candidatures pour : <?php echo esc_html($offre_a_modifier['titre_poste']); ?></h2>
<a href="<?php echo SITE_URL; ?>entreprise.php?view=mes_offres" class="btn btn-secondary btn-sm" style="margin-bottom:20px;"><i class="fas fa-arrow-left"></i> Retour à Mes Offres</a>

<?php if (count($candidatures_list) > 0): ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Étudiant</th>
                    <th>Date Candidature</th>
                    <th>CV</th>
                    <th>LM</th>
                    <th>Statut Actuel</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($candidatures_list as $candidature): ?>
                    <tr>
                        <td>
                            <strong><a href="<?php echo SITE_URL; ?>entreprise.php?view=profil_detail&id_etudiant=<?php echo $candidature['id_etudiant_utilisateur']; ?>" title="Voir profil complet"><?php echo esc_html($candidature['nom_etudiant']); ?></a></strong><br>
                            <small><?php echo esc_html($candidature['titre_profil_etudiant'] ?? 'N/A'); ?></small><br>
                            <small><a href="mailto:<?php echo esc_html($candidature['email_etudiant']); ?>"><?php echo esc_html($candidature['email_etudiant']); ?></a></small>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($candidature['date_candidature'])); ?></td>
                        <td>
                            <a href="<?php echo esc_html($candidature['lien_cv_candidature']); ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Voir CV"><i class="fas fa-file-pdf"></i> CV</a>
                        </td>
                        <td>
                            <?php if (!empty($candidature['lien_lm_candidature'])): ?>
                                <a href="<?php echo esc_html($candidature['lien_lm_candidature']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Voir LM"><i class="fas fa-file-alt"></i> LM</a>
                            <?php else: echo 'N/A'; endif; ?>
                        </td>
                        <td>
                            <span class="badge <?php echo $statut_labels_candidature[$candidature['statut_candidature']]['class'] ?? 'badge-secondary'; ?>">
                                <i class="fas <?php echo $statut_labels_candidature[$candidature['statut_candidature']]['icon'] ?? 'fa-question-circle'; ?>"></i>
                                <?php echo $statut_labels_candidature[$candidature['statut_candidature']]['label'] ?? ucfirst($candidature['statut_candidature']); ?>
                            </span>
                             <?php if (!empty($candidature['decision_entreprise_commentaire'])): ?>
                                <small style="display:block; font-style:italic; margin-top:5px; color:var(--secondary-color);" title="Commentaire : <?php echo esc_html($candidature['decision_entreprise_commentaire']); ?>">
                                    <i class="fas fa-comment-dots"></i> Note ajoutée
                                </small>
                            <?php endif; ?>
                        </td>
                        <td class="actions-column">
                            <form action="<?php echo SITE_URL; ?>entreprise.php" method="POST">
                                <input type="hidden" name="action" value="update_statut_candidature">
                                <input type="hidden" name="id_candidature" value="<?php echo $candidature['id']; ?>">
                                <input type="hidden" name="id_offre_pour_redirect" value="<?php echo $id_offre_courante; ?>">
                                <div class="form-group" style="margin-bottom:5px;">
                                    <select name="nouveau_statut" class="form-control form-control-sm" style="padding:5px; font-size:0.85em; width:100%;">
                                        <option value="">Changer statut...</option>
                                        <?php foreach($options_statut_select as $val => $label): ?>
                                            <?php if ($val !== $candidature['statut_candidature']): // Ne pas proposer le statut actuel ?>
                                            <option value="<?php echo $val; ?>"><?php echo $label; ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-bottom:5px;">
                                     <textarea name="commentaire_decision" rows="2" class="form-control form-control-sm" style="font-size:0.85em; padding:5px; width:100%;" placeholder="Ajouter un commentaire (optionnel)"><?php echo esc_html($candidature['decision_entreprise_commentaire'] ?? ''); ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm" style="width:100%;"><i class="fas fa-sync-alt"></i> Mettre à jour</button>
                            </form>
                        </td>
                    </tr>
                    <?php if(!empty($candidature['message_candidature'])): ?>
                    <tr>
                        <td colspan="6" style="background-color:#f9f9f9; padding:10px 15px; border-bottom: 2px solid #ddd;">
                            <small><strong><i class="fas fa-comment-alt"></i> Message du candidat :</strong> <?php echo nl2br(esc_html($candidature['message_candidature'])); ?></small>
                        </td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Aucune candidature reçue pour cette offre pour le moment.
    </div>
<?php endif; ?>