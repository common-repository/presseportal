<?php
/*
 Plugin Name: Presseportal Plugin
 Plugin URI: http://wordpress.org/extend/plugins/presseportal/
 Description: The plugin loads news from German Presseportal service and shows them in your blog.
 Version: 0.1.1
 Author: Karsten Strunk
 Author URI: http://www.strunk.eu/wordpress
 Min WP Version: 3.0

 This program is distributed under the GNU General Public License, Version 2,
 June 1991. Copyright (C) 1989, 1991 Free Software Foundation, Inc., 51 Franklin
 St, Fifth Floor, Boston, MA 02110, USA

 THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

require_once dirname(__FILE__) . '/options.php';

if (is_admin()) {
    require_once dirname(__FILE__) . '/admin.php';
}

/********************************/
/** Installation/Deinstallation */
/********************************/

/**
 * Called on plugin deinstallation
 */
function presseportal_uninstall() {
    presseportal_delete_options();
}
register_uninstall_hook(__FILE__, 'presseportal_uninstall');


/********************************/
/** Activation/Deactivation     */
/********************************/

/**
 * Called on plugin deactivation. Disables the cron job.
 */
function presseportal_deactivate() {
    presseportal_deactivate_cron();
}
register_deactivation_hook(__FILE__, 'presseportal_deactivate');


/********************************/
/** Initialization              */
/********************************/

/**
 * Initializes the plugin
 */
function presseportal_init()
{
    // Reference to stylesheets
    wp_register_style('plugin_style', plugin_dir_url(__FILE__) . '/css/Presseportal.css');
    wp_enqueue_style('plugin_style', plugin_dir_url(__FILE__) . '/css/Presseportal.css');

    // Localize plugin
    load_plugin_textdomain('Presseportal', false, dirname(plugin_basename(__FILE__)) . '/i18n/');

    // Activate cron if not enabled
    if (presseportal_is_cron_enabled()) {
        presseportal_activate_cron();
    }
}
add_action('init', 'presseportal_init');

/**
 * Called on plugin initialization and performs the database migration if needed
 */
function presseportal_migrate() {
    //
    // TODO Do database migration here
    //
}
add_action('init', 'presseportal_migrate');


/********************************/
/** Cron job                    */
/********************************/

/**
 * Activates the cron job
 */
function presseportal_activate_cron() {
    if (!wp_next_scheduled('presseportal_load_stories_event')) {
        wp_schedule_event(time(), 'hourly', 'presseportal_load_stories_event');
    }
}

/**
 * The cron job to load news regulary.
 */
function presseportal_load_stories_hourly() {
    presseportal_load_stories_and_create_posts();
}
add_action('presseportal_load_stories_event', 'presseportal_load_stories_hourly');

/**
 * Deactivates the cron job
 */
function presseportal_deactivate_cron() {
    wp_clear_scheduled_hook('presseportal_load_stories_event');
}

/**
 * Determines whether the cron job is enabled
 */
function presseportal_is_cron_enabled() {
    $cron_enabled =  presseportal_get_option(PRESSEPORTAL_CRON_ENABLED);
    if ($cron_enabled == true) {
        return true;
    }
    else {
        return false;
    }
}


/********************************/
/** Story functions             */
/********************************/

