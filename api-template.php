<?php 
	
	/*
		function: get_option_page_id
		returns the ACF $post_id value to use
		to get values for an options page 
	*/
	
	if (!function_exists('get_option_page_id')) {
		function get_option_page_id($slug) {
			$post_id = 'options';
			if (!function_exists('acf_get_options_pages')) {
				// acf not installed, or function name changed
				return $post_id;
			}
			$pages = acf_get_options_pages();
			if (empty($pages)) {
				return $post_id;
			}
			foreach ($pages as $page_slug => $page) {
				if ($page_slug == $slug) {
					$post_id = $page['post_id'];
					// if parent slug not empty then break
					if (!empty($page['parent_slug'])) {
						break;
					}
					// if parent slug is empty and !redirect then break
					if ($page['redirect']) {
						if (isset($pages[$page['menu_slug']])) {
							$post_id = $pages[$page['menu_slug']]['post_id'];
							break;
						}
					}
				}
			} // end foreach $page
			return $post_id;
		} // end function get_option_page_id
	} // end if !function
	
	if (!function_exists('get_options_page_id')) {
		function get_options_page_id($slug) {
			return get_option_page_id($slug);
		} // end function get_options_page_id
	} // end if !function
	
	if (!function_exists('get_option_page_id_filter')) {
		function get_option_page_id_filter($post_id='', $slug='') {
			return get_option_page_id($slug);
		} // end function function get_option_page_id_filter
		add_filter('acf/get_options_page_id', 'get_option_page_id_filter', 10, 2);
	} // end if !function
	
?>