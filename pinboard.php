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

function pb_authtoken_fn() {
	$options = get_option('pb_options');
    $val = isset($options['pb_authtoken']) ? $options['pb_authtoken'] : '';
	echo "<input id='pb_authtoken' name='pb_options[pb_authtoken]' size='40' type='text' value='{$val}' />";
}

function pb_tag_fn() {
	$options = get_option('pb_options');
    $val = isset($options['pb_tag']) ? $options['pb_tag'] : '';
	echo "<input id='pb_tag' name='pb_options[pb_tag]' size='40' type='text' value='{$val}' />";
}

function plugin_options_validate($input) {
	return $input; // return validated input
}

function  section_text_fn() {
	echo '<p>Enter your pinboard preferences here.</p>';
}

function sampleoptions_init_fn() {
	register_setting('pb_options', 'pb_options', 'plugin_options_validate' );
	add_settings_section('pb_section', 'Pinboard Settings', 'section_text_fn', __FILE__);
	add_settings_field('pb_authtoken', 'Pinboard Auth Token', 'pb_authtoken_fn', __FILE__, 'pb_section');
	add_settings_field('pb_tag', 'Pinboard Tag to filter on', 'pb_tag_fn', __FILE__, 'pb_section');
}

function options_page_fn() {
    ?>
<div class="wrap">
    <div class="icon32" id="icon-options-general"><br></div>
    <h2>Pinboard Aggregator Settings</h2>
    <p>Use this page to set your pinboard credentials, update schedule, etc.</p>
    <form action="options.php" method="post">
        <?php settings_fields('pb_options'); ?>
        <?php do_settings_sections(__FILE__); ?>
        <p class="submit">
            <input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
        </p>
    </form>
</div>
    <?php
}

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
$next = new DateTime('today, 23:40');
// var_dump($next);
// var_dump(new DateTime('now'));
if (!$timestamp || $next->getTimestamp() !== $timestamp) {
    wp_schedule_event($next->getTimestamp(), 'weekly', 'wppb_schedule_links');
}

add_action('wppb_schedule_links', 'wppb_post_links');

function wppb_post_links() {
    include_once(ABSPATH . WPINC . '/pluggable.php');
    require_once(WPPB_ROOT . 'lib/wppb-templating.php');

    $options = get_option('pb_options');

    $user_to_post_as = 1; // TODO: make this a setting
    $pinboard_auth_token = $options['pb_authtoken'];
    $pinboard_tag = $options['pb_tag']; 

    wp_set_auth_cookie($user_to_post_as);

    $url = "https://api.pinboard.in/v1/posts/recent?auth_token=$pinboard_auth_token&tag=$pinboard_tag";
    $pinboard_posts = array_filter(getPosts($url), "isInLastWeek");

    $post = pb_get_template('pinboard-posts.php', array('posts' => $pinboard_posts));
    // var_dump($post);die;
    $new_post = array(
        'post_content' => $post, 
        'post_title' => 'Pinboard posts for...',
        'post_status' => 'publish'
    );

    $post_id = wp_insert_post($new_post);
};
