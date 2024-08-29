<?php
/**
 * Customize component.
 *
 * Integrates the theme's settings into the customizer.
 *
 * @package   Prismatic
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2024 Benjamin Lu
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://luthemes.com/portfolio/prismatic
 */

namespace Prismatic\Customize;

use Backdrop\Contracts\Bootable;
use Prismatic\Tools\Config;
use Prismatic\Tools\Mod;
use Backdrop\App;

use WP_Customize_Manager;
use WP_Customize_Color_Control;
use WP_Customize_Control;

/**
 * Handles setting up everything we need for the customizer.
 *
 * @link   https://developer.wordpress.org/themes/customize-api
 * @since  1.0.0
 * @access public
 */
class Component implements Bootable {

	/**
	 * Array of `Customizable` components bound to the container.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    array
	 */
	protected $components = [];

	/**
	 * Sets up initial object properties.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  array  $components  Array `Customizable` component names.
	 * @return void
	 */
	public function __construct( array $components = [] ) {

		$this->components = $components;
	}

    /**
     * Adds our customizer-related actions to the appropriate hooks.
     *
     * @since  1.0.0
     * @return void
     *
     * @access public
     */
    public function boot(): void {

		// Register panels, sections, settings, controls, and partials.
		array_map( function( $callback ) {
			add_action( 'customize_register', [ $this, $callback ] );
		}, [
			'registerPanels',
			'registerSections',
			'registerSettings',
			'registerControls'
		] );

		add_action('wp_enqueue_scripts', [$this, 'enqueueCustomizerStyles']);
		add_action('wp_enqueue_scripts', [$this, 'enqueueBackgroundStyles']);
	}

	/**
	 * Callback for registering panels.
	 *
	 * @link   https://developer.wordpress.org/themes/customize-api/customizer-objects/#panels
	 * @since  1.0.0
	 * @access public
	 * @param  WP_Customize_Manager  $manager  Instance of the customize manager.
	 * @return void
	 */
	public function registerPanels( WP_Customize_Manager $manager ) {
		$panels = [
				'theme_global'  => esc_html__( 'Theme: Global',  'prismatic' ),
				'theme_header'  => esc_html__( 'Theme: Header',  'prismatic' ),
				'theme_content' => esc_html__( 'Theme: Content', 'prismatic' ),
				'theme_footer'  => esc_html__( 'Theme: Footer',  'prismatic' )
		];

		foreach ( $panels as $panel => $label ) {
				$manager->add_panel( $panel, [
						'title'    => $label,
						'priority' => 100
				] );
		}

		foreach ( $this->components as $component ) {

			App::resolve( $component )->registerPanels( $manager );
		}
	}

	/**
	 * Callback for registering sections.
	 *
	 * @link   https://developer.wordpress.org/themes/customize-api/customizer-objects/#sections
	 * @since  1.0.0
	 * @access public
	 * @param  WP_Customize_Manager  $manager  Instance of the customize manager.
	 * @return void
	 */
    public function registerSections( WP_Customize_Manager $manager ) {
		$manager->get_section( 'custom_css' )->panel = 'theme_global';
		$manager->get_section( 'title_tagline' )->panel = 'theme_header';
		$manager->get_section( 'static_front_page' )->panel = 'theme_content';


		foreach ( $this->components as $component ) {

			App::resolve( $component )->registerSections( $manager );
		}
    }

	/**
	 * Callback for registering controls.
	 *
	 * @link   https://developer.wordpress.org/themes/customize-api/customizer-objects/#controls
	 * @since  1.0.0
	 * @access public
	 * @param  WP_Customize_Manager  $manager  Instance of the customize manager.
	 * @return void
	 */
    public function registerSettings( WP_Customize_Manager $manager ) {

		foreach ( $this->components as $component ) {

			App::resolve( $component )->registerSettings( $manager );
		}
	}

    /**
     * Add our controls for customizer.
     *
     * @since  1.0.0
     * @access public
     * @param  WP_Customize_Manager $manager
     * @return void
     */
    public function registerControls( WP_Customize_Manager $manager ) {

		foreach ( $this->components as $component ) {

			App::resolve( $component )->registerControls( $manager );
		}
	}

	public function enqueueCustomizerStyles() {
		$background = get_theme_mod( 'theme_header_background_color', '#0b5e79' );
		$custom = "
			.site-header {
				background: {$background};
			}
		";

		wp_add_inline_style( 'prismatic-screen', $custom );

	}

	public function enqueueBackgroundStyles() {
		$bg_type = get_theme_mod('theme_global_background_type', 'none');
		$bg_pattern = get_theme_mod('theme_global_background_pattern', 'none');
		$bg_image = get_background_image();
		$patterns = Config::get('background-patterns');

		$custom_css = '';

		if ($bg_type === 'image' && $bg_image) {
			$custom_css = 'body.custom-background { background-image: url("' . esc_url($bg_image) . '"); background-size: cover; }';
		} elseif ($bg_type === 'pattern' && isset($patterns[$bg_pattern])) {
			$pattern_svg = $patterns[$bg_pattern]['svg'];
			$pattern_svg_base64 = base64_encode($pattern_svg); // phpcs:ignore
			$custom_css = 'body.custom-background {
				background-color: rgba(255, 255, 255, 0.9); /* Lighter background color with opacity */
				background-image: url("data:image/svg+xml;base64,' . $pattern_svg_base64 . '");
				background-repeat: repeat;
				background-blend-mode: lighten; /* Blend the pattern with the lighter background color */
			}';
		} elseif ($bg_type === 'none') {
			$custom_css = 'body.custom-background { background-image: none; }';
		}

		if (!empty($custom_css)) {
			wp_add_inline_style('prismatic-screen', $custom_css);
		}
	}
}
