<?php
/*
Plugin Name: AspireDev Header Menu
Plugin URI: https://aspiredev.com
Description: A WordPress plugin to create a header with a dynamic, multi-level navigation menu using a shortcode, with admin panel customization. Developed by AspireDev.
Version: 1.8.0
Author: AspireDev
Author URI: https://aspiredev.com
License: GPL2
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue jQuery and custom scripts
function aspiredev_header_menu_enqueue_scripts() {
    wp_enqueue_script('jquery');
}
add_action('wp_enqueue_scripts', 'aspiredev_header_menu_enqueue_scripts');

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
        null,
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
        'Custom Colors',
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
}
add_action('admin_init', 'aspiredev_header_menu_register_settings');

// Settings page callback
function aspiredev_header_menu_settings_page() {
    ?>
    <div class="wrap">
        <h1>AspireDev Header Menu Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('aspiredev_header_menu_options');
            do_settings_sections('aspiredev-header-menu');
            submit_button();
            ?>
        </form>
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

// Custom colors callback
function aspiredev_header_menu_custom_colors_callback() {
    $options = get_option('aspiredev_header_menu_settings');
    $header_bg = $options['header_bg'] ?? '#34495e';
    $header_gradient = $options['header_gradient'] ?? '#2c3e50';
    $menu_text = $options['menu_text'] ?? '#ecf0f1';
    $menu_hover = $options['menu_hover'] ?? '#2980b9';
    $submenu_bg = $options['submenu_bg'] ?? '#2c3e50';
    $submenu_hover = $options['submenu_hover'] ?? '#3498db';
    ?>
    <div>
        <label>Header Background: <input type="color" name="aspiredev_header_menu_settings[header_bg]" value="<?php echo esc_attr($header_bg); ?>"></label><br>
        <label>Header Gradient: <input type="color" name="aspiredev_header_menu_settings[header_gradient]" value="<?php echo esc_attr($header_gradient); ?>"></label><br>
        <label>Menu Text: <input type="color" name="aspiredev_header_menu_settings[menu_text]" value="<?php echo esc_attr($menu_text); ?>"></label><br>
        <label>Menu Hover: <input type="color" name="aspiredev_header_menu_settings[menu_hover]" value="<?php echo esc_attr($menu_hover); ?>"></label><br>
        <label>Submenu Background: <input type="color" name="aspiredev_header_menu_settings[submenu_bg]" value="<?php echo esc_attr($submenu_bg); ?>"></label><br>
        <label>Submenu Hover: <input type="color" name="aspiredev_header_menu_settings[submenu_hover]" value="<?php echo esc_attr($submenu_hover); ?>"></label>
    </div>
    <p class="description">Customize colors for the header and menu items.</p>
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
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
                position: relative;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }

            .aspiredev-nav {
                max-width: 1200px;
                margin: 0 auto;
                position: relative;
            }

            .main-menu {
                list-style: none;
                margin: 0;
                padding: 0;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100%;
            }

            .menu-item {
                position: relative;
                margin: 0 20px;
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
                transition: all 0.3s ease;
                border-radius: 6px;
                position: relative;
            }

            .menu-item a:hover {
                background: linear-gradient(135deg, <?php echo esc_attr($menu_hover); ?>, #2980b9);
                color: #ffffff;
                transform: translateY(-2px);
                box-shadow: 0 3px 6px rgba(52, 152, 219, 0.3);
            }

            .has-submenu > a::after {
                content: "▼";
                font-size: 10px;
                margin-left: 8px;
                vertical-align: middle;
                transition: transform 0.3s ease;
            }

            .submenu-item-level1 > a::after {
                content: "▼";
                font-size: 10px;
                margin-left: 8px;
                vertical-align: middle;
                transition: transform 0.3s ease;
            }

            .has-submenu:hover > a::after {
                transform: rotate(180deg);
            }

            .submenu.level-1 {
                list-style: none;
                margin: 0;
                padding: 0;
                background: <?php echo esc_attr($submenu_bg); ?>;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                position: absolute;
                top: 100%;
                left: 0;
                display: flex;
                flex-direction: row;
                gap: 8px;
                padding: 12px 0;
                min-width: 600px;
                visibility: hidden;
                opacity: 0;
                transition: opacity 0.3s ease, visibility 0s linear 0.3s;
                z-index: 1000;
                position: absolute;
            }

            .submenu.level-2 {
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background: <?php echo esc_attr($submenu_bg); ?>;
                border-radius: 6px;
                padding: 15px;
                box-sizing: border-box;
                visibility: hidden;
                opacity: 0;
                transition: opacity 0.3s ease, visibility 0s linear 0.3s;
                z-index: 999;
                box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            }

            .submenu.level-2 .submenu-content {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 20px;
                max-height: 600px;
            }

            .submenu.level-2 .submenu-item {
                flex: 0 0 180px;
            }

            .submenu.level-2 .submenu-item a {
                color: <?php echo esc_attr($menu_text); ?>;
                padding: 10px 15px;
                font-size: <?php echo esc_attr($font_size - 1); ?>px; /* Slightly smaller for submenus */
                font-weight: 400;
                display: block;
                border-radius: 4px;
                transition: all 0.3s ease;
            }

            .submenu.level-2 .submenu-item a:hover {
                background: <?php echo esc_attr($submenu_hover); ?>;
                color: #ffffff;
                transform: translateX(4px);
            }

            .menu-item.has-submenu:hover > .submenu.level-1,
            .menu-item.has-submenu:hover .submenu.level-2 {
                visibility: visible;
                opacity: 1;
                transition-delay: 0s;
            }

            /* Responsive Design */
            @media (max-width: 768px) {
                .main-menu {
                    flex-direction: column;
                    align-items: center;
                }

                .menu-item {
                    margin: 8px 0;
                }

                .submenu.level-1 {
                    position: static;
                    width: 100%;
                    min-width: auto;
                    flex-direction: column;
                    padding: 8px;
                }

                .submenu.level-2 {
                    position: static;
                    width: 100%;
                    padding: 12px;
                }
            }
        </style>
        <nav class="aspiredev-nav">
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

