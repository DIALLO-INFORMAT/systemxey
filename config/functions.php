<?php
// config/functions.php

// Ce bloc assure que $pdo est disponible même si ce fichier est inclus
// avant que config.php ne soit inclus dans le script principal (peu probable avec notre structure actuelle, mais bonne pratique).
if (!isset($pdo) && file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} elseif (!isset($pdo)) {
    // Fallback critique si $pdo n'est pas défini et config.php introuvable depuis ici
    // Cela ne devrait jamais arriver si functions.php est toujours inclus après config.php
    error_log("PDO instance not found in functions.php. Ensure config.php is included first.");
    // die("Critical error: Database connection not available in functions."); // Optionnel: arrêter le script
}


function redirect($url_path) {
    // S'assurer que $url_path ne contient pas déjà SITE_URL pour éviter la duplication
    if (strpos($url_path, SITE_URL) === 0) {
        header("Location: " . $url_path);
    } else {
        header("Location: " . SITE_URL . ltrim($url_path, '/'));
    }
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

function isAdmin() {
    return isLoggedIn() && getUserRole() === 'admin';
}

function isStudent() {
    return isLoggedIn() && getUserRole() === 'etudiant';
}

function isCompany() {
    return isLoggedIn() && getUserRole() === 'entreprise';
}

function set_flash_message($message, $type = 'success') {
    $_SESSION['flash_message'] = ['text' => $message, 'type' => $type];
}

function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message_data = $_SESSION['flash_message']; // Renommé pour éviter conflit avec variable $message
        echo '<div class="flash-message ' . esc_html($message_data['type']) . '">' . esc_html($message_data['text']) . '</div>';
        unset($_SESSION['flash_message']);
    }
}

/**
 * Récupère les informations du logo du site.
 * Priorise l'image si une URL valide est fournie pour la clé 'logo_site_url'.
 * Sinon, utilise le texte de la clé 'nom_site_texte_header' ou SITE_NAME par défaut.
 *
 * @param PDO $pdo_conn Instance de PDO
 * @return array ['type' => 'image'|'text', 'value' => 'url_ou_texte']
 */
function get_site_logo_info($pdo_conn) {
    static $logo_info_cache = null; 

    if ($logo_info_cache === null) {
        $logo_url_val = null;
        $logo_text_val = defined('SITE_NAME') ? SITE_NAME : 'SystemXey'; // Fallback si SITE_NAME n'est pas défini

        try {
            // S'assurer que $pdo_conn est une instance valide de PDO
            if (!$pdo_conn instanceof PDO) {
                error_log("Invalid PDO connection provided to get_site_logo_info.");
                // Retourner une valeur par défaut si $pdo_conn n'est pas valide
                return ['type' => 'text', 'value' => $logo_text_val];
            }

            // Essayer de récupérer l'URL du logo image
            $stmt_logo_url = $pdo_conn->prepare("SELECT valeur_contenu_lien FROM contenu_site WHERE cle_contenu = 'logo_site_url' AND type_contenu = 'lien_image' AND valeur_contenu_lien IS NOT NULL AND valeur_contenu_lien != ''");
            $stmt_logo_url->execute();
            $fetched_logo_url = $stmt_logo_url->fetchColumn();
            if ($fetched_logo_url && filter_var($fetched_logo_url, FILTER_VALIDATE_URL)) {
                $logo_url_val = $fetched_logo_url;
            }

            // Essayer de récupérer le texte du logo si l'URL de l'image n'est pas valide ou non trouvée
            if (!$logo_url_val) {
                $stmt_logo_text = $pdo_conn->prepare("SELECT valeur_contenu_texte FROM contenu_site WHERE cle_contenu = 'nom_site_texte_header' AND type_contenu = 'texte'");
                $stmt_logo_text->execute();
                $fetched_logo_text = $stmt_logo_text->fetchColumn();
                if ($fetched_logo_text && !empty(trim($fetched_logo_text))) {
                    $logo_text_val = trim($fetched_logo_text);
                }
            }
        } catch (PDOException $e) {
            error_log("Erreur de récupération du logo du site: " . $e->getMessage());
            // Utiliser les valeurs par défaut en cas d'erreur BDD
        }

        if ($logo_url_val) {
            $logo_info_cache = ['type' => 'image', 'value' => $logo_url_val];
        } else {
            $logo_info_cache = ['type' => 'text', 'value' => $logo_text_val];
        }
    }
    return $logo_info_cache;
}


