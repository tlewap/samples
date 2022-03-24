<?php
function is_proshop(){
   return TEDEE_PROSHOP?true:false;
}

require_once get_theme_file_path() . '/inc/thumb-sizes.php';
require_once get_theme_file_path() . '/inc/custom-post-types/cpt-functionalities.php';
require_once get_theme_file_path() . '/inc/custom-post-types/cpt-installations.php';
require_once get_theme_file_path() . '/inc/custom-post-types/cpt-products.php';
require_once get_theme_file_path() . '/inc/custom-post-types/cpt-shared_sections.php';
require_once get_theme_file_path() . '/inc/custom-post-types/cpt-knowledge_base.php';

require_once get_theme_file_path() . '/inc/wc_functions.php';
require_once get_theme_file_path() . '/inc/wc_custom_functions.php';
require_once get_theme_file_path() . '/inc/accordion_item_render.php';
require_once get_theme_file_path() . '/inc/theme_hooks.php';
require_once get_theme_file_path() . '/inc/theme_enqueue_scripts.php';
require_once get_theme_file_path() . '/inc/plus_minus_add_cart_buttons.php';
require_once get_theme_file_path() . '/inc/acf-options-page-settings.php';
require_once get_theme_file_path() . '/inc/theme_navigation_settings.php';

require_once get_theme_file_path() . '/inc/translations.php';
require_once get_theme_file_path() . '/inc/map_functions.php';


if (is_proshop()){
    require_once get_theme_file_path() . '/inc/proshop.php';
}

