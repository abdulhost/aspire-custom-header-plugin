<?php
/*
Plugin Name: AspireDev Header Menu
Plugin URI: https://aspiredev.com
Description: A WordPress plugin to create a header with a dynamic, multi-level navigation menu using a shortcode, with an enhanced admin panel for customization. Developed by AspireDev.
Version: 2.1.3
Author: AspireDev
Author URI: https://aspiredev.com
License: GPL2
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue scripts and styles
function aspiredev_header_menu_enqueue_scripts() {
    wp_enqueue_script('jquery');
}
add_action('wp_enqueue_scripts', 'aspiredev_header_menu_enqueue_scripts');
add_action('admin_enqueue_scripts', 'aspiredev_header_menu_enqueue_scripts');

// Admin menu
function aspiredev_header_menu_admin_menu() {
    add_menu_page(
        'AspireDev Header Menu',
        'Header Menu',
        'manage_options',
        'aspiredev-header-menu',
        'aspiredev_header_menu_settings_page',
        'dashicons-menu',
        20
    );
}
add_action('admin_menu', 'aspiredev_header_menu_admin_menu');

// Register settings
function aspiredev_header_menu_register_settings() {
    register_setting('aspiredev_header_menu_options', 'aspiredev_header_menu_settings');

    add_settings_section(
        'aspiredev_header_menu_main_section',
        'Header Menu Settings',
        'aspiredev_header_menu_section_callback',
        'aspiredev-header-menu'
    );

    add_settings_field(
        'aspiredev_header_menu_color_scheme',
        'Color Scheme',
        'aspiredev_header_menu_color_scheme_callback',
        'aspiredev-header-menu',
        'aspiredev_header_menu_main_section'
    );

    add_settings_field(
        'aspiredev_header_menu_custom_colors',
        'Custom Colors & Theme Integration',
        'aspiredev_header_menu_custom_colors_callback',
        'aspiredev-header-menu',
        'aspiredev_header_menu_main_section'
    );

    add_settings_field(
        'aspiredev_header_menu_padding',
        'Header Padding (px)',
        'aspiredev_header_menu_padding_callback',
        'aspiredev-header-menu',
        'aspiredev_header_menu_main_section'
    );

    add_settings_field(
        'aspiredev_header_menu_font_size',
        'Font Size (px)',
        'aspiredev_header_menu_font_size_callback',
        'aspiredev-header-menu',
        'aspiredev_header_menu_main_section'
    );

    add_settings_field(
        'aspiredev_header_menu_border_radius',
        'Border Radius (px)',
        'aspiredev_header_menu_border_radius_callback',
        'aspiredev-header-menu',
        'aspiredev_header_menu_main_section'
    );

    add_settings_field(
        'aspiredev_header_menu_shadow_intensity',
        'Shadow Intensity (0-10)',
        'aspiredev_header_menu_shadow_intensity_callback',
        'aspiredev-header-menu',
        'aspiredev_header_menu_main_section'
    );

    add_settings_field(
        'aspiredev_header_menu_transition_speed',
        'Transition Speed (s)',
        'aspiredev_header_menu_transition_speed_callback',
        'aspiredev-header-menu',
        'aspiredev_header_menu_main_section'
    );

    add_settings_field(
        'aspiredev_header_menu_item_spacing',
        'Menu Item Spacing (px)',
        'aspiredev_header_menu_item_spacing_callback',
        'aspiredev-header-menu',
        'aspiredev_header_menu_main_section'
    );

    add_settings_field(
        'aspiredev_header_menu_submenu_width',
        'Submenu Width (px)',
        'aspiredev_header_menu_submenu_width_callback',
        'aspiredev-header-menu',
        'aspiredev_header_menu_main_section'
    );
}
add_action('admin_init', 'aspiredev_header_menu_register_settings');

// Fetch theme colors
function aspiredev_header_menu_get_theme_colors() {
    $theme_colors = [];

    // Elementor colors
    if (did_action('elementor/loaded') && class_exists('\Elementor\Plugin')) {
        // Try fetching from active kit (Elementor >= 3.0)
        $kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
        if ($kit) {
            $system_colors = $kit->get_settings('system_colors');
            $custom_colors = $kit->get_settings('custom_colors');
            if (is_array($system_colors)) {
                foreach ($system_colors as $color) {
                    if (isset($color['_id'], $color['color']) && preg_match('/^#[0-9a-fA-F]{6}$/', $color['color'])) {
                        $theme_colors["elementor-system-{$color['_id']}"] = $color['color'];
                    }
                }
            }
            if (is_array($custom_colors)) {
                foreach ($custom_colors as $color) {
                    if (isset($color['_id'], $color['color']) && preg_match('/^#[0-9a-fA-F]{6}$/', $color['color'])) {
                        $theme_colors["elementor-custom-{$color['_id']}"] = $color['color'];
                    }
                }
            }
        }
        // Fallback to schemes_manager (older Elementor versions)
        if (empty($theme_colors) && \Elementor\Plugin::$instance->schemes_manager) {
            $color_scheme = \Elementor\Plugin::$instance->schemes_manager->get_scheme('color');
            if ($color_scheme) {
                $colors = $color_scheme->get_scheme_value();
                foreach ($colors as $key => $color) {
                    if (preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                        $theme_colors["elementor-{$key}"] = $color;
                    }
                }
            }
        }
    }

    // Astra colors
    if (function_exists('astra_get_option')) {
        $astra_palette = astra_get_option('global-color-palette');
        if (isset($astra_palette['palette']) && is_array($astra_palette['palette'])) {
            foreach ($astra_palette['palette'] as $index => $color) {
                if (preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                    $theme_colors["astra-color-$index"] = $color;
                }
            }
        }
        $astra_globals = [
            'theme-color' => 'primary',
            'link-color' => 'link',
            'button-bg-color' => 'button-bg',
            'header-bg-color' => 'header-bg',
            'footer-bg-color' => 'footer-bg',
            'text-color' => 'text',
        ];
        foreach ($astra_globals as $option => $key) {
            $color = astra_get_option($option);
            if ($color && preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                $theme_colors["astra-$key"] = $color;
            }
        }
    }

    // Fallback colors
    if (empty($theme_colors)) {
        $theme_colors = [
            'primary' => '#0073aa',
            'secondary' => '#00a32a',
            'accent' => '#ff5733',
        ];
    }

    return array_unique($theme_colors);
}

// Section callback with instructions and about
function aspiredev_header_menu_section_callback() {
    echo '<p>Customize your header menu below. Use the shortcode <code>[header menu="your-menu-slug"]</code> in pages or posts, replacing "your-menu-slug" with the slug of your WordPress navigation menu (e.g., "main-menu"). Ensure the menu is created in Appearance > Menus.</p>';
    echo '<div class="aspiredev-about">';
    echo '<h3>About Me</h3>';
    echo '<p>Developed by AspireDev - Your trusted partner in web development.</p>';
    echo '<p><strong>Support Email:</strong> <a href="mailto:aspiredevlab@gmail.com">aspiredevlab@gmail.com</a></p>';
    echo '</div>';
}

// Settings page callback
function aspiredev_header_menu_settings_page() {
    ?>
    <div class="wrap">
        <h1>AspireDev Header Menu Settings</h1>
        <form method="post" action="options.php" id="aspiredev-header-menu-form">
            <?php
            settings_fields('aspiredev_header_menu_options');
            do_settings_sections('aspiredev-header-menu');
            submit_button('Save Changes');
            ?>
            <button type="button" id="aspiredev-reset-defaults" class="button">Reset to Defaults</button>
        </form>
        <script>
            jQuery(document).ready(function($) {
                // Color preview updates
                $('input[type="color"]').on('input', function() {
                    var id = $(this).attr('id');
                    var color = $(this).val();
                    $('#preview_' + id).css('background', color);
                });

                // Theme color selection
                $('.aspiredev-theme-color-select').on('change', function() {
                    var color = $(this).val();
                    var targetId = $(this).data('target');
                    $('#' + targetId).val(color).trigger('input');
                });

                // Reset to defaults
                $('#aspiredev-reset-defaults').on('click', function() {
                    if (confirm('Are you sure you want to reset all settings to default?')) {
                        $.post(ajaxurl, {
                            action: 'aspiredev_reset_defaults',
                            nonce: '<?php echo wp_create_nonce('aspiredev_reset_nonce'); ?>'
                        }, function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert('Error resetting defaults.');
                            }
                        });
                    }
                });
            });
        </script>
        <style>
            .aspiredev-color-section {
                margin-bottom: 20px;
            }
            .aspiredev-color-section h4 {
                margin-top: 15px;
                font-size: 16px;
                font-weight: 600;
            }
            .color-preview {
                display: inline-block;
                width: 20px;
                height: 20px;
                margin-left: 10px;
                border: 1px solid #ccc;
                vertical-align: middle;
            }
            .aspiredev-theme-color-select {
                margin-right: 10px;
            }
            .aspiredev-about {
                margin-top: 20px;
                padding: 15px;
                background: #f9f9f9;
                border-left: 4px solid #0073aa;
                border-radius: 4px;
            }
            .aspiredev-about h3 {
                margin-top: 0;
                font-size: 18px;
            }
        </style>
    </div>
    <?php
}

// Color scheme callback
function aspiredev_header_menu_color_scheme_callback() {
    $options = get_option('aspiredev_header_menu_settings');
    $color_scheme = $options['color_scheme'] ?? 'default';
    $schemes = [
        'default' => 'Default (Dark Blue Gradient)',
        'light' => 'Light Theme',
        'dark' => 'Dark Theme',
        'custom' => 'Custom'
    ];
    ?>
    <select name="aspiredev_header_menu_settings[color_scheme]" id="aspiredev_header_menu_color_scheme">
        <?php foreach ($schemes as $key => $label) : ?>
            <option value="<?php echo esc_attr($key); ?>" <?php selected($color_scheme, $key); ?>>
                <?php echo esc_html($label); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description">Choose a pre-defined color scheme or select 'Custom' to use custom colors below.</p>
    <?php
}

// Custom colors callback with improved global color handling
function aspiredev_header_menu_custom_colors_callback() {
    $options = get_option('aspiredev_header_menu_settings');
    $header_bg = $options['header_bg'] ?? '#34495e';
    $header_gradient = $options['header_gradient'] ?? '#2c3e50';
    $menu_text = $options['menu_text'] ?? '#ecf0f1';
    $menu_hover = $options['menu_hover'] ?? '#2980b9';
    $submenu_bg = $options['submenu_bg'] ?? '#2c3e50';
    $submenu_hover = $options['submenu_hover'] ?? '#3498db';

    // Get theme colors using the new function
    $theme_colors = aspiredev_header_menu_get_theme_colors();

    ?>
    <div class="aspiredev-color-section">
        <h4>Theme Colors</h4>
        <label>Header Background: 
            <select class="aspiredev-theme-color-select" name="aspiredev_header_menu_settings[theme_color_header_bg]" data-target="aspiredev_header_bg">
                <option value="">Select Theme Color</option>
                <?php foreach ($theme_colors as $key => $color) : ?>
                    <option value="<?php echo esc_attr($color); ?>" <?php selected($options['theme_color_header_bg'] ?? '', $color); ?>>
                        <?php echo esc_html($key . ' (' . $color . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label><br>
        <label>Header Gradient: 
            <select class="aspiredev-theme-color-select" name="aspiredev_header_menu_settings[theme_color_header_gradient]" data-target="aspiredev_header_gradient">
                <option value="">Select Theme Color</option>
                <?php foreach ($theme_colors as $key => $color) : ?>
                    <option value="<?php echo esc_attr($color); ?>" <?php selected($options['theme_color_header_gradient'] ?? '', $color); ?>>
                        <?php echo esc_html($key . ' (' . $color . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label><br>
        <label>Menu Text: 
            <select class="aspiredev-theme-color-select" name="aspiredev_header_menu_settings[theme_color_menu_text]" data-target="aspiredev_menu_text">
                <option value="">Select Theme Color</option>
                <?php foreach ($theme_colors as $key => $color) : ?>
                    <option value="<?php echo esc_attr($color); ?>" <?php selected($options['theme_color_menu_text'] ?? '', $color); ?>>
                        <?php echo esc_html($key . ' (' . $color . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label><br>
        <label>Menu Hover: 
            <select class="aspiredev-theme-color-select" name="aspiredev_header_menu_settings[theme_color_menu_hover]" data-target="aspiredev_menu_hover">
                <option value="">Select Theme Color</option>
                <?php foreach ($theme_colors as $key => $color) : ?>
                    <option value="<?php echo esc_attr($color); ?>" <?php selected($options['theme_color_menu_hover'] ?? '', $color); ?>>
                        <?php echo esc_html($key . ' (' . $color . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label><br>
        <label>Submenu Background: 
            <select class="aspiredev-theme-color-select" name="aspiredev_header_menu_settings[theme_color_submenu_bg]" data-target="aspiredev_submenu_bg">
                <option value="">Select Theme Color</option>
                <?php foreach ($theme_colors as $key => $color) : ?>
                    <option value="<?php echo esc_attr($color); ?>" <?php selected($options['theme_color_submenu_bg'] ?? '', $color); ?>>
                        <?php echo esc_html($key . ' (' . $color . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label><br>
        <label>Submenu Hover: 
            <select class="aspiredev-theme-color-select" name="aspiredev_header_menu_settings[theme_color_submenu_hover]" data-target="aspiredev_submenu_hover">
                <option value="">Select Theme Color</option>
                <?php foreach ($theme_colors as $key => $color) : ?>
                    <option value="<?php echo esc_attr($color); ?>" <?php selected($options['theme_color_submenu_hover'] ?? '', $color); ?>>
                        <?php echo esc_html($key . ' (' . $color . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label><br>

        <h4>Custom Colors</h4>
        <label>Header Background: <input type="color" name="aspiredev_header_menu_settings[header_bg]" value="<?php echo esc_attr($header_bg); ?>" id="aspiredev_header_bg"><span class="color-preview" id="preview_header_bg" style="background: <?php echo esc_attr($header_bg); ?>;"></span></label><br>
        <label>Header Gradient: <input type="color" name="aspiredev_header_menu_settings[header_gradient]" value="<?php echo esc_attr($header_gradient); ?>" id="aspiredev_header_gradient"><span class="color-preview" id="preview_header_gradient" style="background: <?php echo esc_attr($header_gradient); ?>;"></span></label><br>
        <label>Menu Text: <input type="color" name="aspiredev_header_menu_settings[menu_text]" value="<?php echo esc_attr($menu_text); ?>" id="aspiredev_menu_text"><span class="color-preview" id="preview_menu_text" style="background: <?php echo esc_attr($menu_text); ?>;"></span></label><br>
        <label>Menu Hover: <input type="color" name="aspiredev_header_menu_settings[menu_hover]" value="<?php echo esc_attr($menu_hover); ?>" id="aspiredev_menu_hover"><span class="color-preview" id="preview_menu_hover" style="background: <?php echo esc_attr($menu_hover); ?>;"></span></label><br>
        <label>Submenu Background: <input type="color" name="aspiredev_header_menu_settings[submenu_bg]" value="<?php echo esc_attr($submenu_bg); ?>" id="aspiredev_submenu_bg"><span class="color-preview" id="preview_submenu_bg" style="background: <?php echo esc_attr($submenu_bg); ?>;"></span></label><br>
        <label>Submenu Hover: <input type="color" name="aspiredev_header_menu_settings[submenu_hover]" value="<?php echo esc_attr($submenu_hover); ?>" id="aspiredev_submenu_hover"><span class="color-preview" id="preview_submenu_hover" style="background: <?php echo esc_attr($submenu_hover); ?>;"></span></label>
    </div>
    <p class="description">Select theme colors from Elementor or Astra, or customize with color pickers. Previews update live.</p>
    <?php
}

// Padding callback
function aspiredev_header_menu_padding_callback() {
    $options = get_option('aspiredev_header_menu_settings');
    $padding = $options['padding'] ?? '10';
    ?>
    <input type="number" name="aspiredev_header_menu_settings[padding]" value="<?php echo esc_attr($padding); ?>" min="0" max="50">
    <p class="description">Set the header padding in pixels (0-50).</p>
    <?php
}

// Font size callback
function aspiredev_header_menu_font_size_callback() {
    $options = get_option('aspiredev_header_menu_settings');
    $font_size = $options['font_size'] ?? '16';
    ?>
    <input type="number" name="aspiredev_header_menu_settings[font_size]" value="<?php echo esc_attr($font_size); ?>" min="12" max="24">
    <p class="description">Set the font size in pixels (12-24).</p>
    <?php
}

// Border radius callback
function aspiredev_header_menu_border_radius_callback() {
    $options = get_option('aspiredev_header_menu_settings');
    $border_radius = $options['border_radius'] ?? '6';
    ?>
    <input type="number" name="aspiredev_header_menu_settings[border_radius]" value="<?php echo esc_attr($border_radius); ?>" min="0" max="20">
    <p class="description">Set the border radius in pixels (0-20).</p>
    <?php
}

// Shadow intensity callback
function aspiredev_header_menu_shadow_intensity_callback() {
    $options = get_option('aspiredev_header_menu_settings');
    $shadow_intensity = $options['shadow_intensity'] ?? '3';
    ?>
    <input type="number" name="aspiredev_header_menu_settings[shadow_intensity]" value="<?php echo esc_attr($shadow_intensity); ?>" min="0" max="10">
    <p class="description">Set the shadow intensity (0-10).</p>
    <?php
}

// Transition speed callback
function aspiredev_header_menu_transition_speed_callback() {
    $options = get_option('aspiredev_header_menu_settings');
    $transition_speed = $options['transition_speed'] ?? '0.3';
    ?>
    <input type="number" step="0.1" name="aspiredev_header_menu_settings[transition_speed]" value="<?php echo esc_attr($transition_speed); ?>" min="0.1" max="1">
    <p class="description">Set the transition speed in seconds (0.1-1).</p>
    <?php
}

// Menu item spacing callback
function aspiredev_header_menu_item_spacing_callback() {
    $options = get_option('aspiredev_header_menu_settings');
    $item_spacing = $options['item_spacing'] ?? '20';
    ?>
    <input type="number" name="aspiredev_header_menu_settings[item_spacing]" value="<?php echo esc_attr($item_spacing); ?>" min="0" max="50">
    <p class="description">Set the spacing between menu items in pixels (0-50).</p>
    <?php
}

// Submenu width callback
function aspiredev_header_menu_submenu_width_callback() {
    $options = get_option('aspiredev_header_menu_settings');
    $submenu_width = $options['submenu_width'] ?? '600';
    ?>
    <input type="number" name="aspiredev_header_menu_settings[submenu_width]" value="<?php echo esc_attr($submenu_width); ?>" min="300" max="1200">
    <p class="description">Set the submenu width in pixels (300-1200).</p>
    <?php
}

// Create the header shortcode
function aspiredev_header_shortcode($atts) {
    $options = get_option('aspiredev_header_menu_settings');
    $color_scheme = $options['color_scheme'] ?? 'default';
    $header_bg = $options['header_bg'] ?? '#34495e';
    $header_gradient = $options['header_gradient'] ?? '#2c3e50';
    $menu_text = $options['menu_text'] ?? '#ecf0f1';
    $menu_hover = $options['menu_hover'] ?? '#2980b9';
    $submenu_bg = $options['submenu_bg'] ?? '#2c3e50';
    $submenu_hover = $options['submenu_hover'] ?? '#3498db';
    $padding = $options['padding'] ?? '10';
    $font_size = $options['font_size'] ?? '16';
    $border_radius = $options['border_radius'] ?? '6';
    $shadow_intensity = $options['shadow_intensity'] ?? '3';
    $transition_speed = $options['transition_speed'] ?? '0.3';
    $item_spacing = $options['item_spacing'] ?? '20';
    $submenu_width = $options['submenu_width'] ?? '600';

    // Handle theme color overrides if selected
    $theme_colors = aspiredev_header_menu_get_theme_colors();

    foreach (['header_bg', 'header_gradient', 'menu_text', 'menu_hover', 'submenu_bg', 'submenu_hover'] as $field) {
        $theme_key = 'theme_color_' . $field;
        if (!empty($options[$theme_key]) && isset($theme_colors[array_search($options[$theme_key], $theme_colors)])) {
            $$field = $options[$theme_key];
        }
    }

    // Default color schemes
    $color_schemes = [
        'default' => [
            'header_bg' => '#34495e',
            'header_gradient' => '#2c3e50',
            'menu_text' => '#ecf0f1',
            'menu_hover' => '#2980b9',
            'submenu_bg' => '#2c3e50',
            'submenu_hover' => '#3498db'
        ],
        'light' => [
            'header_bg' => '#f0f4f8',
            'header_gradient' => '#e0e7f0',
            'menu_text' => '#2c3e50',
            'menu_hover' => '#3498db',
            'submenu_bg' => '#ffffff',
            'submenu_hover' => '#2980b9'
        ],
        'dark' => [
            'header_bg' => '#1a252f',
            'header_gradient' => '#0f161b',
            'menu_text' => '#d3d8de',
            'menu_hover' => '#4a90e2',
            'submenu_bg' => '#1a252f',
            'submenu_hover' => '#4a90e2'
        ]
    ];

    if ($color_scheme !== 'custom') {
        $colors = $color_schemes[$color_scheme];
        $header_bg = $colors['header_bg'];
        $header_gradient = $colors['header_gradient'];
        $menu_text = $colors['menu_text'];
        $menu_hover = $colors['menu_hover'];
        $submenu_bg = $colors['submenu_bg'];
        $submenu_hover = $colors['submenu_hover'];
    }

    // Sanitize menu slug
    $atts = shortcode_atts(
        array(
            'menu' => 'main-menu',
        ),
        $atts,
        'header'
    );
    $menu_slug = sanitize_text_field($atts['menu']);

    // Get the menu
    $menu_items = wp_get_nav_menu_items($menu_slug);

    if (!$menu_items) {
        return '<div class="aspiredev-header">No menu found for slug: ' . esc_html($menu_slug) . '</div>';
    }

    // Organize menu items into a hierarchical structure
    $menu_tree = array();
    $menu_items_by_id = array();

    foreach ($menu_items as $item) {
        $menu_items_by_id[$item->ID] = $item;
        $item->children = array();
    }

    foreach ($menu_items as $item) {
        if ($item->menu_item_parent && isset($menu_items_by_id[$item->menu_item_parent])) {
            $menu_items_by_id[$item->menu_item_parent]->children[] = $item;
        } else {
            $menu_tree[] = $item;
        }
    }

    // Generate menu HTML with embedded CSS and data attributes
    ob_start();
    ?>
    <header class="aspiredev-header" style="padding: <?php echo esc_attr($padding); ?>px 0;">
        <style>
            .aspiredev-header {
                background: linear-gradient(90deg, <?php echo esc_attr($header_bg); ?>, <?php echo esc_attr($header_gradient); ?>);
                box-shadow: 0 <?php echo esc_attr($shadow_intensity * 2); ?>px <?php echo esc_attr($shadow_intensity * 4); ?>px rgba(0, 0, 0, 0.2);
                position: relative;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                width: 100%;
                box-sizing: border-box;
            }

            .aspiredev-nav {
                max-width: 1200px;
                margin: 0 auto;
                position: relative;
                padding: 0 15px;
            }

            .menu-toggle {
                display: none;
                font-size: 24px;
                color: <?php echo esc_attr($menu_text); ?>;
                background: none;
                border: none;
                cursor: pointer;
                padding: 10px;
            }

            .main-menu {
                list-style: none;
                margin: 0;
                padding: 0;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100%;
                flex-wrap: wrap;
            }

            .menu-item {
                position: relative;
                margin: 0 <?php echo esc_attr($item_spacing); ?>px;
                display: flex;
                align-items: center;
            }

            .menu-item a {
                color: <?php echo esc_attr($menu_text); ?>;
                text-decoration: none;
                font-size: <?php echo esc_attr($font_size); ?>px;
                font-weight: 500;
                padding: 10px 18px;
                display: block;
                transition: color <?php echo esc_attr($transition_speed); ?>s ease;
                border-radius: <?php echo esc_attr($border_radius); ?>px;
                position: relative;
            }

            .menu-item a::before {
                content: '';
                position: absolute;
                bottom: 0;
                left: 50%;
                width: 0;
                height: 2px;
                background: <?php echo esc_attr($menu_hover); ?>;
                transition: width <?php echo esc_attr($transition_speed); ?>s ease;
                transform: translateX(-50%);
            }

            .menu-item a:hover::before {
                width: 85%;
            }

            .menu-item a:hover {
                color: #ffffff;
            }

            .has-submenu > a::after {
                content: "▼";
                font-size: 10px;
                margin-left: 8px;
                vertical-align: middle;
                transition: transform <?php echo esc_attr($transition_speed); ?>s ease;
            }

            .submenu-item-level1 > a::after {
                content: "▼";
                font-size: 10px;
                margin-left: 8px;
                vertical-align: middle;
                transition: transform <?php echo esc_attr($transition_speed); ?>s ease;
            }

            .has-submenu:hover > a::after {
                transform: rotate(180deg);
            }

            .submenu.level-1 {
                list-style: none;
                margin: 0;
                padding: 0;
                background: <?php echo esc_attr($submenu_bg); ?>;
                border-radius: <?php echo esc_attr($border_radius); ?>px;
                box-shadow: 0 <?php echo esc_attr($shadow_intensity * 2); ?>px <?php echo esc_attr($shadow_intensity * 4); ?>px rgba(0, 0, 0, 0.3);
                position: absolute;
                top: 100%;
                left: 0;
                display: flex;
                flex-direction: row;
                gap: 8px;
                padding: 12px 0;
                min-width: <?php echo esc_attr($submenu_width); ?>px;
                visibility: hidden;
                opacity: 0;
                transition: opacity <?php echo esc_attr($transition_speed); ?>s ease, visibility 0s linear <?php echo esc_attr($transition_speed); ?>s;
                z-index: 1000;
            }

            .submenu.level-2 {
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background: <?php echo esc_attr($submenu_bg); ?>;
                border-radius: <?php echo esc_attr($border_radius); ?>px;
                padding: 15px;
                box-sizing: border-box;
                visibility: hidden;
                opacity: 0;
                transition: opacity <?php echo esc_attr($transition_speed); ?>s ease, visibility 0s linear <?php echo esc_attr($transition_speed); ?>s;
                z-index: 999;
                box-shadow: 0 <?php echo esc_attr($shadow_intensity); ?>px <?php echo esc_attr($shadow_intensity * 3); ?>px rgba(0, 0, 0, 0.2);
            }

            .submenu.level-2 .submenu-content {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                gap: 15px;
                max-height: 600px;
            }

            .submenu.level-2 .submenu-item {
                flex: 0 0 120px;
            }

            .submenu.level-2 .submenu-item a {
                color: <?php echo esc_attr($menu_text); ?>;
                padding: 10px 15px;
                font-size: <?php echo esc_attr($font_size - 1); ?>px;
                font-weight: 400;
                display: block;
                border-radius: <?php echo esc_attr($border_radius); ?>px;
                transition: color <?php echo esc_attr($transition_speed); ?>s ease;
                position: relative;
            }

            .submenu.level-2 .submenu-item a::before {
                content: '';
                position: absolute;
                bottom: 0;
                left: 50%;
                width: 0;
                height: 2px;
                background: <?php echo esc_attr($submenu_hover); ?>;
                transition: width <?php echo esc_attr($transition_speed); ?>s ease;
                transform: translateX(-50%);
            }

            .submenu.level-2 .submenu-item a:hover::before {
                width: 85%;
            }

            .submenu.level-2 .submenu-item a:hover {
                color: #ffffff;
            }

            .menu-item.has-submenu:hover > .submenu.level-1,
            .menu-item.has-submenu:hover .submenu.level-2 {
                visibility: visible;
                opacity: 1;
                transition-delay: 0s;
            }

            /* Responsive Design */
            @media (max-width: 991px) {
                .menu-toggle {
                    display: block;
                }

                .main-menu {
                    display: none;
                    flex-direction: column;
                    width: 100%;
                    position: absolute;
                    top: 100%;
                    left: 0;
                    background: <?php echo esc_attr($header_bg); ?>;
                    padding: 10px 0;
                    z-index: 1000;
                }

                .main-menu.active {
                    display: flex;
                }

                .menu-item {
                    margin: 8px 0;
                    width: 100%;
                    text-align: center;
                }

                .menu-item a {
                    padding: 12px 20px;
                    font-size: <?php echo esc_attr($font_size); ?>px;
                }

                .menu-item a::before {
                    display: none; /* Disable hover line effect on mobile */
                }

                .submenu.level-1 {
                    position: static;
                    width: 100%;
                    min-width: auto;
                    flex-direction: column;
                    padding: 0;
                    background: <?php echo esc_attr($submenu_bg); ?>;
                    box-shadow: none;
                    visibility: visible;
                    opacity: 1;
                    display: none;
                }

                .submenu.level-1.active {
                    display: flex;
                }

                .submenu.level-2 {
                    position: static;
                    width: 100%;
                    padding: 0;
                    background: <?php echo esc_attr($submenu_bg); ?>;
                    box-shadow: none;
                    visibility: visible;
                    opacity: 1;
                    display: none;
                }

                .submenu.level-2.active {
                    display: block;
                }

                .submenu.level-2 .submenu-content {
                    grid-template-columns: 1fr;
                    gap: 10px;
                }

                .submenu.level-2 .submenu-item a::before {
                    display: none; /* Disable hover line effect on mobile */
                }

                .has-submenu > a::after {
                    display: none;
                }
            }

            @media (max-width: 768px) {
                .menu-item a {
                    font-size: <?php echo esc_attr($font_size - 2); ?>px;
                }

                .submenu.level-2 .submenu-item a {
                    font-size: <?php echo esc_attr($font_size - 3); ?>px;
                }

                .aspiredev-nav {
                    padding: 0 10px;
                }
            }

            @media (max-width: 576px) {
                .aspiredev-nav {
                    padding: 0 5px;
                }

                .menu-item a {
                    padding: 10px 15px;
                }

                .submenu.level-1 {
                    min-width: 100%;
                }
            }
        </style>
        <nav class="aspiredev-nav">
            <button class="menu-toggle" aria-label="Toggle Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <ul class="main-menu">
                <?php foreach ($menu_tree as $item): ?>
                    <li class="menu-item <?php echo !empty($item->children) ? 'has-submenu' : ''; ?>" 
                        data-menu-id="<?php echo esc_attr($item->ID); ?>">
                        <a href="<?php echo esc_url($item->url); ?>"><?php echo esc_html($item->title); ?></a>
                        <?php if (!empty($item->children)): ?>
                            <ul class="submenu level-1">
                                <?php $first_child = true;
                                foreach ($item->children as $child): ?>
                                    <li class="submenu-item submenu-item-level1" data-child-id="<?php echo esc_attr($child->ID); ?>">
                                        <a href="<?php echo esc_url($child->url); ?>"><?php echo esc_html($child->title); ?></a>
                                        <?php if (!empty($child->children)): ?>
                                            <ul style="display:none;"> <!-- Hidden to avoid rendering, handled by JS -->
                                                <?php foreach ($child->children as $grandchild): ?>
                                                    <li><a href="<?php echo esc_url($grandchild->url); ?>"><?php echo esc_html($grandchild->title); ?></a></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </li>
                                    <?php if ($first_child) {
                                        $first_child_id = $child->ID;
                                        $first_child = false;
                                    } ?>
                                <?php endforeach; ?>
                                <div class="submenu level-2" data-default-id="<?php echo esc_attr($first_child_id ?? ''); ?>">
                                    <div class="submenu-content"></div>
                                </div>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </header>
    <?php
    return ob_get_clean();
}
add_shortcode('header', 'aspiredev_header_shortcode');

