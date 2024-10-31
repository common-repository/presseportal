<?php
require_once plugin_dir_path(dirname(__FILE__)) . '/options.php';
require_once plugin_dir_path(dirname(__FILE__)) . '/Presseportal.class.php';

/**
 * Handles the settings on the 'Offices/Companies'-tab on admin page
 */
function presseportal_option_page_process_offices_companies_options() {

	if (isset ($_POST['save'])) {

		for($resourceNo = 0; $resourceNo < PRESSEPORTAL_MAX_RESOURCE_COUNT; $resourceNo++) {

			$old_resource_id = presseportal_get_option(PRESSEPORTAL_RESOURCE_ID . $resourceNo);

			// Id of resource
			$resource_id = strip_tags($_POST['resource_id' . $resourceNo]);
			if ($resource_id != $old_resource_id) {
				// If a resource id changes the associated last story id must be deleted.
				// Different resources have different story ids
				presseportal_delete_option(PRESSEPORTAL_RESOURCE_LAST_STORY_ID . $resourceNo);
			}
			presseportal_update_option(PRESSEPORTAL_RESOURCE_ID . $resourceNo, $resource_id);

			// Name of resource
			$resourceNo_name = strip_tags($_POST['resource_name' . $resourceNo]);
			presseportal_update_option(PRESSEPORTAL_RESOURCE_NAME . $resourceNo, $resourceNo_name);

			// Category for resource
			$category_id = strip_tags($_POST['resource_category_id' . $resourceNo]);
			presseportal_update_option(PRESSEPORTAL_RESOURCE_CATEGORY_ID . $resourceNo, $category_id);

			// Type of resource
			$resourceNo_type = strip_tags($_POST['resource_type' . $resourceNo]);
			presseportal_update_option(PRESSEPORTAL_RESOURCE_TYPE . $resourceNo, $resourceNo_type);

			// User for resource
			$user_id = strip_tags($_POST['resource_user_id' . $resourceNo]);
			presseportal_update_option(PRESSEPORTAL_RESOURCE_USER_ID . $resourceNo, $user_id);

			// Delete incomplete resources
			if (empty($resource_id)) {
				presseportal_delete_option(PRESSEPORTAL_RESOURCE_ID            . $resourceNo);
				presseportal_delete_option(PRESSEPORTAL_RESOURCE_NAME          . $resourceNo);
				presseportal_delete_option(PRESSEPORTAL_RESOURCE_LAST_STORY_ID . $resourceNo);
				presseportal_delete_option(PRESSEPORTAL_RESOURCE_CATEGORY_ID   . $resourceNo);
				presseportal_delete_option(PRESSEPORTAL_RESOURCE_TYPE          . $resourceNo);
				presseportal_delete_option(PRESSEPORTAL_RESOURCE_USER_ID       . $resourceNo);
				presseportal_delete_option(PRESSEPORTAL_FILTER_POSITIVE        . $resourceNo);
				presseportal_delete_option(PRESSEPORTAL_FILTER_NEGATIVE        . $resourceNo);
			}
		}
	}

}

/**
 * Shows the settings on the 'Offices/Companies'-tab on admin page
 */
function presseportal_option_page_show_offices_companies_options() {
	$api_key = presseportal_get_option(PRESSEPORTAL_API_KEY);
?>

    <table id="PresseportalTabOfficesCompanies" class="form-table" style="clear:none">

		<tr valign="top">
			<td colspan="2">
				<div class="adminHelpMessage">
					<img src="<?php echo plugins_url('img/info.gif', dirname(__FILE__))?>" alt="Info" align="middle" />
					<?php _e('On this page you can define what stories are loaded and added to your blog. Simpley select a public office or a company.', 'Presseportal'); ?>
				</div>
			</td>
		</tr>

<?php
		for($resourceNo = 0; $resourceNo < PRESSEPORTAL_MAX_RESOURCE_COUNT; $resourceNo++) {
?>
            <tr valign="top">
                <td scope="row" class="label">
                	<label for="resource_name<?php echo $resourceNo;?>"><?php _e('Office/Company', 'Presseportal'); ?></label>
                </td>
            	<td>
            		<input name="resource_id<?php echo $resourceNo;?>"
            			id="resource_id<?php echo $resourceNo;?>"
            			size="50"
            			value="<?php echo presseportal_get_option(PRESSEPORTAL_RESOURCE_ID . $resourceNo); ?>"
            			type="hidden" class="regular-text" />
            		<input name="resource_type<?php echo $resourceNo;?>"
            			id="resource_type<?php echo $resourceNo;?>"
            			size="10"
            			value="<?php echo presseportal_get_option(PRESSEPORTAL_RESOURCE_TYPE . $resourceNo); ?>"
            			type="hidden" class="regular-text" />
            		<input name="resource_name<?php echo $resourceNo;?>"
            			id="resource_name<?php echo $resourceNo;?>"
            			class="resource_name"
            			size="50"
            			value="<?php echo presseportal_get_option(PRESSEPORTAL_RESOURCE_NAME . $resourceNo); ?>"
            			type="text" class="regular-text" readonly="readonly" />
            	</td>
            	<td>
            		&nbsp;
            	</td>
        	</tr>
        	<tr>
            	<td>
            		&nbsp;
            	</td>
            	<td>
					<button id="searchResourceDialogButton<?php echo $resourceNo;?>"
						class="searchResourceDialogButton button-secondary" <?php echo (empty($api_key) ? ' disabled="disabled" ' : '') ?>>
						<?php _e('Search for office/company', 'Presseportal'); ?>
					</button>
					<button id="removeResourceButton<?php echo $resourceNo;?>"
						class="removeResourceButton button-secondary">
						<?php _e('Remove office/company', 'Presseportal'); ?>
					</button>
            	</td>
        	</tr>
        	<tr>
                <td scope="row">
                	<label for="resource_user_id<?php echo $resourceNo;?>"><?php _e('User for new posts', 'Presseportal'); ?></label>
                </td>
            	<td>
<?php
					$current_user = presseportal_get_option(PRESSEPORTAL_RESOURCE_USER_ID . $resourceNo);
					wp_dropdown_users("hide_empty=0&name=resource_user_id" . $resourceNo . "&selected=" . $current_user . "&show_option_none=Default");
?>
   				</td>
        	</tr>
        	<tr class="resourceRow">
                <td scope="row">
                	<label for="resource_category_id<?php echo $resourceNo;?>"><?php _e('Category for new posts', 'Presseportal'); ?></label>
                </td>
            	<td>
<?php
					$current_cat = presseportal_get_option(PRESSEPORTAL_RESOURCE_CATEGORY_ID . $resourceNo);
					wp_dropdown_categories("hide_empty=0&name=resource_category_id" . $resourceNo . "&selected=" . $current_cat . "&show_option_none=Default");
?>
   				</td>
        	</tr>
<?php
		}
?>

	</table>

<?php
}


