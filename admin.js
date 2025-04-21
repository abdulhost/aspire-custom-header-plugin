(function($) {
    $(document).ready(function() {
        // Frontend menu interactions
        var $menuToggle = $('.menu-toggle');
        var $mainMenu = $('.main-menu');

        // Toggle menu on mobile
        $menuToggle.on('click', function() {
            $mainMenu.toggleClass('active');
        });

        // Submenu handling
        function updateLevel2Content(id) {
            var $level2Content = $('.submenu.level-2 .submenu-content');
            $level2Content.empty();
            
            var items = $('.submenu-item[data-child-id="' + id + '"]').find('ul li').map(function() {
                return {
                    title: $(this).find('a').text(),
                    url: $(this).find('a').attr('href')
                };
            }).get();

            if (items.length === 0 && id) {
                items = $('.menu-item[data-menu-id="' + id + '"]').find('.submenu-item:first').find('ul li').map(function() {
                    return {
                        title: $(this).find('a').text(),
                        url: $(this).find('a').attr('href')
                    };
                }).get();
            }

            items.forEach(function(item) {
                $level2Content.append(
                    $('<div class="submenu-item">').append(
                        $('<a>').attr('href', item.url).text(item.title)
                    )
                );
            });
        }

        // Initialize default submenu
        var $level2 = $('.submenu.level-2');
        var defaultId = $level2.data('default-id');
        if (defaultId) {
            updateLevel2Content(defaultId);
        }

        // Desktop hover
        $('.menu-item.has-submenu').hover(
            function() {
                if (window.innerWidth > 991) {
                    $(this).find('.submenu').css({
                        'visibility': 'visible',
                        'opacity': '1'
                    });
                    var firstChildId = $(this).find('.submenu-item:first').data('child-id');
                    if (firstChildId) {
                        updateLevel2Content(firstChildId);
                    }
                }
            },
            function() {
                if (window.innerWidth > 991) {
                    $(this).find('.submenu').css({
                        'visibility': 'hidden',
                        'opacity': '0'
                    });
                }
            }
        );

        // Mobile click
        $('.menu-item.has-submenu > a').on('click', function(e) {
            if (window.innerWidth <= 991) {
                e.preventDefault();
                $(this).siblings('.submenu.level-1').toggleClass('active');
                var childId = $(this).parent().find('.submenu-item:first').data('child-id');
                if (childId) {
                    updateLevel2Content(childId);
                }
            }
        });

        // Level-1 item hover/click
        $('.submenu-item').hover(
            function() {
                if (window.innerWidth > 991) {
                    var childId = $(this).data('child-id');
                    if (childId) {
                        updateLevel2Content(childId);
                    }
                }
            },
            function() {
                if (window.innerWidth > 991) {
                    var parent = $(this).closest('.menu-item.has-submenu');
                    var firstChildId = parent.find('.submenu-item:first').data('child-id');
                    if (firstChildId) {
                        updateLevel2Content(firstChildId);
                    }
                }
            }
        );

        $('.submenu-item.has-submenu > a').on('click', function(e) {
            if (window.innerWidth <= 991) {
                e.preventDefault();
                var childId = $(this).parent().data('child-id');
                if (childId) {
                    updateLevel2Content(childId);
                    $(this).siblings('.submenu.level-2').toggleClass('active');
                }
            }
        });

        // Admin settings page interactions (only run on admin page)
        if ($('body.toplevel_page_aspiredev-header-menu').length) {
            // Color preview updates
            $('input[type="color"]').on('input', function() {
                var id = $(this).attr('id');
                $('#preview_' + id).css('background', $(this).val());
            });

            // Theme color selection
            $('.aspiredev-theme-color-select').on('change', function() {
                var color = $(this).val();
                var targetId = $(this).data('target');
                if (color) {
                    $('#' + targetId).val(color).trigger('input');
                }
            });

            // Reset to defaults
            $('#aspiredev-reset-defaults').on('click', function() {
                if (confirm(aspiredevHeaderMenu.resetConfirm)) {
                    $.ajax({
                        url: aspiredevHeaderMenu.ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'aspiredev_reset_defaults',
                            nonce: aspiredevHeaderMenu.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert('Error resetting defaults.');
                            }
                        },
                        error: function() {
                            alert('Error resetting defaults.');
                        }
                    });
                }
            });
        }
    });
})(jQuery);