/**
 * Loads the newest stories from PressePortal.de and creates new posts for each of them.
 * Previously loaded stories are ignoried. Stories are filtered by filter rules settings.
 * The stories are returned in chronological order.
 */
 function presseportal_load_stories_and_create_posts() {

    presseportal_update_option(PRESSEPORTAL_CRON_LAST_DATE, current_time('timestamp'));
    presseportal_update_option(PRESSEPORTAL_CRON_LAST_STATUS, null);

    // Load stories for each resource
    $success = true;
    for($resourceNo = 0; $resourceNo < PRESSEPORTAL_MAX_RESOURCE_COUNT && $success = true; $resourceNo++) {

        // Do not execute empty configs
        $resource_id = presseportal_get_option(PRESSEPORTAL_RESOURCE_ID . $resourceNo);
        if (empty($resource_id)) {
            continue;
        }

        // Get id of last retrieved story
        $last_story_id = presseportal_get_option(PRESSEPORTAL_RESOURCE_LAST_STORY_ID . $resourceNo);

        // Do load stories for current resource
        $stories = presseportal_load_stories($resourceNo);

         // Check for valid response
        if (isSet($stories)) {
            $success = true;

            // Add new posts for new stories
            $max_story_id = $last_story_id;
            foreach($stories AS $story) {
                $story_id = $story->id;

                // Do we need to create a new post for this story?
                if ($story_id > $last_story_id) {
                    presseportal_add_story($story, $resourceNo);
                    $max_story_id = $story_id;
                }
            }

            // Remember the highest story id so it don't get added again.
            presseportal_update_option(PRESSEPORTAL_RESOURCE_LAST_STORY_ID . $resourceNo, $max_story_id);
        } else {
            // No data received. Something has gone wrong.
            $success = false;
        }
    }

    presseportal_update_option(PRESSEPORTAL_CRON_LAST_STATUS, $success);
}
do_action('presseportal_load_stories_and_create_posts');

/**
 * Loads the newest stories from PressePortal.de for one resource. Stories are filtered by filter rules settings.
 * The stories are returned in chronological order.
 *
 * @param resource_no Number of resource to load
 */
function presseportal_load_stories($resourceNo, $load_teaser_only = false) {
    require_once(dirname(__FILE__) . '/Presseportal.class.php');

    // Get required options
    $api_key = presseportal_get_option(PRESSEPORTAL_API_KEY);
    $resource_id = presseportal_get_option(PRESSEPORTAL_RESOURCE_ID . $resourceNo);

    // Check for required options
    if (empty ($api_key) || empty ($resource_id)) {
        return null;
    }

    // Get positive filter for resource. If none is defined use default.
    $filter_positive = presseportal_get_option(PRESSEPORTAL_RESOURCE_FILTER_POSITIVE . $resourceNo);
    if (empty($filter_positive)) {
        // Use default filter
        $filter_positive = presseportal_get_option(PRESSEPORTAL_DEFAULT_FILTER_POSITIVE);
    }

    // Prepare positive filters
    $positive_filters = explode(',', strtolower(trim($filter_positive)));
    foreach($positive_filters as $key => $val) {
        if(empty($val)){
            unset($positive_filters[$key]);
        } else {
            $positive_filters[$key] = trim($val);
        }
    }

    // Get negative filter for resource. If none is defined use default.
    $filter_negative = presseportal_get_option(PRESSEPORTAL_RESOURCE_FILTER_NEGATIVE . $resourceNo);
    if (empty($filter_negative)) {
        // Use default filter
        $filter_negative = presseportal_get_option(PRESSEPORTAL_DEFAULT_FILTER_NEGATIVE);
    }

    // Prepare negative filters
    $negative_filters = explode(',', strtolower(trim($filter_negative)));
    foreach($negative_filters as $key => $val) {
        if(empty($val)){
            unset($negative_filters[$key]);
        } else {
            $negative_filters[$key] = trim($val);
        }
    }

    // Load stories now:

    $pp = new Presseportal($api_key, 'de');
    $pp->format = 'xml';
    $pp->limit = '30';

    if ($load_teaser_only) {
        $pp->teaser = true;
    }

    // Load the latest stories for current ressource
    $resource_type = presseportal_get_option(PRESSEPORTAL_RESOURCE_TYPE . $resourceNo);
    if ($resource_type == 'COMPANY') {
        $response = $pp->get_company_articles($resource_id);
    }
    else {
        $response = $pp->get_office_articles($resource_id);
    }


    $stories = array();
    if((!$response->error) && ($response->stories)) {
        foreach($response->stories AS $story) {
            $title = $story->title;

            if ($load_teaser_only) {
                $content = $story->teaser;
            }
            else {
                $content = $story->body;
            }

            // Apply positive filters
            $filter_success = true;
            if (count($positive_filters) > 0) {
                $filter_success = (presseportal_do_filter($title, $positive_filters) || presseportal_do_filter($content, $positive_filters));
            }

            // Apply negative filters
            if (count($negative_filters) > 0) {
                if (presseportal_do_filter($title, $negative_filters) || presseportal_do_filter($content, $negative_filters)) {
                    $filter_success = false;
                }
            }

            if ($filter_success) {
                $stories[] = $story;
            }
        }

        // Stories are in not in chronological order. So sort it;
        usort($stories, create_function('$value1, $value2', 'return ($value1 >= $value2) ? +1 : -1;'));
    } else {
        $stories = null;
    }

    return $stories;
}
do_action('presseportal_load_stories');

