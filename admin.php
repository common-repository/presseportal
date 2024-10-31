<?php
require_once dirname(__FILE__) . '/options.php';
require_once dirname(__FILE__) . '/admin/general.php';
require_once dirname(__FILE__) . '/admin/offices_companies.php';
require_once dirname(__FILE__) . '/admin/cron.php';

/**
 * Shows an error message on the plugins page when plugin configuration is incomplete.
 */
function presseportal_admin_notice() {
	global $pagenow;
    if ($pagenow == 'plugins.php') {
		$api_key = presseportal_get_option(PRESSEPORTAL_API_KEY);

		$resource_id_count = 0;
		for($resourceNo = 0; $resourceNo < PRESSEPORTAL_MAX_RESOURCE_COUNT; $resourceNo++) {
			$resource_id = presseportal_get_option(PRESSEPORTAL_RESOURCE_ID . $resourceNo);
			if (!empty($resource_id)) {
				$resource_id_count++;;
			}
		}

		if (empty($api_key) || ($resource_id_count == 0)) {
			print ('<div id="message" class="error">' . _('Presseportal plugin: Please enter API key and select resources to load', 'Presseportal') . '</div>');
		}
    }
}
add_action('admin_notices', 'presseportal_admin_notice');


/**
 * Options page
 */
function presseportal_option_page() {

	$action_message = '';

	// Save options
	$action_message .= presseportal_option_page_process_general_options();
	$action_message .= presseportal_option_page_process_offices_companies_options();
	$action_message .= presseportal_option_page_process_cron_options();

	/**
	 * Verify changed options
	 */
	if (isset ($_POST['save'])) {

		// Verify that api_key and resource_id are set
		$resource_id_count = 0;
		for($resourceNo = 0; $resourceNo < PRESSEPORTAL_MAX_RESOURCE_COUNT; $resourceNo++) {
			$resource_id = presseportal_get_option(PRESSEPORTAL_RESOURCE_ID . $resourceNo);
			if (!empty($resource_id)) {
				$resource_id_count++;;
			}
		}
		$api_key = presseportal_get_option(PRESSEPORTAL_API_KEY);
		if (empty ($api_key) || ($resource_id_count == 0)) {
			presseportal_update_option(PRESSEPORTAL_CRON_ENABLED, false);
			presseportal_deactivate_cron();
		}

		$action_message .= __('Your settings have been saved.', 'Presseportal');
	}

	// Show messages
	if (!empty($action_message)) {
?>
		<div id="message" class="updated"><?php echo $action_message ?></div>
<?php
	}
?>

	<script type="text/javascript">
		jQuery(document).ready(function() {
			presseportal_admin_show_tab("<?php echo (isset($_POST['admin_tab_index']) ? $_POST['admin_tab_index'] : ''); ?>");
		});
	</script>

	<div id="PresseportalAdmin" class="wrap" style="display:none;">

        <h2>Presseportal - <?php _e('Options', 'Presseportal'); ?></h2>

        <form id="PresseportalOptionsForm" name="settings-form" method="post" action="">

     		<ul>
         		<li><a href="#PresseportalTabGeneral"><?php _e('General', 'Presseportal'); ?></a></li>
         		<li><a href="#PresseportalTabOfficesCompanies"><?php _e('Offices/Companies', 'Presseportal'); ?></a></li>
         		<li><a href="#PresseportalTabCron"><?php _e('Cronjob', 'Presseportal'); ?></a></li>
	     	</ul>

<?php
			presseportal_option_page_show_general_options();
			presseportal_option_page_show_offices_companies_options();
			presseportal_option_page_show_cron_options();
?>

	        <table class="form-table" style="clear:none">
	            <tr>
	                <td scope="row" colspan="2">
						<input type="submit" name="save" value="<?php _e('Save all', 'Presseportal'); ?>" class="button-primary" />
					</td>
	            </tr>

	        </table>

        </form>

 	</div>

<?php

	presseportal_option_page_show_search_dialog();

}


/**
 * Creates the admin menu
 */
function presseportal_create_admin_menu() {
	if(current_user_can('manage_options')){
		// Create new top-level menu
		add_options_page('Presseportal-Plugin',
						 'Presseportal',
						 'manage_options',
						 'presseportal_options',
						 'presseportal_option_page');
	}
}
add_action('admin_menu', 'presseportal_create_admin_menu');

/**
 * Initializes the admin menu
 */
function presseportal_admin_init() {

	wp_enqueue_script('jquery');
	wp_enqueue_script('presseportal-jquery-validate', plugin_dir_url(__FILE__) . 'js/jquery.validate.min.js', array ('jquery'), '1.8.1', true);
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-tabs');

	wp_enqueue_script('jquery-ui-dialog');
	wp_enqueue_style ('wp-jquery-ui-dialog');

	wp_enqueue_style ('presseportal-admin.css', plugin_dir_url(__FILE__) . 'css/admin.css');
	wp_enqueue_script('presseportal-admin.js', plugin_dir_url(__FILE__) . 'js/admin.js', null, null, true);
	$locData = array ('error_no_result'               => __('No result from server.', 'Presseportal'),
	                  'error_unknown'                 => __('An error occurred', 'Presseportal'),
	                  'validation_required_api_key'   => __('Please enter API key', 'Presseportal'),
	                  'validation_required_resource'  => __('Please select resource', 'Presseportal')
	                  );
	wp_localize_script('presseportal-admin.js', 'presseportal', $locData );
}
add_action('admin_init', 'presseportal_admin_init');

?>