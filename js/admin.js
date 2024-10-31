jQuery(document).ready(function() {
	presseportal_admin_init_form_validation();
	presseportal_admin_init_tabs();
	presseportal_admin_init_search_dialog();
});

/**
 * Initializes the form validation in admin area
 */
function presseportal_admin_init_form_validation() {

	// Form validation
	jQuery("#PresseportalOptionsForm").validate({
		rules: {
			api_key: {
				required: true
			},
			resource_name: {
				required: true
			}
		},
		messages: {
			api_key: presseportal.validation_required_api_key,
			resource_name: presseportal.validation_required_resource,
		},
		invalidHandler: function(form, validator) {
            var element = jQuery(validator.errorList[0].element);
            var tab = element.parents("table")[0];
			jQuery("#PresseportalOptionsForm").tabs('select', tab.id);
		}
	});
}

/**
 * Initializes the tabs in admin area
 */
function presseportal_admin_init_tabs() {

	// Init tabs
	jQuery("#PresseportalOptionsForm").tabs();

	// Remember tab after clicking the 'save' button
	jQuery("#PresseportalOptionsForm").submit(function() {
		var $form = this;
  		selected_tab_idx = jQuery("#PresseportalOptionsForm").tabs("option", "active");
		jQuery('<input />', {type: 'hidden', name: 'admin_tab_index', value: selected_tab_idx}).appendTo($form);
		return true;
	});

	// Show tabs
	jQuery("#PresseportalAdmin").show();
}

/**
 * Show the tab with the given index.
 */
function presseportal_admin_show_tab(tab_index) {
	jQuery("#PresseportalOptionsForm").tabs({active: tab_index});
}

/**
 * Initializes the dialog for search for resources in admin area
 */
function presseportal_admin_init_search_dialog() {

	// Enable search dialog button only if api_key is set
	jQuery("#api_key").keyup(function() {
		api_key = jQuery("#api_key").val();
		if (api_key) {
			jQuery(".searchResourceDialogButton").removeAttr("disabled");
		}
		else {
			jQuery(".searchResourceDialogButton").attr("disabled", "disabled");
		}
		return true;
	});

	// Open search dialog on click on button click
	jQuery(".searchResourceDialogButton").each(function(resource_index) {
		jQuery(this).click(function() {
			presseportal_admin_open_search_dialog(resource_index);
			return false;
		});
	});

	// Open search dialog on click on resource name
	jQuery(".resource_name").each(function(resource_index) {
		jQuery(this).click(function() {
			presseportal_admin_open_search_dialog(resource_index);
			return false;
		});
	});

	// Delete resource button
	jQuery(".removeResourceButton").each(function(resource_index) {
		jQuery(this).click(function() {
			jQuery("#resource_id" + resource_index).val("");
			jQuery("#resource_name" + resource_index).val("");
			return false;
		});
	});

	// Init cancel button in serch dialog
	jQuery("#cancelSearchResourceDialog").click(function() {
		jQuery("#searchResourceDialog").dialog("destroy");
		return false;
	});

	// On button click start searching
	jQuery("#searchResourceButton").click(function() {
		presseportal_admin_process_search();
		return false;
	});
}

/**
 * Opens the search dialog
 */
function presseportal_admin_open_search_dialog(resource_index) {

	api_key = jQuery('#api_key').val();
	if (api_key) {

		// Init apply button in search dialog
		jQuery("#applySearchResourceDialog").unbind('click');
		jQuery("#applySearchResourceDialog").click(function() {
			presseportal_admin_apply_selected_resource(resource_index);
			jQuery("#searchResourceDialog").dialog("close");
			return false;
		});

		jQuery("#searchResourceDialog").dialog({
			closeOnEscape: true,
			modal: true,
			width: 500,
			dialogClass: 'wp-dialog',
			open: function(event, ui) {
				// Set initial search terms
				jQuery('#searchResourceTerms').val(jQuery('#resource_name').val());

				// Enable search button
				jQuery("#searchResourceButton").removeAttr("disabled");

				// Hide result list
				jQuery("#searchResourceResult").hide();

				// Hide error message
				jQuery("#searchResourceErrorMessage").hide();
			}
		});
	}
}

/**
 * Processes the search for companies.
 */
function presseportal_admin_process_search() {
	// Disable search button
	jQuery("#searchResourceButton").attr("disabled", "disabled");

	// Hide result list
	jQuery("#searchResourceResult").hide();

	// Hide error message
	jQuery("#searchResourceErrorMessage").hide();

	jQuery.post(
		ajaxurl,
		{
			action: 'presseportal_search_resources',
			terms: jQuery('#searchResourceTerms').val(),
			type: jQuery('#searchResourceType').val(),
			api_key: jQuery('#api_key').val(),
		},
		presseportal_admin_handle_search_result
	);
}

/**
 * Handles the search result from AJAX-respone.
 */
function presseportal_admin_handle_search_result(response, textStatus) {
	try {
		var resources = JSON.parse(response);

		if (resources && resources.length > 0) {
			// Add resources to result list
			jQuery("#resourceSelector").empty();
			jQuery.each(resources, function(index, resource) {
				jQuery("<option/>").val(resource.id).text(resource.name).appendTo("#resourceSelector");
			});
			jQuery("#resourceSelector option:first").attr("selected", "selected");

			// Show result list
			jQuery("#searchResourceResult").show();
		}
		else {
			jQuery("#searchResourceErrorMessage").text(presseportal.error_no_result);
			jQuery("#searchResourceErrorMessage").show();
		}

	} catch(e) {
		jQuery("#searchResourceErrorMessage").text(presseportal.error_unknown + ": " + e);
		jQuery("#searchResourceErrorMessage").show();
	}

	// Enable search button
	jQuery("#searchResourceButton").removeAttr("disabled");
}

/**
 * When the user has chosen an resource, this method applies the new resource.
 */
function presseportal_admin_apply_selected_resource(resource_index) {
	// Copy selected resource from dialog to admin page

	resource_id = jQuery("#resourceSelector option:selected").val();
	jQuery("#resource_id" + resource_index).val(resource_id);

	resource_name = jQuery("#resourceSelector option:selected").text();
	jQuery("#resource_name" + resource_index).val(resource_name);

	resource_type = jQuery('#searchResourceType').val();
	jQuery("#resource_type" + resource_index).val(resource_type);
}