/**
 * Internal function: Filters a story
 */
function presseportal_do_filter($text, $filters) {
    $text = strtolower($text);

    $matches = false;
    for ($i = 0; $i < count($filters) && ($matches == false); $i++) {
        if (preg_match('/' . $filters[$i] . '/', $text)) {
            $matches = true;
        }
    }

    return $matches;
}

/**
 * Internal function: Creates a new post out of story.
 *
 * @param story Story to add
 * @param resourceNo Number of resource from which the story is originated.
 */
function presseportal_add_story($story, $resourceNo) {

    // Format text
    $text = preg_replace("/(\r?\n){2}/", "<p/>", $story->body);
    $text = preg_replace("/\n/", " ", $text);

    // Create new post text
    $post_content = '<div class="presseportal-post">';
    $post_content .= '<div class="text" style="text-align: justify;">' . $text . '</div>';
    $post_content .= '<div class="source">' . __('Source', 'Presseportal') . ': <a href="' . $story->url . '" target="_blank">www.presseportal.de</a></div>';
    $post_content .= '</div>';

    // Title
    $post_title = $story->title;

    // Category. Use special category for resource or default if none is defined.
    $post_category = presseportal_get_option(PRESSEPORTAL_RESOURCE_CATEGORY_ID . $resourceNo);
    if (empty($post_category) || $post_category == -1) {
        // Use default category
        $post_category = presseportal_get_option(PRESSEPORTAL_DEFAULT_CATEGORY_ID);
    }

    // User.
    // Get user for resource. If none is defined use default.
    $post_user = presseportal_get_option(PRESSEPORTAL_RESOURCE_USER_ID . $resourceNo);
    if (empty($post_user) || $post_user == -1) {
        // Use default filter
        $post_user = presseportal_get_option(PRESSEPORTAL_DEFAULT_USER_ID);
    }

    // Status
    $post_status = (presseportal_get_option(PRESSEPORTAL_CRON_ADD_PUBLISH) ? 'publish' : 'draft');

    // Create new wordpress post
    $new_post = array(
        'post_title' => $post_title,
        'post_content' => $post_content,
        'post_status' => $post_status,
        'post_author' => $post_user,
        'post_type' => 'post',
        'post_category' => array($post_category)
    );
    $post_id = wp_insert_post($new_post);

    // Send notification email if configured
    $notify_email = presseportal_get_option(PRESSEPORTAL_CRON_NOTIFY);
    if ($notify_email) {
        $user_data = get_userdata($post_user);

        $email_title = __('Presseportal: New post added', 'Presseportal') . ':' . $post_title;
        $email_message = $story->body . "\r\n\r\n" . __('URL to admin page', 'Presseportal') . ': ' . site_url('/wp-admin/edit.php');
        $email_sender = get_bloginfo('admin_email');

        $email_header = 'From: ' . $email_sender . "\r\n";
        $email_header .= "Content-type: text/plain; charset=UTF-8\r\n";

//        print("TO:      " . $user_data->user_email);
//        print("TITLE:   " . $email_title);
//        print("MESSAGE: " . $email_message);

        mail($user_data->user_email, $email_title, $email_message, $email_header);
    }
}

?>