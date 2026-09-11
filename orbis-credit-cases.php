<?php
/*
Plugin Name: Orbis Credit Cases
Plugin URI: https://www.pronamic.eu/plugins/orbis-credit-cases/
Description: Orbis Credit Cases.

Version: 1.0.0
Requires at least: 3.0

Author: Pronamic
Author URI: https://www.pronamic.eu/

Text Domain: orbis-credit-cases
Domain Path: /languages/

License: GPL

GitHub URI: https://github.com/wp-orbis/wp-orbis-credit-cases
*/

class OrbisCreditCasesPlugin {
	public function __construct( $file ) {
		$this->file = $file;
	}

	public function setup() {
		// Actions
		add_action( 'init', array( $this, 'init' ) );

		add_filter( 'query_vars', array( $this, 'query_vars' ) );

		add_filter( 'template_include', array( $this, 'template_include_credit_cases' ) );
		add_filter( 'template_include', array( $this, 'template_include_credit_case' ) );

		add_filter( 'the_content', array( $this, 'the_content' ) );
	}


	/**
	 * Initailize.
	 */
	public function init() {
		// Rewrite Rules
		$match_dir = '([^/]+)';

		// @see https://make.wordpress.org/core/2015/10/07/add_rewrite_rule-accepts-an-array-of-query-vars-in-wordpress-4-4/
		// Matching
		add_rewrite_rule(
			'orbis-credit-cases/?$',
			array(
				'orbis_credit_cases' => true,
			),
			'top'
		);

		add_rewrite_rule(
			'orbis-credit-cases/' . $match_dir . '/?$',
			array(
				'orbis_credit_cases'   => true,
				'orbis_credit_case_id' => '$matches[1]',
			),
			'top'
		);

		register_post_type( 'orbis_credit_case', array(
			'labels'             => array(
				'name'               => _x( 'Credit Cases', 'post type general name', 'orbis-credit-cases' ),
				'singular_name'      => _x( 'Credit Case', 'post type singular name', 'orbis-credit-cases' ),
				'menu_name'          => _x( 'Credit Cases', 'admin menu', 'orbis-credit-cases' ),
				'name_admin_bar'     => _x( 'Credit Case', 'add new on admin bar', 'orbis-credit-cases' ),
				'add_new'            => _x( 'Add New', 'credit-case', 'orbis-credit-cases' ),
				'add_new_item'       => __( 'Add New Credit Case', 'orbis-credit-cases' ),
				'new_item'           => __( 'New Credit Case', 'orbis-credit-cases' ),
				'edit_item'          => __( 'Edit Credit Case', 'orbis-credit-cases' ),
				'view_item'          => __( 'View Credit Case', 'orbis-credit-cases' ),
				'all_items'          => __( 'All Credit Cases', 'orbis-credit-cases' ),
				'search_items'       => __( 'Search Credit Cases', 'orbis-credit-cases' ),
				'parent_item_colon'  => __( 'Parent Credit Case:', 'orbis-credit-cases' ),
				'not_found'          => __( 'No credit cases found.', 'orbis-credit-cases' ),
				'not_found_in_trash' => __( 'No credit cases found in Trash.', 'orbis-credit-cases' ),
			),
			'description'        => __( 'Description.', 'orbis-credit-cases' ),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array(
				'slug'       => 'credit-cases',
				'with_front' => false,
			),
			'menu_icon'          => 'dashicons-category',
			'capability_type'    => 'post',
			'has_archive'        => true,
			'show_in_rest'       => true,
			'rest_base'          => 'orbis/credit-cases',
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array(
				'title',
				'editor',
				'comments',
				'revisions',
				'author',
			),
		) );
	}

	/**
	 * Query vars.
	 *
	 * @param array $query_vars Query vars.
	 * @return array
	 */
	public function query_vars( $query_vars ) {
		$query_vars[] = 'orbis_credit_cases';
		$query_vars[] = 'orbis_credit_case_id';

		return $query_vars;
	}

	/**
	 * Template include.
	 *
	 * @param string $template
	 * @return string
	 */
	public function template_include_credit_cases( $template ) {
		$value = get_query_var( 'orbis_credit_cases', null );

		if ( null === $value ) {
			return $template;
		}

		$template = __DIR__ . '/templates/credit-cases.php';

		return $template;
	}

	/**
	 * Template include.
	 *
	 * @param string $template
	 * @return string
	 */
	public function template_include_credit_case( $template ) {
		$value = get_query_var( 'orbis_credit_case_id', null );

		if ( null === $value ) {
			return $template;
		}

		$template = __DIR__ . '/templates/credit-case.php';

		return $template;
	}

	/**
	 * The content.
	 *
	 * @link https://core.trac.wordpress.org/browser/tags/5.4/src/wp-includes/post-template.php#L253
	 * @link https://developer.wordpress.org/reference/hooks/the_content/
	 */
	public function the_content( $content ) {
		global $wpdb;

		if ( 'orbis_credit_case' !== \get_post_type() ) {
			return $content;
		}

		$post_id = \get_the_ID();

		$credit_case_id = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM orbis_credit_cases WHERE post_id = %d LIMIT 1;', $post_id ) );

		if ( null === $credit_case_id ) {
			return $content;
		}

		$credit_case_url = \home_url( \user_trailingslashit( 'orbis-credit-cases/' . $credit_case_id ) );

		$content .= \sprintf(
			'<p><a href="%s">%s</a></p>',
			\esc_url ( $credit_case_url ),
			\esc_html( $credit_case_url )
		);

		return $content;
	}
}

global $orbis_credit_cases_plugin;

$orbis_credit_cases_plugin = new OrbisCreditCasesPlugin( __FILE__ );

$orbis_credit_cases_plugin->setup();
