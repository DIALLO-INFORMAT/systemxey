<?php
// views/public/accueil.php
global $pdo; 
?>

<section class="hero-section" style="background-image: url('<?php echo get_site_content('accueil_hero_image_url', $pdo); ?>');">
    <div class="hero-content">
        <h1><?php echo get_site_content('accueil_hero_titre', $pdo); ?></h1>
        <p><?php echo get_site_content('accueil_hero_sous_titre', $pdo); ?></p>
        <?php
            // Correction des liens pour les boutons du héros
            $url_bouton1 = get_site_content('accueil_hero_lien_bouton1_url', $pdo);
            $url_bouton2 = get_site_content('accueil_hero_lien_bouton2_url', $pdo);

            // S'assurer que les URLs sont complètes si elles sont relatives
            if (!preg_match("~^(?:f|ht)tps?://~i", $url_bouton1) && !empty($url_bouton1)) {
                $url_bouton1 = SITE_URL . ltrim($url_bouton1, '/');
            }
            if (!preg_match("~^(?:f|ht)tps?://~i", $url_bouton2) && !empty($url_bouton2)) {
                $url_bouton2 = SITE_URL . ltrim($url_bouton2, '/');
            }
        ?>
        <a href="<?php echo esc_html($url_bouton1); ?>" class="btn btn-primary"><?php echo get_site_content('accueil_hero_lien_bouton1_texte', $pdo); ?></a>
        <a href="<?php echo esc_html($url_bouton2); ?>" class="btn btn-secondary" style="margin-left:10px;"><?php echo get_site_content('accueil_hero_lien_bouton2_texte', $pdo); ?></a>
    </div>
</section>

<section class="partners-section" id="partenaires">
    <div class="container">
        <h2 class="section-title">Nos Partenaires</h2>
        <div class="section-title-underline"></div>
        <div class="carousel-container">
            <div class="carousel-track">
                <?php
                try {
                    $stmt_partenaires = $pdo->query("SELECT nom_partenaire, lien_logo_partenaire, lien_site_partenaire FROM partenaires WHERE est_actif = 1 ORDER BY ordre_affichage ASC");
                    $partenaires = $stmt_partenaires->fetchAll(); // Récupère une seule fois
                    if ($partenaires && count($partenaires) > 0) { // Vérifie si $partenaires n'est pas vide
                        foreach ($partenaires as $partenaire) { // Boucle une seule fois sur les partenaires originaux
                            echo '<div class="carousel-item">';
                            if (!empty($partenaire['lien_site_partenaire'])) {
                                echo '<a href="' . esc_html($partenaire['lien_site_partenaire']) . '" target="_blank" rel="noopener noreferrer">';
                            }
                            echo '<img src="' . esc_html($partenaire['lien_logo_partenaire']) . '" alt="' . esc_html($partenaire['nom_partenaire']) . '">';
                            if (!empty($partenaire['lien_site_partenaire'])) {
                                echo '</a>';
                            }
                            echo '<p>' . esc_html($partenaire['nom_partenaire']) . '</p>';
                            echo '</div>';
                        }
                        // Optionnel: Dupliquer pour effet de boucle si peu d'items (seulement si nécessaire pour le JS du carrousel)
                        // Si votre carrousel JS gère la boucle infinie, cette duplication n'est pas nécessaire.
                        // Exemple pour cloner SI MOINS DE 5 ITEMS et SI vous voulez un effet de "remplissage" pour un carrousel JS simple :
                        // $items_visibles_approximatif = 5; 
                        // if (count($partenaires) < $items_visibles_approximatif && count($partenaires) > 0) {
                        //     $needed_clones = $items_visibles_approximatif - count($partenaires);
                        //     for($i=0; $i < $needed_clones; $i++){
                        //         $clone_partenaire = $partenaires[$i % count($partenaires)]; // Boucle sur les items existants
                        //         echo '<div class="carousel-item">';
                        //         if (!empty($clone_partenaire['lien_site_partenaire'])) {
                        //             echo '<a href="' . esc_html($clone_partenaire['lien_site_partenaire']) . '" target="_blank" rel="noopener noreferrer">';
                        //         }
                        //         echo '<img src="' . esc_html($clone_partenaire['lien_logo_partenaire']) . '" alt="' . esc_html($clone_partenaire['nom_partenaire']) . ' (clone)">';
                        //         if (!empty($clone_partenaire['lien_site_partenaire'])) {
                        //             echo '</a>';
                        //         }
                        //         echo '<p>' . esc_html($clone_partenaire['nom_partenaire']) . '</p>';
                        //         echo '</div>';
                        //     }
                        // }
                    } else {
                        echo '<p style="text-align:center; width:100%;">Aucun partenaire à afficher pour le moment.</p>';
                    }
                } catch (PDOException $e) {
                    error_log("Erreur récupération partenaires: " . $e->getMessage());
                    echo '<p style="text-align:center; width:100%;">Erreur lors du chargement des partenaires.</p>';
                }
                ?>
            </div>
             <?php if (isset($partenaires) && count($partenaires) > 3): // Ajuster ce nombre selon combien d'items vous voulez pour afficher la nav ?>
            <div class="carousel-nav">
                <button class="carousel-prev"><i class="fas fa-chevron-left"></i></button>
                <button class="carousel-next"><i class="fas fa-chevron-right"></i></button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section id="comment-ca-marche">
    <div class="container">
        <h2 class="section-title">Comment ça marche ?</h2>
        <div class="section-title-underline"></div>
        <div class="card-grid" style="grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px;">
            <div class="card">
                <div class="card-content">
                    <h3 class="card-title" style="text-align:center;"><i class="fas fa-user-graduate" style="color:var(--primary-color); margin-right:10px;"></i>Pour les Étudiants</h3>
                    <p style="white-space: pre-line;"><?php echo get_site_content('texte_comment_ca_marche_etudiants', $pdo); ?></p>
                </div>
            </div>
            <div class="card">
                <div class="card-content">
                    <h3 class="card-title" style="text-align:center;"><i class="fas fa-building" style="color:var(--primary-color); margin-right:10px;"></i>Pour les Entreprises</h3>
                    <p style="white-space: pre-line;"><?php echo get_site_content('texte_comment_ca_marche_entreprises', $pdo); ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="avantages" style="background-color: var(--light-gray);">
    <div class="container">
        <h2 class="section-title">Nos Avantages</h2>
        <div class="section-title-underline"></div>
        <p class="section-subtitle">Découvrez pourquoi SystemXey.sn est la plateforme idéale pour votre avenir professionnel ou vos besoins en recrutement.</p>
        <div class="card-grid">
            <div class="card">
                <div class="card-content" style="text-align:center;">
                    <i class="fas fa-bullseye fa-3x" style="color:var(--accent-color); margin-bottom:15px;"></i>
                    <h4 class="card-title" style="font-size:1.2em;">Visibilité Accrue</h4>
                    <p class="card-text">Étudiants, mettez en avant vos compétences. Entreprises, trouvez les perles rares.</p>
                </div>
            </div>
            <div class="card">
                <div class="card-content" style="text-align:center;">
                    <i class="fas fa-handshake fa-3x" style="color:var(--accent-color); margin-bottom:15px;"></i>
                    <h4 class="card-title" style="font-size:1.2em;">Connexions Directes</h4>
                    <p class="card-text">Facilitez la mise en relation entre talents et recruteurs, sans intermédiaire.</p>
                </div>
            </div>
            <div class="card">
                <div class="card-content" style="text-align:center;">
                    <i class="fas fa-cogs fa-3x" style="color:var(--accent-color); margin-bottom:15px;"></i>
                    <h4 class="card-title" style="font-size:1.2em;">Plateforme Intuitive</h4>
                    <p class="card-text">Une interface simple et efficace pour une expérience utilisateur optimale.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="newsletter-section" id="newsletter">
    <div class="container">
        <h3>Restez Informé !</h3>
        <p>Abonnez-vous à notre newsletter pour recevoir les dernières actualités, offres d'emploi et conseils carrière.</p>
        <form action="<?php echo SITE_URL; ?>index.php" method="POST" class="newsletter-form">
            <input type="hidden" name="action" value="subscribe_newsletter">
            <input type="hidden" name="current_view" value="accueil"> 
            <input type="email" name="newsletter_email" placeholder="Votre adresse email" required>
            <button type="submit" class="btn">S'abonner</button>
        </form>
    </div>