/**
 * Shows a dialog to search for offices/companies
 */
function presseportal_option_page_show_search_dialog() {
?>

  	<div id="searchResourceDialog" class="wp-dialog" style="display:none" title="<?php _e('Search for resources', 'Presseportal'); ?>">

		<div class="adminHelpMessage">
			<img src="<?php echo plugins_url('img/info.gif', dirname(__FILE__))?>" alt="Info" align="middle" />
			<?php _e('You can search for German companies here. Start the search and select one item from the result list. Press apply to accept.', 'Presseportal'); ?>
		</div>

		<form>
			<table>
				<tr>
					<td>
						<label for="searchResourceType" class="label"><?php _e('Type', 'Presseportal'); ?>:
					</td>
					<td>
						<select id="searchResourceType" name="type">
							<option value="OFFICE" selected><?php _e('Office', 'Presseportal'); ?></option>
							<option value="COMPANY"><?php _e('Company', 'Presseportal'); ?></option>
						</select>
					</td>
				</tr>

				<tr>
					<td>
						<label for="searchResourceTerms" class="label"><?php _e('Search terms', 'Presseportal'); ?>:</label>
					</td>
					<td>
						<input id="searchResourceTerms" size="30" type="text" class="regular-text" />
					</td>
				</tr>

				<tr>
					<td colspan="2" style="text-align: center;">
						<span id="searchResourceErrorMessage"></span>
					</td>
				</tr>

				<tr>
					<td colspan="2" style="text-align: center;">
						<button id="searchResourceButton" class="button-primary">
							<?php _e('Search', 'Presseportal'); ?>
						</button>
						<button id="cancelSearchResourceDialog" class="button-primary"><?php _e('Cancel', 'Presseportal'); ?></button>
					</td>
				</tr>
			</table>
		</form>

		<div id="searchResourceResult" style="display: none;">
			<hr/>

			<label for="resource_id"><?php _e('Search result', 'Presseportal'); ?>:</label>
			<select id="resourceSelector" name="resource_id"></select>
			<br/>
			<p style="text-align: center;">
				<button id="applySearchResourceDialog" class="button-primary"><?php _e('Apply', 'Presseportal'); ?></button>
			</p>
		</div>

	</div>

<?php
}


/**
 * Ajax function: Searches for resources
 */
function presseportal_search_resources_callback() {

	$result = array();

	$terms = trim($_POST['terms']);
	$type = trim($_POST['type']);

    $api_key = presseportal_get_option(PRESSEPORTAL_API_KEY);
	if (empty($api_key)) {
		// If api key is not in database, use api_key from request
		$api_key = trim($_POST['api_key']);
	};

	if (!empty ($terms) && !empty ($type) && !empty ($api_key)) {
	    $pp = new Presseportal($api_key, 'de');
	    $pp->format = 'xml';
	    $pp->limit = '30';

		if ($type == 'COMPANY') {
			$response = $pp->search_company($terms);
		}
		else {
			$response = $pp->search_office($terms);
		}

		if((!$response->error) && ($response->offices)) {
			foreach($response->offices AS $resourceNo) {
				$result[] = array('name' => $resourceNo->name, 'id' => $resourceNo->id);
			}
		}
		else {
			// Empty result
		}
	}

	// Return reponse
	echo json_encode($result);

	// this is required to return a proper result
	die();
}
add_action('wp_ajax_presseportal_search_resources', 'presseportal_search_resources_callback');

?>
