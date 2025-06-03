<div class="form-container">
<h2>Connexion</h2>
<form action="<?php echo SITE_URL; ?>index.php?view=connexion" method="POST">
<input type="hidden" name="action" value="connexion">
<div class="form-group">
<label for="email_or_username">Email ou Nom d'utilisateur</label>
<input type="text" id="email_or_username" name="email_or_username" required value="<?php echo esc_html($_POST['email_or_username'] ?? ''); ?>">
</div>
<div class="form-group">
<label for="password_login">Mot de passe</label>
<input type="password" id="password_login" name="password" required>
</div>
<button type="submit" class="btn">Se connecter</button>
</form>
<p class="form-text">Pas encore de compte ? <a href="<?php echo SITE_URL; ?>index.php?view=inscription_etudiant">Inscription Étudiant</a> | <a href="<?php echo SITE_URL; ?>index.php?view=inscription_entreprise">Inscription Entreprise</a></p>
<!-- <p class="form-text"><a href="<?php echo SITE_URL; ?>index.php?view=mot_de_passe_oublie">Mot de passe oublié ?</a></p> -->
</div>
  ```
  