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

if (!$timestamp) {
    wp_schedule_event(time(), 'weekly', 'wppb_schedule_links');
}

add_action('wppb_schedule_links', 'wppb_post_links');

function wppb_post_links() {
    include_once(ABSPATH . WPINC . '/pluggable.php');
    require_once(WPPB_ROOT . 'lib/wppb-templating.php');

    wp_set_auth_cookie(1);

    $url = "https://api.pinboard.in/v1/posts/recent?auth_token=&tag=";
    $pinboard_posts = array_filter(getPosts($url), "isInLastWeek");

    $post = pb_get_template('pinboard-posts.php', array('posts' => $pinboard_posts));

    $new_post = array(
        'post_content' => $post, 
        'post_title' => 'wppb_post_links',
        'post_status' => 'publish'
    );

    $post_id = wp_insert_post($new_post);
}
