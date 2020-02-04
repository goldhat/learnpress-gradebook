<?php

class LearnPressGradeBookClasses {

	public static function init() {

		add_action('init', array('LearnPressGradeBookClasses', 'register'));
		add_filter( 'rwmb_meta_boxes', array('LearnPressGradeBookClasses', 'metaboxes'));



	}

	public static function metaboxes() {

		$prefix = 'gradebook_class_';

		$meta_boxes[] = array(
			'id' => 'gradebook_class_metabox',
			'title' => esc_html__( 'Add Users to Class', 'learnpress-gradebook' ),
			'post_types' => array('gradebook_class'),
			'context' => 'after_title',
			'priority' => 'default',
			'autosave' => 'false',
			'fields' => array(
				array(
					'id' => $prefix . 'user',
					'type' => 'user',
					'name' => esc_html__( 'User', 'learnpress-gradebook' ),
					'field_type' => 'select_advanced',
					'multiple' => true
				),
				array(
					'id' => $prefix . 'button_2',
					'type' => 'button',
					'name' => esc_html__( 'Button', 'metabox-online-generator' ),
				),
			),

		);

		return $meta_boxes;

	}

	public static function register() {

		$labels = array(
			'name'                  => _x( 'GradeBook Classes', 'Post Type General Name', '' ),
			'singular_name'         => _x( 'GradeBook Class', 'Post Type Singular Name', '' ),
			'menu_name'             => __( 'GradeBook Classes', '' ),
			'name_admin_bar'        => __( 'GradeBook Classes', '' ),
			'archives'              => __( 'Item Archives', '' ),
			'attributes'            => __( 'Item Attributes', '' ),
			'parent_item_colon'     => __( 'Parent Item:', '' ),
			'all_items'             => __( 'All Items', '' ),
			'add_new_item'          => __( 'Add New Item', '' ),
			'add_new'               => __( 'Add New', '' ),
			'new_item'              => __( 'New Item', '' ),
			'edit_item'             => __( 'Edit Item', '' ),
			'update_item'           => __( 'Update Item', '' ),
			'view_item'             => __( 'View Item', '' ),
			'view_items'            => __( 'View Items', '' ),
			'search_items'          => __( 'Search Item', '' ),
			'not_found'             => __( 'Not found', '' ),
			'not_found_in_trash'    => __( 'Not found in Trash', '' ),
			'featured_image'        => __( 'Featured Image', '' ),
			'set_featured_image'    => __( 'Set featured image', '' ),
			'remove_featured_image' => __( 'Remove featured image', '' ),
			'use_featured_image'    => __( 'Use as featured image', '' ),
			'insert_into_item'      => __( 'Insert into item', '' ),
			'uploaded_to_this_item' => __( 'Uploaded to this item', '' ),
			'items_list'            => __( 'Items list', '' ),
			'items_list_navigation' => __( 'Items list navigation', '' ),
			'filter_items_list'     => __( 'Filter items list', '' ),
		);
		$args = array(
			'label'                 => __( 'GradeBook Class', '' ),
			'description'           => __( 'Post Type Description', '' ),
			'labels'                => $labels,
			'supports'              => array( 'title' ),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 5,
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => true,
			'exclude_from_search'   => false,
			'publicly_queryable'    => true,
			'capability_type'       => 'page',
		);
		register_post_type( 'gradebook_class', $args );

	}


}
