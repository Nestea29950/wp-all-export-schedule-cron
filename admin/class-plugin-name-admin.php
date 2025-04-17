<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/admin
 * @author     Your Name <email@example.com>
 */
class Plugin_Name_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {


		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/plugin-name-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/plugin-name-admin.js', array( 'jquery' ), $this->version, false );

	}

}

// Ajouter un menu personnalisé dans l'admin de WordPresss
function mon_plugin_menu() {
    add_menu_page(
        'Mon Plugin Export',                // Titre de la page
        'Export',                           // Nom dans le menu
        'manage_options',                   // Capacité d'accès (administrateur)
        'mon-plugin-export',                // Slug de la page
        'mon_plugin_export_page',           // Fonction pour afficher le contenu de la page
        'dashicons-download',               // Icône du menu
        20                                   // Position dans le menu
    );
}
add_action('admin_menu', 'mon_plugin_menu');

// Fonction pour afficher le contenu de la page d'administration du plugin

function mon_plugin_export_page() {
    $base_url = site_url('/wp-load.php');
    $export_dir = plugin_dir_path(__FILE__) . 'exports/';

    $exports_disponibles = getListWPallExport();

    $user_id = get_current_user_id();
    $exports_suivis = get_exports_suivis_par_user($user_id);
    
    $secret = get_option('roquette_secret_key_wp_allexport');
    $api = get_option('roquette_api_key_wp_allexport');
    

    // Enregistrer params clé secret + api keys
    if (isset($_POST['register_params_secret'])) {
        if (get_option('roquette_secret_key_wp_allexport') === false) {
            add_option('roquette_secret_key_wp_allexport', $_POST['secret_key'], '', 'yes');
        } else {
            update_option('roquette_secret_key_wp_allexport', $_POST['secret_key']);
        }
    
        if (get_option('roquette_api_key_wp_allexport') === false) {
            add_option('roquette_api_key_wp_allexport', $_POST['api_key'], '', 'yes');
        } else {
            update_option('roquette_api_key_wp_allexport', $_POST['api_key']);
        }
    }
    


    // Ajout de l'export dans la table suivi
    if (isset($_POST['ajouter_export']) && isset($_POST['export_id'])) {
        $user_id = get_current_user_id();
        $export_id = $_POST['export_id'];
        $start_at = $_POST['start_at'];
        $interval_minutes = $_POST['interval_minutes'];
    
        $result = ajouter_export_suivi($user_id, $export_id, $start_at , $interval_minutes);
    
        if ($result === 'added') {
            echo '<div class="notice notice-success"><p>Export ajouté !</p></div>';
        } elseif ($result === 'already_exists') {
            echo '<div class="notice notice-info"><p>Export déjà suivi.</p></div>';
        }
    }

    // Gestion de la suppression dans la table export suivi
    if (isset($_POST['retirer_export']) && isset($_POST['export_id'])) {
        $user_id = get_current_user_id();
        $export_id = $_POST['export_id'];
    
        $result = retirer_export_suivi($user_id, $export_id);
    
        if ($result === 'removed') {
            echo '<div class="notice notice-warning"><p>Export retiré.</p></div>';
        } elseif ($result === 'not_found') {
            echo '<div class="notice notice-info"><p>Aucun export à retirer.</p></div>';
        }
    }
    

    // Export maintenant
    if (isset($_POST['exporter_maintenant']) && isset($_POST['export_id'])) {
        $export_id = intval($_POST['export_id']);
        $export_key = "MtfgACgxqz1n";
        $cle_secrete = '871a37331d1e0541';
  

        $urls = [
            "trigger" => "$base_url?export_key=$export_key&export_id=$export_id&action=trigger",
            "processing" => "$base_url?export_key=$export_key&export_id=$export_id&action=processing",
            "file" => "$base_url?security_token=$cle_secrete&export_id=$export_id&action=get_data"
        ];
        
     
        // Télécharger le fichier
        foreach (['trigger', 'processing'] as $step) {
            wp_remote_get($urls[$step]);
        }


        $response = wp_remote_get($urls['file']);
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $filename = $export_dir . "export_{$export_id}_" . date('Ymd_His') . ".csv";
            file_put_contents($filename, $body);
            echo '<div class="notice notice-success"><p>Export téléchargé avec succès : <a href="' . plugin_dir_url(__FILE__) . 'exports/' . basename($filename) . '" target="_blank">' . basename($filename) . '</a></p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Erreur lors du téléchargement de l\'export.</p></div>';
        }
    }
    
    ?>
    <div class="wrap">
        <h1>Paramètres Wp all export : </h1>
        <form method="post">
            <label for="secret_key">Clé secrete :</label>
            <input type="text" name="secret_key" value="<?php echo($secret) ?>">
            <label for="api_key">Clé api :</label>
            <input type="text" name="api_key" value="<?php echo($api) ?>">
            <input type="submit" name="register_params_secret" class="button button-primary" value="Enregistrer paramètres">
        </form>
        <br>
        <h1>Sélectionner un export WP All Export</h1>
        <form method="post" action="">
            <label for="export_select">Choisir un export :</label>
            <select name="export_id" id="export_select">
                <?php foreach ($exports_disponibles as $export): ?>
                    <option value="<?php echo esc_attr($export['id']); ?>">
                        <?php echo esc_html($export['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br>
                <label for="start_at">Mettre un format de type : <code> YYYY-MM-DD HH:MM:SS </code></label>
                <input type="datetime-local" name="start_at" >
            <br>
            <label for="interval_minutes">Interval <code> en minute(s) </code></label>
                <input type="text" name="interval_minutes" >
            <br>
            <br>
            <input type="submit" name="ajouter_export" class="button button-primary" value="Ajouter cet export">
        </form>

        <?php if (!empty($exports_suivis)): ?>
            <hr>
            <h2>Exports suivis</h2>
            <?php foreach ($exports_suivis as $export): ?>
                <div style="border: 1px solid #ccc; padding: 15px; margin-bottom: 20px;">
                    <strong><?php echo esc_html($export->export_name); ?></strong> (ID: <?php echo $export->export_id; ?>)<br>
                    <strong>Début :</strong> <?php echo esc_html($export->start_at); ?><br>
                    <strong>Intervalle :</strong> <?php echo esc_html($export->interval_minutes); ?> minute(s)<br>
                    <strong>Derniere fois lancé :</strong> <?php echo esc_html($export->last_run); ?> <br><br>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="export_id" value="<?php echo $export->export_id; ?>">
                        <input type="submit" name="retirer_export" class="button button-secondary" value="Retirer">
                        <input type="submit" name="exporter_maintenant" class="button button-primary" value="Exporter maintenant">
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>


    </div>
    <?php
}


function getListWPallExport(){
    global $wpdb;
    $table = $wpdb->prefix . 'pmxe_exports';
    $exports = $wpdb->get_results("SELECT id, friendly_name FROM $table");

    $result = [];
    if ($exports) {
        foreach ($exports as $export) {
            $result[] = [
                'id' => $export->id,
                'name' => $export->friendly_name
            ];
        }
    }

    return $result;
}


function mon_plugin_nom_export($exports, $id) {
    foreach ($exports as $export) {
        if ($export['id'] == $id) {
            return $export['name'];
        }
    }
    return 'Export inconnu';
}

function ajouter_export_suivi($user_id, $export_id ,$start_at, $interval_minutes) {
    global $wpdb;
    $table = $wpdb->prefix . 'roquette_table_cron_export';

    $export_id = intval($export_id);
    $user_id = intval($user_id);

    // Vérifier si l'entrée existe déjà
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE user_id = %d AND export_id = %d",
        $user_id, $export_id
    ));

    if (!$exists) {
        // Essayer d'insérer et afficher l'erreur si nécessaire
        $inserted = $wpdb->insert($table, [
            'user_id' => $user_id,
            'export_id' => $export_id,
            'start_at' => $start_at,
            'interval_minutes' => $interval_minutes,
        ]);

        // Vérifie si l'insertion a échoué
        if ($inserted === false) {
            // Afficher l'erreur SQL de WordPress
            return 'Error: ' . $wpdb->last_error;
        }
        return 'added';
    } else {
        return 'already_exists';
    }
}


