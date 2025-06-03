<?php
// views/public/profils_etudiants.php
global $pdo; // $pdo est inclus via index.php -> config.php

// Filtres
$filter_domaine = $_GET['domaine'] ?? '';
$filter_niveau = $_GET['niveau'] ?? '';
$filter_etablissement = $_GET['etablissement'] ?? '';
$filter_disponibilite = $_GET['disponibilite'] ?? '';
$search_profil_q = trim($_GET['q_profil'] ?? '');
$filter_competences = trim($_GET['competences'] ?? '');

// Mode d'affichage
$view_mode_profils = $_GET['mode'] ?? 'card'; // 'card' or 'list'

// Construction de la requête SQL pour les profils publics
// On sélectionne les profils étudiants qui sont visibles (p.est_visible = 1)
// et dont le compte utilisateur associé est actif (u.est_actif = 1)
// et dont le rôle est bien 'etudiant'.
$sql_profils = "SELECT p.id_utilisateur, p.nom_complet, p.titre_profil, p.lien_photo, 
                       p.domaine_etudes, p.niveau_etudes, p.etablissement, p.disponibilite,
                       p.type_contrat_recherche, u.email
                FROM profils_etudiants p
                JOIN utilisateurs u ON p.id_utilisateur = u.id
                WHERE p.est_visible = 1 AND u.est_actif = 1 AND u.role = 'etudiant'"; // Condition clé pour l'affichage public

$params_profils = [];

if (!empty($search_profil_q)) {
    $sql_profils .= " AND (p.nom_complet LIKE :q_profil OR p.titre_profil LIKE :q_profil OR p.competences_cles LIKE :q_profil)";
    $params_profils[':q_profil'] = '%' . $search_profil_q . '%';
}
if (!empty($filter_domaine)) {
    $sql_profils .= " AND p.domaine_etudes = :domaine";
    $params_profils[':domaine'] = $filter_domaine;
}
if (!empty($filter_niveau)) {
    $sql_profils .= " AND p.niveau_etudes = :niveau";
    $params_profils[':niveau'] = $filter_niveau;
}
if (!empty($filter_etablissement)) {
    $sql_profils .= " AND p.etablissement = :etablissement";
    $params_profils[':etablissement'] = $filter_etablissement;
}
if (!empty($filter_disponibilite)) {
    $sql_profils .= " AND p.disponibilite = :disponibilite";
    $params_profils[':disponibilite'] = $filter_disponibilite;
}
if (!empty($filter_competences)) {
    $sql_profils .= " AND p.competences_cles LIKE :competences";
    $params_profils[':competences'] = '%' . $filter_competences . '%';
}

$sql_profils .= " ORDER BY p.date_derniere_maj DESC";

// Pagination
$profils_par_page = ($view_mode_profils === 'list') ? 12 : 9; // Plus en mode liste
$page_actuelle_profils = isset($_GET['page_profils']) ? (int)$_GET['page_profils'] : 1;
$page_actuelle_profils = max(1, $page_actuelle_profils);

// Compter le total des profils pour la pagination SANS le LIMIT
$stmt_count_profils_sql = "SELECT COUNT(p.id) " . substr($sql_profils, strpos($sql_profils, "FROM profils_etudiants"));
$stmt_count_profils = $pdo->prepare($stmt_count_profils_sql);
$stmt_count_profils->execute($params_profils);
$total_profils = $stmt_count_profils->fetchColumn();
$total_pages_profils = ceil($total_profils / $profils_par_page);

$offset_profils = ($page_actuelle_profils - 1) * $profils_par_page;
$sql_profils .= " LIMIT $offset_profils, $profils_par_page";

$stmt_profils_list = $pdo->prepare($sql_profils);
$stmt_profils_list->execute($params_profils);
$profils_list = $stmt_profils_list->fetchAll();

// Construire l'URL de base pour les filtres et la pagination (sans le paramètre 'mode' ni 'page_profils')
$query_params_base_profils = $_GET;
unset($query_params_base_profils['mode'], $query_params_base_profils['page_profils']);
$base_filter_url_profils = SITE_URL . "index.php?" . http_build_query($query_params_base_profils);
if (strpos($base_filter_url_profils, '?') === false) { // Si aucun autre paramètre
    $base_filter_url_profils .= '?view=profils_etudiants';
}
if (substr($base_filter_url_profils, -1) !== '&' && substr($base_filter_url_profils, -1) !== '?') {
     $base_filter_url_profils .= '&'; // S'assurer qu'on peut ajouter mode= et page_profils=
}

?>

