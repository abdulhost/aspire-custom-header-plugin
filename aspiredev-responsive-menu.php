<?php


// Mobile header shortcode
function aspiredev_mobile_header_shortcode($atts) {
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

    // Handle theme color overrides
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
        'mobile_header'
    );
    $menu_slug = sanitize_text_field($atts['menu']);

    // Get the menu
    $menu_items = wp_get_nav_menu_items($menu_slug);

    if (!$menu_items) {
        return '<div class="aspiredev-mobile-header">No menu found for slug: ' . esc_html($menu_slug) . '</div>';
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

    // Generate mobile menu HTML with embedded CSS
    ob_start();
    ?>
    <header class="aspiredev-mobile-header" style="padding: <?php echo esc_attr($padding); ?>px 0;">
        <style>
            .aspiredev-mobile-header {
                background: linear-gradient(90deg, <?php echo esc_attr($header_bg); ?>, <?php echo esc_attr($header_gradient); ?>);
                box-shadow: 0 <?php echo esc_attr($shadow_intensity * 2); ?>px <?php echo esc_attr($shadow_intensity * 4); ?>px rgba(0, 0, 0, 0.2);
                position: relative;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                width: 100%;
                box-sizing: border-box;
                display: none;
            }

            .aspiredev-mobile-nav {
                max-width: 1200px;
                margin: 0 auto;
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0 15px;
                position: relative;
            }

            .mobile-site-branding {
                display: flex;
                align-items: center;
                gap: 10px;
                flex: 1;
            }

            .mobile-site-icon img {
                width: 24px;
                height: 24px;
                border-radius: 50%;
            }

            .mobile-site-title {
                color: <?php echo esc_attr($menu_text); ?>;
                font-size: <?php echo esc_attr($font_size); ?>px;
                font-weight: 600;
                text-decoration: none;
                display: block;
            }

            .mobile-menu-toggle {
                background: none;
                border: none;
                cursor: pointer;
                padding: 10px;
                position: relative;
                z-index: 1001;
            }

            .mobile-menu-toggle span {
                display: block;
                width: 25px;
                height: 3px;
                background: <?php echo esc_attr($menu_text); ?>;
                margin: 5px 0;
                transition: all <?php echo esc_attr($transition_speed); ?>s ease;
            }

            .mobile-menu-toggle.active span:nth-child(1) {
                transform: rotate(45deg) translate(5px, 5px);
            }

            .mobile-menu-toggle.active span:nth-child(2) {
                opacity: 0;
            }

            .mobile-menu-toggle.active span:nth-child(3) {
                transform: rotate(-45deg) translate(7px, -7px);
            }

            .mobile-main-menu {
                list-style: none;
                margin: 0;
                padding: 0;
                display: none;
                flex-direction: column;
                width: 100%;
                position: absolute;
                top: 100%;
                left: 0;
                background: <?php echo esc_attr($submenu_bg); ?>;
                z-index: 1000;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            }

            .mobile-main-menu.active {
                display: flex;
            }

            .mobile-menu-item {
                width: 100%;
                text-align: left;
            }

            .mobile-menu-item a {
                color: <?php echo esc_attr($menu_text); ?>;
                text-decoration: none;
                font-size: <?php echo esc_attr($font_size); ?>px;
                font-weight: 500;
                padding: 12px 15px;
                display: block;
                transition: background <?php echo esc_attr($transition_speed); ?>s ease;
            }

            .mobile-menu-item a:hover {
                background: <?php echo esc_attr($menu_hover); ?>;
            }

            .mobile-has-submenu > a::after {
                content: "▶";
                float: right;
                font-size: 12px;
                margin-right: 15px;
            }

            .mobile-has-submenu.active > a::after {
                transform: rotate(90deg);
            }

            .mobile-submenu.level-1 {
                list-style: none;
                margin: 0;
                padding: 0;
                background: <?php echo esc_attr(darken_color($submenu_bg, 10)); ?>;
                display: none;
            }

            .mobile-submenu.level-1.active {
                display: block;
            }

            .mobile-submenu.level-1 .mobile-submenu-item a {
                padding: 10px 25px;
                font-size: <?php echo esc_attr($font_size - 1); ?>px;
            }

            .mobile-submenu.level-1 .mobile-has-submenu > a::after {
                content: "▶";
                float: right;
                font-size: 12px;
                margin-right: 15px;
            }

            .mobile-submenu.level-1 .mobile-has-submenu.active > a::after {
                transform: rotate(90deg);
            }

            .mobile-submenu.level-2 {
                list-style: none;
                margin: 0;
                padding: 0;
                background: <?php echo esc_attr(darken_color($submenu_bg, 20)); ?>;
                display: none;
            }

            .mobile-submenu.level-2.active {
                display: block;
            }

            .mobile-submenu.level-2 .mobile-submenu-item a {
                padding: 8px 35px;
                font-size: <?php echo esc_attr($font_size - 2); ?>px;
            }

            /* Show mobile header only on mobile devices */
            @media (max-width: 991px) {
                .aspiredev-mobile-header {
                    display: block;
                }
                .aspiredev-header {
                    display: none;
                }
            }

            @media (max-width: 768px) {
                .mobile-menu-item a {
                    font-size: <?php echo esc_attr($font_size - 1); ?>px;
                }

                .mobile-submenu.level-1 .mobile-submenu-item a {
                    font-size: <?php echo esc_attr($font_size - 2); ?>px;
                }

                .mobile-submenu.level-2 .mobile-submenu-item a {
                    font-size: <?php echo esc_attr($font_size - 3); ?>px;
                }

                .aspiredev-mobile-nav {
                    padding: 0 10px;
                }

                .mobile-site-title {
                    font-size: <?php echo esc_attr($font_size - 1); ?>px;
                }
            }

            @media (max-width: 576px) {
                .aspiredev-mobile-nav {
                    padding: 0 5px;
                }

                .mobile-menu-item a {
                    padding: 10px 12px;
                }

                .mobile-submenu.level-1 .mobile-submenu-item a {
                    padding: 8px 22px;
                }

                .mobile-submenu.level-2 .mobile-submenu-item a {
                    padding: 6px 32px;
                }

                .mobile-site-title {
                    font-size: <?php echo esc_attr($font_size - 2); ?>px;
                }
            }
        </style>
        <nav class="aspiredev-mobile-nav">
            <div class="mobile-site-branding">
                <?php if (get_site_icon_url()): ?>
                    <div class="mobile-site-icon">
                        <a href="<?php echo esc_url(home_url('/')); ?>">
                            <img src="<?php echo esc_url(get_site_icon_url()); ?>" alt="Site Icon">
                        </a>
                    </div>
                <?php endif; ?>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="mobile-site-title">
                    <?php echo esc_html(get_bloginfo('name')); ?>
                </a>
            </div>
            <button class="mobile-menu-toggle" aria-label="Toggle Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <ul class="mobile-main-menu">
                <?php foreach ($menu_tree as $item): ?>
                    <li class="mobile-menu-item <?php echo !empty($item->children) ? 'mobile-has-submenu' : ''; ?>">
                        <a href="<?php echo esc_url($item->url); ?>"><?php echo esc_html($item->title); ?></a>
                        <?php if (!empty($item->children)): ?>
                            <ul class="mobile-submenu level-1">
                                <?php foreach ($item->children as $child): ?>
                                    <li class="mobile-submenu-item <?php echo !empty($child->children) ? 'mobile-has-submenu' : ''; ?>">
                                        <a href="<?php echo esc_url($child->url); ?>"><?php echo esc_html($child->title); ?></a>
                                        <?php if (!empty($child->children)): ?>
                                            <ul class="mobile-submenu level-2">
                                                <?php foreach ($child->children as $grandchild): ?>
                                                    <li class="mobile-submenu-item">
                                                        <a href="<?php echo esc_url($grandchild->url); ?>">
                                                            <?php echo esc_html($grandchild->title); ?>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
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
add_shortcode('mobile_header', 'aspiredev_mobile_header_shortcode');

// Mobile-specific JavaScript
add_action('wp_footer', function() {
    if (!class_exists('Elementor\Plugin') || !\Elementor\Plugin::$instance->editor->is_edit_mode()) {
        ?>
        <script>
            (function($) {
                var $j = $.noConflict();

                $j(document).ready(function() {
                    // Hamburger menu toggle
                    $j('.mobile-menu-toggle').on('click', function() {
                        $j(this).toggleClass('active');
                        $j('.mobile-main-menu').toggleClass('active');
                    });

                    // Main menu item click (level-0)
                    $j('.mobile-menu-item.mobile-has-submenu > a').on('click', function(e) {
                        if (window.innerWidth <= 991) {
                            e.preventDefault();
                            var $parent = $j(this).parent();
                            var $submenu = $parent.find('.mobile-submenu.level-1');
                            var isActive = $submenu.hasClass('active');

                            // Close all other main menu submenus
                            $j('.mobile-menu-item.mobile-has-submenu').not($parent).removeClass('active').find('.mobile-submenu.level-1').removeClass('active');
                            $j('.mobile-submenu.level-2').removeClass('active');

                            if (!isActive) {
                                $parent.addClass('active');
                                $submenu.addClass('active');
                            } else {
                                $parent.removeClass('active');
                                $submenu.removeClass('active');
                            }
                        }
                    });

                    // Level-1 submenu item click
                    $j('.mobile-submenu.level-1 .mobile-has-submenu > a').on('click', function(e) {
                        if (window.innerWidth <= 991) {
                            e.preventDefault();
                            var $parent = $j(this).parent();
                            var $submenu = $parent.find('.mobile-submenu.level-2');
                            var isActive = $submenu.hasClass('active');

                            // Close other level-2 submenus within the same level-1
                            $parent.siblings().find('.mobile-submenu.level-2').removeClass('active');
                            $parent.siblings().removeClass('active');

                            if (!isActive) {
                                $parent.addClass('active');
                                $submenu.addClass('active');
                            } else {
                                $parent.removeClass('active');
                                $submenu.removeClass('active');
                            }
                        }
                    });
                });
            })(jQuery);
        </script>
        <?php
    }
});