function retirer_export_suivi($user_id, $export_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'roquette_table_cron_export';

    $export_id = intval($export_id);
    $user_id = intval($user_id);

    $deleted = $wpdb->delete($table, [
        'user_id' => $user_id,
        'export_id' => $export_id,
    ]);

    return $deleted ? 'removed' : 'not_found';
}

function get_exports_suivis_par_user($user_id) {
    global $wpdb;
    $table_cron_export = $wpdb->prefix . 'roquette_table_cron_export';
    $table_exports = $wpdb->prefix . 'pmxe_exports';

    // Récupère tous les export_id et les informations supplémentaires associés à l'utilisateur
    $query = "
        SELECT
            e.id AS export_id,
            e.friendly_name AS export_name,
            r.start_at,
            r.interval_minutes,
            r.last_run
        FROM $table_cron_export r
        INNER JOIN $table_exports e ON r.export_id = e.id
        WHERE r.user_id = %d
    ";

    $results = $wpdb->get_results($wpdb->prepare($query, $user_id));

    return $results;
}



// -----------------CRON 

add_filter('cron_schedules', function($schedules) {
    $schedules['5_minutes'] = [
        'interval' => 300,
        'display' => 'Toutes les 5 minutes'
    ];
    return $schedules;
});

if (!wp_next_scheduled('roquette_check_recurring_exports')) {
    wp_schedule_event(time(), '5_minutes', 'roquette_check_recurring_exports');
}