// Ensure compatibility with Elementor and add toggle functionality
add_action('wp_footer', function() {
    if (!class_exists('Elementor\Plugin') || !\Elementor\Plugin::$instance->editor->is_edit_mode()) {
        ?>
        <script>
            (function($) {
                var $j = $.noConflict();

                $j(document).ready(function() {
                    var $menuToggle = $j('.menu-toggle');
                    var $mainMenu = $j('.main-menu');
                    var currentLevel2Id = null; // Track the currently displayed level-2 submenu ID

                    // Toggle menu on mobile
                    $menuToggle.on('click', function() {
                        $mainMenu.toggleClass('active');
                    });

                    // Function to populate level-2 content
                    function updateLevel2Content(id, $submenuLevel2) {
                        var $level2Content = $submenuLevel2.find('.submenu-content');
                        $level2Content.empty();
                        console.log('Updating content for id:', id); // Debug log
                        var items = $j('.submenu-item[data-child-id="' + id + '"]').closest('li').find('ul li').map(function() {
                            return {
                                title: $j(this).find('a').text(),
                                url: $j(this).find('a').attr('href')
                            };
                        }).get();

                        console.log('Found items:', items); // Debug log

                        if (items.length === 0 && id) {
                            items = $j('.menu-item[data-menu-id="' + id + '"]').find('.submenu-item:first').closest('li').find('ul li').map(function() {
                                return {
                                    title: $j(this).find('a').text(),
                                    url: $j(this).find('a').attr('href')
                                };
                            }).get();
                        }

                        items.forEach(function(item) {
                            $level2Content.append(
                                $j('<div class="submenu-item">').append(
                                    $j('<a>').attr('href', item.url).text(item.title)
                                )
                            );
                        });

                        currentLevel2Id = id; // Update the current level-2 ID
                    }

                    // Initialize with default content for each level-2 submenu
                    $j('.submenu.level-2').each(function() {
                        var defaultId = $j(this).data('default-id');
                        if (defaultId) {
                            console.log('Initializing with default content for id:', defaultId); // Debug log
                            updateLevel2Content(defaultId, $j(this));
                        }
                    });

                    // Show level-1 and level-2 on hover for desktop
                    $j('.menu-item.has-submenu').hover(
                        function() {
                            if (window.innerWidth > 991) {
                                console.log('Hovering over menu item'); // Debug log
                                var $submenuLevel1 = $j(this).find('.submenu.level-1');
                                var $submenuLevel2 = $j(this).find('.submenu.level-2');
                                $submenuLevel1.css({
                                    'visibility': 'visible',
                                    'opacity': '1'
                                });
                                $submenuLevel2.css({
                                    'visibility': 'visible',
                                    'opacity': '1'
                                });
                                var firstChildId = $j(this).find('.submenu-item:first').data('child-id');
                                if (firstChildId) {
                                    updateLevel2Content(firstChildId, $submenuLevel2);
                                }
                            }
                        },
                        function() {
                            if (window.innerWidth > 991) {
                                $j(this).find('.submenu.level-1').css({
                                    'visibility': 'hidden',
                                    'opacity': '0'
                                });
                                $j(this).find('.submenu.level-2').css({
                                    'visibility': 'hidden',
                                    'opacity': '0'
                                });
                            }
                        }
                    );

                    // Toggle submenu on mobile for main menu items
                    $j('.menu-item.has-submenu > a').on('click', function(e) {
                        if (window.innerWidth <= 991) {
                            e.preventDefault();
                            var $submenu = $j(this).parent().find('.submenu.level-1');
                            var $submenuLevel2 = $j(this).parent().find('.submenu.level-2');
                            var isActive = $submenu.hasClass('active');
                            
                            // Close all other level-1 submenus
                            $j('.submenu.level-1').not($submenu).removeClass('active');
                            $j('.submenu.level-2').not($submenuLevel2).removeClass('active');

                            $submenu.toggleClass('active');
                            if (!isActive && $submenu.hasClass('active')) {
                                var childId = $j(this).parent().find('.submenu-item:first').data('child-id');
                                if (childId) {
                                    updateLevel2Content(childId, $submenuLevel2);
                                    $submenuLevel2.addClass('active');
                                }
                            } else {
                                $submenuLevel2.removeClass('active');
                            }
                        }
                    });

                    // Update level-2 content on hover for level-1 items (desktop)
                    $j('.submenu-item').hover(
                        function() {
                            if (window.innerWidth > 991) {
                                var childId = $j(this).data('child-id');
                                if (childId) {
                                    var $submenuLevel2 = $j(this).closest('.menu-item.has-submenu').find('.submenu.level-2');
                                    updateLevel2Content(childId, $submenuLevel2);
                                }
                            }
                        },
                        function() {
                            // Do not reset to default on hover out to maintain current state
                        }
                    );

                    // Toggle level-2 submenu on click for level-1 items (mobile)
                    $j('.submenu-item a').on('click', function(e) {
                        if (window.innerWidth <= 991) {
                            e.preventDefault();
                            var childId = $j(this).parent().data('child-id');
                            var $submenuLevel2 = $j(this).closest('.menu-item.has-submenu').find('.submenu.level-2');
                            var isActive = $submenuLevel2.hasClass('active');

                            // Only update if clicking a different submenu item
                            if (childId && currentLevel2Id !== childId) {
                                updateLevel2Content(childId, $submenuLevel2);
                                $submenuLevel2.addClass('active');
                            } else if (isActive) {
                                $submenuLevel2.removeClass('active');
                            } else {
                                $submenuLevel2.addClass('active');
                            }
                        }
                    });
                });
            })(jQuery);
        </script>
        <?php
    }
});
?>