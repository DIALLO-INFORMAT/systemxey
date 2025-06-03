<?php
// views/etudiant/notifications.php
global $pdo, $user_id;

// Logique de récupération des vraies notifications à implémenter ici.
// Pour l'instant, on simule.
$notifications = [
    ['id' => 1, 'message' => 'Votre candidature pour "Développeur PHP chez SystemCorp" a été acceptée ! Un email vous a été envoyé.', 'type' => 'success', 'lue' => 0, 'date_creation' => date('Y-m-d H:i:s', strtotime('-1 day')), 'lien' => SITE_URL.'etudiant.php?view=mes_candidatures'],
    ['id' => 2, 'message' => 'Nouvelle offre de stage en Marketing Digital chez InnovaTech.', 'type' => 'info', 'lue' => 0, 'date_creation' => date('Y-m-d H:i:s', strtotime('-3 hours')), 'lien' => SITE_URL.'index.php?view=offre_detail&id=XYZ'], // Remplacer XYZ par un ID réel
    ['id' => 3, 'message' => 'Pensez à mettre à jour votre section "Compétences" pour de meilleurs résultats.', 'type' => 'warning', 'lue' => 1, 'date_creation' => date('Y-m-d H:i:s', strtotime('-5 days')), 'lien' => SITE_URL.'etudiant.php?view=gerer_profil'],
];

// Marquer comme lues (simulé, en réalité, vous feriez un UPDATE en BDD)
// if (isset($_GET['mark_read']) && $_GET['mark_read'] === 'all') { /* ... */ }
?>
<h2><i class="fas fa-bell"></i> Mes Notifications</h2>

<?php if (count($notifications) > 0): ?>
    <div class="list-group">
        <?php foreach ($notifications as $notif): ?>
            <a href="<?php echo esc_html($notif['lien'] ?? '#'); ?>" class="list-group-item list-group-item-action <?php echo $notif['lue'] ? '' : 'font-weight-bold'; ?>" style="margin-bottom:10px; border-left: 5px solid <?php 
                if($notif['type'] === 'success') echo 'var(--accent-color)';
                elseif($notif['type'] === 'info') echo 'var(--primary-color)';
                elseif($notif['type'] === 'warning') echo '#ffc107';
                else echo 'var(--secondary-color)';
            ?>; border-radius:var(--border-radius); padding:15px; display:block; text-decoration:none; color:var(--text-color);">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <?php if($notif['type'] === 'success'): ?><i class="fas fa-check-circle" style="color:var(--accent-color); margin-right:5px;"></i>
                        <?php elseif($notif['type'] === 'info'): ?><i class="fas fa-info-circle" style="color:var(--primary-color); margin-right:5px;"></i>
                        <?php elseif($notif['type'] === 'warning'): ?><i class="fas fa-exclamation-triangle" style="color:#ffc107; margin-right:5px;"></i>
                        <?php endif; ?>
                        <?php echo esc_html($notif['message']); ?>
                    </div>
                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($notif['date_creation'])); ?></small>
                </div>
                <?php if(!$notif['lue']): ?>
                    <!-- <form action="#" method="POST" style="display:inline; float:right; margin-left:10px;">
                        <input type="hidden" name="action" value="mark_notification_read">
                        <input type="hidden" name="notification_id" value="<?php echo $notif['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-light">Marquer comme lue</button>
                    </form> -->
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Aucune notification pour le moment.
    </div>
<?php endif; ?>
