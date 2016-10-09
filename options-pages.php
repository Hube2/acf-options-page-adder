<?php 

	/*
		Plugin Name: Advanced Custom Fields: Options Page Adder
		Plugin URI: https://github.com/Hube2/acf-options-page-adder
		Description: Allows easy creation of options pages using Advanced Custom Fields Pro without needing to do any PHP coding. Requires that ACF Pro is installed.
		Author: John A. Huebner II
		Author URI: https://github.com/Hube2
		GitHub Plugin URI: https://github.com/Hube2/acf-options-page-adder
		Version: 3.2.0
	*/
	
	// If this file is called directly, abort.
	if (!defined('WPINC')) {die;}
	
	$duplicator = dirname(__FILE__).'/fieldset-duplicator.php';
	if (file_exists($duplicator)) {
		include($duplicator);
	}
	
	new acfOptionsPageAdder();
	
	class acfOptionsPageAdder {
		
		private $version = '3.2.0';
		private $post_type = 'acf-options-page';
		private $parent_menus = array();
		private $exclude_locations = array('',
																			 'cpt_main_menu',
																			 'edit.php?post_type=acf-field-group',
																			 //'edit-comments.php',
																			 //'plugins.php',
																			 //'edit-tags.php?taxonomy=link_category',
																			 'edit.php?post_type=acf-options-page',
																			 );
		private $text_domain = 'acf-options-page-adder';
		
		public function __construct() {
			register_activation_hook(__FILE__, array($this, 'activate'));
			register_deactivation_hook(__FILE__, array($this, 'deactivate'));
			add_action('plugins_loaded', array($this, 'load_text_domain'));
			add_action('after_setup_theme', array($this, 'after_setup_theme'), 1);
			add_action('admin_head', array($this, 'admin_head'));
			add_filter('jh_plugins_list', array($this, 'meta_box_data'));
		} // end public function __construct
			
			function meta_box_data($plugins=array()) {
				
				$plugins[] = array(
					'title' => 'ACF Options Page Adder',
					'screens' => array('acf-field-group', 'edit-acf-field-group', 'acf-options-page'),
					'doc' => 'https://github.com/Hube2/acf-options-page-adder'
				);
				return $plugins;
				
			} // end function meta_box
		
		public function admin_head() {
			//echo '<pre>'; print_r(get_current_screen()); die;
		} // end public function admin_head
		
		public function after_setup_theme() {
			// check to see if acf5 is installed
			// if not then do not run anything else in this plugin
			// move all other actions to this function except text domain since this is too late
			if (!class_exists('acf') ||
					!function_exists('acf_get_setting') ||
					intval(acf_get_setting('version')) < 5 ||
					!class_exists('acf_pro')) {
				$this->active = false;
				return;
			}
			add_action('init', array($this, 'init'), 0);
			add_action('admin_menu', array($this, 'build_admin_menu_list'), 999);
			add_filter('acf/load_field/name=_acfop_parent', array($this, 'acf_load_parent_menu_field'));
			add_filter('acf/load_field/name=_acfop_capability', array($this, 'acf_load_capabilities_field'));
			add_filter('manage_edit-'.$this->post_type.'_columns', array($this, 'admin_columns'));
			add_action('manage_'.$this->post_type.'_posts_custom_column', array($this, 'admin_columns_content'), 10, 2);
			add_action('acf/include_fields', array($this, 'acf_include_fields'));
			add_filter('acf_options_page/post_type', array($this, 'get_post_type'));
			add_filter('acf_options_page/text_domain', array($this, 'get_text_domain'));
		} // end public function after_setup_theme
		
		public function init() {
			$this->register_post_type();
			$this->acf_add_options_pages();
			do_action('acf_options_page/init');
		} // end public function init
		
		public function get_post_type($value='') {
			return $this->post_type;
		} // end public function get_post_type
		
		public function get_text_domain($value='') {
			return $this->text_domain;
		} // end public function get_text_domain
		
		public function acf_include_fields() {
			// this function is called when ACF5 is installed
			$field_group = array(
				'key' => 'acf_options-page-details',
				'title' => __('Options Page Details', $this->text_domain),
				'fields' => array(
					array(
						'key' => 'field_acf_key_acfop_message',
						'label' => __('Options Page Message', $this->text_domain),
						'name' => '',
						'prefix' => '',
						'type' => 'message',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'message' => __('Title above is the title that will appear on the page. Enter other details as needed.<br />For more information see the ACF documentation for <a href="http://www.advancedcustomfields.com/resources/acf_add_options_page/" target="_blank">acf_add_options_page()</a> and <a href="http://www.advancedcustomfields.com/resources/acf_add_options_sub_page/" target="_blank">acf_add_options_sub_page()</a>.', $this->text_domain)
					),
					array(
						'key' => 'field_acf_key_acfop_menu',
						'label' => __('Menu Text', $this->text_domain),
						'name' => '_acfop_menu',
						'prefix' => '',
						'type' => 'text',
						'instructions' => __('Will default to title if left blank.', $this->text_domain),
						'required' => 0,
						'conditional_logic' => 0,
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
						'readonly' => 0,
						'disabled' => 0
					),
					array(
						'key' => 'field_acf_key_acfop_parent',
						'label' => __('Menu Location (Parent)', $this->text_domain),
						'name' => '_acfop_parent',
						'prefix' => '',
						'type' => 'select',
						'instructions' => __('Select the menu this options page will appear under. Will default to None.', $this->text_domain),
						'required' => 0,
						'conditional_logic' => 0,
						'choices' => array(), // dynamic populate
						'default_value' => 'none',
						'allow_null' => 0,
						'multiple' => 0,
						'ui' => 0,
						'ajax' => 0,
						'placeholder' => '',
						'disabled' => 0,
						'readonly' => 0
					),
					array(
						'key' => 'field_acf_key_acfop_capability',
						'label' => __('Capability', $this->text_domain),
						'name' => '_acfop_capability',
						'prefix' => '',
						'type' => 'select',
						'instructions' => __('The user capability to view this options page. Will default to manage_options.', $this->text_domain),
						'required' => 0,
						'conditional_logic' => 0,
						'choices' => array(), // dynamic populate
						'default_value' => 'manage_options',
						'allow_null' => 1,
						'multiple' => 0,
						'ui' => 0,
						'ajax' => 0,
						'placeholder' => '',
						'disabled' => 0,
						'readonly' => 0
					),
					array(
						'key' => 'field_acf_key_acfop_position',
						'label' => __('Menu Position', $this->text_domain),
						'name' => '_acfop_position',
						'prefix' => '',
						'type' => 'text',
						'instructions' => __('The position in the menu order this menu should appear. WARNING: if two menu items use the same position attribute, one of the items may be overwritten so that only one item displays! Risk of conflict can be reduced by using decimal instead of integer values, e.g. 63.3 instead of 63. Defaults to bottom of utility menu items.<br /><em>Core Menu Item Positions: 2=Dashboard, 4=Separator, 5=Posts, 10=Media, 15=Links, 20=Pages, 25=Comments, 59=Separator, 60=Appearance, 65=Plugins, 70=Users, 75=Tools, 80=Settings, 99=Separator</em>', $this->text_domain),
						'required' => 0,
						'conditional_logic' => array(
							array(
								array(
									'field' => 'field_acf_key_acfop_parent',
									'operator' => '==',
									'value' => 'none',
								),
							),
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'min' => '',
						'max' => '',
						'step' => '',
						'readonly' => 0,
						'disabled' => 0,
					),
					array(
						'key' => 'field_acf_key_acfop_icon',
						'label' => 'Icon',
						'name' => '_acfop_icon',
						'prefix' => '',
						'type' => 'text',
						'instructions' => __('The icon url for this menu. Defaults to default WordPress gear.<br /><em>Check out <a href="http://melchoyce.github.io/dashicons/" target="_blank">http://melchoyce.github.io/dashicons/</a> for what to put in this field.</em>', $this->text_domain),
						'required' => 0,
						'conditional_logic' => array(
							array(
								array(
									'field' => 'field_acf_key_acfop_parent',
									'operator' => '==',
									'value' => 'none',
								),
							),
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
						'readonly' => 0,
						'disabled' => 0,
					),
					array(
						'key' => 'field_acf_key_acfop_redirect',
						'label' => __('Redirect', $this->text_domain),
						'name' => '_acfop_redirect',
						'prefix' => '',
						'type' => 'radio',
						'instructions' => __('If set to true, this options page will redirect to the first child page (if a child page exists). If set to false, this parent page will appear alongside any child pages. Defaults to true.<br /><em><strong>NOTE: Changing this setting will effect the location or appearance of sub options pages currently associated with this options page.</strong></em>', $this->text_domain),
						'required' => 0,
						'conditional_logic' => array(
							array(
								array(
									'field' => 'field_acf_key_acfop_parent',
									'operator' => '==',
									'value' => 'none',
								),
							),
						),
						'choices' => array(
							1 => 'True',
							0 => 'False',
						),
						'other_choice' => 0,
						'save_other_choice' => 0,
						'default_value' => 1,
						'layout' => 'horizontal',
					),
					array(
						'key' => 'field_acf_key_acfop_slug',
						'label' => __('Slug', $this->text_domain),
						'name' => '_acfop_slug',
						'prefix' => '',
						'type' => 'text',
						'instructions' => __('Will default to sanitized title.', $this->text_domain),
						'required' => 0,
						'conditional_logic' => array(
							array(
								array(
									'field' => 'field_acf_key_acfop_parent',
									'operator' => '==',
									'value' => 'none',
								),
								array(
									'field' => 'field_acf_key_acfop_redirect',
									'operator' => '==',
									'value' => 0,
								),
							),
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
						'readonly' => 0,
						'disabled' => 0
					),
					array(
						'key' => 'field_acf_key_acfop_order',
						'label' => __('Order', $this->text_domain),
						'name' => '_acfop_order',
						'prefix' => '',
						'type' => 'number',
						'instructions' => __('The order that this child menu should appear under its parent menu.', $this->text_domain),
						'required' => 0,
						'conditional_logic' => array(
							array(
								array(
									'field' => 'field_acf_key_acfop_parent',
									'operator' => '!=',
									'value' => 'none',
								),
							),
						),
						'default_value' => 0,
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
						'readonly' => 0,
						'disabled' => 0,
					),
					array (
						'key' => 'field_acf_key_acfop_save_to',
						'label' => 'Save to',
						'name' => '_acfop_save_to',
						'type' => 'radio',
						'instructions' => __('ACF v5,2,7 added the ability to save and load data to/from a post rather than options.', $this->text_domain),
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array (
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'choices' => array (
							'options' => __('Options', $this->text_domain),
							'post' => __('Post Object', $this->text_domain),
						),
						'other_choice' => 0,
						'save_other_choice' => 0,
						'default_value' => 'options',
						'layout' => 'horizontal',
					),
					array (
						'key' => 'field_acf_key_acfop_post_page',
						'label' => 'Post/Page',
						'name' => '_acfop_post_page',
						'type' => 'post_object',
						'instructions' => __('Select the post object to save and load data to/from', $this->text_domain),
						'required' => 1,
						'conditional_logic' => array (
							array (
								array (
									'field' => 'field_acf_key_acfop_save_to',
									'operator' => '==',
									'value' => 'post',
								),
							),
						),
						'wrapper' => array (
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'post_type' => array (
						),
						'taxonomy' => array (
						),
						'allow_null' => 0,
						'multiple' => 0,
						'return_format' => 'id',
						'ui' => 1,
					),
					array(
						'key' => 'field_acf_key_acfop_autoload',
						'label' => __('Autoload Values', $this->text_domain),
						'name' => '_acfop_autoload',
						'prefix' => '',
						'type' => 'radio',
						'instructions' => __('Whether to load the options (values saved from this options page) when WordPress starts up. Added in ACF v5.2.8.', $this->text_domain),
						'required' => 0,
						'conditional_logic' => array (
							array (
								array (
									'field' => 'field_acf_key_acfop_save_to',
									'operator' => '==',
									'value' => 'options',
								),
							),
						),
						'choices' => array(
							1 => 'True',
							0 => 'False',
						),
						'other_choice' => 0,
						'save_other_choice' => 0,
						'default_value' => 1,
						'layout' => 'horizontal',
					),
				),
				'location' => array(
					array(
						array(
							'param' => 'post_type',
							'operator' => '==',
							'value' => $this->post_type
						)
					)
				),
				'menu_order' => 0,
				'position' => 'normal',
				'style' => 'default',
				'label_placement' => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen' => array(
					0 => 'permalink',
					1 => 'the_content',
					2 => 'excerpt',
					3 => 'custom_fields',
					4 => 'discussion',
					5 => 'comments',
					6 => 'slug',
					7 => 'author',
					8 => 'format',
					9 => 'featured_image',
					10 => 'categories',
					11 => 'tags'
				),
			);
			register_field_group($field_group);
		} // end public function acf_include_fields
		
		public function acf_load_parent_menu_field($field) {
			$field['choices'] = $this->parent_menus;
			return $field;
		} // end public function acf_load_parent_menu_field
		
		public function acf_load_capabilities_field($field) {
			global $wp_roles;
			if (!$wp_roles || !count($wp_roles->roles)) {
				return $field;
			}
			$sorted_caps = array();
			$caps = array();
			foreach ($wp_roles->roles as $role) {
				foreach ($role['capabilities'] as $cap => $value) {
					if (!in_array($cap, $sorted_caps)) {
						$sorted_caps[] = $cap;
					}
				} // end foreach cap
			} // end foreach role
			sort($sorted_caps);
			foreach ($sorted_caps as $cap) {
				$caps[$cap] = $cap;
			} // end foreach sorted_caps
			$field['choices'] = $caps;
			return $field;
		} // end public function 
		
		public function admin_columns($columns) {
			$new_columns = array();
			foreach ($columns as $index => $column) {
				if ($index == 'title') {
					$new_columns[$index] = $column;
					$new_columns['menu_text'] = __('Menu Text', $this->text_domain);
					$new_columns['slug'] = __('Slug', $this->text_domain);
					$new_columns['location'] = __('Location (Parent)', $this->text_domain);
					$new_columns['redirect'] = __('Redirect', $this->text_domain);
					$new_columns['order'] = __('Order', $this->text_domain);
					$new_columns['capability'] = __('Capability', $this->text_domain);
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
				case 'menu_text':
					$value = trim(get_post_meta($post_id, '_acfop_menu', true));
					if (!$value) {
						$value = trim(get_the_title($post_id));
					}
					echo $value;
					break;
				case 'slug':
					$value = trim(get_post_meta($post_id, '_acfop_slug', true));
					if (!$value) {
						$value = trim(get_the_title($post_id));
						$value = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $value), '-'));
					}
					echo $value;
					break;
				case 'location':
					$value = get_post_meta($post_id, '_acfop_parent', true);
					if (isset($this->parent_menus[$value])) {
						echo $this->parent_menus[$value];
					} else {
						global $acf_options_pages;
						if (count($acf_options_pages)) {
							foreach ($acf_options_pages as $key => $options_page) {
								if ($key == $value) {
									echo $options_page['menu_title'];
								} // end if key == value
							} // end foreach acf_options_page
						} // end if cout acf_options_pages
					} // end if esl
					break;
				case 'capability':
					$value = get_post_meta($post_id, '_acfop_capability', true);
					echo $value;
					//the_field('_acfop_capability', $post_id);
					break;
				case 'order':
					$value = get_post_meta($post_id, '_acfop_order', true);
					if ($value != '') {
						echo $value;
					}
					break;
				case 'redirect':
					$value = get_post_meta($post_id, '_acfop_redirect', true);
					if ($value == 1) {
						echo 'True';
					} elseif ($value == 0 && $value != '') {
						echo 'False';
					}
					break;
				default:
					// do nothing
					break;
			} // end switch
		} // end public function admin_columns_content
		
		public function build_admin_menu_list() {
			global $menu;
			//global $submenu;
			$parent_menus = array('none' => 'None');
			
			$options_pages = array();
			if (isset($GLOBALS['acf_options_pages'])) {
				$options_pages = $GLOBALS['acf_options_pages'];
			}
			//echo '<pre>'; print_r($options_pages);
			//print_r($menu); die;
			if (!count($menu)) {
				// bail early
				$this->parent_menus = $parent_menus;
				return;
			}
			//print_r($menu); die;
			foreach ($menu as $item) {
				if (isset($item[0]) && $item[0] != '' && 
						isset($item[2]) && !in_array($item[2], $this->exclude_locations)) {
					if ($item[2] == 'edit-comments.php') {
						$parent_menus[$item[2]] = 'Comments';
					} elseif ($item[2] == 'plugins.php') {
						$parent_menus[$item[2]] = 'Plugins';
					} elseif (isset($item[5]) && preg_match('/^toplevel_page_/i', $item[5])) {
						// search options pages to get correct slug
						//echo '<pre>'; print_r($item); echo '</pre>';
						$found = false;
						foreach ($options_pages as $options_page) {
							if ($item[0] == $options_page['page_title']) {
								$slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $item[0]), '-'));
								$parent_menus[$slug] = $item[0];
								$found = true;
							}
						}
						if (!$found) {
							$key = $item[2];
							$value = $item[0];
							if (!preg_match('/\.php/', $key)) {
								//$key = 'admin.php?page='.$key;
							}
							$parent_menus[$key] = $value;
						}
					} else {
						$key = $item[2];
						$value = $item[0];
						if (!preg_match('/\.php/', $key)) {
							//$key = 'admin.php?page='.$key;
						}
						$parent_menus[$key] = $value;
					} // end if else
				} // end if good parent menu
			} // end foreach menu
			//echo '<pre>'; print_r($menu); 
			//die;
			$this->parent_menus = $parent_menus;
		} // end public function build_admin_menu_listacf_load_capabilities_field
		
		public function load_text_domain() {
			load_plugin_textdomain($this->text_domain, false, dirname(plugin_basename(__FILE__)).'/lang/');
			do_action('acf_options_page/load_text_domain'); 
		} // end public function load_text_domain
		
		public function sort_by_order($a, $b) {
			if ($a['order'] == $b['order']) {
				return 0;
			} elseif ($a['order'] < $b['order']) {
				return -1;
			} else {
				return 1;
			}
		} // end public function sort_by_order
		
		public function activate() {
			// just in case I want to do anything on activate
		} // end public function activate
		
		public function deactivate() {
			// just in case I want to do anyting on deactivate
		} // end public function deactivate
		
		private function register_post_type() {
			// register the post type
			$args = array('label' => __('Options Pages', $this->text_domain),
										'description' => '',
										'public' => false,
										'show_ui' => true,
										'show_in_menu' => true,
										'capability_type' => 'post',
										'map_meta_cap' => true,
										'hierarchical' => false,
										'rewrite' => array('slug' => $this->post_type, 'with_front' => false),
										'query_var' => true,
										'exclude_from_search' => true,
										'menu_position' => 100,
										'menu_icon' => 'dashicons-admin-generic',
										'supports' => array('title','custom-fields','revisions'),
										'labels' => array('name' => __('Options Pages', $this->text_domain),
																			'singular_name' => __('Options Page', $this->text_domain),
																			'menu_name' =>	__('Options Pages', $this->text_domain),
																			'add_new' => __('Add Options Page', $this->text_domain),
																			'add_new_item' => __('Add New Options Page', $this->text_domain),
																			'edit' => __('Edit', $this->text_domain),
																			'edit_item' => __('Edit Options Page', $this->text_domain),
																			'new_item' => __('New Options Page', $this->text_domain),
																			'view' => __('View Options Page', $this->text_domain),
																			'view_item' => __('View Options Page', $this->text_domain),
																			'search_items' => __('Search Options Pages', $this->text_domain),
																			'not_found' => __('No Options Pages Found', $this->text_domain),
																			'not_found_in_trash' => __('No Options Pages Found in Trash', $this->text_domain),
																			'parent' => __('Parent Options Page', $this->text_domain)));
			register_post_type($this->post_type, $args);
		} // end private function register_post_type
		
		private function acf_add_options_pages() {
			if (!function_exists('acf_add_options_sub_page')) {
				return;
			}
			// get all the options pages and add them
			$options_pages = array('top' => array(), 'sub' => array());
			$args = array('post_type' => $this->post_type,
										'post_status' => 'publish',
										'posts_per_page' => -1,
										'order' => 'ASC');
			$page_query = new WP_Query($args);
			if (count($page_query->posts)) {
				foreach ($page_query->posts as $post) {
					$id = $post->ID;
					$title = get_the_title($id);
					$menu_text = trim(get_post_meta($id, '_acfop_menu', true));
					if (!$menu_text) {
						$menu_text = $title;
					}
					$slug = trim(get_post_meta($id, '_acfop_slug', true));
					if (!$slug) {
						$slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
					}
					$parent = get_post_meta($id, '_acfop_parent', true);
					$capability = get_post_meta($id, '_acfop_capability', true);
					$post_id = 'options';
					$save_to = get_post_meta($id, '_acfop_save_to', true);
					$autoload = 0;
					if ($save_to == 'post') {
						$post_id = intval(get_post_meta($id, '_acfop_post_page', true));
					} else {
						$autoload = get_post_meta($id, '_acfop_autoload', true);
					}
					if ($parent == 'none') {
						$options_page = array('page_title' =>	$title,
																	'menu_title' => $menu_text,
																	'menu_slug' => $slug,
																	'capability' => $capability,
																	'post_id' => $post_id,
																	'autoload' => $autoload);
						$redirect = true;
						$value = get_post_meta($id, '_acfop_redirect', true);
						if ($value == '0') {
							$redirect = false;
						}
						$options_page['redirect'] = $redirect;
						if ($redirect) {
							$options_page['slug'] = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
						}
						
						$icon = '';
						$value = get_post_meta($id, '_acfop_icon', true);
						if ($value != '') {
							$icon = $value;
						}
						if ($icon) {
							$options_page['icon_url'] = $icon;
						}
						
						$menu_position = '';
						$value = get_post_meta($id, '_acfop_position', true);
						if ($value != '') {
							$menu_position = $value;
						}
						if ($menu_position) {
							$options_page['position'] = $menu_position;
						}
						
						$options_pages['top'][] = $options_page;
					} else {
						$order = 0;
						$value = get_post_meta($id, '_acfop_order', true);
						if ($value) {
							$order = $value;
						}
						$options_pages['sub'][] = array('title' => $title,
																						'menu' => $menu_text,
																						'parent' => $parent,
																						'slug' => $slug,
																						'capability' => $capability,
																						'order' => $order,
																						'post_id' => $post_id,
																						'autoload' => $autoload);
					}
				} // end foreach $post;
			} // end if have_posts
			wp_reset_query();
			if (count($options_pages['top'])) {
				foreach ($options_pages['top'] as $options_page) {
					acf_add_options_page($options_page);
				}
			}
			if (count($options_pages['sub'])) {
				usort($options_pages['sub'], array($this, 'sort_by_order'));
				foreach ($options_pages['sub'] as $options_page) {
					acf_add_options_sub_page($options_page);
				}
			}
		} // end private function acf_add_options_pages
		
	} // end class acfOptionsPageAdder
	
	if (!function_exists('jh_plugins_list_meta_box')) {
		function jh_plugins_list_meta_box() {
			if (apply_filters('remove_hube2_nag', false)) {
				return;
			}
			$plugins = apply_filters('jh_plugins_list', array());
				
			$id = 'plugins-by-john-huebner';
			$title = '<a style="text-decoration: none; font-size: 1em;" href="https://github.com/Hube2" target="_blank">Plugins by John Huebner</a>';
			$callback = 'show_blunt_plugins_list_meta_box';
			$screens = array();
			foreach ($plugins as $plugin) {
				$screens = array_merge($screens, $plugin['screens']);
			}
			$context = 'side';
			$priority = 'low';
			add_meta_box($id, $title, $callback, $screens, $context, $priority);
			
			
		} // end function jh_plugins_list_meta_box
		add_action('add_meta_boxes', 'jh_plugins_list_meta_box');
			
		function show_blunt_plugins_list_meta_box() {
			$plugins = apply_filters('jh_plugins_list', array());
			?>
				<p style="margin-bottom: 0;">Thank you for using my plugins</p>
				<ul style="margin-top: 0; margin-left: 1em;">
					<?php 
						foreach ($plugins as $plugin) {
							?>
								<li style="list-style-type: disc; list-style-position:">
									<?php 
										echo $plugin['title'];
										if ($plugin['doc']) {
											?> <a href="<?php echo $plugin['doc']; ?>" target="_blank">Documentation</a><?php 
										}
									?>
								</li>
							<?php 
						}
					?>
				</ul>
				<p><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=hube02%40earthlink%2enet&lc=US&item_name=Donation%20for%20WP%20Plugins%20I%20Use&no_note=0&cn=Add%20special%20instructions%20to%20the%20seller%3a&no_shipping=1&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted" target="_blank">Please consider making a small donation.</a></p><?php 
		}
	} // end if !function_exists

?>