<?php
// views/public/offres_emploi.php
global $pdo;

// Paramètres de filtrage, recherche et mode d'affichage
$search_term = trim($_GET['q'] ?? '');
$filter_type_contrat = trim($_GET['type_contrat'] ?? '');
$filter_lieu = trim($_GET['lieu'] ?? '');
$filter_entreprise_nom = trim($_GET['entreprise_nom'] ?? '');
$view_mode_offres = $_GET['mode'] ?? 'card'; // 'card' or 'list'

// Construction de la requête SQL
$sql_offres = "SELECT o.*, e.nom_entreprise, e.lien_logo AS logo_entreprise 
               FROM offres_emploi o
               JOIN entreprises e ON o.id_entreprise_utilisateur = e.id_utilisateur
               WHERE o.est_active = 1 AND o.statut_validation_admin = 'validee'";
$params = [];

if (!empty($search_term)) {
    $sql_offres .= " AND (o.titre_poste LIKE :search_term OR o.description_poste LIKE :search_term OR o.competences_requises LIKE :search_term)";
    $params[':search_term'] = '%' . $search_term . '%';
}
if (!empty($filter_type_contrat)) {
    $sql_offres .= " AND o.type_contrat = :type_contrat";
    $params[':type_contrat'] = $filter_type_contrat;
}
if (!empty($filter_lieu)) {
    $sql_offres .= " AND o.lieu LIKE :lieu";
    $params[':lieu'] = '%' . $filter_lieu . '%';
}
if (!empty($filter_entreprise_nom)) {
    $sql_offres .= " AND e.nom_entreprise LIKE :nom_entreprise";
    $params[':nom_entreprise'] = '%' . $filter_entreprise_nom . '%';
}

$sql_offres .= " ORDER BY o.date_publication DESC";

// Pagination
$offres_par_page = ($view_mode_offres === 'list') ? 10 : 9; // Plus d'offres par page en mode liste
$page_actuelle = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page_actuelle = max(1, $page_actuelle);

$stmt_count = $pdo->prepare(str_replace("SELECT o.*, e.nom_entreprise, e.lien_logo AS logo_entreprise", "SELECT COUNT(o.id)", $sql_offres));
$stmt_count->execute($params);
$total_offres = $stmt_count->fetchColumn();
$total_pages = ceil($total_offres / $offres_par_page);

$offset = ($page_actuelle - 1) * $offres_par_page;
$sql_offres .= " LIMIT $offset, $offres_par_page";

$stmt_offres = $pdo->prepare($sql_offres);
$stmt_offres->execute($params);
$offres = $stmt_offres->fetchAll();

// Construire l'URL de base pour les filtres et la pagination (sans le paramètre 'mode')
$query_params_base = $_GET;
unset($query_params_base['mode']); // On le gérera séparément pour les boutons de mode
$base_filter_url = SITE_URL . "index.php?" . http_build_query($query_params_base);
// Si http_build_query est vide (pas d'autres params), on s'assure d'avoir au moins ?view=offres_emploi
if (strpos($base_filter_url, '?') === false) {
    $base_filter_url .= '?view=offres_emploi';
} else if (substr($base_filter_url, -1) !== '&' && substr($base_filter_url, -1) !== '?') {
     $base_filter_url .= '&';
}


?>