</section>

<section id="statistiques-cles">
    <div class="container">
        <h2 class="section-title">Quelques Chiffres Clés</h2>
        <div class="section-title-underline"></div>
        <div class="card-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
            <?php
                $total_etudiants = $pdo->query("SELECT COUNT(*) FROM profils_etudiants WHERE est_visible = 1")->fetchColumn();
                $total_entreprises = $pdo->query("SELECT COUNT(*) FROM entreprises WHERE est_valide_admin = 1")->fetchColumn();
                $total_offres = $pdo->query("SELECT COUNT(*) FROM offres_emploi WHERE est_active = 1 AND statut_validation_admin = 'validee'")->fetchColumn();
            ?>
            <div class="card" style="background: var(--primary-color); color: white; text-align:center; padding:25px;">
                <i class="fas fa-user-graduate fa-3x" style="margin-bottom:15px;"></i>
                <h3 style="font-size:2.5em; margin-bottom:5px;"><?php echo $total_etudiants; ?>+</h3>
                <p style="font-size:1.1em; color: #e0e0e0;">Étudiants Inscrits</p>
            </div>
             <div class="card" style="background: var(--accent-color); color: white; text-align:center; padding:25px;">
                <i class="fas fa-building fa-3x" style="margin-bottom:15px;"></i>
                <h3 style="font-size:2.5em; margin-bottom:5px;"><?php echo $total_entreprises; ?>+</h3>
                <p style="font-size:1.1em; color: #e0e0e0;">Entreprises Partenaires</p>
            </div>
             <div class="card" style="background: var(--secondary-color); color: white; text-align:center; padding:25px;">
                <i class="fas fa-briefcase fa-3x" style="margin-bottom:15px;"></i>
                <h3 style="font-size:2.5em; margin-bottom:5px;"><?php echo $total_offres; ?>+</h3>
                <p style="font-size:1.1em; color: #e0e0e0;">Offres d'Emploi Actives</p>
            </div>
        </div>
    </div>
</section>