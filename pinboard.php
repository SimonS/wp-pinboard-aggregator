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

// insert auth token here, this will eventually be pulled from settings
$url = "https://api.pinboard.in/v1/posts/recent?auth_token=&tag=";

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

$pinboard_posts = array_filter(getPosts($url), "isInLastWeek");
