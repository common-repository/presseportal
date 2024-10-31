<?php
require_once plugin_dir_path(dirname(__FILE__)) . '/options.php';

/**
 * Handle the settings on the 'Cron'-tab on admin page
 */
function presseportal_option_page_process_cron_options() {

	if (isset ($_POST['save'])) {

		// Activate/deactivate cron job
		if (isset ($_POST['cron_enabled'])) {
			presseportal_update_option(PRESSEPORTAL_CRON_ENABLED, true);
			presseportal_activate_cron();
		} else {
			presseportal_update_option(PRESSEPORTAL_CRON_ENABLED, false);
			presseportal_deactivate_cron();
		}

		// Add new posts as draft
		if (isset ($_POST['cron_add_publish'])) {
			presseportal_update_option(PRESSEPORTAL_CRON_ADD_PUBLISH, true);
		} else {
			presseportal_update_option(PRESSEPORTAL_CRON_ADD_PUBLISH, false);
		}

		// Email notification
		if (isset ($_POST['cron_notify'])) {
			presseportal_update_option(PRESSEPORTAL_CRON_NOTIFY, true);
		} else {
			presseportal_update_option(PRESSEPORTAL_CRON_NOTIFY, false);
		}
	}

	/**
	  * Resets the id of the last processed story
	  */
	if (isset ($_POST['resetStoryId'])) {
		presseportal_update_option(PRESSEPORTAL_CRON_LAST_DATE, '');
		presseportal_update_option(PRESSEPORTAL_CRON_LAST_STATUS, '');

		for($resourceNo = 0; $resourceNo < PRESSEPORTAL_MAX_RESOURCE_COUNT; $resourceNo++) {
			presseportal_update_option(PRESSEPORTAL_RESOURCE_LAST_STORY_ID . $resourceNo, '');
		}

		return __('Last processed story id has been reset.', 'Presseportal');
	}

	/**
	  * Forces loading of stories
	  */
	if (isset ($_POST['loadStories'])) {
		presseportal_load_stories_and_create_posts();

		return __('Stories have been loaded.', 'Presseportal');
	}

}


/**
 * Shows the settings on the 'Cron'-tab on admin page
 */
function presseportal_option_page_show_cron_options() {
	$api_key = presseportal_get_option(PRESSEPORTAL_API_KEY);
?>

    <table id="PresseportalTabCron" class="form-table" style="clear:none">

		<tr valign="top">
			<td colspan="2">
				<div class="adminHelpMessage">
					<img src="<?php echo plugins_url('img/info.gif', dirname(__FILE__))?>" alt="Info" align="middle" />
					<?php _e('New stories can be automatically loaded and added to your blog. This is done every hour.', 'Presseportal'); ?>
				</div>
			</td>
		</tr>

        <tr valign="top">
            <td scope="row" class="label">
            	<label for="cron"><?php _e('Enable cronjob', 'Presseportal'); ?>:</label>
            </td>
            <td>
                <input type="checkbox" name="cron_enabled" value="true" <?php echo (presseportal_is_cron_enabled()) ? 'checked' : ''; ?> >
            </td>
        </tr>

        <tr valign="top">
            <td scope="row" class="label">
            	<label for="cron_add_publish"><?php _e('Publish new posts immediately', 'Presseportal'); ?>:</label>
            </td>
            <td>
            	<input type="checkbox" name="cron_add_publish" value="true" <?php echo presseportal_get_option(PRESSEPORTAL_CRON_ADD_PUBLISH) ? 'checked' : ''; ?> >
            </td>
        </tr>

        <tr valign="top">
            <td scope="row" class="label">
            	<label for="cron_notify"><?php _e('Notify on every new story', 'Presseportal'); ?>:</label>
            </td>
            <td>
            	<input type="checkbox" name="cron_notify" value="true" <?php echo presseportal_get_option(PRESSEPORTAL_CRON_NOTIFY) ? 'checked' : ''; ?> >
            </td>
        </tr>

<?php
		for($resourceNo = 0; $resourceNo < PRESSEPORTAL_MAX_RESOURCE_COUNT; $resourceNo++) {
?>
            <tr valign="top">
                <td scope="row" class="label">
                	<label for="cron_last_story_id<?php echo $resourceNo;?>">
<?php
						_e('Last processed story id', 'Presseportal');
						$resourceNo_name = presseportal_get_option(PRESSEPORTAL_RESOURCE_NAME . $resourceNo);
						if (empty ($resourceNo_name)) {
							$resourceNo_name = __('unknown', 'Presseportal');
						}
						echo "(" . $resourceNo_name . ")";
?>:</label>
                </td>
                <td>
                    <input name="resource_last_story_id<?php echo $resourceNo;?>" size="10"
                    	value="<?php echo presseportal_get_option(PRESSEPORTAL_RESOURCE_LAST_STORY_ID . $resourceNo); ?>"
                    	type="text"
                    	readonly="readonly" />
                </td>
            </tr>
<?php
		}
?>
            <tr valign="top">
                <td scope="row" class="label">
                	<label"><?php _e('Last cron execution time', 'Presseportal'); ?>:</label>
                </td>
                <td>
<?php
							$last_load_date = presseportal_get_option(PRESSEPORTAL_CRON_LAST_DATE);
							if (!empty ($last_load_date)) {
								$last_load_date_time = date_i18n(get_option('date_format') . " " . get_option('time_format'), $last_load_date);
					} else {
						$last_load_date_time = __('unknown', 'Presseportal');
					}

					$last_load_status = presseportal_get_option(PRESSEPORTAL_CRON_LAST_STATUS);
					if (empty ($last_load_status)) {
						$last_load_status = false;
					}

					echo '(' . __('Date/time', 'Presseportal') . ': ' . $last_load_date_time . ', ' . __('State', 'Presseportal') . ': ' . ($last_load_status == true ? __('successfull', 'Presseportal') : __('unknown', 'Presseportal')) . ")";
?>
                </td>

        <tr>
            <td scope="row" colspan="2">
				<input type="submit" name="loadStories" value="<?php _e('Load stories now', 'Presseportal'); ?>" class="button-secondary"
					<?php echo (empty($api_key) ? ' disabled="disabled" ' : '') ?>/>
				<input type="submit" name="resetStoryId" value="<?php _e('Reset last processed story id', 'Presseportal'); ?>" class="button-secondary" />
			</td>
        </tr>

	</table>

<?php
}
?>
