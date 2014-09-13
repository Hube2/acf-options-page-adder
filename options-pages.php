<?php 

  /*
    Plugin Name: Advanced Custom Fields: Options Page Adder
    Plugin URI: https://github.com/Hube2/acf-options-page-adder
    Description: Allows easy creation of options pages using Advanced Custom Fields Pro without needing to do any PHP coding. Requires that ACF-Pro is installed (or ACF4 & ACF Options Page).
    Author: John A. Huebner II
    Author URI: https://github.com/Hube2
    Version: 2.0.0
  */
	
	// If this file is called directly, abort.
	if (!defined('WPINC')) {die;}
  
  new acfOptionsPageAdder();
  
  class acfOptionsPageAdder {
    
		private $version = '2.0.0';
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
      add_action('acf/include_fields', array($this, 'acf_include_fields')); // ACF5
      add_action('init', array($this, 'acf_add_options_sub_page'));
    } // end public function __construct
    
    public function acf_add_options_sub_page() {
      if (!function_exists('acf_add_options_sub_page') || !function_exists('get_field')) {
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
					if ($parent == 'none') {
						$options_pages['top'][] = array('page_title' =>  $title,
																						'menu_title' => $menu_text,
																						'menu_slug' => $slug,
																						'capability' => $capability);
					} else {
						$options_pages['sub'][] = array('title' => $title,
																						'menu' => $menu_text,
																						'parent' => $parent,
																						'slug' => $slug,
																						'capability' => $capability);
					}
        } // end foreach $post;
      } // end if have_posts
      wp_reset_query();
			//echo '<pre>'; print_r($options_pages); die;
			if (count($options_pages['top'])) {
				foreach ($options_pages['top'] as $options_page) {
					acf_add_options_page($options_page);
				}
			}
			if (count($options_pages['sub'])) {
				foreach ($options_pages['sub'] as $options_page) {
					acf_add_options_sub_page($options_page);
				}
			}
    } // end public function acf_add_options_sub_page
    
    public function acf_include_fields() {
      // this function is called when ACF5 is installed
      if (!function_exists('register_field_group')) {
        return;
      }
      $field_group = array('key' => 'acf_options-page-details',
                           'title' => 'Options Page Details',
                           'fields' => array(array('key' => 'field_acf_key_acfop_message',
                                                   'label' => 'Options Page Message',
                                                   'name' => '',
                                                   'prefix' => '',
                                                   'type' => 'message',
                                                   'instructions' => '',
                                                   'required' => 0,
                                                   'conditional_logic' => 0,
                                                   'message' => 'Title above is the title that will appear on the page. Enter other details as needed.<br />For more information see the ACF documentation for <a href="http://www.advancedcustomfields.com/resources/acf_add_options_page/" target="_blank">acf_add_options_page()</a> and <a href="" target="_blank">acf_add_options_sub_page()</a>.'),
                                            
                                             array('key' => 'field_acf_key_acfop_menu',
                                                   'label' => 'Menu Text',
                                                   'name' => '_acfop_menu',
                                                   'prefix' => '',
                                                   'type' => 'text',
                                                   'instructions' => 'Will default to title if left blank.',
                                                   'required' => 0,
                                                   'conditional_logic' => 0,
                                                   'default_value' => '',
                                                   'placeholder' => '',
                                                   'prepend' => '',
                                                   'append' => '',
                                                   'maxlength' => '',
                                                   'readonly' => 0,
                                                   'disabled' => 0),
                                             array('key' => 'field_acf_key_acfop_slug',
                                                   'label' => 'Slug',
                                                   'name' => '_acfop_slug',
                                                   'prefix' => '',
                                                   'type' => 'text',
                                                   'instructions' => 'Will default to sanitized title.',
                                                   'required' => 0,
                                                   'conditional_logic' => 0,
                                                   'default_value' => '',
                                                   'placeholder' => '',
                                                   'prepend' => '',
                                                   'append' => '',
                                                   'maxlength' => '',
                                                   'readonly' => 0,
                                                   'disabled' => 0),
                                             array('key' => 'field_acf_key_acfop_parent',
                                                   'label' => 'Menu Location',
                                                   'name' => '_acfop_parent',
                                                   'prefix' => '',
                                                   'type' => 'select',
                                                   'instructions' => 'Select the menu this options page will appear under. Will default to None.',
                                                   'required' => 0,
                                                   'conditional_logic' => 0,
                                                   'choices' => array(), // dynamic populate
                                                   'default_value' => 'themes.php',
                                                   'allow_null' => 1,
                                                   'multiple' => 0,
                                                   'ui' => 0,
                                                   'ajax' => 0,
                                                   'placeholder' => '',
                                                   'disabled' => 0,
                                                   'readonly' => 0),
                                             array('key' => 'field_acf_key_acfop_capability',
                                                   'label' => 'Capability',
                                                   'name' => '_acfop_capability',
                                                   'prefix' => '',
                                                   'type' => 'select',
                                                   'instructions' => 'The user capability to view this options page. Will default to manage_options.',
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
                                                   'readonly' => 0),
																							),
                           'location' => array(array(array('param' => 'post_type',
                                                           'operator' => '==',
                                                           'value' => $this->post_type))),
                           'menu_order' => 0,
                           'position' => 'normal',
                           'style' => 'default',
                           'label_placement' => 'top',
                           'instruction_placement' => 'label',
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
                                                     11 => 'tags'));
      register_field_group($field_group);
    } // end public function acf_include_fields
    
    public function admin_columns($columns) {
      $new_columns = array();
      foreach ($columns as $index => $column) {
        if ($index == 'title') {
          $new_columns[$index] = $column;
          $new_columns['menu_text'] = 'Menu Text';
          $new_columns['slug'] = 'Slug';
          $new_columns['location'] = 'Location (Parent)';
					$new_columns['order'] = 'Order';
          $new_columns['capability'] = 'Capability';
        } else {
          if (strtolower($column) != 'date') {
            $new_columns[$index] = $column;
          }
        }
      }
      return $new_columns;
    } // end public function admin_columns
    
    public function admin_columns_content($column_name, $post_id) {
      if (!function_exists('get_field')) {
        echo '&nbsp;';
        return;
      }
      switch ($column_name) {
        case 'menu_text':
					$value = trim(get_post_meta($post_id, '_acfop_menu', true));
          //$value = trim(get_field('_acfop_menu', $post_id));
          if (!$value) {
            $value = trim(get_the_title($post_id));
          }
          echo $value;
          break;
        case 'slug':
					$value = trim(get_post_meta($post_id, '_acfop_slug', true));
          //$value = trim(get_field('_acfop_slug', $post_id));
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
      if (!count($menu)) {
        $this->parent_menus = $parent_menus;
        return;
      }
			//echo '<pre>'; print_r($menu); die;
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
						$key = $item[2];
						$value = $item[0];
						if (!preg_match('/\.php/', $key)) {
							//$key = 'admin.php?page='.$key;
						}
            $parent_menus[$key] = $value;
          } // end if else
        } // end if good parent menu
      } // end foreach menu
			
      $this->parent_menus = $parent_menus;
			//echo '<pre>'; print_r($parent_menus); die;
    } // end public function build_admin_menu_list
    
    public function acf_load_parent_menu_field($field) {
      $field['choices'] = $this->parent_menus;
      return $field;
    } // end public function acf_load_parent_menu_field
    
    public function acf_load_capabilities_field($field) {
      global $wp_roles;
      if (!$wp_roles || !count($wp_roles->roles)) {
        return $field;
      }
      $roles = $wp_roles->roles;
      $sorted_caps = array();
      $caps = array();
      if (count($roles)) {
        foreach ($roles as $role) {
          foreach ($role['capabilities'] as $cap => $value) {
            if (!in_array($cap, $sorted_caps)) {
              $sorted_caps[] = $cap;
            }
          } // end foreach cap
        } // end foreach role
      } // end if count roles
      sort($sorted_caps);
      foreach ($sorted_caps as $cap) {
        $caps[$cap] = $cap;
      } // end foreach sorted_caps
      $field['choices'] = $caps;
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
										'menu_icon' => 'dashicons-admin-generic',
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
                                       'parent' => 'Parent '.$this->label));
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
