<?php
require_once plugin_dir_path(dirname(__FILE__)) . '/options.php';

/**
 * Handles the settings on the 'General'-tab on admin page
 */
function presseportal_option_page_process_general_options() {

	if (isset ($_POST['save'])) {
		presseportal_update_option(PRESSEPORTAL_API_KEY,             strip_tags($_POST['api_key']));
		presseportal_update_option(PRESSEPORTAL_DEFAULT_USER_ID,     strip_tags($_POST['default_user_id']));
		presseportal_update_option(PRESSEPORTAL_DEFAULT_CATEGORY_ID, strip_tags($_POST['default_category_id']));

		presseportal_update_option(PRESSEPORTAL_DEFAULT_FILTER_POSITIVE, strip_tags($_POST['filter_positive']));
		presseportal_update_option(PRESSEPORTAL_DEFAULT_FILTER_NEGATIVE, strip_tags($_POST['filter_negative']));
	}

}

/**
 * Shows the settings on the 'General'-tab on admin page
 */
function presseportal_option_page_show_general_options() {
	$api_key = presseportal_get_option(PRESSEPORTAL_API_KEY);
?>
    <table id="PresseportalTabGeneral" class="form-table" style="clear:none">
		<tr valign="top">
			<td colspan="2">
				<div class="adminHelpMessage">
					<img src="<?php echo plugins_url('img/info.gif', dirname(__FILE__))?>" alt="Info" align="middle" />
					<?php _e('This page contains fundamental plugin settings. Without API key and resource id the plugin is unable to work.', 'Presseportal'); ?>
					<br>
					<?php _e('If you do not have an API key, please register on the following page to get one', 'Presseportal'); ?>:
					<a href="http://www.presseportal.de/services/" target="_blank"><?php _e('Click here', 'Presseportal'); ?></a>.
				</div>
			</td>
		</tr>

        <tr valign="top">
            <td scope="row" class="label">
            	<label for="api_key"><?php _e('Presseportal API key', 'Presseportal'); ?>:</label>
            </td>
            <td>
				<?php $api_key = presseportal_get_option(PRESSEPORTAL_API_KEY); ?>
                <input name="api_key" id="api_key" size="50" value="<?php echo $api_key; ?>" type="text" class="regular-text" />
            </td>
        </tr>

        <tr valign="top">
            <td scope="row" class="label">
            	<label for="cron_user_id"><?php _e('Default user for new posts', 'Presseportal'); ?>:</label>
            </td>
            <td>
<?php
							$current_user = presseportal_get_option(PRESSEPORTAL_DEFAULT_USER_ID);
							wp_dropdown_users("orderby=user_nicename&name=default_user_id&selected=" . $current_user);
?>
			</td>
        </tr>

        <tr valign="top">
            <td scope="row" class="label">
            	<label for="cron_category_id"><?php _e('Default category for new posts', 'Presseportal'); ?>:</label>
            </td>
            <td>
<?php
						$current_cat = presseportal_get_option(PRESSEPORTAL_DEFAULT_CATEGORY_ID);
						wp_dropdown_categories("hide_empty=0&name=default_category_id&selected=" . $current_cat . "&show_option_none=Keine");
?>
			</td>
        </tr>

        <tr valign="top">
            <td scope="row" class="label">
            	<label for="filter_positive"><?php _e('Default positive filters', 'Presseportal'); ?>:<br/>
                	(<?php _e('Separate multiple filters by comma.', 'Presseportal'); ?>)
                </label>
            </td>
            <td>
                <textarea name="filter_positive" cols="50" rows="5"><?php echo presseportal_get_option(PRESSEPORTAL_DEFAULT_FILTER_POSITIVE); ?></textarea>
            </td>
        </tr>

        <tr valign="top">
            <td scope="row" class="label">
            	<label for="filter_negative"><?php _e('Default negative filters', 'Presseportal'); ?>:<br/>
                	(<?php _e('Separate multiple filters by comma.', 'Presseportal'); ?>)
                </label>
            </td>
            <td>
                <textarea name="filter_negative" cols="50" rows="5"><?php echo presseportal_get_option(PRESSEPORTAL_DEFAULT_FILTER_NEGATIVE); ?></textarea>
            </td>
        </tr>

	</table>
<?php
}

?>
