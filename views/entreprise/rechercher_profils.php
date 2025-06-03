<?php
// views/entreprise/rechercher_profils.php
global $pdo, $user_id_entreprise;

// Filtres (identiques à la vue publique, mais l'action sera différente si un profil est cliqué)
$filter_domaine = $_GET['domaine'] ?? '';
$filter_niveau = $_GET['niveau'] ?? '';
$filter_etablissement = $_GET['etablissement'] ?? '';
$filter_disponibilite = $_GET['disponibilite'] ?? '';
$search_profil_q = trim($_GET['q_profil'] ?? '');
$filter_competences = trim($_GET['competences'] ?? ''); // Nouveau filtre par compétences

// Construction de la requête SQL
$sql_profils_ent = "SELECT p.*, u.email 
                    FROM profils_etudiants p
                    JOIN utilisateurs u ON p.id_utilisateur = u.id
                    WHERE p.est_visible = 1 AND u.est_actif = 1 AND u.role = 'etudiant'";
$params_profils_ent = [];

if (!empty($search_profil_q)) {
    $sql_profils_ent .= " AND (p.nom_complet LIKE :q_profil OR p.titre_profil LIKE :q_profil OR p.description_personnelle LIKE :q_profil)";
    $params_profils_ent[':q_profil'] = '%' . $search_profil_q . '%';
}
if (!empty($filter_domaine)) {
    $sql_profils_ent .= " AND p.domaine_etudes = :domaine";
    $params_profils_ent[':domaine'] = $filter_domaine;
}
if (!empty($filter_niveau)) {
    $sql_profils_ent .= " AND p.niveau_etudes = :niveau";
    $params_profils_ent[':niveau'] = $filter_niveau;
}
if (!empty($filter_etablissement)) {
    $sql_profils_ent .= " AND p.etablissement = :etablissement";
    $params_profils_ent[':etablissement'] = $filter_etablissement;
}
if (!empty($filter_disponibilite)) {
    $sql_profils_ent .= " AND p.disponibilite = :disponibilite";
    $params_profils_ent[':disponibilite'] = $filter_disponibilite;
}
if (!empty($filter_competences)) {
    // Recherche simple pour une compétence à la fois (peut être amélioré pour multiples)
    $sql_profils_ent .= " AND p.competences_cles LIKE :competences";
    $params_profils_ent[':competences'] = '%' . $filter_competences . '%';
}


$sql_profils_ent .= " ORDER BY p.date_derniere_maj DESC";

// Pagination
$profils_par_page_ent = 10;
$page_actuelle_profils_ent = isset($_GET['page_profils_ent']) ? (int)$_GET['page_profils_ent'] : 1;
$page_actuelle_profils_ent = max(1, $page_actuelle_profils_ent);

$stmt_count_profils_ent = $pdo->prepare(str_replace("SELECT p.*, u.email", "SELECT COUNT(p.id)", $sql_profils_ent));
$stmt_count_profils_ent->execute($params_profils_ent);
$total_profils_ent = $stmt_count_profils_ent->fetchColumn();
$total_pages_profils_ent = ceil($total_profils_ent / $profils_par_page_ent);

$offset_profils_ent = ($page_actuelle_profils_ent - 1) * $profils_par_page_ent;
$sql_profils_ent .= " LIMIT $offset_profils_ent, $profils_par_page_ent";


$stmt_profils_list_ent = $pdo->prepare($sql_profils_ent);
$stmt_profils_list_ent->execute($params_profils_ent);
$profils_list_entreprise = $stmt_profils_list_ent->fetchAll();

$view_mode_ent = $_GET['mode_ent'] ?? 'card'; // 'card' or 'list'
?>
<h2><i class="fas fa-users"></i> Rechercher des Profils Étudiants</h2>
<p>Trouvez les talents qui correspondent à vos besoins parmi notre base d'étudiants qualifiés.</p>

