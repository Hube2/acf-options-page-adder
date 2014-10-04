<?php 
	
	// If this file is called directly, abort.
	if (!defined('WPINC')) {die;}
	
	new acfOptionsPagefieldsetDuplicator();
	
	class acfOptionsPagefieldsetDuplicator {
		
		private $post_type = 'acf-opt-grp-dup';
		private $text_domain;
		private $duplicators = array();
		private $field_groups = array();				// will hold the dyamically generated field groups
		private $acf_field_groups = array();		// will hold data for field groups to duplicate ??? will see
		private $options_pages = array();				// will hold all data for options pages ??? will see
		
		/*
				$duplicators = array(
											   array(
												   'duplicator_name' => '',		// duplicator name   NR
													 'description' => '',				// description				NR
													 'id' => '',								// duplicator id
													 														//		post_id
													 'slug' => '',							// ??? not sure could be useful in the future
													 'type' => '',							// copy/multiply
													 'tabs' => '',							// true/false
													 														//		used for multiply
													 'field_group' => '',				// can be any valid field group
													 														// 		not just what is created using this plugin
																											// 		will allow adding duplicated through code
													 'title' => '',							// field group title
													 														// 		the title for the field group copy
																											//		used for copy
																											//		if blank it will default to the title 
																											//		of the field group being duplicated
													 'options_page' => '',			// options page id
													 														// 		any valid options page
																											//		this one is used for multiply
													 'duplicates' => array(			// array of duplicate information
													 														//		array for each duplicate
													 								   array(
																						   'title' => '',					// title or tab label
																							 'prefix' => '',				// feild prefix for duplicate
																							 'options_page' => '',	// options page id
																							 												// 		used for copy only
																						 ),
																					 
																					 ),
													 
												 ),
											 );
		
		*/
		
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
			// second hook for acf/include_fields with and insanely high priorty
			add_action('acf/include_fields', array($this, 'add_duplicates'), 999);
		} // end public function __construct
		
		public function init() {
			$this->register_post_type();
			$this->build_duplicators();
		} // end public funtion init
		
		public function acf_include_fields() {
			$this->register_duplicator_field_group();
		} // end public function acf_include_fields
		
		public function add_duplicates() {
			if (!count($this->duplicators)) {
				return;
			}
			// get all registered field groups and fields
			// build duplicated field groups and register them with ACF
			$this->acf_get_field_groups();
			// **********************************************************************
			// **********************************************************************
			// **********************************************************************
			// **********************************************************************
			// get all options pages, does it matter if the options page exists?
			// build duplicate field groups and register to the correct options page
			echo '<pre>'; print_r($this->duplicators); print_r($this->field_groups); die;
			
		} // end public function add_duplicates
		
		private function duplicate($args) {
			
		} // end private function duplicate
		
		private function acf_get_field_groups() {
			$field_groups = acf_get_field_groups();
			//echo '<pre>'; print_r($field_groups); die;
			$count = count($field_groups);
			for ($i=0; $i<$count; $i++) {
				// skip field group of this plugin
				if ($field_groups[$i]['key'] != 'group_acf_opt_grp_dup' && 
						$field_groups[$i]['key'] != 'acf_options-page-details') {
					$fields = acf_get_fields($field_groups[$i]['key']);
					$field_groups[$i]['fields'] = $fields;
					$this->field_groups[$field_groups[$i]['key']] = $field_groups[$i];
				}
			}
		} // end public function acf_get_field_groups
		
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
					$group_title = '';
					$slug = get_post_meta($post_id, '_acf_field_grp_dup_group', true);
					$args=array(
						'name' => $slug,
						'post_type' => 'acf-field-group',
						'post_status' => 'publish',
						'posts_per_page' => 1
					);
					$query = new WP_Query($args);
					if (count($query->posts)) {
						$group_title = get_the_title($query->posts[0]->ID);
					}
					echo $group_title;
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
			//echo '<pre>'; print_r($query->posts); echo '</pre>';
			if (count($query->posts)) {
				foreach ($query->posts as $post) {
					$choices[$post->post_name] = $post->post_title;
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
				<div class="error">
					<p>
						<strong>
							<?php _e('A word of caution. Options page field names and keys are normally limited to 64 characters because the options_name field of the wp_options table is limited to 64 characters. Due to the way the ACF works, and the way that the duplication process works, it is possible for field names to exceed this maximum. This will casue a silent failure when saving values. When using options pages in ACF you should modify the database and increase this maximum to 255. The WP team has been talking about doing this for over 4 years.', $this->text_domain); ?>
						</strong>
					</p>
				</div>
			<?php 
		} // end public function admin_message
		
		public function load_text_domain() {
			$this->text_domain = apply_filters('acf_options_page/text_domain', false);
		} // end public function load_text_domain
		
		private function build_duplicators() {
			// get duplicators and build $this->duplicators array
			$args = array('post_type' => $this->post_type,
										'status' => 'publish',
										'posts_per_page' => -1);
			$query = new WP_Query($args);
			if (count($query->posts)) {
				foreach ($query->posts as $post) {
					$args = $this->build_duplicator($post->ID);
					$this->add_duplicator($args);
				}
			}
			//echo '<pre>'; print_r($this->duplicators); die;
		} // end private function build_duplicators
		
		private function build_duplicator($id) {
			// get the fields for the duplicator and create $args;
			$args = array();
			$args['duplicator_name'] = get_the_title($id);
			$args['description'] = get_post_meta($id, '_acf_field_grp_dup_desc', true);
			$args['id'] = $id;
			$args['slug'] = '';
			$args['type'] = get_post_meta($id, '_acf_field_grp_dup_method', true);
			$args['tabs'] = intval(get_post_meta($id, '_acf_field_grp_dup_tabs', true));
			$args['field_group'] = get_post_meta($id, '_acf_field_grp_dup_group', true);
			$args['title'] = trim(get_post_meta($id, '_acf_field_grp_dup_title', true));
			
			$args['options_page'] = '';
			if ($args['type'] == 'multiply') {
				$args['options_page'] = get_post_meta($id, '_acf_field_grp_dup_page', true);
				$repeater = '_acf_field_grp_dups';
			} else {
				// type = copy
				$repeater = '_acf_field_grp_dup_pages';
			}
			$count = intval(get_post_meta($id, $repeater, true));
			$duplicates = array();
			for ($i=0; $i<$count; $i++) {
				$duplicate = array();
				$duplicate['title'] = get_post_meta($id, $repeater.'_'.$i.'__acf_field_grp_dup_title', true);
				$duplicate['prefix'] = get_post_meta($id, $repeater.'_'.$i.'__acf_field_grp_dup_prefix', true);
				$duplicate['options_page'] = get_post_meta($id, $repeater.'_'.$i.'__acf_field_grp_dup_page', true);
				$duplicates[] = $duplicate;
			} // end for
			$args['duplicates'] = $duplicates;
			return $args;
		} // end private function build_duplicator
		
		private function add_duplicator($args) {
			// add duplicator to $this->duplicators;
			// eventually this will be callable to add duplicators through code
			$this->duplicators[] = $args;
		} // end private function add_duplicator
		
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
		
		private function register_duplicator_field_group() {
			$text_domain = $this->text_domain;
			$field_group = array(
				'key' => 'group_acf_opt_grp_dup',
				'title' => __('Duplicator Settings', $text_domain),
				'fields' => array(
					array(
						'key' => 'field_acf_field_grp_dup_desc',
						'label' => __('Description', $text_domain),
						'name' => '_acf_field_grp_dup_desc',
						'prefix' => '',
						'type' => 'textarea',
						'instructions' => __('Enter a description for your duplicator. This description will be shown on the admin page to remind you and others why it was created or what it does.', $text_domain),
						'required' => 0,
						'conditional_logic' => 0,
						'default_value' => '',
						'placeholder' => '',
						'maxlength' => '',
						'rows' => '',
						'new_lines' => 'wpautop',
						'readonly' => 0,
						'disabled' => 0,
					),
					array(
						'key' => 'field_acf_field_grp_dup_method',
						'label' => __('What do you want to duplicate?', $text_domain),
						'name' => '_acf_field_grp_dup_method',
						'prefix' => '',
						'type' => 'radio',
						'instructions' => __('<em>Please note that this will not allow you to copy multiple field groups to the same options page multiple times. You will need to create another duplicator to accomplish this.</em>', $text_domain),
						'required' => 1,
						'conditional_logic' => 0,
						'choices' => array(
							'copy' => __('Duplicate a Field Group to Multiple Options Pages', $text_domain),
							'multiply' => __('Duplicate a Field Group to the Same Options Page Multiple Times', $text_domain),
						),
						'other_choice' => 0,
						'save_other_choice' => 0,
						'default_value' => 'copy',
						'layout' => 'vertical',
					),
					array(
						'key' => 'field_acf_field_grp_dup_tabs',
						'label' => __('Tabs?', $text_domain),
						'name' => '_acf_field_grp_dup_tabs',
						'prefix' => '',
						'type' => 'radio',
						'instructions' => __('Do you want to put the copies into tabs?<br />Selecting yes will add all of the duplicates to a single field group and each duplicate will be in its own tab.<br /><em>This could have unexpected results if your field groups already contain tab fields.</em>', $text_domain),
						'required' => 0,
						'conditional_logic' => array(
							array(
								array(
									'field' => 'field_acf_field_grp_dup_method',
									'operator' => '==',
									'value' => 'multiply',
								),
							),
						),
						'choices' => array(
							1 => __('Yes', $text_domain),
							0 => __('No', $text_domain),
						),
						'other_choice' => 0,
						'save_other_choice' => 0,
						'default_value' => 0,
						'layout' => 'horizontal',
					),
					array(
						'key' => 'field_acf_field_grp_dup_title',
						'label' => __('Field Group Title', $text_domain),
						'name' => '_acf_field_grp_dup_title',
						'prefix' => '',
						'type' => 'text',
						'instructions' => __('Enter the Title for the new compound tabbed group that will be created. It this is left blank then the title of the duplicated field group will be used.', $text_domain),
						'required' => 0,
						'conditional_logic' => array(
							array(
								array(
									'field' => 'field_acf_field_grp_dup_method',
									'operator' => '==',
									'value' => 'multiply',
								),
								array(
									'field' => 'field_acf_field_grp_dup_tabs',
									'operator' => '==',
									'value' => '1',
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
						'key' => 'field_acf_field_grp_dup_group',
						'label' => __('Field Group to Duplicate', $text_domain),
						'name' => '_acf_field_grp_dup_group',
						'prefix' => '',
						'type' => 'select',
						'instructions' => __('Select the field group that you want to duplicate.', $text_domain),
						'required' => 0,
						'conditional_logic' => 0,
						'choices' => array(), // dynamically generated
						'default_value' => array(
						),
						'allow_null' => 0,
						'multiple' => 0,
						'ui' => 0,
						'ajax' => 0,
						'placeholder' => '',
						'disabled' => 0,
						'readonly' => 0,
					),
					array(
						'key' => 'field_acf_field_grp_dup_pages',
						'label' => 'Apply to Options Pages',
						'name' => '_acf_field_grp_dup_pages',
						'prefix' => '',
						'type' => 'repeater',
						'instructions' => __('Select the options pages that the duplicated field group should be applied to.<br />&nbsp;<br /><strong>New Field Name: </strong>When getting field values you must use the prefix you set here along with the field name set in the field group. For example if your field name is <strong>"my_field"</strong> and your prefix is <strong>"my_prefix_"</strong> then you would use the field name of <strong>"my_prefix_my_field"</strong> when getting the value or for any other operation that requires the field name.<br />&nbsp;<br /><strong>New Field Key: </strong>In order to create unique fields for each field group the ACF "key" value of each field must also be generated. The field key will be the original field key appended with an underscore and your prefix. For example, if the original field key looked somthing like <strong>"field_541c4c1f8d1ab"</strong> and your prefix is <strong>"my_prefix_"</strong> then the new field key will be <strong>"field_541c4c1f8d1ab_my_prefix_"</strong>. You would use this new field key anywhere you would normally use the original field key.', $text_domain),
						'required' => 0,
						'conditional_logic' => array(
							array(
								array(
									'field' => 'field_acf_field_grp_dup_method',
									'operator' => '==',
									'value' => 'copy',
								),
							),
						),
						'min' => 2,
						'max' => '',
						'layout' => 'table',
						'button_label' => __('Add Options Page', $text_domain),
						'sub_fields' => array(
							array(
								'key' => 'field_acf_field_grp_dup_pages_sub_title',
								'label' => __('Field Group Title', $text_domain),
								'name' => '_acf_field_grp_dup_title',
								'prefix' => '',
								'type' => 'text',
								'instructions' => __('Use a different title for the field group on this options page.<br />If you do not specify a title for the field group it will default to the original field group title.', $text_domain),
								'required' => 0,
								'conditional_logic' => 0,
								'column_width' => '',
								'default_value' => '',
								'placeholder' => '',
								'prepend' => '',
								'append' => '',
								'maxlength' => '',
								'readonly' => 0,
								'disabled' => 0,
							),
							array(
								'key' => 'field_acf_field_grp_dup_pages_sub_page',
								'label' => __('Options Page', $text_domain),
								'name' => '_acf_field_grp_dup_page',
								'prefix' => '',
								'type' => 'select',
								'instructions' => __('Select the options page to duplicate the field groups to.', $text_domain),
								'required' => 0,
								'conditional_logic' => 0,
								'column_width' => '',
								'choices' => array(), // will be dynamically generated
 								'default_value' => array(
								),
								'allow_null' => 0,
								'multiple' => 0,
								'ui' => 0,
								'ajax' => 0,
								'placeholder' => '',
								'disabled' => 0,
								'readonly' => 0,
							),
							array(
								'key' => 'field_acf_field_grp_dup_pages_sub_prefix',
								'label' => __('Field Name Prefix', $text_domain),
								'name' => '_acf_field_grp_dup_prefix',
								'prefix' => '',
								'type' => 'text',
								'instructions' => __('Enter the prefix to apply to all fields names in the field group. You must supply a unique prefix for each duplication.', $text_domain),
								'required' => 1,
								'conditional_logic' => 0,
								'column_width' => '',
								'default_value' => '',
								'placeholder' => '',
								'prepend' => '',
								'append' => '',
								'maxlength' => '',
								'readonly' => 0,
								'disabled' => 0,
							),
						),
					),
					array(
						'key' => 'field_acf_field_grp_dup_page',
						'label' => __('Apply to Options Page', $text_domain),
						'name' => '_acf_field_grp_dup_page',
						'prefix' => '',
						'type' => 'select',
						'instructions' => __('Select the options page that this field group will be duplicated to.', $text_domain),
						'required' => 0,
						'conditional_logic' => array(
							array(
								array(
									'field' => 'field_acf_field_grp_dup_method',
									'operator' => '==',
									'value' => 'multiply',
								),
							),
						),
						'choices' => array(), // will be dynamically generated
						'default_value' => array(
						),
						'allow_null' => 0,
						'multiple' => 0,
						'ui' => 0,
						'ajax' => 0,
						'placeholder' => '',
						'disabled' => 0,
						'readonly' => 0,
					),
					array(
						'key' => 'field_acf_field_grp_dups',
						'label' => __('Duplicates', $text_domain),
						'name' => '_acf_field_grp_dups',
						'prefix' => '',
						'type' => 'repeater',
						'instructions' => __('Set the values to be used for each duplication of the field group on this page.<br />&nbsp;<br /><strong>New Field Name: </strong>When getting field values you must use the prefix you set here along with the field name set in the field group. For example if your field name is <strong>"my_field"</strong> and your prefix is <strong>"my_prefix_"</strong> then you would use the field name of <strong>"my_prefix_my_field"</strong> when getting the value or for any other operation that requires the field name.<br />&nbsp;<br /><strong>New Field Key: </strong>In order to create unique fields for each field group the ACF "key" value of each field must also be generated. The field key will be the original field key appended with an underscore and your prefix. For example, if the original field key looked somthing like <strong>"field_541c4c1f8d1ab"</strong> and your prefix is <strong>"my_prefix_"</strong> then the new field key will be <strong>"field_541c4c1f8d1ab_my_prefix_"</strong>. You would use this new field key anywhere you would normally use the original field key.', $text_domain),
						'required' => 0,
						'conditional_logic' => array(
							array(
								array(
									'field' => 'field_acf_field_grp_dup_method',
									'operator' => '==',
									'value' => 'multiply',
								),
							),
						),
						'min' => 2,
						'max' => '',
						'layout' => 'table',
						'button_label' => __('Add Duplicate', $text_domain),
						'sub_fields' => array(
							array(
								'key' => 'field_acf_field_grp_dups_sub_title_1',
								'label' => __('Field Group Title', $text_domain),
								'name' => '_acf_field_grp_dup_title',
								'prefix' => '',
								'type' => 'text',
								'instructions' => __('Enter the field group	title to use for this duplicate.<br />If you do not supply a title then the title of the field group will be used.<br />Having the same field group title used multiple times could be confusing to the user.', $text_domain),
								'required' => 0,
								'conditional_logic' => array(
									array(
										array(
											'field' => 'field_acf_field_grp_dup_method',
											'operator' => '==',
											'value' => 'multiply',
										),
										array(
											'field' => 'field_acf_field_grp_dup_tabs',
											'operator' => '==',
											'value' => '0',
										),
									),
								),
								'column_width' => '',
								'default_value' => '',
								'placeholder' => '',
								'prepend' => '',
								'append' => '',
								'maxlength' => '',
								'readonly' => 0,
								'disabled' => 0,
							),
							array(
								'key' => 'field_acf_field_grp_dups_sub_title_2',
								'label' => __('Tab Label', $text_domain),
								'name' => '_acf_field_grp_dup_title',
								'prefix' => '',
								'type' => 'text',
								'instructions' => __('Enter the tab label to use for this duplicate.<br />If no value is given then the labels<strong>"Tab 1"</strong>, <strong>"Tab 2"</strong>, <strong>"Tab 3"</strong>, etc, will be used.', $text_domain),
								'required' => 0,
								'conditional_logic' => array(
									array(
										array(
											'field' => 'field_acf_field_grp_dup_method',
											'operator' => '==',
											'value' => 'multiply',
										),
										array(
											'field' => 'field_acf_field_grp_dup_tabs',
											'operator' => '==',
											'value' => '1',
										),
									),
								),
								'column_width' => '',
								'default_value' => '',
								'placeholder' => '',
								'prepend' => '',
								'append' => '',
								'maxlength' => '',
								'readonly' => 0,
								'disabled' => 0,
							),
							array(
								'key' => 'field_acf_field_grp_dups_sub_prefix',
								'label' => __('Field Name Prefix', $text_domain),
								'name' => '_acf_field_grp_dup_prefix',
								'prefix' => '',
								'type' => 'text',
								'instructions' => __('Enter the prefix to apply to all fields names in the field group. You must supply a unique prefix for each duplication.', $text_domain),
								'required' => 1,
								'conditional_logic' => 0,
								'column_width' => '',
								'default_value' => '',
								'placeholder' => '',
								'prepend' => '',
								'append' => '',
								'maxlength' => '',
								'readonly' => 0,
								'disabled' => 0,
							),
						),
					),
				),
				'location' => array(
					array(
						array(
							'param' => 'post_type',
							'operator' => '==',
							'value' => 'acf-opt-grp-dup',
						),
					),
				),
				'menu_order' => 0,
				'position' => 'acf_after_title',
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
					9 => 'page_attributes',
					10 => 'featured_image',
					11 => 'categories',
					12 => 'tags',
					13 => 'send-trackbacks',
				),);
			register_field_group($field_group);
		} // end private function register_duplicator_field_group
		
	} // end class acfOptionsPagefieldsetDuplicator
	
?>