// Fonction pour récupérer le contenu générique du site depuis la BDD
function get_site_content($cle_contenu, $pdo_conn) {
    static $site_contents_cache = []; // Cache statique pour éviter les requêtes répétées

    // S'assurer que $pdo_conn est une instance valide de PDO
    if (!$pdo_conn instanceof PDO) {
        error_log("Invalid PDO connection provided to get_site_content for key: " . $cle_contenu);
        return "[$cle_contenu - Erreur BDD]";
    }

    // Si le cache est vide, charger tous les contenus une seule fois par requête
    if (empty($site_contents_cache)) { 
        try {
            $stmt_all_content = $pdo_conn->query("SELECT cle_contenu, valeur_contenu_texte, valeur_contenu_lien, type_contenu FROM contenu_site");
            while ($row_content = $stmt_all_content->fetch()) {
                $site_contents_cache[$row_content['cle_contenu']] = $row_content;
            }
        } catch (PDOException $e) {
            error_log("Erreur de récupération initiale du contenu du site: " . $e->getMessage());
            // On ne remplit pas le cache, la fonction essaiera de récupérer la clé spécifique ci-dessous si le cache est vide.
        }
    }

    // Vérifier si la clé spécifique est dans le cache
    if (isset($site_contents_cache[$cle_contenu])) {
        $content_item = $site_contents_cache[$cle_contenu];
        if ($content_item['type_contenu'] === 'lien_image' || $content_item['type_contenu'] === 'lien_url') {
            return esc_html($content_item['valeur_contenu_lien'] ?? '');
        }
        return esc_html($content_item['valeur_contenu_texte'] ?? '');
    } 
    // Si la clé n'est pas dans le cache (par exemple, après une erreur de chargement initial du cache),
    // essayer de la récupérer spécifiquement. (Optionnel, mais peut aider si le chargement initial échoue)
    // else {
    //     try {
    //         $stmt_single_content = $pdo_conn->prepare("SELECT valeur_contenu_texte, valeur_contenu_lien, type_contenu FROM contenu_site WHERE cle_contenu = ?");
    //         $stmt_single_content->execute([$cle_contenu]);
    //         $content_item_single = $stmt_single_content->fetch();
    //         if ($content_item_single) {
    //             $site_contents_cache[$cle_contenu] = $content_item_single; // Mettre en cache pour la prochaine fois
    //             if ($content_item_single['type_contenu'] === 'lien_image' || $content_item_single['type_contenu'] === 'lien_url') {
    //                 return esc_html($content_item_single['valeur_contenu_lien'] ?? '');
    //             }
    //             return esc_html($content_item_single['valeur_contenu_texte'] ?? '');
    //         }
    //     } catch (PDOException $e) {
    //         error_log("Erreur de récupération du contenu spécifique '$cle_contenu': " . $e->getMessage());
    //     }
    // }
    
    // Valeur par défaut si la clé n'est pas trouvée du tout
    return "[$cle_contenu non défini]"; 
}

function esc_html($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function truncate_text($text, $length = 100, $ending = '...') {
    if (mb_strlen($text ?? '') > $length) { // Ajout de ?? '' pour éviter erreur sur null
        $text = mb_substr($text, 0, $length - mb_strlen($ending)) . $ending;
    }
    return $text;
}

function generate_filter_options($pdo_conn, $table, $column, $distinct = true, $current_value = '') {
    $options_html = '';
    // S'assurer que $pdo_conn est valide
    if (!$pdo_conn instanceof PDO) {
        error_log("Invalid PDO connection for generate_filter_options ($table.$column).");
        return "<option value=\"\">Erreur chargement (BDD)</option>";
    }
    try {
        // Valider les noms de table et de colonne (simplifié, pour une vraie appli, utiliser une whitelist)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table) || !preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            throw new InvalidArgumentException("Nom de table ou de colonne invalide pour le filtre.");
        }

        $query = "SELECT " . ($distinct ? "DISTINCT " : "") . "`" . $column . "`" . 
                 " FROM `" . $table . "`" . 
                 " WHERE `" . $column . "` IS NOT NULL AND `" . $column . "` != '' ORDER BY `" . $column . "` ASC";
        $stmt = $pdo_conn->query($query);
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $value = esc_html($row[0]);
            $selected = ($current_value === $value) ? 'selected' : '';
            $options_html .= "<option value=\"$value\" $selected>$value</option>";
        }
    } catch (PDOException $e) {
        error_log("Erreur génération filtres pour $table.$column: " . $e->getMessage());
        $options_html .= "<option value=\"\">Erreur chargement</option>";
    } catch (InvalidArgumentException $e) {
        error_log("Erreur argument filtre: " . $e->getMessage());
        $options_html .= "<option value=\"\">Erreur config filtre</option>";
    }
    return $options_html;
}

/**
 * Formate une date en français.
 * @param string $date_string Date au format YYYY-MM-DD ou YYYY-MM-DD HH:MM:SS
 * @param string $format 'short', 'long', 'datetime'
 * @return string Date formatée ou la chaîne originale si invalide
 */
function format_date_fr($date_string, $format = 'short') {
    if (empty($date_string) || $date_string === '0000-00-00' || $date_string === '0000-00-00 00:00:00') {
        return 'N/A';
    }
    try {
        $date = new DateTime($date_string);
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE);
        
        if ($format === 'long') {
            $formatter->setPattern('d MMMM yyyy');
        } elseif ($format === 'datetime') {
            $formatter->setPattern('d MMMM yyyy HH:mm');
        } elseif ($format === 'short_month_year') {
            $formatter->setPattern('MMMM yyyy');
        } else { // short par défaut
            $formatter->setPattern('dd/MM/yyyy');
        }
        return $formatter->format($date);
    } catch (Exception $e) {
        return $date_string; // Retourner la date originale si le formatage échoue
    }
}
?>