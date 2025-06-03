```php
<div class="form-container">
<h2>Inscription Étudiant</h2>
<form action="<?php echo SITE_URL; ?>index.php?view=inscription_etudiant" method="POST">
<input type="hidden" name="action" value="inscrire_etudiant">
<div class="form-group">
<label for="nom_complet_etu">Nom Complet *</label>
<input type="text" id="nom_complet_etu" name="nom_complet" required value="<?php echo esc_html($_POST['nom_complet'] ?? ''); ?>">
</div>
<div class="form-group">
<label for="email_etu">Email *</label>
<input type="email" id="email_etu" name="email" required value="<?php echo esc_html($_POST['email'] ?? ''); ?>">
</div>
<div class="form-group">
<label for="password_etu">Mot de passe (min. 6 caractères) *</label>
<input type="password" id="password_etu" name="password" required>
</div>
<div class="form-group">
<label for="password_confirm_etu">Confirmer le mot de passe *</label>
<input type="password" id="password_confirm_etu" name="password_confirm" required>
</div>
<div class="form-group">
<label for="etablissement_etu">Établissement *</label>
<input type="text" id="etablissement_etu" name="etablissement" required value="<?php echo esc_html($_POST['etablissement'] ?? ''); ?>">
</div>
<div class="form-group">
<label for="domaine_etudes_etu">Domaine d'études principal</label>
<input type="text" id="domaine_etudes_etu" name="domaine_etudes" value="<?php echo esc_html($_POST['domaine_etudes'] ?? ''); ?>">
</div>
<button type="submit" class="btn">S'inscrire</button>
</form>
<p class="form-text">Déjà un compte ? <a href="<?php echo SITE_URL; ?>index.php?view=connexion">Connectez-vous</a></p>
<p class="form-text">Vous êtes une entreprise ? <a href="<?php echo SITE_URL; ?>index.php?view=inscription_entreprise">Inscrivez-vous ici</a></p>
</div>
  ```