<?php
/*
Plugin Name: Pinboard Aggregator
Plugin URI: https://github.com/simons/pinboard-aggregator
Description: Posts aggregated link lists from Pinboard.in to your WordPress blog
Version: 1.0
Author: Simon Scarfe
Author URI: https://breakfastdinnertea.co.uk
License: GPL2
*/

define('ALTERNATE_WP_CRON', true); // TODO: remove this
define('WPPB_ROOT', plugin_dir_path(__FILE__) . '/');  

function doMenu() {
    add_options_page('Pinboard Aggegator', 'Pinboard Aggegator', 'administrator', __FILE__, 'options_page_fn');
}
add_action('admin_menu', 'doMenu');

add_action('admin_init', 'sampleoptions_init_fn' );

function setting_string_fn() {
	$options = get_option('plugin_options');
	echo "<input id='plugin_text_string' name='plugin_options[text_string]' size='40' type='text' value='{$options['text_string']}' />";
}

function plugin_options_validate($input) {
	// Check our textbox option field contains no HTML tags - if so strip them out
	$input['text_string'] =  wp_filter_nohtml_kses($input['text_string']);	
	return $input; // return validated input
}

function  section_text_fn() {
	echo '<p>Below are some examples of different option controls.</p>';
}

function sampleoptions_init_fn() {
	register_setting('plugin_options', 'plugin_options', 'plugin_options_validate' );
	add_settings_section('main_section', 'Main Settings', 'section_text_fn', __FILE__);
	add_settings_field('plugin_text_string', 'Text Input', 'setting_string_fn', __FILE__, 'main_section');
	// add_settings_field('plugin_text_pass', 'Password Text Input', 'setting_pass_fn', __FILE__, 'main_section');
	// add_settings_field('plugin_textarea_string', 'Large Textbox!', 'setting_textarea_fn', __FILE__, 'main_section');
	// add_settings_field('plugin_chk2', 'A Checkbox', 'setting_chk2_fn', __FILE__, 'main_section');
	// add_settings_field('radio_buttons', 'Select Shape', 'setting_radio_fn', __FILE__, 'main_section');
	// add_settings_field('drop_down1', 'Select Color', 'setting_dropdown_fn', __FILE__, 'main_section');
	// add_settings_field('plugin_chk1', 'Restore Defaults Upon Reactivation?', 'setting_chk1_fn', __FILE__, 'main_section');
}


function options_page_fn() {
    ?>
<div class="wrap">
    <div class="icon32" id="icon-options-general"><br></div>
    <h2>Pinboard Aggregator Settings</h2>
    <p>Use this page to set your pinboard credentials, update schedule, etc.</p>
    <form action="options.php" method="post">
        <?php settings_fields('plugin_options'); ?>
        <?php do_settings_sections(__FILE__); ?>
        <p class="submit">
            <input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
        </p>
    </form>
</div>
    <?php
}

$user_to_post_as = 1; // TODO: make this a setting
$pinboard_auth_token = ''; // TODO: make this a setting
$pinboard_tag = '';  // TODO: make this a setting

function getPosts($url) {
    $pinboard_request = wp_remote_retrieve_body(wp_remote_get($url));
    $pinboard_xml = simplexml_load_string($pinboard_request, 'SimpleXMLElement', LIBXML_NOCDATA);

    return $pinboard_xml->xpath('//post');
}

function isInLastWeek($post) {
    $weekAgo = date_create('-7 days');
    $postTime = date_create($post['time']);

    return $postTime > $weekAgo;
}

add_filter('cron_schedules', 'wppb_add_weekly'); 
function wppb_add_weekly( $schedules ) {
    $schedules['weekly'] = array(
        'interval' => 7 * 24 * 60 * 60, // 7 days * 24 hours * 60 minutes * 60 seconds
        'display' => __('Once Weekly', 'wp-pinboard')
    );
  
  return $schedules;
}

$timestamp = wp_next_scheduled('wppb_schedule_links');
// TODO: make setting
$next = new DateTime('next friday, 6pm');

if (!$timestamp || $next->getTimestamp() !== $timestamp) {
    wp_schedule_event($next->getTimestamp(), 'weekly', 'wppb_schedule_links');
}

add_action('wppb_schedule_links', 'wppb_post_links');

function wppb_post_links() {
    include_once(ABSPATH . WPINC . '/pluggable.php');
    require_once(WPPB_ROOT . 'lib/wppb-templating.php');

    wp_set_auth_cookie($user_to_post_as);

    $url = "https://api.pinboard.in/v1/posts/recent?auth_token=$pinboard_auth_token&tag=$pinboard_tag";
    $pinboard_posts = array_filter(getPosts($url), "isInLastWeek");

    $post = pb_get_template('pinboard-posts.php', array('posts' => $pinboard_posts));

    $new_post = array(
        'post_content' => $post, 
        'post_title' => 'wppb_post_links'
    );

    $post_id = wp_insert_post($new_post);
}
