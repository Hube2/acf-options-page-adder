<?php 

	/*
		Plugin Name: Advance Custom Fields: Options Page Adder
		Plugin URI: https://github.com/Hube2/acf-options-page-adder
		Description: Allows easy creation of options pages using Advanced Custom Fields (ACF) and ACF: Options Page  without needing to do any PHP coding. Requires that both ACF and ACF: Options Page be installed and active.
		Author: John A. Huebner II
		Author URI: https://github.com/Hube2
		Version: 0.0.1
		
		Copyright 2014 John A. Huebner II
	*/
	
	new acfOptionsPageAdder();
	
	class acfOptionsPageAdder {
		
		private $post_type = 'acf-options-page';
		private $label = 'Options Page';
		private $parent_menus = array();
		private $prefix = 'acf-op-';
		
		public function __construct() {
			register_activation_hook(__FILE__, array($this, 'activate'));
			register_deactivation_hook(__FILE__, array($this, 'deactivate'));
			add_action('init', array($this, 'register_post_type'));
			add_action('admin_menu', array($this, 'build_admin_menu_list'), 999);
			add_filter('acf/load_field/name=_acfop_parent', array($this, 'acf_load_parent_menu_field'));
			add_filter('acf/load_field/name=_acfop_capability', array($this, 'acf_load_capabilities_field'));
			add_filter('manage_edit-'.$this->post_type.'_columns', array($this, 'admin_columns'));
			add_action('manage_'.$this->post_type.'_posts_custom_column', array($this, 'admin_columns_content'), 10, 2 );	
			add_action('acf/register_fields', array($this, 'acf_register_fields'));
			add_action('plugins_loaded', array($this, 'acf_add_options_sub_page'));
		} // end public function __construct
		
		public function acf_add_options_sub_page() {
			if (!function_exists('acf_add_options_sub_page') || !function_exists('get_field')) {
				return;
			}
			// get all the options pages and add them
			//global $post;
			$args = array('post_type' => $this->post_type,
										'post_status' => 'publish',
										'posts_per_page' => -1,
										'order' => 'ASC');
			$page_query = new WP_Query($args);
			//echo '<pre>';print_r($page_query);die;
			if ($page_query->have_posts()) {
				foreach ($page_query->posts as $post) {
					$id = $post->ID;
					/*
							fields
									_acfop_menu = menu text
									_acfop_slug = slug
									_acfop_parent = parent_menu
									_acfop_capability = capability
					*/
					//$page_query->the_post();
					$title = get_the_title($id);
					$menu_text = trim(get_field('_acfop_menu', $id));
					if (!$menu_text) {
						$menu_text = $title;
					}
					$slug = trim(get_field('_acfop_slug', $id));
					if (!$menu_text) {
						$slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
					}
					$parent = get_field('_acfop_parent', $id);
					$capability = get_field('_acfop_capability', $id);
					$options_page = array('title' => $menu_text,
																'menu' => $title,
																'parent' => $parent,
																'capability' => $capability);
					acf_add_options_sub_page($options_page);
				} // end foreach $post;
			} // end if have_posts
			//wp_reset_query();
		} // end public function acf_add_options_sub_page
		
		public function acf_register_fields() {
			if (!function_exists('register_field_group')) {
				return;
			}
			$field_group = array('id' => 'acf_options-page-details',
													 'title' => 'Options Page Details',
													 'fields' => array(array('key' => '_acf_key_acfop_message',
													 												 'label' => 'Options Page Message',
																									 'name' => '',
																									 'type' => 'message',
																									 'message' => 'Title above is the title that will '.
																									 							'appear on the page. Enter other '.
																																'details as needed',),
																						 array('key' => '_acf_key_acfop_menu',
																						 			 'label' => 'Menu Text',
																									 'name' => '_acfop_menu',
																									 'type' => 'text',
																									 'instructions' => 'Will default to title if left blank.',
																									 'default_value' => '',
																									 'placeholder' => '',
																									 'prepend' => '',
																									 'append' => '',
																									 'formatting' => 'none',
																									 'maxlength' => '',),
																						 array('key' => '_acf_key_acfop_slug',
																						 			 'label' => 'Slug',
																									 'name' => '_acfop_slug',
																									 'type' => 'text',
																									 'instructions' => 'Will default to sanitized title '.
																									 									 'if left blank and will be '.
																																		 'prefixed with "'.
																																		 $this->prefix.'" (ACF Options Page)',
																									 'default_value' => '',
																									 'placeholder' => '',
																									 'prepend' => '',
																									 'append' => '',
																									 'formatting' => 'none',
																									 'maxlength' => '',),
																						 array('key' => '_acf_key_acfop_parent',
																						 			 'label' => 'Menu Location',
																									 'name' => '_acfop_parent',
																									 'type' => 'select',
																									 'instructions' => 'Select the menu this options '.
																									 									 'page will appear under. Will '.
																																		 'default to Appearance Menu.',
																									 'required' => 0,
																									 'choices' => array(), // dynamic populate
																									 'default_value' => 'themes.php',
																									 'allow_null' => 1,
																									 'multiple' => 0,),
																						 array('key' => '_acf_key_acfop_capability',
																						 			 'label' => 'Capability',
																									 'name' => '_acfop_capability',
																									 'type' => 'select',
																									 'instructions' => 'The user capability to view '.
																									 									 'this options page. Will default '.
																																		 'to manage_options.',
																									 'choices' => array(), // dynamic populate
																									 'default_value' => 'manage_options',
																									 'allow_null' => 0,
																									 'multiple' => 0,),),
													 'location' => array(array(array('param' => 'post_type',
													 																 'operator' => '==',
																													 'value' => $this->post_type,
																													 'order_no' => 0,
																													 'group_no' => 0,),),),
													 'options' => array('position' => 'normal',
													 										'layout' => 'default',
																							'hide_on_screen' => array(0 => 'permalink',
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
																																				11 => 'tags',),),
													 'menu_order' => 0,);
			register_field_group($field_group);
		} // end public function acf_register_fields
		
		public function admin_columns($columns) {
			$new_columns = array();
			foreach ($columns as $index => $column) {
				if (strtolower($column) == 'title') {
					$new_columns[$index] = $column;
					$new_columns['menu_text'] = __('Menu Text');
					$new_columns['slug'] = __('Slug');
					$new_columns['location'] = __('Location (Parent)');
					$new_columns['capability'] = __('Capability');
				} else {
					if (strtolower($column) != 'date') {
						$new_columns[$index] = $column;
					}
				}
			}
			return $new_columns;
		} // end public function admin_columns
		
		public function admin_columns_content($column_name, $column_id) {
			if (!function_exists('get_field')) {
				echo '&nbsp;';
				return;
			}
			global $post;
			$id = $post->ID;
			switch ($column_name) {
				case 'menu_text':
					$value = trim(get_field('_acfop_menu', $id));
					if (!$value) {
						$value = trim(get_the_title($id));
					}
					echo $value;
					break;
				case 'slug':
					$value = trim(get_field('_acfop_slug', $id));
					if (!$value) {
						$value = trim(get_the_title($id));
						$value = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $value), '-'));
					}
					$value = $this->prefix.$value;
					echo $value;
					break;
				case 'location':
					//$value = get_field('_acfop_parent', $id);
					//echo $value;
					//echo '<br><pre>'; print_r($this->parent_menus); echo '</pre><br>';
					echo $this->parent_menus[get_field('_acfop_parent', $id)];
					break;
				case 'capability':
					the_field('_acfop_capability', $id);
					break;
				default:
					// do nothing
					break;
			} // end switch
		} // end public function admin_columns_content
		
		public function build_admin_menu_list() {
			global $menu;
			//global $submenu;
			$parent_menus = array('' => 'None');
			if (!count($menu)) {
				$this->parent_menus = $parent_menus;
				return;
			}
			foreach ($menu as $item) {
				if (isset($item[0]) && $item[0] != '' && $item[0] != 'Options Pages' &&
						$item[0] != 'CPT UI' && $item[0] != 'Custom Fields' && $item[0] != 'Links' && 
						$item[0] != 'Options' && 
						isset($item[2]) && $item[2] != '') {
					if ($item[2] == 'edit-comments.php') {
						$parent_menus[$item[2]] = 'Comments';
					} elseif ($item[2] == 'plugins.php') {
						$parent_menus[$item[2]] = 'Plugins';
					} else {
						$parent_menus[$item[2]] = $item[0];
					} // end if else
				} // end if good parent menu
			} // end foreach menu
			$this->parent_menus = $parent_menus;
			//echo '<pre>'; print_r($menu); echo '</pre><br><br><pre>'; print_r($submenu); die;
		} // end public function build_admin_menu_list
		
		public function acf_load_parent_menu_field($field) {
			//echo '<pre>'; print_r($this->parent_menus); echo '</pre>';
			$field['choices'] = $this->parent_menus;
			return $field;
		} // end public function acf_load_parent_menu_field
		
		public function acf_load_capabilities_field($field) {
			global $wp_roles;
			$roles = $wp_roles->roles;
			$sorted_caps = array();
			$caps = array();
			//echo '<pre>'; print_r($wp_roles); echo '</pre>';
			if (count($roles)) {
				foreach ($roles as $role) {
					foreach ($role['capabilities'] as $cap => $value) {
						if (!in_array($cap, $sorted_caps)) {
							$sorted_caps[] = $cap;
						}
					} // end foreach cap
				} // end foreach role
			}
			sort($sorted_caps);
			foreach ($sorted_caps as $cap) {
				$caps[$cap] = $cap;
			} // end foreach sorted_caps
			$field['choices'] = $caps;
			//echo '<pre>'; print_r($caps); echo '</pre>';
			return $field;
		} // end public function acf_load_capabilities_field
		
		public function register_post_type() {
			// register the post type
			$args = array('label' => $this->label.'s',
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
										'supports' => array('title','custom-fields','revisions'),
										'labels' => array ('name' => $this->label.'s',
																			 'singular_name' => $this->label,
																			 'menu_name' =>  $this->label.'s',
																			 'add_new' => 'Add '.$this->label,
																			 'add_new_item' => 'Add New '.$this->label,
																			 'edit' => 'Edit',
																			 'edit_item' => 'Edit '.$this->label,
																			 'new_item' => 'New '.$this->label,
																			 'view' => 'View '.$this->label,
																			 'view_item' => 'View '.$this->label,
																			 'search_items' => 'Search '.$this->label.'s',
																			 'not_found' => 'No '.$this->label.'s Found',
																			 'not_found_in_trash' => 'No '.$this->label.'s Found in Trash',
																			 'parent' => 'Parent '.$this->label
										)
				);
			register_post_type($this->post_type, $args);
		} // end public function register_post_type
		
		public function activate() {
			// just in case I want to do anything on activate
		} // end public function activate
		
		public function deactivate() {
			// just in case I want to do anyting on deactivate
		} // end public function deactivate
		
	} // end class acfOptionsPageAdder

?>