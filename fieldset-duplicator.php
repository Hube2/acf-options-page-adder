<?php 
	
	// If this file is called directly, abort.
	if (!defined('WPINC')) {die;}
	
	new acfOptionsPagefieldsetDuplicator();
	
	class acfOptionsPagefieldsetDuplicator {
		
		private $post_type = 'acf-opt-grp-dup';
		private $text_domain;
		
		public function __construct() {
			add_action('admin_notices', array($this, 'admin_message'));
			
			add_action('acf_options_page/init', array($this, 'init'));
			
			add_filter('acf/location/rule_values/post_type', array($this, 'acf_location_rules_values_post_type'));
			add_filter('acf/location/rule_match/post_type', array($this, 'acf_location_rules_match_none'), 10, 3);
		} // end public function __construct
		
		public function init() {
			$this->register_post_type();
		} // end public funtion init
		
		public function acf_location_rules_match_none($match, $rule, $options) {
			$match = -1;
			return $match;
		} // end public function acf_location_rules_match_none
		
		public function acf_location_rules_values_post_type($choices) {
			if (!isset($choices['none'])) {
				$choices['none'] = 'None (hidden)';
			}
			return $choices;
		} // end public function acf_location_rules_values_user
		
		public function admin_message() {
			// updated below-h2
			$screen = get_current_screen();
			if ($screen->id != 'edit-acf-opt-grp-dup') {
				return;
			}
			?>
				<div class="updated">
					<p>
						<strong>
							<?php _e('Options Page Field Group Duplicators allow the use of the same ACF field group on multiple options pages or to duplicate an ACF field group multiple times to the same options page.<br />The duplication process automatically adds a prefix to all duplicated fields that you specify so that you do not need to duplicate a field group and manually modify each field name.<br />In addition the option &quot;None (hidden)&quot; has been added to the Post Type Location Rules in ACF so that you can create field groups that do not normally appear anywhere.', $this->text_domain); ?>
						</strong>
					</p>
				</div>
			<?php 
		} // end public function admin_message
		
		private function register_post_type() {
			$options_page_post_type = apply_filters('acf_options_page/post_type', false);
			$text_domain = $this->text_domain = apply_filters('acf_options_page/text_domain', false);
			if ($options_page_post_type === false || $text_domain === false) {
				return;
			}
      $args = array('label' => __('Field Group Duplicators', $text_domain),
										'singular_label' => __('Field Group Duplicator', $text_domain),
                    'description' => '',
                    'public' => false,
										'has_archive' => false,
                    'show_ui' => true,
                    'show_in_menu' => 'edit.php?post_type='.$options_page_post_type,
                    'capability_type' => 'post',
                    'map_meta_cap' => true,
                    'hierarchical' => false,
                    'rewrite' => array('slug' => $this->post_type, 'with_front' => true),
                    'query_var' => $this->post_type,
                    'exclude_from_search' => true,
                    'menu_position' => false,
										//'menu_icon' => 'dashicons-admin-generic',
                    'supports' => array('title','custom-fields','revisions'),
                    'labels' => array('name' => __('Options Page Field Group Duplicators', $text_domain),
                                      'singular_name' => __('Field Group Duplicator', $text_domain),
                                      'menu_name' =>  __('Field Group Duplicators', $text_domain),
                                      'add_new' => __('Add Field Group Duplicator', $text_domain),
                                      'add_new_item' => __('Add New Field Group Duplicator', $text_domain),
                                      'edit' => __('Edit', $text_domain),
                                      'edit_item' => __('Edit Field Group Duplicator', $text_domain),
                                      'new_item' => __('New Field Group Duplicator', $text_domain),
                                      'view' => __('View Field Group Duplicator', $text_domain),
                                      'view_item' => __('View Field Group Duplicator', $text_domain),
                                      'search_items' => __('Search Field Group Duplicators', $text_domain),
                                      'not_found' => __('No Field Group Duplicators Found', $text_domain),
                                      'not_found_in_trash' => __('No Field Group Duplicators Found in Trash', $text_domain),
                                      'parent' => __('Parent Field Group Duplicators', $text_domain)));
			$post_type = register_post_type($this->post_type, $args);
		} // end private function register_post_type
		
	} // end class acfOptionsPagefieldsetDuplicator
	
?>