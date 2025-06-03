<?php
// views/admin/manage_site_content.php
global $pdo;

$stmt_contenu = $pdo->query("SELECT cle_contenu, valeur_contenu_texte, valeur_contenu_lien, type_contenu, description_admin FROM contenu_site ORDER BY cle_contenu ASC");
$contenus = $stmt_contenu->fetchAll();
?>
<h2><i class="fas fa-edit"></i> Gérer le Contenu du Site</h2>
<p>Modifiez ici les textes, liens et images principaux de votre site.</p>

<form action="<?php echo SITE_URL; ?>admin.php?view=manage_site_content" method="POST">
    <input type="hidden" name="action" value="update_site_content">

    <?php foreach ($contenus as $item_contenu): ?>
        <div class="content-item">
            <label for="contenu_<?php echo esc_html($item_contenu['cle_contenu']); ?>"><?php echo ucfirst(str_replace('_', ' ', esc_html($item_contenu['cle_contenu']))); ?></label>
            <?php if(!empty($item_contenu['description_admin'])): ?>
                <small><?php echo esc_html($item_contenu['description_admin']); ?></small>
            <?php endif; ?>

            <?php if ($item_contenu['type_contenu'] === 'texte' || $item_contenu['type_contenu'] === 'html_brut'): ?>
                <textarea id="contenu_<?php echo esc_html($item_contenu['cle_contenu']); ?>" 
                          name="contenu[<?php echo esc_html($item_contenu['cle_contenu']); ?>][texte]" 
                          rows="<?php echo ($item_contenu['type_contenu'] === 'html_brut' || strlen($item_contenu['valeur_contenu_texte'] ?? '') > 100) ? '5' : '2'; ?>"
                          class="form-control"><?php echo esc_html($item_contenu['valeur_contenu_texte'] ?? ''); ?></textarea>
                <input type="hidden" name="contenu[<?php echo esc_html($item_contenu['cle_contenu']); ?>][lien]" value=""> <!-- Champ lien vide -->
            <?php elseif ($item_contenu['type_contenu'] === 'lien_image' || $item_contenu['type_contenu'] === 'lien_url'): ?>
                 <input type="url" id="contenu_<?php echo esc_html($item_contenu['cle_contenu']); ?>_lien" 
                       name="contenu[<?php echo esc_html($item_contenu['cle_contenu']); ?>][lien]" 
                       value="<?php echo esc_html($item_contenu['valeur_contenu_lien'] ?? ''); ?>" 
                       class="form-control" placeholder="https://...">
                <input type="hidden" name="contenu[<?php echo esc_html($item_contenu['cle_contenu']); ?>][texte]" value=""> <!-- Champ texte vide -->
                <?php if ($item_contenu['type_contenu'] === 'lien_image' && !empty($item_contenu['valeur_contenu_lien'])): ?>
                    <img src="<?php echo esc_html($item_contenu['valeur_contenu_lien']); ?>" alt="Aperçu" style="max-height: 80px; margin-top: 10px; border:1px solid #ddd; padding:3px;">
                <?php endif; ?>
            <?php elseif ($item_contenu['type_contenu'] === 'couleur'): ?>
                <input type="color" id="contenu_<?php echo esc_html($item_contenu['cle_contenu']); ?>_texte" 
                       name="contenu[<?php echo esc_html($item_contenu['cle_contenu']); ?>][texte]" 
                       value="<?php echo esc_html($item_contenu['valeur_contenu_texte'] ?? '#007bff'); ?>" 
                       class="form-control" style="height:40px; padding:5px;">
                <input type="hidden" name="contenu[<?php echo esc_html($item_contenu['cle_contenu']); ?>][lien]" value="">
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer les modifications du contenu</button>
</form>