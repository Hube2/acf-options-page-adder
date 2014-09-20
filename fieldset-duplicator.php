<?php 
	
	// If this file is called directly, abort.
	if (!defined('WPINC')) {die;}
	
	new acfOptionsPagefieldsetDuplicator();
	
	class acfOptionsPagefieldsetDuplicator {
		
		private $post_type = 'acf-opt-grp-dup';
		private $text_domain;
		
		public function __construct() {
			add_action('admin_notices', array($this, 'admin_message'));
			add_filter('manage_edit-'.$this->post_type.'_columns', array($this, 'admin_columns'));
			add_action('manage_'.$this->post_type.'_posts_custom_column', array($this, 'admin_columns_content'), 10, 2);
			
			add_action('acf_options_page/init', array($this, 'init'));
			add_action('acf_options_page/load_text_domain', array($this, 'load_text_domain'));
			
			add_filter('acf/location/rule_values/post_type', array($this, 'acf_location_rules_values_post_type'));
			add_filter('acf/location/rule_match/post_type', array($this, 'acf_location_rules_match_none'), 10, 3);
			add_filter('acf/load_field/name=_acf_field_grp_dup_group', array($this, 'load_acf_field_grp_dup_group'));
			add_filter('acf/load_field/name=_acf_field_grp_dup_page', array($this, 'load_acf_field_grp_dup_page'));
			add_action('acf/include_fields', array($this, 'acf_include_fields'));
		} // end public function __construct
		
		public function init() {
			$this->register_post_type();
		} // end public funtion init
		
		public function acf_include_fields() {
			// this function is called when ACF5 is installed
			// *******************************************************************************************
			// *******************************************************************************************
			// *******************************************************************************************
			// *******************************************************************************************
			return;
			$text_domain = $this->text_domain;
			$field_group = array();
			register_field_group($field_group);
		} // end public function acf_include_fields
		
		public function admin_columns($columns) {
			$new_columns = array();
			foreach ($columns as $index => $column) {
				if ($index == 'title') {
					$new_columns[$index] = $column;
					$new_columns['description'] = __('Description', $this->text_domain);
					$new_columns['method'] = __('Duplication Type', $this->text_domain);
					$new_columns['field_group'] = __('Field Group', $this->text_domain);
					$new_columns['options_pages'] = __('Options Page(s)', $this->text_domain);
					$new_columns['field_prefixes'] = __('Field Prefix(es)', $this->text_domain);
				} else {
					if (strtolower($column) != 'date') {
						$new_columns[$index] = $column;
					}
				}
			}
			return $new_columns;
		} // end public function admin_columns
		
		public function admin_columns_content($column_name, $post_id) {
			switch ($column_name) {
				case 'description':
					echo get_post_meta($post_id, '_acf_field_grp_dup_desc', true);
					break;
				case 'method':
					$method = get_post_meta($post_id, '_acf_field_grp_dup_method', true);
					if ($method == 'copy') {
						_e('Duplicate a Field Group to Multiple Options Pages', $this->text_domain);
					} elseif ($method == 'multiply') {
						_e('Duplicate a Field Group to the Same Options Page Multiple Times', $this->text_domain);
					}
					break;
				case 'field_group':
					$field_group_id = intval(get_post_meta($post_id, '_acf_field_grp_dup_group', true));
					echo get_the_title($field_group_id);
					break;
				case 'options_pages':
					$options_pages = array();
					$method = get_post_meta($post_id, '_acf_field_grp_dup_method', true);
					if ($method == 'copy') {
						$pages = intval(get_post_meta($post_id, '_acf_field_grp_dup_pages', true));
						for ($i=0; $i<$pages; $i++) {
							$key = '_acf_field_grp_dup_pages_'.$i.'__acf_field_grp_dup_page';
							$options_pages[] = get_post_meta($post_id, $key, true);
						}
					} elseif ($method == 'multiply') {
						$options_pages[] = get_post_meta($post_id, '_acf_field_grp_dup_page', true);
					}
					if (count($options_pages)) {
						$list = '';
						global $acf_options_pages;
						foreach ($options_pages as $page) {
							if (isset($acf_options_pages[$page])) {
								if ($list != '') {
									$list .= '<br />';
								}
								$list .= $acf_options_pages[$page]['page_title'];
							}
						}
						echo $list;
					}
					break;
				case 'field_prefixes':
					$method = get_post_meta($post_id, '_acf_field_grp_dup_method', true);
					if ($method == 'copy') {
						$repeater = '_acf_field_grp_dup_pages';
					} elseif ($method == 'multiply') {
						$repeater = '_acf_field_grp_dups';
					}
					$prefixes = intval(get_post_meta($post_id, $repeater, true));
					$list = '';
					for ($i=0; $i<$prefixes; $i++) {
						if ($list != '') {
							$list .= '<br />';
						}
						$key = $repeater.'_'.$i.'__acf_field_grp_dup_prefix';
						$list .= get_post_meta($post_id, $key, true);
					}
					echo $list;
					break;
				default:
					// do nothing
					break;
			} // end switch
		} // end public function admin_columns_content
		
		public function load_acf_field_grp_dup_group($field) {
			// doing query posts so that this only shows field groups
			// created in the ACF editor and not field groups create with code
			$choices = array();
			$args = array('post_type' => 'acf-field-group',
										'status' => 'publish',
										'posts_per_page' => -1);
			$query = new WP_Query($args);
			if (count($query->posts)) {
				foreach ($query->posts as $post) {
					$choices[$post->ID] = $post->post_title;
				}
			}
			$field['choices'] = $choices;
			return $field;
		} // end public function load_acf_field_grp_dup_group
		
		public function load_acf_field_grp_dup_page($field) {
			global $acf_options_pages;
			$choices = array();
			if (count($acf_options_pages)) {
				foreach ($acf_options_pages as $key => $page) {
					if ((!isset($page['redirect']) || $page['redirect']) && !$page['parent_slug']) {
						continue;
					}
					$choices[$key] = $page['page_title'];
				}
			}
			$field['choices'] = $choices;
			return $field;
		} // end public function load_acf_field_grp_dup_page
		
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
		
		public function load_text_domain() {
			$this->text_domain = apply_filters('acf_options_page/text_domain', false);
		} // end public function load_text_domain
		
		private function register_post_type() {
			$options_page_post_type = apply_filters('acf_options_page/post_type', false);
			$text_domain = $this->text_domain;
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