<?php
// views/admin/newsletter_subscribers.php
global $pdo;

$stmt_subs = $pdo->query("SELECT email, date_inscription FROM newsletter_inscrits ORDER BY date_inscription DESC");
$subscribers = $stmt_subs->fetchAll();
?>
<h2><i class="fas fa-envelope-open-text"></i> Inscrits à la Newsletter (<?php echo count($subscribers); ?>)</h2>

<?php if(count($subscribers) > 0): ?>
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Email</th>
                <th>Date d'Inscription</th>
                <!-- <th>Actions</th> -->
            </tr>
        </thead>
        <tbody>
            <?php foreach($subscribers as $sub): ?>
            <tr>
                <td><?php echo esc_html($sub['email']); ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($sub['date_inscription'])); ?></td>
                <!-- <td><button class="btn btn-sm btn-danger" disabled>Supprimer (Bientôt)</button></td> -->
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p><a href="mailto:<?php 
    $emails_list = array_map(function($s){ return $s['email']; }, $subscribers);
    echo implode(';', $emails_list); 
?>" class="btn btn-success"><i class="fas fa-paper-plane"></i> Envoyer un email à tous</a> (ouvre votre client mail)</p>
<?php else: ?>
<p>Aucun utilisateur n'est encore inscrit à la newsletter.</p>
<?php endif; ?>