add_action('roquette_check_recurring_exports', 'roquette_lancer_exports_si_necessaire');

function roquette_lancer_exports_si_necessaire() {
    global $wpdb;
    $table = $wpdb->prefix . 'roquette_table_cron_export';

    $exports = $wpdb->get_results("SELECT * FROM $table");
    $now = current_time('timestamp');

    foreach ($exports as $export) {
        $export_id = $export->export_id;
        $interval = intval($export->interval_minutes) * 60;

        // Si last_run n'est pas défini, on prend start_at
        $last_run = $export->last_run ? strtotime($export->last_run) : strtotime($export->start_at);

        if ($now >= ($last_run + $interval)) {
            roquette_execute_export($export_id);

            // Mettre à jour la date d'exécution dans la BDD
            $wpdb->update(
                $table,
                ['last_run' => current_time('mysql')],
                ['id' => $export->id]
            );
        }
    }
}


function roquette_execute_export($export_id) {
    $base_url = site_url('/wp-load.php');
    $export_dir = plugin_dir_path(__FILE__) . 'exports/';

    $secret = get_option('roquette_secret_key_wp_allexport');
    $api = get_option('roquette_api_key_wp_allexport');
    

    $urls = [
        "trigger" => "$base_url?export_key=$api&export_id=$export_id&action=trigger",
        "processing" => "$base_url?export_key=$api&export_id=$export_id&action=processing",
        "file" => "$base_url?security_token=$secret&export_id=$export_id&action=get_data"
    ];

    foreach (['trigger', 'processing'] as $step) {
        wp_remote_get($urls[$step]);
    }

    $response = wp_remote_get($urls['file']);
    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $filename = $export_dir . "export_{$export_id}_" . date('Ymd_His') . ".csv";
        file_put_contents($filename, $body);
    }
}


// Shortcode : 

function roquette_shortcode_liste_exports() {
    $export_dir = plugin_dir_path(__FILE__) . 'exports/';
    $export_url = plugin_dir_url(__FILE__) . 'exports/';

    if (!is_dir($export_dir)) {
        return '<p>Le dossier des exports n\'existe pas.</p>';
    }

    $files = scandir($export_dir);
    $output = '<ul class="liste-exports">';

    foreach ($files as $file) {
        // Ne garder que les fichiers valides
        if ($file === '.' || $file === '..') continue;

        $filepath = $export_dir . $file;

        if (is_file($filepath)) {
            $output .= '<li><a href="' . esc_url($export_url . $file) . '" download>' . esc_html($file) . '</a></li>';
        }
    }

    $output .= '</ul>';

    return $output;
}
add_shortcode('liste_exports_csv', 'roquette_shortcode_liste_exports');
