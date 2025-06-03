<?php
// views/admin/dashboard.php
global $pdo;

// --- Récupération des statistiques ---
$total_users = $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
$total_etudiants_users = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'etudiant'")->fetchColumn();
$total_entreprises_users = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'entreprise'")->fetchColumn();

$total_profils_etudiants = $pdo->query("SELECT COUNT(*) FROM profils_etudiants")->fetchColumn();
$total_profils_entreprises = $pdo->query("SELECT COUNT(*) FROM entreprises")->fetchColumn();

$total_offres = $pdo->query("SELECT COUNT(*) FROM offres_emploi")->fetchColumn();
$offres_en_attente = $pdo->query("SELECT COUNT(*) FROM offres_emploi WHERE statut_validation_admin = 'en_attente'")->fetchColumn();
$offres_validees = $pdo->query("SELECT COUNT(*) FROM offres_emploi WHERE statut_validation_admin = 'validee' AND est_active = 1")->fetchColumn();

$total_candidatures = $pdo->query("SELECT COUNT(*) FROM candidatures")->fetchColumn();
$total_partenaires = $pdo->query("SELECT COUNT(*) FROM partenaires WHERE est_actif = 1")->fetchColumn();
$total_newsletter = $pdo->query("SELECT COUNT(*) FROM newsletter_inscrits")->fetchColumn();

// Données pour graphiques (exemple simple, à affiner)
$stats_inscriptions_labels = [];
$stats_inscriptions_etudiants = [];
$stats_inscriptions_entreprises = [];

// Récupérer les inscriptions des 7 derniers jours
for ($i = 6; $i >= 0; $i--) {
    $date_jour = date('Y-m-d', strtotime("-$i days"));
    $stats_inscriptions_labels[] = date('d/m', strtotime("-$i days"));

    $stmt_etu = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE role = 'etudiant' AND DATE(date_inscription) = ?");
    $stmt_etu->execute([$date_jour]);
    $stats_inscriptions_etudiants[] = $stmt_etu->fetchColumn();

    $stmt_ent = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE role = 'entreprise' AND DATE(date_inscription) = ?");
    $stmt_ent->execute([$date_jour]);
    $stats_inscriptions_entreprises[] = $stmt_ent->fetchColumn();
}

?>
<h2><i class="fas fa-chart-pie"></i> Tableau de Bord Administrateur</h2>

<div class="stat-card-grid">
    <div class="stat-card">
        <div class="stat-info">
            <h4><?php echo $total_users; ?></h4>
            <p>Utilisateurs Inscrits</p>
        </div>
        <div class="stat-icon"><i class="fas fa-users"></i></div>
    </div>
    <div class="stat-card info">
        <div class="stat-info">
            <h4><?php echo $total_etudiants_users; ?></h4>
            <p>Comptes Étudiants</p>
        </div>
        <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
    </div>
    <div class="stat-card success">
        <div class="stat-info">
            <h4><?php echo $total_entreprises_users; ?></h4>
            <p>Comptes Entreprises</p>
        </div>
        <div class="stat-icon"><i class="fas fa-building"></i></div>
    </div>
     <div class="stat-card warning">
        <div class="stat-info">
            <h4><?php echo $offres_en_attente; ?></h4>
            <p>Offres en Attente</p>
        </div>
        <div class="stat-icon"><i class="fas fa-hourglass-start"></i></div>
    </div>
</div>
<div class="stat-card-grid">
    <div class="stat-card">
        <div class="stat-info">
            <h4><?php echo $total_offres; ?></h4>
            <p>Offres d'Emploi (Total)</p>
        </div>
        <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
    </div>
    <div class="stat-card success">
        <div class="stat-info">
            <h4><?php echo $offres_validees; ?></h4>
            <p>Offres Actives et Validées</p>
        </div>
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
    </div>
    <div class="stat-card info">
        <div class="stat-info">
            <h4><?php echo $total_candidatures; ?></h4>
            <p>Candidatures Totales</p>
        </div>
        <div class="stat-icon"><i class="fas fa-file-signature"></i></div>
    </div>
     <div class="stat-card">
        <div class="stat-info">
            <h4><?php echo $total_newsletter; ?></h4>
            <p>Inscrits Newsletter</p>
        </div>
        <div class="stat-icon"><i class="fas fa-newspaper"></i></div>
    </div>
</div>


<div class="card" style="margin-top:30px; padding:20px;">
    <h3 style="margin-bottom:20px;">Inscriptions des 7 derniers jours</h3>
    <canvas id="inscriptionsChart" width="400" height="150"></canvas>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('inscriptionsChart').getContext('2d');
    const inscriptionsChart = new Chart(ctx, {
        type: 'line', // ou 'bar'
        data: {
            labels: <?php echo json_encode($stats_inscriptions_labels); ?>,
            datasets: [{
                label: 'Étudiants Inscrits',
                data: <?php echo json_encode($stats_inscriptions_etudiants); ?>,
                borderColor: 'rgb(0, 123, 255)',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.1,
                fill: true
            }, {
                label: 'Entreprises Inscrites',
                data: <?php echo json_encode($stats_inscriptions_entreprises); ?>,
                borderColor: 'rgb(40, 167, 69)',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1 // Pour n'afficher que des entiers si les nombres sont petits
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: false,
                    text: 'Évolution des inscriptions'
                }
            }
        }
    });
});
</script>