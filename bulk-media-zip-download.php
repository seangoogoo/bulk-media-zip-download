<?php
/**
 * Plugin Name: Bulk Media ZIP Download
 * Plugin URI: https://example.com/bulk-media-zip-download
 * Description: Ajoute une action groupée "Télécharger les fichiers (ZIP)" dans la médiathèque WordPress (vue liste) pour télécharger plusieurs médias en une seule archive ZIP.
 * Version: 1.0.1
 * Author: Jensen SIU
 * Author URI: https://www.jensen-siu.net
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: bulk-media-zip-download
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Sécurité : Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe principale du plugin Bulk Media ZIP Download.
 *
 * Gère l'ajout d'une action groupée dans la médiathèque pour télécharger
 * les fichiers sélectionnés sous forme d'archive ZIP.
 *
 * @since 1.0.1
 */
class BMZD_Bulk_Media_Zip_Download {

    /**
     * Clé de l'action groupée.
     *
     * @var string
     */
    const ACTION_KEY = 'download_files_zip';

    /**
     * Nom du nonce pour la sécurité.
     *
     * @var string
     */
    const NONCE_ACTION = 'bmzd_download_media_zip';

    /**
     * Instance unique de la classe (Singleton).
     *
     * @var BMZD_Bulk_Media_Zip_Download|null
     */
    private static $instance = null;

    /**
     * Récupère l'instance unique de la classe.
     *
     * @return BMZD_Bulk_Media_Zip_Download
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructeur privé pour le pattern Singleton.
     * Initialise les hooks WordPress.
     */
    private function __construct() {
        // Hook pour ajouter l'action groupée dans le menu déroulant
        add_filter('bulk_actions-upload', [$this, 'register_bulk_action']);

        // Hook pour traiter l'action groupée sélectionnée
        add_filter('handle_bulk_actions-upload', [$this, 'handle_bulk_action'], 10, 3);

        // Hook pour afficher les messages d'erreur dans l'admin
        add_action('admin_notices', [$this, 'display_admin_notices']);
    }

    /**
     * Ajoute l'action groupée "Télécharger les fichiers (ZIP)" au menu déroulant.
     *
     * @param array $bulk_actions Liste des actions groupées existantes.
     * @return array Liste modifiée avec la nouvelle action.
     */
    public function register_bulk_action($bulk_actions) {
        // Vérifier que l'utilisateur a la capacité upload_files
        if (!current_user_can('upload_files')) {
            return $bulk_actions;
        }

        $bulk_actions[self::ACTION_KEY] = __('Télécharger les fichiers (ZIP)', 'bulk-media-zip-download');

        return $bulk_actions;
    }

    /**
     * Gère le traitement de l'action groupée sélectionnée.
     *
     * Génère directement le ZIP et l'envoie au navigateur.
     * WordPress vérifie déjà le nonce de l'action groupée en interne.
     *
     * @param string $redirect    URL de redirection par défaut.
     * @param string $action      Nom de l'action sélectionnée.
     * @param array  $post_ids    IDs des attachements sélectionnés.
     * @return string URL de redirection (non utilisé si téléchargement direct).
     */
    public function handle_bulk_action($redirect, $action, $post_ids) {
        // Ignorer si ce n'est pas notre action
        if ($action !== self::ACTION_KEY) {
            return $redirect;
        }

        // Vérifier les permissions utilisateur
        if (!current_user_can('upload_files')) {
            return add_query_arg('bmzd_error', 'unauthorized', $redirect);
        }

        // Vérifier qu'il y a des IDs sélectionnés
        if (empty($post_ids)) {
            return add_query_arg('bmzd_error', 'no_selection', $redirect);
        }

        // Vérifier la disponibilité de ZipArchive
        if (!class_exists('ZipArchive')) {
            return add_query_arg('bmzd_error', 'zip_not_available', $redirect);
        }

        // Nettoyer et valider les IDs
        $clean_ids = array_filter(array_map('intval', $post_ids));

        if (empty($clean_ids)) {
            return add_query_arg('bmzd_error', 'invalid_ids', $redirect);
        }

        // Collecter les fichiers valides
        $files_to_zip = $this->collect_valid_files($clean_ids);

        if (empty($files_to_zip)) {
            return add_query_arg('bmzd_error', 'no_valid_files', $redirect);
        }

        // Créer et envoyer le ZIP
        $result = $this->create_and_send_zip($files_to_zip);

        // Si on arrive ici, c'est qu'il y a eu une erreur
        if ($result === false) {
            return add_query_arg('bmzd_error', 'zip_creation_failed', $redirect);
        }

        return $redirect;
    }

