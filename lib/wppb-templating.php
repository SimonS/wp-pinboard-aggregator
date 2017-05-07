<?php
/**
 * This is all heavily derived from a blog post here: 
 * http://jeroensormani.com/how-to-add-template-files-in-your-plugin/
 */
function pb_locate_template($template_name, $template_path = '') {
	if (!$template_path) {
		$template_path = 'templates/';
    }

	$template = locate_template(
        array(
            $template_path . $template_name,
            $template_name
    	)
    );
	
	if (!$template) {
		$template = plugin_dir_path(__FILE__) . $template_path . $template_name;
	}

	return apply_filters('pb_locate_template', $template, $template_name, $template_path);
}

function pb_get_template($template_name, $args = array(), $tempate_path = '') {
	if (is_array($args) && isset($args)) {
		extract($args);
    }

	$template_file = pb_locate_template($template_name, $tempate_path);

	if (!file_exists($template_file)) {
		_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $template_file ), '1.0.0' );
		return;
    }

    ob_start();						
    
    require $template_file;			
    $template = ob_get_contents();			
    $content .= $template;
    ob_end_clean();
    
    return $content;
}