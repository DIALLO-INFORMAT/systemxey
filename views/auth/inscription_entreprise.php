```php
<div class="form-container">
<h2>Inscription Entreprise</h2>
<form action="<?php echo SITE_URL; ?>index.php?view=inscription_entreprise" method="POST">
<input type="hidden" name="action" value="inscrire_entreprise">
<div class="form-group">
<label for="nom_entreprise_ent">Nom de l'Entreprise *</label>
<input type="text" id="nom_entreprise_ent" name="nom_entreprise" required value="<?php echo esc_html($_POST['nom_entreprise'] ?? ''); ?>">
</div>
<div class="form-group">
<label for="email_ent">Email de contact (Compte) *</label>
<input type="email" id="email_ent" name="email" required value="<?php echo esc_html($_POST['email'] ?? ''); ?>">
</div>
<div class="form-group">
<label for="password_ent">Mot de passe (min. 6 caractères) *</label>
<input type="password" id="password_ent" name="password" required>
</div>
<div class="form-group">
<label for="password_confirm_ent">Confirmer le mot de passe *</label>
<input type="password" id="password_confirm_ent" name="password_confirm" required>
</div>
<div class="form-group">
<label for="secteur_activite_ent">Secteur d'activité *</label>
<input type="text" id="secteur_activite_ent" name="secteur_activite" required value="<?php echo esc_html($_POST['secteur_activite'] ?? ''); ?>">
</div>
<button type="submit" class="btn">S'inscrire</button>
</form>
<p class="form-text">Déjà un compte ? <a href="<?php echo SITE_URL; ?>index.php?view=connexion">Connectez-vous</a></p>
<p class="form-text">Vous êtes étudiant ? <a href="<?php echo SITE_URL; ?>index.php?view=inscription_etudiant">Inscrivez-vous ici</a></p>
</div>
  ```