<section id="liste-profils-etudiants">
    <div class="container">
        <h1 class="section-title">Découvrez Nos Talents Étudiants</h1>
        <p class="section-subtitle">Explorez les profils de nos étudiants prêts à intégrer le monde professionnel.</p>

        <form action="<?php echo SITE_URL; ?>index.php" method="GET" class="filters-bar">
            <input type="hidden" name="view" value="profils_etudiants">
            <input type="hidden" name="mode" value="<?php echo esc_html($view_mode_profils); ?>">
            <div class="filter-group" style="flex-grow:2;">
                <label for="q_profil">Recherche (Nom, Titre, Compétence)</label>
                <input type="text" id="q_profil" name="q_profil" value="<?php echo esc_html($search_profil_q); ?>" placeholder="Ex: Salimatou, Marketing...">
            </div>
            <div class="filter-group">
                <label for="domaine">Domaine d'études</label>
                <select id="domaine" name="domaine">
                    <option value="">Tous domaines</option>
                    <?php echo generate_filter_options($pdo, 'profils_etudiants', 'domaine_etudes', true, $filter_domaine); ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="niveau">Niveau d'études</label>
                <select id="niveau" name="niveau">
                    <option value="">Tous niveaux</option>
                    <?php echo generate_filter_options($pdo, 'profils_etudiants', 'niveau_etudes', true, $filter_niveau); ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="etablissement">Établissement</label>
                <select id="etablissement" name="etablissement">
                    <option value="">Tous établissements</option>
                    <?php echo generate_filter_options($pdo, 'profils_etudiants', 'etablissement', true, $filter_etablissement); ?>
                </select>
            </div>
             <div class="filter-group">
                <label for="competences">Compétence clé</label>
                <input type="text" id="competences" name="competences" value="<?php echo esc_html($filter_competences); ?>" placeholder="Ex: Communication">
            </div>
            <div class="filter-group">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrer</button>
            </div>
            <div class="filter-group">
                <label>Vue:</label>
                <div style="display:flex; gap:5px;">
                    <a href="<?php echo $base_filter_url_profils; ?>mode=card" class="btn btn-sm <?php echo ($view_mode_profils === 'card') ? 'btn-primary' : 'btn-secondary'; ?>"><i class="fas fa-th-large"></i> Cartes</a>
                    <a href="<?php echo $base_filter_url_profils; ?>mode=list" class="btn btn-sm <?php echo ($view_mode_profils === 'list') ? 'btn-primary' : 'btn-secondary'; ?>"><i class="fas fa-list"></i> Liste</a>
                </div>
            </div>
        </form>

        <?php if (count($profils_list) > 0): ?>
            <?php if ($view_mode_profils === 'card'): ?>
            <div class="card-grid">
                <?php foreach ($profils_list as $profil): ?>
                    <div class="card">
                        <div class="card-image-container">
                            <img src="<?php echo esc_html(!empty($profil['lien_photo']) ? $profil['lien_photo'] : DEFAULT_PROFILE_PIC); ?>" alt="Photo de <?php echo esc_html($profil['nom_complet']); ?>">
                        </div>
                        <div class="card-content">
                            <h3 class="card-title"><?php echo esc_html($profil['nom_complet']); ?></h3>
                            <?php if (!empty($profil['titre_profil'])): ?>
                                <p class="card-text" style="font-style:italic; color:var(--primary-color); margin-bottom: 8px;"><?php echo esc_html($profil['titre_profil']); ?></p>
                            <?php endif; ?>
                            <p class="card-text"><strong>Domaine:</strong> <?php echo esc_html($profil['domaine_etudes'] ?? 'N/A'); ?></p>
                            <p class="card-text"><strong>Niveau:</strong> <?php echo esc_html($profil['niveau_etudes'] ?? 'N/A'); ?></p>
                            <!-- Établissement déjà dans le filtre, pas besoin de le répéter sur la carte peut-être -->
                            <div class="card-actions">
                                <a href="<?php echo SITE_URL; ?>index.php?view=profil_etudiant_public_detail&id=<?php echo $profil['id_utilisateur']; ?>" class="btn btn-primary">Voir Aperçu du Profil</a>
                                <?php if (isCompany()): ?>
                                    <a href="<?php echo SITE_URL; ?>entreprise.php?view=profil_detail&id_etudiant=<?php echo $profil['id_utilisateur']; ?>" class="btn btn-secondary btn-sm" style="margin-top:5px;"><i class="fas fa-user-tie"></i> Profil Entreprise</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php elseif ($view_mode_profils === 'list'): ?>
            <div class="list-view-container" style="display:flex; flex-direction:column; gap:15px;">
                <?php foreach ($profils_list as $profil): ?>
                <div class="card" style="flex-direction:row; align-items:center; padding:15px;">
                    <div class="card-image-container" style="width:100px; height:100px; border-radius:50%; margin-right:20px; flex-shrink:0;">
                         <img src="<?php echo esc_html(!empty($profil['lien_photo']) ? $profil['lien_photo'] : DEFAULT_PROFILE_PIC); ?>" alt="Photo de <?php echo esc_html($profil['nom_complet']); ?>" style="border-radius:50%;">
                    </div>
                    <div class="card-content" style="padding:0; flex-grow:1;">
                        <h3 class="card-title" style="margin-bottom:5px;"><?php echo esc_html($profil['nom_complet']); ?></h3>
                        <?php if (!empty($profil['titre_profil'])): ?>
                            <p class="card-text" style="font-style:italic; color:var(--primary-color); margin-bottom:5px;"><?php echo esc_html($profil['titre_profil']); ?></p>
                        <?php endif; ?>
                        <p class="card-text" style="font-size:0.9em; margin-bottom:3px;"><i class="fas fa-graduation-cap"></i> Domaine: <?php echo esc_html($profil['domaine_etudes'] ?? 'N/A'); ?></p>
                        <p class="card-text" style="font-size:0.9em; margin-bottom:3px;"><i class="fas fa-layer-group"></i> Niveau: <?php echo esc_html($profil['niveau_etudes'] ?? 'N/A'); ?></p>
                         <p class="card-text" style="font-size:0.9em;"><i class="fas fa-school"></i> Étab.: <?php echo esc_html($profil['etablissement'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="card-actions" style="padding:0; margin-left:auto; flex-shrink:0; display:flex; flex-direction:column; gap:5px;">
                         <a href="<?php echo SITE_URL; ?>index.php?view=profil_etudiant_public_detail&id=<?php echo $profil['id_utilisateur']; ?>" class="btn btn-primary btn-sm" style="width:150px;">Aperçu Profil</a>
                        <?php if (isCompany()): ?>
                            <a href="<?php echo SITE_URL; ?>entreprise.php?view=profil_detail&id_etudiant=<?php echo $profil['id_utilisateur']; ?>" class="btn btn-secondary btn-sm" style="width:150px;"><i class="fas fa-user-tie"></i> Vue Entreprise</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Pagination pour profils -->
            <?php if ($total_pages_profils > 1): ?>
            <nav aria-label="Page navigation profils" style="margin-top: 30px; display:flex; justify-content:center;">
                <ul class="pagination" style="list-style:none; display:flex; gap:5px;">
                    <?php
                    $query_params_profils_nav = $_GET; 
                    $query_params_profils_nav['mode'] = $view_mode_profils; // S'assurer que le mode est inclus
                    ?>
                    <?php if ($page_actuelle_profils > 1): ?>
                        <?php $query_params_profils_nav['page_profils'] = $page_actuelle_profils - 1; ?>
                        <li class="page-item"><a class="page-link btn btn-sm btn-secondary" href="<?php echo SITE_URL; ?>index.php?<?php echo http_build_query($query_params_profils_nav); ?>">« Préc.</a></li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages_profils; $i++): ?>
                        <?php $query_params_profils_nav['page_profils'] = $i; ?>
                        <?php if ($i == $page_actuelle_profils): ?>
                            <li class="page-item active"><span class="page-link btn btn-sm btn-primary" style="cursor:default;"><?php echo $i; ?></span></li>
                        <?php else: ?>
                            <li class="page-item"><a class="page-link btn btn-sm btn-secondary" href="<?php echo SITE_URL; ?>index.php?<?php echo http_build_query($query_params_profils_nav); ?>"><?php echo $i; ?></a></li>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page_actuelle_profils < $total_pages_profils): ?>
                         <?php $query_params_profils_nav['page_profils'] = $page_actuelle_profils + 1; ?>
                        <li class="page-item"><a class="page-link btn btn-sm btn-secondary" href="<?php echo SITE_URL; ?>index.php?<?php echo http_build_query($query_params_profils_nav); ?>">Suiv. »</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>

        <?php else: ?>
            <p style="text-align: center; font-size: 1.2em; color: var(--secondary-color); margin-top:30px;">
                <i class="fas fa-users-slash" style="font-size: 2em; display:block; margin-bottom:10px;"></i>
                Aucun profil étudiant ne correspond à vos critères pour le moment, ou aucun profil n'est public.
            </p>
        <?php endif; ?>
        
        <?php if (!isCompany() && !isLoggedIn()): ?>
        <div style="text-align:center; margin-top:40px; padding:25px; background-color: var(--light-gray); border-radius:var(--border-radius); border: 1px solid var(--primary-color-light, #cfe2ff);">
            <h4 style="color:var(--primary-color); margin-bottom:10px;">Vous êtes une entreprise à la recherche de talents ?</h4>
            <p style="margin-bottom:15px;">Connectez-vous ou inscrivez-vous pour accéder aux profils complets des étudiants, les contacter directement et publier vos offres d'emploi ou de stage.</p>
            <a href="<?php echo SITE_URL; ?>index.php?view=connexion" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Se Connecter (Entreprise)</a>
            <a href="<?php echo SITE_URL; ?>index.php?view=inscription_entreprise" class="btn btn-secondary" style="margin-left:10px;"><i class="fas fa-user-plus"></i> S'inscrire (Entreprise)</a>
        </div>
        <?php endif; ?>
    </div>
</section>