    /**
     * Collecte les fichiers valides à partir des IDs d'attachements.
     *
     * @param array $ids Liste des IDs d'attachements.
     * @return array Tableau associatif [nom_dans_zip => chemin_complet].
     */
    private function collect_valid_files($ids) {
        $files = [];
        $used_names = [];

        foreach ($ids as $id) {
            // Vérifier que c'est bien un attachment
            if (get_post_type($id) !== 'attachment') {
                continue;
            }

            // Récupérer le chemin du fichier
            $file_path = get_attached_file($id);

            if (empty($file_path) || !file_exists($file_path)) {
                continue;
            }

            // Générer un nom unique dans l'archive
            $basename = basename($file_path);
            $unique_name = $this->get_unique_filename($basename, $used_names);
            $used_names[] = $unique_name;

            $files[$unique_name] = $file_path;
        }

        return $files;
    }

    /**
     * Génère un nom de fichier unique pour éviter les doublons dans le ZIP.
     *
     * @param string $basename   Nom de base du fichier.
     * @param array  $used_names Liste des noms déjà utilisés.
     * @return string Nom de fichier unique.
     */
    private function get_unique_filename($basename, $used_names) {
        if (!in_array($basename, $used_names, true)) {
            return $basename;
        }

        $pathinfo = pathinfo($basename);
        $name = $pathinfo['filename'];
        $ext = isset($pathinfo['extension']) ? '.' . $pathinfo['extension'] : '';

        $counter = 1;
        do {
            $new_name = $name . '-' . $counter . $ext;
            $counter++;
        } while (in_array($new_name, $used_names, true));

        return $new_name;
    }

    /**
     * Crée l'archive ZIP et l'envoie au navigateur.
     *
     * @param array $files Tableau associatif [nom_dans_zip => chemin_complet].
     * @return bool False en cas d'erreur, ne retourne pas si succès (exit).
     */
    private function create_and_send_zip($files) {
        // Créer un répertoire temporaire si nécessaire
        $upload_dir = wp_upload_dir();
        $tmp_dir = trailingslashit($upload_dir['basedir']) . 'bmzd-tmp';

        if (!file_exists($tmp_dir)) {
            wp_mkdir_p($tmp_dir);
        }

        // Ajouter un fichier .htaccess pour protéger le répertoire
        $htaccess_file = $tmp_dir . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            file_put_contents($htaccess_file, 'deny from all');
        }

        // Ajouter un fichier index.php vide pour empêcher le listing
        $index_file = $tmp_dir . '/index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '<?php // Silence is golden');
        }

        // Générer le nom du fichier ZIP avec horodatage
        $timestamp = date('Ymd-His');
        $zip_filename = 'medias-selection-' . $timestamp . '.zip';
        $zip_path = trailingslashit($tmp_dir) . $zip_filename;

        // Créer l'archive ZIP
        $zip = new ZipArchive();
        $result = $zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($result !== true) {
            return false;
        }

        // Ajouter les fichiers à l'archive
        foreach ($files as $archive_name => $file_path) {
            $zip->addFile($file_path, $archive_name);
        }

        $zip->close();

        // Vérifier que le ZIP a été créé correctement
        if (!file_exists($zip_path)) {
            return false;
        }

        $zip_size = filesize($zip_path);

        // Envoyer les headers HTTP pour le téléchargement
        // Nettoyer les buffers de sortie existants
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Désactiver la compression pour éviter les conflits
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }

        // Headers pour le téléchargement
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
        header('Content-Length: ' . $zip_size);
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Flush les headers
        flush();

        // Envoyer le fichier
        readfile($zip_path);

        // Supprimer le fichier temporaire
        @unlink($zip_path);

        // Terminer le script
        exit;
    }

    /**
     * Affiche les messages d'erreur dans l'interface d'administration.
     */
    public function display_admin_notices() {
        if (!isset($_GET['bmzd_error'])) {
            return;
        }

        $error_code = sanitize_text_field($_GET['bmzd_error']);
        $message = $this->get_error_message($error_code);

        if (!empty($message)) {
            printf(
                '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
                esc_html($message)
            );
        }
    }

    /**
     * Récupère le message d'erreur correspondant à un code.
     *
     * @param string $error_code Code d'erreur.
     * @return string Message d'erreur traduit.
     */
    private function get_error_message($error_code) {
        $messages = [
            'unauthorized'        => __('Vous n\'avez pas les permissions nécessaires pour télécharger des médias.', 'bulk-media-zip-download'),
            'no_selection'        => __('Veuillez sélectionner au moins un média à télécharger.', 'bulk-media-zip-download'),
            'invalid_ids'         => __('Les identifiants de médias fournis sont invalides.', 'bulk-media-zip-download'),
            'zip_not_available'   => __('L\'extension PHP ZipArchive n\'est pas disponible sur ce serveur.', 'bulk-media-zip-download'),
            'no_valid_files'      => __('Aucun fichier valide trouvé parmi les médias sélectionnés.', 'bulk-media-zip-download'),
            'zip_creation_failed' => __('Erreur lors de la création de l\'archive ZIP.', 'bulk-media-zip-download'),
        ];

        return isset($messages[$error_code]) ? $messages[$error_code] : '';
    }
}

/**
 * Initialise le plugin.
 */
BMZD_Bulk_Media_Zip_Download::get_instance();
