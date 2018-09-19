<?php

/**
 * Enqueue block editor only JavaScript and CSS
 */
function learndash_editor_scripts() {
	// Make paths variables so we don't write em twice ;).
	$blockPath = '../assets/js/editor.blocks.js';
	$editorStylePath = '../assets/css/blocks.editor.css';

	// Enqueue the bundled block JS file.
	wp_enqueue_script(
		'ldlms-blocks-js',
		plugins_url( $blockPath, __FILE__ ),
		[ 'wp-i18n', 'wp-element', 'wp-blocks', 'wp-components' ],
		filemtime( plugin_dir_path(__FILE__) . $blockPath )
	);

	/**
	 * @TODO: This needs to move to an external JS library since it will be used globally.
	 */
	$ldlms = array(
		'settings' => array(),
	);
	$ldlms_settings['settings']['custom_labels'] = get_option( 'learndash_settings_custom_labels' );
	foreach ( $ldlms_settings['settings']['custom_labels'] as $key => $val ) {
		if ( empty( $val ) ) {
			$ldlms_settings['settings']['custom_labels'][ $key ] = LearnDash_Custom_Label::get_label( $key );
			if ( substr( $key, 0, strlen( 'button') ) != 'button' ) {
				$ldlms_settings['settings']['custom_labels'][ $key . '_lower' ] = LearnDash_Custom_Label::label_to_lower( $key );
				$ldlms_settings['settings']['custom_labels'][ $key . '_slug' ] = LearnDash_Custom_Label::label_to_slug( $key );
			}
		}
	}

	$ldlms_settings['settings']['per_page'] = get_option( 'learndash_settings_per_page' );
	$ldlms_settings['settings']['courses_taxonomies'] = get_option( 'learndash_settings_courses_taxonomies' );
	$ldlms_settings['settings']['lessons_taxonomies'] = get_option( 'learndash_settings_lessons_taxonomies' );
	$ldlms_settings['settings']['topics_taxonomies'] = get_option( 'learndash_settings_topics_taxonomies' );

	//$ldlms_settings['settings']['quizzes_taxonomies'] = get_option( 'learndash_settings_quizzes_taxonomies' );

	$ldlms_settings['settings']['quizzes_taxonomies'] = array();
	$object_taxonomies = get_object_taxonomies( 'sfwd-quiz' );

	if ( ( !empty( $object_taxonomies ) ) && ( is_array( $object_taxonomies ) ) ) {
		if ( in_array( 'category', $object_taxonomies ) ) {
			$ldlms_settings['settings']['quizzes_taxonomies']['wp_post_category'] = 'yes';
		}
		if ( in_array( 'post_tag', $object_taxonomies ) ) {
			$ldlms_settings['settings']['quizzes_taxonomies']['wp_post_tag'] = 'yes';
		}
	}

	$ldlms_settings['plugins']['learndash-course-grid'] = array();
	$ldlms_settings['plugins']['learndash-course-grid']['enabled'] = learndash_enqueue_course_grid_scripts();
	$ldlms_settings['plugins']['learndash-course-grid']['col_default'] = 3;
	$ldlms_settings['plugins']['learndash-course-grid']['col_max'] = 12;

	if ( true === $ldlms_settings['plugins']['learndash-course-grid']['enabled'] ) {
		if ( defined( 'LEARNDASH_COURSE_GRID_COLUMNS' ) ) {
			$col_default = intval( LEARNDASH_COURSE_GRID_COLUMNS );
			if ( ( ! empty( $col_default ) ) && ( $col_default > 0 ) ) {
				$ldlms_settings['plugins']['learndash-course-grid']['col_default'] = $col_default;
			}
		}

		if ( defined( 'LEARNDASH_COURSE_GRID_MAX_COLUMNS' ) ) {
			$col_max = intval( LEARNDASH_COURSE_GRID_MAX_COLUMNS );
			if ( ( ! empty( $col_max ) ) && ( $col_max > 0 ) ) {
				$ldlms_settings['plugins']['learndash-course-grid']['col_max'] = $col_max;
			}
		}
	}

	$ldlms_settings['meta'] = array();
	$ldlms_settings['meta']['posts_per_page'] = get_option( 'posts_per_page' );
	if ( is_admin() ) {
		$current_screen = get_current_screen();
		if ( 'post' === $current_screen->base ) {

			global $post, $post_type, $editing;
			$ldlms_settings['meta']['post'] = array();

			$ldlms_settings['meta']['post']['post_id'] = $post->ID;
			$ldlms_settings['meta']['post']['post_type'] = $post_type;
			$ldlms_settings['meta']['post']['editing'] = $editing;

			$ldlms_settings['meta']['post']['course_id'] = 0;

			if ( ! empty( $post_type ) ) {
				$course_post_types = array( 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' );

				$course_id = 0;
				if ( 'sfwd-courses' === $post_type ) {
					$course_id = $post->ID;
				} else if ( in_array( $post_type, $course_post_types ) ) {
					$course_id = learndash_get_course_id();
				}
				$ldlms_settings['meta']['post']['course_id'] = $course_id;
			}
		}
	}

	if ( function_exists( 'gutenberg_get_jed_locale_data' ) ) {
		$locale = gutenberg_get_jed_locale_data( 'learndash' );
		$ldlms_settings['locale'] = $locale;
	}
	
	//error_log('ldlms_settings<pre>'. print_r($ldlms_settings, true) .'</pre>');
	wp_localize_script( 'ldlms-blocks-js', 'ldlms_settings', $ldlms_settings );

	// Enqueue optional editor only styles.
	wp_enqueue_style(
		'ldlms-blocks-editor-css',
		plugins_url( $editorStylePath, __FILE__ ),
		[ 'wp-blocks' ],
		filemtime( plugin_dir_path( __FILE__ ) . $editorStylePath )
	);

	// Call our function to load CSS/JS used by the shortcodes.
	learndash_load_resources();

	$filepath = SFWD_LMS::get_template( 'learndash_pager.css', null, null, true );
	if ( ! empty( $filepath ) ) {
		wp_enqueue_style( 'learndash_pager_css', learndash_template_url_from_path( $filepath ), array(), LEARNDASH_SCRIPT_VERSION_TOKEN );
		$learndash_assets_loaded['styles']['learndash_pager_css'] = __FUNCTION__;
	} 

	$filepath = SFWD_LMS::get_template( 'learndash_pager.js', null, null, true );
	if ( !empty( $filepath ) ) {
		wp_enqueue_script( 'learndash_pager_js', learndash_template_url_from_path( $filepath ), array( 'jquery' ), LEARNDASH_SCRIPT_VERSION_TOKEN, true );
		$learndash_assets_loaded['scripts']['learndash_pager_js'] = __FUNCTION__;
	}
}
// Hook scripts function into block editor hook.
add_action( 'enqueue_block_editor_assets', 'learndash_editor_scripts' );

/**
 * Enqueue front end and editor JavaScript and CSS
 */
function learndash_scripts() {
	// Make paths variables so we don't write em twice ;)
	$blockPath = '../assets/js/frontend.blocks.js';
	$stylePath = '../assets/css/blocks.style.css';

	if ( ! is_admin() ) {
		// Enqueue the bundled block JS file.
		wp_enqueue_script(
			'ldlms-blocks-frontend',
			plugins_url( $blockPath, __FILE__ ),
			[],
			filemtime( plugin_dir_path(__FILE__) . $blockPath )
		);
	}

	// Enqueue frontend and editor block styles
	wp_enqueue_style(
		'learndash-blocks',
		plugins_url($stylePath, __FILE__),
		[ 'wp-blocks' ],
		filemtime(plugin_dir_path(__FILE__) . $stylePath )
	);
}

// Hook scripts function into block editor hook.
add_action( 'enqueue_block_assets', 'learndash_scripts' );

/**
 * Custom function to enqueue needed CSS/JS for Course Grid.
 *
 * @since 2.5.9
 *
 * @return boolean true is resources loaded. false is not loaded.
 */
function learndash_enqueue_course_grid_scripts() {

	// Check if Course Grid add-on is installed.
	if ( ( defined( 'LEARNDASH_COURSE_GRID_FILE' ) ) && ( file_exists( LEARNDASH_COURSE_GRID_FILE ) ) ) {
		// Newer versions of Coure Grid have a function to load resources.
		if ( function_exists( 'learndash_course_grid_load_resources' ) ) {
			learndash_course_grid_load_resources();
		} else {
			// Handle older versions of Course Grid. 1.4.1 and lower.
			wp_enqueue_style( 'learndash_course_grid_css', plugins_url( 'style.css', LEARNDASH_COURSE_GRID_FILE ) );
			wp_enqueue_script( 'learndash_course_grid_js', plugins_url( 'script.js', LEARNDASH_COURSE_GRID_FILE ), array( 'jquery' ) );
			wp_enqueue_style( 'ld-cga-bootstrap', plugins_url( 'bootstrap.min.css', LEARNDASH_COURSE_GRID_FILE ) );
		}

		return true;
	}

	return false;
}