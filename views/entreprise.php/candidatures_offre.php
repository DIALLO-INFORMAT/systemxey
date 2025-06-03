<?php
// views/entreprise/candidatures_offre.php
global $pdo, $user_id_entreprise, $offre_a_modifier; 

if (!$offre_a_modifier) { 
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

// Labels et classes pour les statuts (décisions)
$decision_labels = [
    'postulee' => ['label' => 'Nouvelle', 'class' => 'badge-info', 'icon' => 'fa-envelope'],
    'vue_par_entreprise' => ['label' => 'Vue', 'class' => 'badge-primary', 'icon' => 'fa-eye'],
    'en_cours_analyse' => ['label' => 'En Analyse', 'class' => 'badge-warning', 'icon' => 'fa-hourglass-half'],
    'entretien_planifie' => ['label' => 'Entretien Planifié', 'class' => 'badge-success', 'icon' => 'fa-calendar-check'],
    'acceptee' => ['label' => 'Acceptée', 'class' => 'badge-success', 'icon' => 'fa-thumbs-up'],
    'refusee' => ['label' => 'Refusée', 'class' => 'badge-danger', 'icon' => 'fa-thumbs-down']
];
// Options pour le select de décision
$options_decision_select = [
    '' => '--- Prendre une décision ---', // Option par défaut
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
        <table class="table table-striped"> <!-- Ajout de table-striped pour meilleure lisibilité -->
            <thead class="thead-light"> <!-- En-tête plus distinct -->
                <tr>
                    <th>Étudiant</th>
                    <th>Date</th>
                    <th>Documents</th>
                    <th>Statut Actuel</th>
                    <th style="width:35%;">Décision & Message</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($candidatures_list as $candidature): ?>
                    <tr>
                        <td>
                            <strong><a href="<?php echo SITE_URL; ?>entreprise.php?view=profil_detail&id_etudiant=<?php echo $candidature['id_etudiant_utilisateur']; ?>" title="Voir profil complet de <?php echo esc_html($candidature['nom_etudiant']); ?>"><?php echo esc_html($candidature['nom_etudiant']); ?></a></strong>
                            <br><small class="text-muted"><?php echo esc_html($candidature['titre_profil_etudiant'] ?? 'Profil non titré'); ?></small>
                            <br><small><a href="mailto:<?php echo esc_html($candidature['email_etudiant']); ?>" title="Envoyer un email"><i class="fas fa-envelope"></i> <?php echo esc_html($candidature['email_etudiant']); ?></a></small>
                        </td>
                        <td style="white-space:nowrap;"><?php echo date('d/m/Y H:i', strtotime($candidature['date_candidature'])); ?></td>
                        <td>
                            <a href="<?php echo esc_html($candidature['lien_cv_candidature']); ?>" target="_blank" class="btn btn-sm btn-outline-primary mb-1" title="Voir CV"><i class="fas fa-file-pdf"></i> CV</a>
                            <?php if (!empty($candidature['lien_lm_candidature'])): ?>
                                <a href="<?php echo esc_html($candidature['lien_lm_candidature']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Voir LM"><i class="fas fa-file-alt"></i> LM</a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?php echo $decision_labels[$candidature['statut_candidature']]['class'] ?? 'badge-secondary'; ?>" style="font-size:0.9em;">
                                <i class="fas <?php echo $decision_labels[$candidature['statut_candidature']]['icon'] ?? 'fa-question-circle'; ?>"></i>
                                <?php echo $decision_labels[$candidature['statut_candidature']]['label'] ?? ucfirst(esc_html($candidature['statut_candidature'])); ?>
                            </span>
                            <?php if ($candidature['date_decision_entreprise']): ?>
                                <br><small class="text-muted">Décision le: <?php echo date('d/m/y', strtotime($candidature['date_decision_entreprise'])); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="actions-column">
                            <form action="<?php echo SITE_URL; ?>entreprise.php" method="POST">
                                <input type="hidden" name="action" value="update_statut_candidature">
                                <input type="hidden" name="id_candidature" value="<?php echo $candidature['id']; ?>">
                                <input type="hidden" name="id_offre_pour_redirect" value="<?php echo $id_offre_courante; ?>">
                                
                                <div class="form-group mb-2">
                                    <label for="decision_statut_<?php echo $candidature['id']; ?>" class="sr-only">Décision</label> <!-- sr-only pour accessibilité si pas de label visible -->
                                    <select id="decision_statut_<?php echo $candidature['id']; ?>" name="decision_statut" class="form-control form-control-sm" style="padding:5px; font-size:0.9em; width:100%;">
                                        <?php foreach($options_decision_select as $val => $label): ?>
                                            <option value="<?php echo $val; ?>" <?php echo ($val === $candidature['statut_candidature']) ? 'selected' : ''; ?>>
                                                <?php echo $label; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group mb-2">
                                     <label for="message_pour_candidat_<?php echo $candidature['id']; ?>" class="sr-only">Message</label>
                                     <textarea id="message_pour_candidat_<?php echo $candidature['id']; ?>" name="message_pour_candidat" rows="3" class="form-control form-control-sm" style="font-size:0.9em; padding:5px; width:100%;" placeholder="Message personnalisé au candidat (ex: détails entretien, raisons refus)..."><?php echo esc_html($candidature['decision_entreprise_commentaire'] ?? ''); ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm" style="width:100%;"><i class="fas fa-paper-plane"></i> Envoyer Décision/Message</button>
                            </form>
                        </td>
                    </tr>
                    <?php if(!empty($candidature['message_candidature'])): // Message initial de l'étudiant ?>
                    <tr class="bg-light"> <!-- Ligne légèrement différente pour le message -->
                        <td colspan="5" style="padding:10px 15px; border-bottom: 2px solid #ddd;">
                            <small><strong><i class="fas fa-comment-dots text-primary"></i> Message de l'étudiant :</strong> <?php echo nl2br(esc_html($candidature['message_candidature'])); ?></small>
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