<form action="<?php echo SITE_URL; ?>entreprise.php" method="GET" class="filters-bar">
    <input type="hidden" name="view" value="rechercher_profils">
    <input type="hidden" name="mode_ent" value="<?php echo esc_html($view_mode_ent); ?>">
    <div class="filter-group" style="flex-grow:1;">
        <label for="q_profil_ent">Recherche (Nom, Titre)</label>
        <input type="text" id="q_profil_ent" name="q_profil" value="<?php echo esc_html($search_profil_q); ?>">
    </div>
    <div class="filter-group">
        <label for="domaine_ent">Domaine</label>
        <select id="domaine_ent" name="domaine">
            <option value="">Tous</option> <?php echo generate_filter_options($pdo, 'profils_etudiants', 'domaine_etudes', true, $filter_domaine); ?>
        </select>
    </div>
    <div class="filter-group">
        <label for="niveau_ent">Niveau</label>
        <select id="niveau_ent" name="niveau">
            <option value="">Tous</option> <?php echo generate_filter_options($pdo, 'profils_etudiants', 'niveau_etudes', true, $filter_niveau); ?>
        </select>
    </div>
    <div class="filter-group">
        <label for="etablissement_ent">Établissement</label>
        <select id="etablissement_ent" name="etablissement">
            <option value="">Tous</option> <?php echo generate_filter_options($pdo, 'profils_etudiants', 'etablissement', true, $filter_etablissement); ?>
        </select>
    </div>
    <div class="filter-group">
        <label for="disponibilite_ent">Disponibilité</label>
        <select id="disponibilite_ent" name="disponibilite">
            <option value="">Toutes</option> <?php echo generate_filter_options($pdo, 'profils_etudiants', 'disponibilite', true, $filter_disponibilite); ?>
        </select>
    </div>
    <div class="filter-group">
        <label for="competences_ent">Compétence clé</label>
        <input type="text" id="competences_ent" name="competences" value="<?php echo esc_html($filter_competences); ?>" placeholder="Ex: PHP, Marketing">
    </div>
    <div class="filter-group">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrer</button>
    </div>
    <div class="filter-group">
        <label>Vue:</label>
        <div style="display:flex; gap:5px;">
            <a href="<?php echo SITE_URL; ?>entreprise.php?view=rechercher_profils&mode_ent=card&<?php echo http_build_query(array_diff_key($_GET, ['mode_ent'=>''])); ?>" class="btn btn-sm <?php echo ($view_mode_ent === 'card') ? 'btn-primary' : 'btn-secondary'; ?>"><i class="fas fa-th-large"></i></a>
            <a href="<?php echo SITE_URL; ?>entreprise.php?view=rechercher_profils&mode_ent=list&<?php echo http_build_query(array_diff_key($_GET, ['mode_ent'=>''])); ?>" class="btn btn-sm <?php echo ($view_mode_ent === 'list') ? 'btn-primary' : 'btn-secondary'; ?>"><i class="fas fa-list"></i></a>
        </div>
    </div>
</form>

