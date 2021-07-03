<?php 

	/*
		Plugin Name: ACF Options Page Admin
		Plugin URI: https://wordpress.org/plugins/options-page-admin-for-acf/
		Description: Allows easy creation of options pages using Advanced Custom Fields Pro without needing to do any PHP coding. Requires that ACF Pro is installed.
		Author: John A. Huebner II
		Author URI: https://github.com/Hube2
		Version: 3.10.0
	*/
	
	// If this file is called directly, abort.
	if (!defined('WPINC')) {die;}
	
	new acfOptionsPageAdder();
	
	class acfOptionsPageAdder {
		
		private $version = '3.10.0';
		private $post_type = 'acf-options-page';
		private $parent_menus = array();
		private $exclude_locations = array('',
																			 //'cpt_main_menu',
																			 //'edit.php?post_type=acf-field-group',
																			 //'edit-comments.php',
																			 //'plugins.php',
																			 //'edit-tags.php?taxonomy=link_category',
																			 'edit.php?post_type=acf-options-page',
																			 );
		private $options_pages = array(); // hold all options pages
		private $hooks = array(); // options page hook to post id
		
		public function __construct() {
			add_action('acf/init', array($this, 'init'), 20);
			add_action('admin_menu', array($this, 'add_hooks'), 9999); // makes sure everything has been added
			add_action('plugins_loaded', array($this, 'load_text_domain'));
			add_action('after_setup_theme', array($this, 'after_setup_theme'), 1);
			add_filter('acf/load_value/key=field_acf_key_acfop_title', array($this, 'set_title_field'), 20, 3);
			add_filter('acf/load_value/key=field_acf_key_acfop_slug', array($this, 'set_page_slug_field'), 20, 3);
			add_filter('acf/validate_value/key=field_acf_key_acfop_slug', array($this, 'unique_value'), 10, 4);
			add_filter('acf/validate_value/key=field_acf_key_acfop_slug', array($this, 'slug_characters'), 10, 4);
			add_filter('acf/validate_value/key=field_acf_key_acfop_custom_slug', array($this, 'slug_characters'), 10, 4);
			add_filter('acf/location/rule_values/options_page', array($this, 'options_page_rule_values_titles'), 20);
			add_action('admin_enqueue_scripts', array($this, 'script'));
			add_action('acf/save_post', array($this, 'acf_save_post'), 20);
			add_filter('user_has_cap', array($this, 'attach_files'), 10, 4);
			add_action('acf/include_fields', array($this, 'acf_include_fields'));
		} // end public function __construct
		
		public function attach_files($allcaps, $caps, $args, $user) {
			if (!defined('DOING_AJAX') || !DOING_AJAX || !isset($_REQUEST['post_id']) ||
					!in_array('edit_post', $args)) {
				return $allcaps;
			}
			$id = intval($_REQUEST['post_id']);
			if (get_post_type($id) != $this->post_type ||
					!isset($this->options_pages[$id])) {
				return $allcaps;
			}
			remove_filter('user_has_cap', array($this, 'attach_files'), 10);
			
			$page = $this->options_pages[$id];
			$cap = $page['acf']['capability'];
			if (current_user_can($cap)) {
				// add all $caps to $allcaps
				foreach ($caps as $add) {
					$allcaps[$add] = true;
				}
			}
			add_filter('user_has_cap', array($this, 'attach_files'), 10, 4);
			return $allcaps;
		} // end public function attach_files
		
		public function script() {
			$handle    	= 'acf-options-page-adder';
			$src       	= plugin_dir_url(__FILE__).'options-pages.js';
			$deps      	= array('jquery');
			$ver       	= $this->version;
			$in_footer 	= false;
			wp_enqueue_script($handle, $src, $deps, $ver, $in_footer);
		} // end public function script
		
		public function options_page_rule_values_titles($choices) {
			if (!apply_filters('acf-options-page-adder/choice_titles', true)) {
				return $choices;
			}
			$pages = acf_get_options_pages();
			if (!$pages) {
				return $choices;
			}
			foreach ($pages as $page) {
				$choices[$page['menu_slug']] = $page['page_title'];
			}
			return $choices;
		} // end public function options_page_rule_values_titles
		
		public function set_page_slug_field($value, $post_id, $field) {
			if (!empty($value)) {
				return $value;
			}
			// options page was created before the title field was added
			$slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', get_the_title($post_id)), '-'));
			if ($slug != 'auto-draft') {
				$value = $slug;
			}
			return $value;
		} // end public function set_page_slug_field
		
		public function slug_characters($valid, $value, $post_id, $field) {
			// only allow leters, number, underscores, dashes
			if (!$valid) {
				return $valid;
			}
			if (!preg_match('/^[a-z]/i', $value) || preg_match('/[^-_0-9a-z]/i', $value)) {
				return $field['label'].' '.__('must begin with a letter and include only numbers, letters, underscores and hyphens', 'acf-options-page-adder');
			}
			return $valid;
		} // end public function slug_characters
		
		public function unique_value($valid, $value, $field, $input) {
			// must be unique
			if (!$valid || (!isset($_POST['post_id']) && !isset($_POST['post_ID']))) {
				return $valid;
			}
			if (isset($_POST['post_id'])) {
				$post_id = intval($_POST['post_id']);
			} else {
				$post_id = intval($_POST['post_ID']);
			}
			if (!$post_id) {
				return $valid;
			}
			$post_type = get_post_type($post_id);
			$field_name = $field['name'];
			$args = array(
				'post_type' => $post_type,
				'post_status' => 'publish, draft, trash',
				'post__not_in' => array($post_id),
				'meta_query' => array(
					array(
						'key' => $field_name,
						'value' => $value
					)
				)
			);
			$query = new WP_Query($args);
			if (count($query->posts)){
				return __('This Value is not Unique. Please enter a unique', 'acf-options-page-adder').' '.$field['label'];
			}
			return $valid;
		} // end public function unique_value
		
		public function set_title_field($value, $post_id, $field) {
			if (!empty($value)) {
				return $value;
			}
			// options page was created before the title field was added
			$title = get_the_title(intval($post_id));
			if ($title != 'Auto Draft') {
				$value = $title;
			}
			return $value;
		} // end public function set_title_field
		
		public function after_setup_theme() {
			if (!function_exists('acf_add_options_page')) {
				return;
			}
			// check to see if acf5 is installed
			// if not then do not run anything else in this plugin
			// move all other actions to this function except text domain since this is too late
			/*
			if (!class_exists('acf') ||
					!function_exists('acf_get_setting') ||
					intval(acf_get_setting('version')) < 5 ||
					!class_exists('acf_pro')) {
				$this->active = false;
				return;
			}
			*/
			add_action('admin_menu', array($this, 'build_admin_menu_list'), 9999);
			//add_action('admin_menu', array($this, 'add_hooks'), 9999); // makes sure everything has been added
			add_filter('acf/load_field/name=_acfop_parent', array($this, 'acf_load_parent_menu_field'));
			add_filter('acf/load_field/name=_acfop_capability', array($this, 'acf_load_capabilities_field'));
			add_filter('manage_edit-'.$this->post_type.'_columns', array($this, 'admin_columns'));
			add_action('manage_'.$this->post_type.'_posts_custom_column', array($this, 'admin_columns_content'), 10, 2);
			require_once(dirname(__FILE__).'/api-template.php');
		} // end public function after_setup_theme
		
		public function init() {
			if (!function_exists('acf_add_options_page')) {
				return;
			}
			$this->register_post_type();
			$this->acf_add_options_pages();
			do_action('acf_options_page/init');
		} // end public function init
		
		public function acf_include_fields() {
			if (!function_exists('acf_add_options_page')) {
				return;
			}
			// this function is called when ACF5 is installed
			$field_group = array(
				'key' => 'acf_options-page-details',
				'title' => __('Options Page Details', 'acf-options-page-adder'),
				'fields' => array(
					array(
						'key' => 'field_acf_key_acfop_tab_basic',
						'label' => __('Basic Settings', 'acf-options-page-adder'),
						'name' => '',
						'type' => 'tab',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => ''
						),
						'placement' => 'left',
						'endpoint' => 0
					),
					array(
						'key' => 'field_acf_key_acfop_title',
						'label' => __('Title Text', 'acf-options-page-adder'),
						'name' => '_acfop_title',
						'prefix' => '',
						'type' => 'text',
						'instructions' => __('This will be used as the options page title.', 'acf-options-page-adder'),
						'required' => 1,
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
						'key' => 'field_acf_key_acfop_menu',
						'label' => __('Menu Text', 'acf-options-page-adder'),
						'name' => '_acfop_menu',
						'prefix' => '',
						'type' => 'text',
						'instructions' => __('Will default to title if left blank.', 'acf-options-page-adder'),
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
						'label' => __('Menu Location (Parent)', 'acf-options-page-adder'),
						'name' => '_acfop_parent',
						'prefix' => '',
						'type' => 'select',
						'instructions' => __('Select the menu this options page will appear under. Will default to None.', 'acf-options-page-adder'),
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
						'key' => 'field_acf_key_acfop_slug',
						'label' => __('Slug', 'acf-options-page-adder'),
						'name' => '_acfop_slug',
						'prefix' => '',
						'type' => 'text',
						'instructions' => __('This field is optional in ACF. It is required here. You must know what the slug is to enable get_options_page_post_id() added in 4.4.0', 'acf-options-page-adder'),
						'required' => 1,
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
						'key' => 'field_acf_key_acfop_order',
						'label' => __('Order', 'acf-options-page-adder'),
						'name' => '_acfop_order',
						'prefix' => '',
						'type' => 'number',
						'instructions' => __('The order that this child menu should appear under its parent menu.', 'acf-options-page-adder'),
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
					array(
						'key' => 'field_acf_key_acfop_show_in_graphql',
						'label' => __('Show in GraphQL', 'acf-options-page-adder'),
						'name' => '_acfop_show_in_graphql',
						'prefix' => '',
						'type' => 'true_false',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
								'width' => '',
								'class' => '',
								'id' => '',
						),
						'show_in_graphql' => 1,
						'message' => '',
						'default_value' => 0,
						'ui' => 1,
						'ui_on_text' => '',
						'ui_off_text' => '',
					),
					array(
						'key' => 'field_acf_key_acfop_tab_advanced',
						'label' => __('Advanced Settings', 'acf-options-page-adder'),
						'name' => '',
						'type' => 'tab',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => ''
						),
						'placement' => 'left',
						'endpoint' => 0
					),
					array(
						'key' => 'field_acf_key_acfop_capability',
						'label' => __('Capability', 'acf-options-page-adder'),
						'name' => '_acfop_capability',
						'prefix' => '',
						'type' => 'select',
						'instructions' => __('The user capability to view this options page. Will default to manage_options.', 'acf-options-page-adder'),
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
						'label' => __('Menu Position', 'acf-options-page-adder'),
						'name' => '_acfop_position',
						'prefix' => '',
						'type' => 'text',
						'instructions' => __('The position in the menu order this menu should appear. WARNING: if two menu items use the same position attribute, one of the items may be overwritten so that only one item displays! Risk of conflict can be reduced by using decimal instead of integer values, e.g. 63.3 instead of 63. Defaults to bottom of utility menu items.<br /><em>Core Menu Item Positions: 2=Dashboard, 4=Separator, 5=Posts, 10=Media, 15=Links, 20=Pages, 25=Comments, 59=Separator, 60=Appearance, 65=Plugins, 70=Users, 75=Tools, 80=Settings, 99=Separator</em>', 'acf-options-page-adder'),
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
						'label' => __('Icon', 'acf-options-page-adder'),
						'name' => '_acfop_icon',
						'prefix' => '',
						'type' => 'text',
						'instructions' => __('The icon url for this menu. Defaults to default WordPress gear.<br /><em>Check out <a href="https://developer.wordpress.org/resource/dashicons/" target="_blank">https://developer.wordpress.org/resource/dashicons/</a> for what to put in this field.</em>', 'acf-options-page-adder'),
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
						'label' => __('Redirect', 'acf-options-page-adder'),
						'name' => '_acfop_redirect',
						'type' => 'true_false',
						'ui' => 1,
						'ui_on_text' => __('Yes', 'acf-options-page-adder'),
						'ui_off_text' => __('No', 'acf-options-page-adder'),
						'instructions' => __('If set to Yes, this options page will redirect to the first child page (if a child page exists). If set to No, this parent page will appear alongside any child pages. Defaults to Yes.<br /><em><strong>NOTE: Changing this setting will effect the location or appearance of sub options pages currently associated with this options page.</strong></em>', 'acf-options-page-adder'),
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
						'default_value' => 0,
					),
					array(
						'key' => 'field_acf_key_acfop_save_to',
						'label' => __('Save To', 'acf-options-page-adder'),
						'name' => '_acfop_save_to',
						'type' => 'radio',
						'instructions' => '<span style="font-size:.85em;">'.__('<sup>1</sup> The standard setting for ACF options pages.<br /><sup>2</sup> Save values to a selected post.<br /><sup>3</sup> You can choose to save all fields of the options page to this options page post in this plugin.<br /><sup>4</sup> Use the options page slug. This replaces the standard ACF "option" prefix in the options table so that you can speicify the slug you set for this options page as the post ID.<br /><sup>5</sup> Works the same as <sup>4</sup> except that you can specify the slug/post ID. This is useful to set other places, for example "user_1" or "term_27"', 'acf-options-page-adder').'</span>',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'choices' => array(
							'options' => __('Options', 'acf-options-page-adder').'<sup>1</sup>',
							'post' => __('Post Object', 'acf-options-page-adder').'<sup>2</sup>',
							'this_post' => __('This Post', 'acf-options-page-adder').'<sup>3</sup>',
							'page_slug' => __('Use Page Slug', 'acf-options-page-adder').'<sup>4</sup>',
							'custom_slug' => __('Use Custom Slug', 'acf-options-page-adder'.'<sup>5</sup>')
						),
						'other_choice' => 0,
						'save_other_choice' => 0,
						'default_value' => 'options',
						'layout' => 'horizontal',
					),
					array(
						'key' => 'field_acf_key_acfop_custom_slug',
						'label' => __('Custom Slug/ID', 'acf-options-page-adder'),
						'name' => '_acfop_custom_slug',
						'prefix' => '',
						'type' => 'text',
						'instructions' => __('Enter the Custom Slug to use for saving fields on this options page.', 'acf-options-page-adder'),
						'required' => 1,
						'conditional_logic' => array(
							array(
								array(
									'field' => 'field_acf_key_acfop_save_to',
									'operator' => '==',
									'value' => 'custom_slug',
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
						'key' => 'field_acf_key_acfop_post_page',
						'label' => __('Post/Page', 'acf-options-page-adder'),
						'name' => '_acfop_post_page',
						'type' => 'post_object',
						'instructions' => __('Select the post object to save and load data to/from.', 'acf-options-page-adder'),
						'required' => 1,
						'conditional_logic' => array(
							array(
								array(
									'field' => 'field_acf_key_acfop_save_to',
									'operator' => '==',
									'value' => 'post',
								),
							),
						),
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'post_type' => array(
						),
						'taxonomy' => array(
						),
						'allow_null' => 0,
						'multiple' => 0,
						'return_format' => 'id',
						'ui' => 1,
					),
					array(
						'key' => 'field_acf_key_acfop_autoload',
						'label' => __('Autoload Values', 'acf-options-page-adder'),
						'name' => '_acfop_autoload',
						'type' => 'true_false',
						'ui' => 1,
						'ui_on_text' => __('Yes', 'acf-options-page-adder'),
						'ui_off_text' => __('No', 'acf-options-page-adder'),
						'instructions' => __('Whether to load the options (values saved from this options page) when WordPress starts up. Added in ACF v5.2.8.', 'acf-options-page-adder'),
						'required' => 0,
						'conditional_logic' => array(
							array(
								array(
									'field' => 'field_acf_key_acfop_save_to',
									'operator' => '==',
									'value' => 'options',
								),
							),
							array(
								array(
									'field' => 'field_acf_key_acfop_save_to',
									'operator' => '==',
									'value' => 'page_slug',
								),
							),
							array(
								array(
									'field' => 'field_acf_key_acfop_save_to',
									'operator' => '==',
									'value' => 'custom_slug',
								),
							),
						),
						'default_value' => 1,
					),
					array(
						'key' => 'field_acf_key_acfop_tab_content',
						'label' => __('Customize', 'acf-options-page-adder'),
						'name' => '',
						'type' => 'tab',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => ''
						),
						'placement' => 'left',
						'endpoint' => 0
					),
					array(
						'key' => 'field_acf_key_acfop_custize',
						'label' => __('Customization', 'acf-options-page-adder'),
						'name' => '_acfop_customize',
						'type' => 'true_false',
						'ui' => 1,
						'ui_on_text' => __('Yes', 'acf-options-page-adder'),
						'ui_off_text' => __('No', 'acf-options-page-adder'),
						'instructions' => __('Turning this on will allow you to add header and footer content to the options page<br /><span style="color: #C00;">WARNING:</span> On options page with a large number of fields this feature can cause the loading of the options page to cause an out of memory fatal error. This feature should not be used on options pages that may have large numbers of fields to display. If this feature is off it will disable not only the content editors here but also will disable the filters mentioned in the documentation.', 'acf-options-page-adder'),
						'message' => __('Allow Customization of Header &amp; Footer?', 'acf-options-page-adder'),
						'required' => 0,
						'conditional_logic' => 0,
						'default_value' => 0,
					),
					array(
						'key' => 'field_acf_key_acfop_header_content',
						'label' => __('Header Content', 'acf-options-page-adder'),
						'name' => '_acfop_header_content',
						'type' => 'wysiwyg',
						'instructions' => __('Content will be added to the options page header after the Options Page Title.', 'acf-options-page-adder'),
						'required' => 0,
						'conditional_logic' => array(
							array(
								array(
									'field' => 'field_acf_key_acfop_custize',
									'operator' => '==',
									'value' => 1,
								),
							),
						),
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'tabs' => 'all',
						'toolbar' => 'full',
						'media_upload' => 1,
						'delay' => 0,
					),
					array(
						'key' => 'field_acf_key_acfop_footer_content',
						'label' => __('Footer Content', 'acf-options-page-adder'),
						'name' => '_acfop_footer_content',
						'type' => 'wysiwyg',
						'instructions' => __('Content will be added to the options page footer after all ACF Field Groups.', 'acf-options-page-adder'),
						'required' => 0,
						'conditional_logic' => array(
							array(
								array(
									'field' => 'field_acf_key_acfop_custize',
									'operator' => '==',
									'value' => 1,
								),
							),
						),
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'tabs' => 'all',
						'toolbar' => 'full',
						'media_upload' => 1,
						'delay' => 0,
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
				'label_placement' => 'left',
				'instruction_placement' => 'field',
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
			acf_add_local_field_group($field_group);
		} // end public function acf_include_fields
		
		private function build_options_page_settings($post_id, $title='') {
			$skip_hook = false;
			if ($title == '') {
				$skip_hook = true;
			}
			$settings = array();
			$settings['acf'] = array();
			if ($title == '') {
				$title = get_the_title($post_id);
			}
			$show_in_graphql = trim(get_post_meta($post_id, '_acfop_show_in_graphql', true));
			if ($show_in_graphql) {
				$settings['acf']['show_in_graphql'] = $show_in_graphql;
			}
			$settings['acf']['page_title'] = $title;
			$menu_text = trim(get_post_meta($post_id, '_acfop_menu', true));
			if (!$menu_text) {
				$menu_text = $title;
			}
			$settings['acf']['menu_title'] = $menu_text;
			$slug = trim(get_post_meta($post_id, '_acfop_slug', true));
			if (!$slug) {
				$slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
			}
			$settings['acf']['menu_slug'] = $slug;
			
			$parent = get_post_meta($post_id, '_acfop_parent', true);
			if ($parent == 'none') {
				$parent = '';
			}
			$settings['acf']['parent_slug'] = $parent;
			
			$settings['acf']['capability'] = get_post_meta($post_id, '_acfop_capability', true);
			
			$save_id = 'options';
			$save_to = get_post_meta($post_id, '_acfop_save_to', true);
			$autoload = 0;
			if ($save_to == 'post') {
				$save_id = intval(get_post_meta($post_id, '_acfop_post_page', true));
			} elseif ($save_to == 'this_post') {
				$save_id = $post_id;
			} elseif ($save_to == 'page_slug') {
				$save_id = trim(get_post_meta($post_id, '_acfop_slug', true));
			} elseif ($save_to == 'custom_slug') {
				$save_id = trim(get_post_meta($post_id, '_acfop_custom_slug', true));
			} else {
				$autoload = intval(get_post_meta($post_id, '_acfop_autoload', true));
			}
			$settings['acf']['post_id'] = $save_id;
			$settings['acf']['autoload'] = $autoload;
			
			if (!$parent) {
				$settings['type'] = 'page';
				$settings['order'] = false;
				$redirect = true;
				$value = get_post_meta($post_id, '_acfop_redirect', true);
				if ($value == '0' && $value != '') {
					$redirect = false;
				}
				$settings['acf']['redirect'] = $redirect;
				
				$icon = '';
				$value = get_post_meta($post_id, '_acfop_icon', true);
				if ($value != '') {
					$icon = $value;
				}
				$settings['acf']['icon_url'] = $icon;
				$menu_position = false;
				$value = get_post_meta($post_id, '_acfop_position', true);
				if ($value != '') {
					$menu_position = $value;
				}
				$settings['acf']['position'] = $menu_position;
				
			} else {
				// parent set
				$settings['type'] = 'sub_page';
				$settings['order'] = intval(get_post_meta($post_id, '_acfop_order', true));
			}
			if (!$skip_hook) {
				$settings['hook'] = get_plugin_page_hookname($slug, $parent);
			}
			$allow = get_post_meta($post_id, '_acfop_customize', true);
			$header = '';
			$footer = '';
			if ($allow === '') {
				$allow = 0;
				$header = trim(get_post_meta($post_id, '_acfop_header_content', true));
				$footer = trim(get_post_meta($post_id, '_acfop_footer_content', true));
			} else {
				$allow = intval($allow);
			}
			if ($allow || $header || $footer) {
				$allow = true;
				update_post_meta($post_id, '_acfop_customize', '1');
			} else {
				$allow = false;
				update_post_meta($post_id, '_acfop_customize', '0');
			}
			$header = trim(get_post_meta($post_id, '_acfop_header_content', true));
			$footer = trim(get_post_meta($post_id, '_acfop_footer_content', true));
			$settings['customize'] = $allow;
			$settings['header'] = $header;
			$settings['footer'] = $footer;
			
			return $settings;
		} // end private function build_options_page_settings
		
		public function add_hooks() {
			if (!function_exists('acf_add_options_page')) {
				return;
			}
			//echo '<pre>'; print_r($this->options_pages); die;
			foreach($this->options_pages as $id => $options_page) {
				if (isset($options_page['customize']) && !$options_page['customize']) {
					continue;
				}
				//echo get_plugin_page_hookname($options_page['acf']['menu_slug'], $options_page['acf']['parent_slug']);
				//echo '<pre>'; print_r($options_page); die;
				if (isset($options_page['hook'])) {
					$hook = $options_page['hook'];
				} else {
					$hook = get_plugin_page_hookname($options_page['acf']['menu_slug'], $options_page['acf']['parent_slug']);
					$this->options_pages[$id]['hook'] = $hook;
				}
				add_action($hook, array($this, 'customize_start'), 1);
				add_action($hook, array($this, 'customize_end'), 20);
				$this->hooks[$hook] = $id;
			}
		} // end public function add_hooks
		
		public function customize_start() {
			//echo 'here'; die;
			ob_start();
		} // end public function customize_start
		
		public function customize_end() {
			
			$replaces = 1;
			
			$content = ob_get_clean();
			
			
			
			$hook = current_filter();
			
			$options_page = $this->options_pages[$this->hooks[$hook]];
			//echo $this->hooks[$hook];
			
			$header = '';
			if (!$options_page['header']) {
				$header = trim(get_post_meta($this->hooks[$hook], '_acfop_header_content', true));
			} else {
				$header = apply_filters('acf_the_content', $options_page['header']);
			}
			$header = apply_filters('acf-options-page-adder/page-header', $header, $hook);
			if ($header) {
				$content = str_replace('</h1>', '</h1>'.$header, $content, $replaces);
			}
			
			$footer = '';
			if (!$options_page['footer']) {
				$footer = trim(get_post_meta($this->hooks[$hook], '_acfop_footer_content', true));
			} else {
				$footer = apply_filters('acf_the_content', $options_page['footer']);
			}
			$footer = apply_filters('acf-options-page-adder/page-footer', $footer, $hook);
			if ($footer) {
				$content = str_replace('</form>', '</form>'.$footer, $content, $replaces);
			}
			
			$content = apply_filters('acf-options-page-adder/page-content', $content, $hook);
			
			echo $content;
		} // end public function customize_end
		
		public function acf_save_post($post_id) {
			if (!is_numeric($post_id)) {
				return;
			}
			// post types that need titles set
			$post_type = get_post_type($post_id);
			if ($this->post_type != $post_type) {
				return;
			}
			
			remove_action('acf/save_post', array($this, 'acf_save_post'), 20);
			
			// set post title based on title field
			$title = get_post_meta($post_id, '_acfop_title', true);
			// strip all html
			$title = preg_replace('#</?\w+[^>]*>#s', '', $title);
			$slug = sanitize_title($title);
			$args = array(
				'ID' => $post_id,
				'post_title' => $title,
				'post_name' => $slug,
				'post_content' => serialize($this->build_options_page_settings($post_id, $title))
			);
			wp_update_post($args);
			add_action('acf/save_post', array($this, 'acf_save_post'), 20);
			
			// remove header/footer content if customize is turned off
			$customize = intval(get_post_meta($post_id, '_acfop_customize', true));
			if (!$customize) {
				update_post_meta($post_id, '_acfop_header_content', '');
				update_post_meta($post_id, '_acfop_footer_content', '');
			}
			
		} // end public function acf_save_post
		
		public function acf_load_parent_menu_field($field) {
			//$this->build_admin_menu_list();
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
					$new_columns['acfop_id'] = __('Post ID', 'acf-options-page-adder');
					$new_columns['acfop_menu_text'] = __('Menu Text', 'acf-options-page-adder');
					$new_columns['acfop_slug'] = __('Slug', 'acf-options-page-adder');
					$new_columns['acfop_location'] = __('Location (Parent)', 'acf-options-page-adder');
					$new_columns['acfop_redirect'] = __('Redirect', 'acf-options-page-adder');
					$new_columns['acfop_saveto'] = __('Save To', 'acf-options-page-adder');
					$new_columns['acfop_order'] = __('Order', 'acf-options-page-adder');
					$new_columns['acfop_capability'] = __('Capability', 'acf-options-page-adder');
					$new_columns['acfop_hook'] = __('Hook Suffix', 'acf-options-page-adder');
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
				case 'acfop_saveto':
					$save_to = get_post_meta($post_id, '_acfop_save_to', true);
					if ($save_to == 'options') {
						$redirect = intval(get_post_meta($post_id, '_acfop_redirect', true));
						$parent = get_post_meta($post_id, '_acfop_parent', true);
						if ($parent == 'none' && $redirect) {
							$save_to = '';
						}
					}
					$redirect = intval(get_post_meta($post_id, '_acfop_redirect', true));
					if ($redirect) {
						echo __('1st sub options page value<br /><strong>OR</strong><br />', 'acf-options-page-adder');
					}
					if ($save_to == 'options') {
						echo __('Options', 'acf-options-page-adder');
					} elseif ($save_to == 'post') {
						echo __('Post', 'acf-options-page-adder'),' ',get_post_meta($post_id, '_acfop_post_page', true);
					} elseif ($save_to == 'this_post') {
						echo __('This Post', 'acf-options-page-adder'),' (',$post_id,')';
					} elseif ($save_to == 'page_slug') {
						$value = trim(get_post_meta($post_id, '_acfop_slug', true));
						if (!$value) {
							$value = trim(get_the_title($post_id));
							$value = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $value), '-'));
						}
						echo $value;
					} elseif ($save_to == 'custom_slug') {
						echo trim(get_post_meta($post_id, '_acfop_custom_slug', true));
					} else {
						$slug = trim(get_post_meta($post_id, '_acfop_slug', true));
						if (!$slug) {
							$slug = trim(get_the_title($post_id));
							$slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $value), '-'));
						}
						$save_to = get_options_page_id($slug);
						//echo __('1st sub option page value<br /><strong>OR</strong><br />', 'acf-options-page-adder');
						if ($save_to == 'options') {
							echo __('Options', 'acf-options-page-adder');
						} elseif ($save_to == $post_id) {
							echo __('This Post', 'acf-options-page-adder'),' (',$post_id,')';
						} else {
							echo __('Post', 'acf-options-page-adder'),' ',$save_to;
						}
					}
					break;
				case 'acfop_id':
					echo $post_id;
					break;
				case 'acfop_menu_text':
					$value = trim(get_post_meta($post_id, '_acfop_menu', true));
					if (!$value) {
						$value = trim(get_the_title($post_id));
					}
					echo $value;
					break;
				case 'acfop_hook':
					$slug = trim(get_post_meta($post_id, '_acfop_slug', true));
					$parent = get_post_meta($post_id, '_acfop_parent', true);
					$value = get_plugin_page_hookname($slug, $parent);
					echo $value;
					break;
				case 'acfop_slug':
					$value = trim(get_post_meta($post_id, '_acfop_slug', true));
					if (!$value) {
						$value = trim(get_the_title($post_id));
						$value = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $value), '-'));
					}
					echo $value;
					break;
				case 'acfop_location':
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
				case 'acfop_capability':
					$value = get_post_meta($post_id, '_acfop_capability', true);
					echo $value;
					//the_field('_acfop_capability', $post_id);
					break;
				case 'acfop_order':
					$value = get_post_meta($post_id, '_acfop_order', true);
					if ($value != '') {
						echo $value;
					}
					break;
				case 'acfop_redirect':
					$value = get_post_meta($post_id, '_acfop_redirect', true);
					if ($value == 1) {
						echo __('Yes', 'acf-options-page-adder');
					} elseif ($value == 0 && $value != '') {
						echo __('No', 'acf-options-page-adder');
					}
					break;
				default:
					// do nothing
					break;
			} // end switch
		} // end public function admin_columns_content
		
		public function build_admin_menu_list() {
			global $menu;
			$parent_menus = array('none' => __('None', 'acf-options-page-adder'));
			$this->exclude_locations = apply_filters('acf-options-page-adder/exlude-locations', $this->exclude_locations);
			$options_pages = acf_get_options_pages();
			if (isset($GLOBALS['acf_options_pages'])) {
				$options_pages = $GLOBALS['acf_options_pages'];
			}
			if (!count($menu)) {
				// bail early
				$this->parent_menus = $parent_menus;
				return;
			}
			foreach ($menu as $index => $item) {
				if (isset($item[0]) && $item[0] != '' && 
						isset($item[2]) && !in_array($item[2], $this->exclude_locations)) {
					if ($item[2] == 'edit-comments.php') {
						$parent_menus[$item[2]] = 'Comments';
					} elseif ($item[2] == 'plugins.php') {
						$parent_menus[$item[2]] = 'Plugins';
					} elseif (isset($item[5]) && preg_match('/^toplevel_page_/i', $item[5]) &&
					          is_array($options_pages) && count($options_pages)) {
						// search options pages to get correct slug
						$found = false;
						foreach ($options_pages as $slug => $options_page) {
							if ($item[0] == $options_page['page_title']) {
								//$slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $item[0]), '-'));
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
					}
				} // end if good parent menu
				if (isset($item[6]) && substr($item[6], 0, 6) == 'fa fa-') {
					$menu[$index][4] .= ' acfop-fontawesome-'.str_replace(' ', '_', $item[6]);
				}
			} // end foreach menu
			$this->parent_menus = $parent_menus;
		} // end public function build_admin_menu_listacf_load_capabilities_field
		
		public function load_text_domain() {
			load_plugin_textdomain('acf-options-page-adder', false, dirname(plugin_basename(__FILE__)).'/lang/');
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
		
		private function register_post_type() {
			// register the post type
			$cap = 'manage_options';
			$cap = apply_filters('acf-options-page-adder/capability', $cap);
			$capabilities = array(
				'edit_post'			=> $cap,
				'delete_post'		=> $cap,
				'edit_posts'		=> $cap,
				'delete_posts'		=> $cap,
			);
			$args = array('label' => __('Options Pages', 'acf-options-page-adder'),
										'description' => '',
										'public' => false,
										'show_ui' => true,
										'show_in_menu' => true,
										'capability_type' => 'post',
										'capabilities' => $capabilities,
										//'map_meta_cap' => true,
										'hierarchical' => false,
										'rewrite' => array('slug' => $this->post_type, 'with_front' => false),
										'query_var' => true,
										'exclude_from_search' => true,
										'menu_position' => 100,
										'menu_icon' => 'dashicons-admin-generic',
										'supports' => array('custom-fields','revisions'),
										'show_in_rest' => false,
										'labels' => array('name' => __('Options Pages', 'acf-options-page-adder'),
																			'singular_name' => __('Options Page', 'acf-options-page-adder'),
																			'menu_name' =>	__('Options Pages', 'acf-options-page-adder'),
																			'add_new' => __('Add Options Page', 'acf-options-page-adder'),
																			'add_new_item' => __('Add New Options Page', 'acf-options-page-adder'),
																			'edit' => __('Edit', 'acf-options-page-adder'),
																			'edit_item' => __('Edit Options Page', 'acf-options-page-adder'),
																			'new_item' => __('New Options Page', 'acf-options-page-adder'),
																			'view' => __('View Options Page', 'acf-options-page-adder'),
																			'view_item' => __('View Options Page', 'acf-options-page-adder'),
																			'search_items' => __('Search Options Pages', 'acf-options-page-adder'),
																			'not_found' => __('No Options Pages Found', 'acf-options-page-adder'),
																			'not_found_in_trash' => __('No Options Pages Found in Trash', 'acf-options-page-adder'),
																			'parent' => __('Parent Options Page', 'acf-options-page-adder')));
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
					$content = $post->post_content;
					if ($content) {
						$content = unserialize($content);
					} else {
						$content = $this->build_options_page_settings($post->ID);
					}
					$this->options_pages[$post->ID] = $content;
				} // end foreach $post;
			} // end if have_posts
			
			if (!count($this->options_pages)) {
				return;
			}
			
			$top = array();
			$sub = array();
			foreach ($this->options_pages as $page) {
				if ($page['type'] == 'page') {
					$top[] = $page;
				} else {
					$sub[] = $page;
				}
			}
			if (count($top)) {
				foreach ($top as $page) {
					acf_add_options_page($page['acf']);
				}
			}
			if (count($sub)) {
				usort($sub, array($this, 'sort_by_order'));
				foreach ($sub as $page) {
					acf_add_options_sub_page($page['acf']);
				}
			}
		} // end private function acf_add_options_pages
		
	} // end class acfOptionsPageAdder
?>
