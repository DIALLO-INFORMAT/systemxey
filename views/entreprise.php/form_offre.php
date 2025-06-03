<?php
// views/entreprise/form_offre.php
global $pdo, $user_id_entreprise, $offre_a_modifier, $form_data_entreprise; // $form_data_entreprise est maintenant disponible

$mode = $_GET['mode'] ?? 'creer'; 
$form_action_handler = ($mode === 'modifier' && isset($offre_a_modifier['id'])) ? 'modifier_offre' : 'creer_offre'; // Renommé pour éviter conflit
$form_title_text = ($mode === 'modifier' && isset($offre_a_modifier['id'])) ? 'Modifier l\'Offre d\'Emploi' : 'Publier une Nouvelle Offre d\'Emploi'; // Renommé

// Pré-remplir les champs
// Priorité : Données POST précédemment soumises (en cas d'erreur de validation)
// Ensuite : Données de l'offre à modifier (si en mode édition)
// Enfin : Valeurs par défaut (chaînes vides ou 1 pour checkbox)
$titre_val = $form_data_entreprise['titre_poste'] ?? ($offre_a_modifier['titre_poste'] ?? '');
$desc_val = $form_data_entreprise['description_poste'] ?? ($offre_a_modifier['description_poste'] ?? '');
$type_val = $form_data_entreprise['type_contrat'] ?? ($offre_a_modifier['type_contrat'] ?? '');
$lieu_val = $form_data_entreprise['lieu'] ?? ($offre_a_modifier['lieu'] ?? '');
$comp_val = $form_data_entreprise['competences_requises'] ?? ($offre_a_modifier['competences_requises'] ?? '');
$date_lim_val = $form_data_entreprise['date_limite_candidature'] ?? ($offre_a_modifier['date_limite_candidature'] ?? '');
// Pour la checkbox, la logique est un peu différente :
// Si _form_data['est_active_offre'] existe, on l'utilise.
// Sinon, si on est en mode modification, on utilise $offre_a_modifier['est_active'].
// Sinon (création sans erreur précédente), on met à 1 (checked) par défaut.
if (isset($form_data_entreprise['est_active_offre'])) {
    $active_val = $form_data_entreprise['est_active_offre'];
} elseif (isset($offre_a_modifier['est_active'])) {
    $active_val = $offre_a_modifier['est_active'];
} else {
    $active_val = 1; // Par défaut cochée pour la création
}

?>
<h2><i class="fas <?php echo ($mode === 'modifier') ? 'fa-edit' : 'fa-plus-circle'; ?>"></i> <?php echo $form_title_text; ?></h2>
<p>Remplissez les détails de votre offre. Elle sera soumise à validation avant publication.</p>

<form action="<?php echo SITE_URL; ?>entreprise.php" method="POST" class="form-container">
    <input type="hidden" name="action" value="<?php echo $form_action_handler; ?>">
    <?php if ($mode === 'modifier' && isset($offre_a_modifier['id'])): ?>
        <input type="hidden" name="id_offre" value="<?php echo $offre_a_modifier['id']; ?>">
    <?php endif; ?>

    <!-- Les champs du formulaire utilisent maintenant $titre_val, $desc_val, etc. -->
    <div class="form-group">
        <label for="titre_poste">Titre du Poste *</label>
        <input type="text" id="titre_poste" name="titre_poste" value="<?php echo esc_html($titre_val); ?>" required>
    </div>
    <div class="form-group">
        <label for="description_poste">Description du Poste et Missions *</label>
        <textarea id="description_poste" name="description_poste" rows="8" required><?php echo esc_html($desc_val); ?></textarea>
    </div>

    <div class="row" style="display:flex; flex-wrap:wrap; gap:20px;">
        <div style="flex:1; min-width:250px;">
            <div class="form-group">
                <label for="type_contrat">Type de Contrat *</label>
                <select id="type_contrat" name="type_contrat" required>
                    <option value="">Sélectionnez...</option>
                    <option value="CDI" <?php echo ($type_val === 'CDI') ? 'selected' : ''; ?>>CDI</option>
                    <option value="CDD" <?php echo ($type_val === 'CDD') ? 'selected' : ''; ?>>CDD</option>
                    <option value="Stage" <?php echo ($type_val === 'Stage') ? 'selected' : ''; ?>>Stage</option>
                    <option value="Alternance" <?php echo ($type_val === 'Alternance') ? 'selected' : ''; ?>>Alternance</option>
                    <option value="Freelance" <?php echo ($type_val === 'Freelance') ? 'selected' : ''; ?>>Freelance / Mission</option>
                    <option value="Autre" <?php echo ($type_val === 'Autre') ? 'selected' : ''; ?>>Autre</option>
                </select>
            </div>
        </div>
        <div style="flex:1; min-width:250px;">
            <div class="form-group">
                <label for="lieu">Lieu (Ville, Pays) *</label>
                <input type="text" id="lieu" name="lieu" value="<?php echo esc_html($lieu_val); ?>" required placeholder="Ex: Dakar, Sénégal">
            </div>
        </div>
    </div>
    
    <div class="form-group">
        <label for="competences_requises">Compétences Requises (séparées par des virgules)</label>
        <input type="text" id="competences_requises" name="competences_requises" value="<?php echo esc_html($comp_val); ?>" placeholder="Ex: PHP, Gestion de projet, Anglais courant">
    </div>
    <div class="form-group">
        <label for="date_limite_candidature">Date Limite de Candidature (Optionnel)</label>
        <input type="date" id="date_limite_candidature" name="date_limite_candidature" value="<?php echo esc_html($date_lim_val); ?>">
    </div>
    <div class="form-check">
        <input type="checkbox" id="est_active_offre" name="est_active_offre" value="1" <?php echo ($active_val == 1) ? 'checked' : ''; ?>>
        <label for="est_active_offre">Rendre cette offre active (si validée par l'admin)</label>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="fas <?php echo ($mode === 'modifier') ? 'fa-save' : 'fa-paper-plane'; ?>"></i> 
        <?php echo ($mode === 'modifier') ? 'Enregistrer les modifications' : 'Soumettre l\'offre'; ?>
    </button>
    <a href="<?php echo SITE_URL; ?>entreprise.php?view=mes_offres" class="btn btn-secondary" style="margin-left:10px;">Annuler</a>
</form>