<?php if (count($profils_list_entreprise) > 0): ?>
    <?php if ($view_mode_ent === 'card'): ?>
    <div class="card-grid">
        <?php foreach ($profils_list_entreprise as $profil_ent): ?>
            <div class="card">
                <div class="card-image-container">
                    <img src="<?php echo esc_html(!empty($profil_ent['lien_photo']) ? $profil_ent['lien_photo'] : DEFAULT_PROFILE_PIC); ?>" alt="Photo de <?php echo esc_html($profil_ent['nom_complet']); ?>">
                </div>
                <div class="card-content">
                    <h3 class="card-title"><?php echo esc_html($profil_ent['nom_complet']); ?></h3>
                    <?php if (!empty($profil_ent['titre_profil'])): ?><p class="card-text" style="font-style:italic; color:var(--primary-color);"><?php echo esc_html($profil_ent['titre_profil']); ?></p><?php endif; ?>
                    <p class="card-text"><strong>Domaine:</strong> <?php echo esc_html($profil_ent['domaine_etudes'] ?? 'N/A'); ?></p>
                    <p class="card-text"><strong>Niveau:</strong> <?php echo esc_html($profil_ent['niveau_etudes'] ?? 'N/A'); ?></p>
                    <div class="card-actions" style="text-align:left;">
                        <a href="<?php echo SITE_URL; ?>entreprise.php?view=profil_detail&id_etudiant=<?php echo $profil_ent['id_utilisateur']; ?>" class="btn btn-primary"><i class="fas fa-eye"></i> Voir Profil Complet</a>
                        <!-- Ajouter bouton pour sauvegarder profil ou contacter -->
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php elseif ($view_mode_ent === 'list'): ?>
    <div class="list-view-container" style="display:flex; flex-direction:column; gap:15px;">
        <?php foreach ($profils_list_entreprise as $profil_ent): ?>
        <div class="card" style="flex-direction:row; align-items:center; padding:15px;">
            <div class="card-image-container" style="width:80px; height:80px; border-radius:50%; margin-right:20px; flex-shrink:0;">
                 <img src="<?php echo esc_html(!empty($profil_ent['lien_photo']) ? $profil_ent['lien_photo'] : DEFAULT_PROFILE_PIC); ?>" alt="Photo de <?php echo esc_html($profil_ent['nom_complet']); ?>" style="border-radius:50%;">
            </div>
            <div class="card-content" style="padding:0; flex-grow:1;">
                <h3 class="card-title" style="margin-bottom:5px;"><?php echo esc_html($profil_ent['nom_complet']); ?></h3>
                <?php if (!empty($profil_ent['titre_profil'])): ?><p class="card-text" style="font-style:italic; color:var(--primary-color); margin-bottom:5px;"><?php echo esc_html($profil_ent['titre_profil']); ?></p><?php endif; ?>
                <p class="card-text" style="font-size:0.85em;">Dom.: <?php echo esc_html($profil_ent['domaine_etudes'] ?? 'N/A'); ?> | Niv.: <?php echo esc_html($profil_ent['niveau_etudes'] ?? 'N/A'); ?> | Dispo.: <?php echo esc_html($profil_ent['disponibilite'] ?? 'N/A'); ?></p>
            </div>
            <div class="card-actions" style="padding:0; margin-left:auto; flex-shrink:0;">
                 <a href="<?php echo SITE_URL; ?>entreprise.php?view=profil_detail&id_etudiant=<?php echo $profil_ent['id_utilisateur']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i> Voir Profil</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($total_pages_profils_ent > 1): ?>
    <nav aria-label="Page navigation profils entreprise" style="margin-top: 30px; display:flex; justify-content:center;">
        <ul class="pagination" style="list-style:none; display:flex; gap:5px;">
            <?php
            $query_params_profils_ent_nav = $_GET;
            unset($query_params_profils_ent_nav['page_profils_ent']);
            $base_url_profils_ent_nav = SITE_URL . "entreprise.php?" . http_build_query($query_params_profils_ent_nav);
            ?>
            <?php if ($page_actuelle_profils_ent > 1): ?><li class="page-item"><a class="page-link btn btn-sm btn-secondary" href="<?php echo $base_url_profils_ent_nav; ?>&page_profils_ent=<?php echo $page_actuelle_profils_ent - 1; ?>">« Préc.</a></li><?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages_profils_ent; $i++): ?><?php if ($i == $page_actuelle_profils_ent): ?><li class="page-item active"><span class="page-link btn btn-sm btn-primary" style="cursor:default;"><?php echo $i; ?></span></li><?php else: ?><li class="page-item"><a class="page-link btn btn-sm btn-secondary" href="<?php echo $base_url_profils_ent_nav; ?>&page_profils_ent=<?php echo $i; ?>"><?php echo $i; ?></a></li><?php endif; ?><?php endfor; ?>
            <?php if ($page_actuelle_profils_ent < $total_pages_profils_ent): ?><li class="page-item"><a class="page-link btn btn-sm btn-secondary" href="<?php echo $base_url_profils_ent_nav; ?>&page_profils_ent=<?php echo $page_actuelle_profils_ent + 1; ?>">Suiv. »</a></li><?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
<?php else: ?>
    <p style="text-align: center; font-size: 1.2em; color: var(--secondary-color); margin-top:30px;">Aucun profil étudiant ne correspond à vos critères de recherche.</p>
<?php endif; ?>