// Ensure compatibility with Elementor
add_action('wp_footer', function() {
    if (!class_exists('Elementor\Plugin') || !\Elementor\Plugin::$instance->editor->is_edit_mode()) {
        ?>
        <script>
            (function($) {
                var $j = $.noConflict();

                $j(document).ready(function() {
                    var $level2 = $j('.submenu.level-2');
                    var $level2Content = $j('.submenu.level-2 .submenu-content');
                    var defaultId = $level2.data('default-id');

                    console.log('Script loaded, defaultId:', defaultId); // Debug log

                    // Function to populate level-2 content
                    function updateLevel2Content(id) {
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
                    }

                    // Initialize with default content
                    if (defaultId) {
                        console.log('Initializing with default content for id:', defaultId); // Debug log
                        updateLevel2Content(defaultId);
                    }

                    // Show level-1 and level-2 on hover over main menu item
                    $j('.menu-item.has-submenu').hover(
                        function() {
                            console.log('Hovering over menu item'); // Debug log
                            $j(this).find('.submenu.level-1').css({
                                'visibility': 'visible',
                                'opacity': '1'
                            });
                            $j(this).find('.submenu.level-2').css({
                                'visibility': 'visible',
                                'opacity': '1'
                            });
                            var firstChildId = $j(this).find('.submenu-item:first').data('child-id');
                            if (firstChildId) {
                                updateLevel2Content(firstChildId);
                            }
                        },
                        function() {
                            $j(this).find('.submenu.level-1').css({
                                'visibility': 'hidden',
                                'opacity': '0'
                            });
                            $j(this).find('.submenu.level-2').css({
                                'visibility': 'hidden',
                                'opacity': '0'
                            });
                        }
                    );

                    // Update level-2 content on hover over level-1 items
                    $j('.submenu-item').hover(
                        function() {
                            var childId = $j(this).data('child-id');
                            if (childId) {
                                updateLevel2Content(childId);
                            }
                        },
                        function() {
                            var parent = $j(this).closest('.menu-item.has-submenu');
                            var firstChildId = parent.find('.submenu-item:first').data('child-id');
                            if (firstChildId) {
                                updateLevel2Content(firstChildId);
                            }
                        }
                    );
                });
            })(jQuery);
        </script>
        <?php
    }
});
?>