<?php
// views/etudiant/rechercher_entreprises.php
global $pdo;

$search_entreprise_q = trim($_GET['q_entreprise'] ?? '');
$filter_secteur = $_GET['secteur'] ?? '';

$sql_entreprises = "SELECT e.*, u.email 
                    FROM entreprises e
                    JOIN utilisateurs u ON e.id_utilisateur = u.id
                    WHERE e.est_valide_admin = 1 AND u.est_actif = 1"; // Afficher seulement entreprises validées et actives
$params_entreprises = [];

if (!empty($search_entreprise_q)) {
    $sql_entreprises .= " AND (e.nom_entreprise LIKE :q_entreprise OR e.description_entreprise LIKE :q_entreprise)";
    $params_entreprises[':q_entreprise'] = '%' . $search_entreprise_q . '%';
}
if (!empty($filter_secteur)) {
    $sql_entreprises .= " AND e.secteur_activite = :secteur";
    $params_entreprises[':secteur'] = $filter_secteur;
}
$sql_entreprises .= " ORDER BY e.nom_entreprise ASC";

// Pagination (simple)
$entreprises_par_page = 10;
$page_actuelle_entreprises = isset($_GET['page_ent']) ? (int)$_GET['page_ent'] : 1;
$page_actuelle_entreprises = max(1, $page_actuelle_entreprises);

$stmt_count_ent = $pdo->prepare(str_replace("SELECT e.*, u.email", "SELECT COUNT(e.id)", $sql_entreprises));
$stmt_count_ent->execute($params_entreprises);
$total_entreprises = $stmt_count_ent->fetchColumn();
$total_pages_entreprises = ceil($total_entreprises / $entreprises_par_page);

$offset_entreprises = ($page_actuelle_entreprises - 1) * $entreprises_par_page;
$sql_entreprises .= " LIMIT $offset_entreprises, $entreprises_par_page";


$stmt_ent_list = $pdo->prepare($sql_entreprises);
$stmt_ent_list->execute($params_entreprises);
$entreprises_list = $stmt_ent_list->fetchAll();
?>
<h2><i class="fas fa-building"></i> Rechercher des Entreprises</h2>
<p>Découvrez les entreprises partenaires et leurs opportunités.</p>

<form action="<?php echo SITE_URL; ?>etudiant.php" method="GET" class="filters-bar">
    <input type="hidden" name="view" value="rechercher_entreprises">
    <div class="filter-group" style="flex-grow:2;">
        <label for="q_entreprise">Recherche (Nom, description)</label>
        <input type="text" id="q_entreprise" name="q_entreprise" value="<?php echo esc_html($search_entreprise_q); ?>" placeholder="Ex: SystemXey, Innovation...">
    </div>
    <div class="filter-group">
        <label for="secteur">Secteur d'activité</label>
        <select id="secteur" name="secteur">
            <option value="">Tous secteurs</option>
            <?php echo generate_filter_options($pdo, 'entreprises', 'secteur_activite', true, $filter_secteur); ?>
        </select>
    </div>
    <div class="filter-group">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrer</button>
    </div>
</form>

<?php if (count($entreprises_list) > 0): ?>
    <div class="card-grid">
        <?php foreach ($entreprises_list as $entreprise): ?>
            <div class="card">
                <div class="card-image-container" style="background-color: #eee; height:120px;"> <!-- Moins haut pour logo -->
                    <img src="<?php echo esc_html(!empty($entreprise['lien_logo']) ? $entreprise['lien_logo'] : DEFAULT_COMPANY_LOGO); ?>" alt="Logo <?php echo esc_html($entreprise['nom_entreprise']); ?>" style="object-fit:contain;">
                </div>
                <div class="card-content">
                    <h3 class="card-title"><?php echo esc_html($entreprise['nom_entreprise']); ?></h3>
                    <p class="card-text"><strong>Secteur:</strong> <?php echo esc_html($entreprise['secteur_activite'] ?? 'N/A'); ?></p>
                    <p class="card-text"><?php echo truncate_text(esc_html(strip_tags($entreprise['description_entreprise'])), 100); ?></p>
                    <div class="card-actions">
                        <?php
                            // Compter les offres actives de cette entreprise
                            $stmt_offres_ent = $pdo->prepare("SELECT COUNT(*) FROM offres_emploi WHERE id_entreprise_utilisateur = ? AND est_active = 1 AND statut_validation_admin = 'validee'");
                            $stmt_offres_ent->execute([$entreprise['id_utilisateur']]);
                            $nb_offres_entreprise = $stmt_offres_ent->fetchColumn();
                        ?>
                        <a href="<?php echo SITE_URL; ?>index.php?view=offres_emploi&entreprise_nom=<?php echo urlencode($entreprise['nom_entreprise']); ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-briefcase"></i> Voir Offres (<?php echo $nb_offres_entreprise; ?>)
                        </a>
                         <?php if(!empty($entreprise['site_web_url'])): ?>
                            <a href="<?php echo esc_html($entreprise['site_web_url']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm" title="Visiter le site web"><i class="fas fa-globe"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
     <!-- Pagination pour entreprises -->
    <?php if ($total_pages_entreprises > 1): ?>
    <nav aria-label="Page navigation entreprises" style="margin-top: 30px; display:flex; justify-content:center;">
        <ul class="pagination" style="list-style:none; display:flex; gap:5px;">
            <?php
            $query_params_ent = $_GET; 
            unset($query_params_ent['page_ent']); 
            $base_url_ent = SITE_URL . "etudiant.php?" . http_build_query($query_params_ent);
            ?>
            <?php if ($page_actuelle_entreprises > 1): ?>
                <li class="page-item"><a class="page-link btn btn-sm btn-secondary" href="<?php echo $base_url_ent; ?>&page_ent=<?php echo $page_actuelle_entreprises - 1; ?>">« Préc.</a></li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages_entreprises; $i++): ?>
                <?php if ($i == $page_actuelle_entreprises): ?>
                    <li class="page-item active"><span class="page-link btn btn-sm btn-primary" style="cursor:default;"><?php echo $i; ?></span></li>
                <?php else: ?>
                    <li class="page-item"><a class="page-link btn btn-sm btn-secondary" href="<?php echo $base_url_ent; ?>&page_ent=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page_actuelle_entreprises < $total_pages_entreprises): ?>
                <li class="page-item"><a class="page-link btn btn-sm btn-secondary" href="<?php echo $base_url_ent; ?>&page_ent=<?php echo $page_actuelle_entreprises + 1; ?>">Suiv. »</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
<?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Aucune entreprise ne correspond à vos critères pour le moment.
    </div>
<?php endif; ?>