<section id="liste-offres-emploi">
    <div class="container">
        <h1 class="section-title">Offres d'Emploi et de Stage</h1>
        <p class="section-subtitle">Trouvez l'opportunité qui correspond à votre profil parmi nos offres validées.</p>

        <form action="<?php echo SITE_URL; ?>index.php" method="GET" class="filters-bar">
            <input type="hidden" name="view" value="offres_emploi">
            <input type="hidden" name="mode" value="<?php echo esc_html($view_mode_offres); ?>"> <!-- Garder le mode actuel lors du filtrage -->
            <div class="filter-group">
                <label for="q">Recherche (mot-clé, compétence)</label>
                <input type="text" id="q" name="q" value="<?php echo esc_html($search_term); ?>" placeholder="Ex: Développeur Web">
            </div>
            <div class="filter-group">
                <label for="type_contrat">Type de contrat</label>
                <select id="type_contrat" name="type_contrat">
                    <option value="">Tous types</option>
                    <?php echo generate_filter_options($pdo, 'offres_emploi', 'type_contrat', true, $filter_type_contrat); ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="lieu">Lieu</label>
                <input type="text" id="lieu" name="lieu" value="<?php echo esc_html($filter_lieu); ?>" placeholder="Ex: Dakar">
            </div>
             <div class="filter-group">
                <label for="entreprise_nom">Nom de l'entreprise</label>
                <input type="text" id="entreprise_nom" name="entreprise_nom" value="<?php echo esc_html($filter_entreprise_nom); ?>" placeholder="Ex: SystemXey Corp">
            </div>
            <div class="filter-group">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrer</button>
            </div>
            <div class="filter-group">
                <label>Vue:</label>
                <div style="display:flex; gap:5px;">
                    <a href="<?php echo $base_filter_url; ?>mode=card" class="btn btn-sm <?php echo ($view_mode_offres === 'card') ? 'btn-primary' : 'btn-secondary'; ?>"><i class="fas fa-th-large"></i> Cartes</a>
                    <a href="<?php echo $base_filter_url; ?>mode=list" class="btn btn-sm <?php echo ($view_mode_offres === 'list') ? 'btn-primary' : 'btn-secondary'; ?>"><i class="fas fa-list"></i> Liste</a>
                </div>
            </div>
        </form>

        <?php if (count($offres) > 0): ?>
            <?php if ($view_mode_offres === 'card'): ?>
                <div class="card-grid">
                    <?php foreach ($offres as $offre): ?>
                        <div class="card">
                            <div class="card-image-container" style="background-color: #eee; height:150px;"> <!-- Hauteur image un peu réduite pour offres -->
                                <img src="<?php echo esc_html(!empty($offre['logo_entreprise']) ? $offre['logo_entreprise'] : DEFAULT_COMPANY_LOGO); ?>" alt="Logo <?php echo esc_html($offre['nom_entreprise']); ?>" style="object-fit:contain;">
                            </div>
                            <div class="card-content">
                                <h3 class="card-title"><?php echo esc_html($offre['titre_poste']); ?></h3>
                                <p class="card-text"><strong><i class="fas fa-building"></i> Entreprise:</strong> <?php echo esc_html($offre['nom_entreprise']); ?></p>
                                <p class="card-text"><strong><i class="fas fa-map-marker-alt"></i> Lieu:</strong> <?php echo esc_html($offre['lieu']); ?></p>
                                <p class="card-text"><strong><i class="fas fa-file-contract"></i> Contrat:</strong> <?php echo esc_html($offre['type_contrat']); ?></p>
                                <p class="card-text" style="margin-bottom: 15px;"><?php echo truncate_text(esc_html(strip_tags($offre['description_poste'])), 100); ?></p>
                                <div class="card-actions">
                                    <a href="<?php echo SITE_URL; ?>index.php?view=offre_detail&id=<?php echo $offre['id']; ?>" class="btn btn-primary">Voir Détails</a>
                                    <?php if (isStudent()): ?>
                                    <form action="<?php echo SITE_URL; ?>etudiant.php" method="POST" style="display:inline-block; margin-left:5px;">
                                        <input type="hidden" name="action" value="toggle_offre_favorite">
                                        <input type="hidden" name="id_offre" value="<?php echo $offre['id']; ?>">
                                        <input type="hidden" name="redirect_back_url" value="<?php echo SITE_URL . 'index.php?' . http_build_query($_GET); ?>">
                                        <?php
                                            $stmt_fav = $pdo->prepare("SELECT COUNT(*) FROM offres_favorites_etudiants WHERE id_etudiant_utilisateur = ? AND id_offre = ?");
                                            $stmt_fav->execute([getUserId(), $offre['id']]);
                                            $is_favorite = $stmt_fav->fetchColumn() > 0;
                                        ?>
                                        <button type="submit" class="btn btn-secondary btn-sm" title="<?php echo $is_favorite ? 'Retirer des favoris' : 'Ajouter aux favoris'; ?>">
                                            <i class="fas fa-heart" style="color: <?php echo $is_favorite ? 'var(--danger-color, #dc3545)' : 'grey'; ?>;"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($view_mode_offres === 'list'): ?>
                <div class="list-view-container" style="display:flex; flex-direction:column; gap:15px;">
                    <?php foreach ($offres as $offre): ?>
                    <div class="card" style="flex-direction:row; align-items:center; padding:15px;">
                        <div class="card-image-container" style="width:100px; height:100px; margin-right:20px; flex-shrink:0; background-color: #eee;">
                             <img src="<?php echo esc_html(!empty($offre['logo_entreprise']) ? $offre['logo_entreprise'] : DEFAULT_COMPANY_LOGO); ?>" alt="Logo <?php echo esc_html($offre['nom_entreprise']); ?>" style="object-fit:contain;">
                        </div>
                        <div class="card-content" style="padding:0; flex-grow:1;">
                            <h3 class="card-title" style="margin-bottom:5px;"><?php echo esc_html($offre['titre_poste']); ?></h3>
                            <p class="card-text" style="font-size:0.9em; margin-bottom:3px;"><i class="fas fa-building"></i> <?php echo esc_html($offre['nom_entreprise']); ?></p>
                            <p class="card-text" style="font-size:0.85em; margin-bottom:3px;"><i class="fas fa-map-marker-alt"></i> <?php echo esc_html($offre['lieu']); ?>   |   <i class="fas fa-file-contract"></i> <?php echo esc_html($offre['type_contrat']); ?></p>
                            <p class="card-text" style="font-size:0.85em;"><i class="fas fa-calendar-alt"></i> Publiée le: <?php echo date('d/m/Y', strtotime($offre['date_publication'])); ?></p>
                        </div>
                        <div class="card-actions" style="padding:0; margin-left:auto; flex-shrink:0; display:flex; flex-direction:column; gap:5px;">
                             <a href="<?php echo SITE_URL; ?>index.php?view=offre_detail&id=<?php echo $offre['id']; ?>" class="btn btn-primary btn-sm" style="width:130px;">Détails</a>
                             <?php if (isStudent()): ?>
                                <form action="<?php echo SITE_URL; ?>etudiant.php" method="POST" style="display:block;">
                                    <input type="hidden" name="action" value="toggle_offre_favorite">
                                    <input type="hidden" name="id_offre" value="<?php echo $offre['id']; ?>">
                                    <input type="hidden" name="redirect_back_url" value="<?php echo SITE_URL . 'index.php?' . http_build_query($_GET); ?>">
                                    <?php
                                        $stmt_fav = $pdo->prepare("SELECT COUNT(*) FROM offres_favorites_etudiants WHERE id_etudiant_utilisateur = ? AND id_offre = ?");
                                        $stmt_fav->execute([getUserId(), $offre['id']]);
                                        $is_favorite_list = $stmt_fav->fetchColumn() > 0;
                                    ?>
                                    <button type="submit" class="btn btn-secondary btn-sm" title="<?php echo $is_favorite_list ? 'Retirer des favoris' : 'Ajouter aux favoris'; ?>" style="width:130px;">
                                        <i class="fas fa-heart" style="color: <?php echo $is_favorite_list ? 'var(--danger-color, #dc3545)' : 'grey'; ?>;"></i> Favori
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation offres" style="margin-top: 30px; display:flex; justify-content:center;">
                <ul class="pagination" style="list-style:none; display:flex; gap:5px;">
                    <?php
                    // S'assurer que le mode est inclus dans les liens de pagination
                    $query_params_pagination = $_GET; // Copie des paramètres GET actuels
                    $query_params_pagination['mode'] = $view_mode_offres; // Assurer que le mode est dans les params pour la pagination
                    ?>
                    <?php if ($page_actuelle > 1): ?>
                        <?php $query_params_pagination['page'] = $page_actuelle - 1; ?>
                        <li class="page-item"><a class="page-link btn btn-sm btn-secondary" href="<?php echo SITE_URL; ?>index.php?<?php echo http_build_query($query_params_pagination); ?>">« Préc.</a></li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php $query_params_pagination['page'] = $i; ?>
                        <?php if ($i == $page_actuelle): ?>
                            <li class="page-item active"><span class="page-link btn btn-sm btn-primary" style="cursor:default;"><?php echo $i; ?></span></li>
                        <?php else: ?>
                            <li class="page-item"><a class="page-link btn btn-sm btn-secondary" href="<?php echo SITE_URL; ?>index.php?<?php echo http_build_query($query_params_pagination); ?>"><?php echo $i; ?></a></li>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page_actuelle < $total_pages): ?>
                        <?php $query_params_pagination['page'] = $page_actuelle + 1; ?>
                        <li class="page-item"><a class="page-link btn btn-sm btn-secondary" href="<?php echo SITE_URL; ?>index.php?<?php echo http_build_query($query_params_pagination); ?>">Suiv. »</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>

        <?php else: ?>
            <p style="text-align: center; font-size: 1.2em; color: var(--secondary-color); margin-top:30px;">
                <i class="fas fa-search-minus" style="font-size: 2em; display:block; margin-bottom:10px;"></i>
                Aucune offre ne correspond à vos critères de recherche pour le moment.
            </p>
        <?php endif; ?>
    </div>
</section>