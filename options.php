<?php

// Name of plugin options
define('PRESSEPORTAL_OPTIONS_KEY',     'presseportal_options');

// List of options for this plugin
define('PRESSEPORTAL_API_KEY',                 'api_key');
define('PRESSEPORTAL_CRON_ENABLED',            'cron_enabled');
define('PRESSEPORTAL_CRON_LAST_DATE',          'cron_last_date');
define('PRESSEPORTAL_CRON_LAST_STATUS',        'cron_last_status');
define('PRESSEPORTAL_CRON_ADD_PUBLISH',        'cron_add_publish');
define('PRESSEPORTAL_CRON_NOTIFY',             'cron_add_notify');

define('PRESSEPORTAL_DEFAULT_CATEGORY_ID',     'default_category_id');
define('PRESSEPORTAL_DEFAULT_USER_ID',         'default_user_id');
define('PRESSEPORTAL_DEFAULT_FILTER_POSITIVE', 'default_filter_positive');
define('PRESSEPORTAL_DEFAULT_FILTER_NEGATIVE', 'default_filter_negative');

// List of options for each resource
define('PRESSEPORTAL_MAX_RESOURCE_COUNT',      10);
for($resourceNo = 0; $resourceNo < PRESSEPORTAL_MAX_RESOURCE_COUNT; $resourceNo++) {
	define('PRESSEPORTAL_RESOURCE_ID'            . $resourceNo, 'resource_id'              . $resourceNo);
	define('PRESSEPORTAL_RESOURCE_NAME'          . $resourceNo, 'resource_name'            . $resourceNo);
	define('PRESSEPORTAL_RESOURCE_LAST_STORY_ID' . $resourceNo, 'resource_last_story_id'   . $resourceNo);
	define('PRESSEPORTAL_RESOURCE_CATEGORY_ID'   . $resourceNo, 'resource_category_id'     . $resourceNo);
	define('PRESSEPORTAL_RESOURCE_TYPE'          . $resourceNo, 'resource_type'            . $resourceNo);
	define('PRESSEPORTAL_RESOURCE_USER_ID'       . $resourceNo, 'resource_user_id'         . $resourceNo);
	define('PRESSEPORTAL_FILTER_POSITIVE'        . $resourceNo, 'resource_filter_positive' . $resourceNo);
	define('PRESSEPORTAL_FILTER_NEGATIVE'        . $resourceNo, 'resource_filter_negative' . $resourceNo);
}

/**
 * Returns all options for this plugin.
 */
function presseportal_get_options() {
	return get_option(PRESSEPORTAL_OPTIONS_KEY);
}

/**
 * Returns the options with the given name.
 *
 * @param name Name of option
 */
function presseportal_get_option($name) {
	$all_options = presseportal_get_options();
	return trim($all_options[$name]);
}

/**
 * Updates all options set in the given options array.
 *
 * @param options Array of options
 */
function presseportal_update_options($options) {
	update_option(PRESSEPORTAL_OPTIONS_KEY, $options);
	wp_cache_set(PRESSEPORTAL_OPTIONS_KEY, $options);
}

/**
 * Updates the option with the given name and value.
 *
 * @param name Name of option
 * @param value Value of option
 */
function presseportal_update_option($name, $value) {
	$options = presseportal_get_options();
	$options[$name] = $value;
	presseportal_update_options($options);
}

/**
 * Deletes the option with the given name.
 *
 * @param name Name of option
 */
function presseportal_delete_option($name) {
	$options = presseportal_get_options();
	unset($options[$name]);
	presseportal_update_options($options);
}

/**
 * Deletes all options
 */
function presseportal_delete_options() {
	delete_option(PRESSEPORTAL_OPTIONS_KEY);
}

?>