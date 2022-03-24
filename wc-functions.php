<?php
function woocommerce_product_archive_loop_start($echo = true)
{
    ob_start();

    wc_set_loop_prop('loop', 0);

    wc_get_template('loop/loop-archive-start.php');

    $loop_start = apply_filters('woocommerce_product_loop_start', ob_get_clean());

    if ($echo) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $loop_start;
    } else {
        return $loop_start;
    }
}


function tedee_show_product_pricing_options($echo = true)
{
    global $product;

    $shippingClass = $product->get_shipping_class();
    $shipping_classname = get_term_by('slug', $shippingClass, 'product_shipping_class');

//    TODO: add tax included/excluded info loaded dynamically for both bundle and single product
//    $price_excl_tax = wc_get_price_excluding_tax($product);
//    $price_incl_tax = wc_get_price_including_tax($product);
//    $taxInfo = $price_excl_tax !== $price_incl_tax ? pll__('tax_included') : pll__('tax_excluded');

    echo '<div class="tax-info">' . pll__('tax_included') . '</div>';

    if ($shipping_classname):
        echo '<div class="tax-info">' . pll__('delivery_time') . ': ' . $shipping_classname->name . '</div>';
    endif;

//    TODO: integrate with WC
    echo '<div class="free-shipping mb-3">' . pll__('free_shipping') . '</div>';
}

//override storefront_page_header in order to hide it when on thank you page
function storefront_page_header()
{
    if ((is_front_page() && is_page_template('template-fullwidth.php'))
        || is_order_received_page()) {
        return;
    }
    ?>
    <header class="entry-header">
        <?php
        storefront_post_thumbnail('full');
        the_title('<h1 class="entry-title">', '</h1>');
        ?>
    </header><!-- .entry-header -->
    <?php
}

function tedee_cart_header($echo = true)
{
    wc_get_template('cart/cart-header.php');
}

function tedee_nav_options_display()
{
    ?>


    <div class="nav-options d-flex align-items-center ml-auto">


        <?php
        if (is_proshop() && is_user_logged_in()) {
            $user = wp_get_current_user();
            $display_name = $user->first_name;

            echo '<div class="proshop_menu">';

            echo "Hi, <b>" . $display_name . "</b> ";

            echo "<div class=d-flex>";
            echo "<div class='proshop_menu_link'><a href=" . get_permalink(wc_get_page_id('myaccount')) . 'edit-account' . ">PROFILE</a></div>";
            echo "<div class='proshop_menu_link proshop_menu_link_logout'><a href=" . wp_logout_url(home_url()) . "'>LOGOUT</a></div>";
            echo '</div>';
            echo '</div>';

        }
        ?>

        <div class="main-nav__lang-switch" style="color: white">

            <?php
            $currentLang = pll_current_language();
            $langListRaw = pll_the_languages(array('raw' => 1));
            ?>


            <div class="current-lang">
                <img src="<?= $langListRaw[$currentLang]['flag'] ?>" alt="<?= $currentLang . '-lang-flag'; ?>">
                <ul>
                    <?php pll_the_languages(array('show_flags' => 1, 'show_names' => 0, 'hide_current' => 1)); ?>
                </ul>
            </div>

        </div>
        <?php

        if (!is_proshop()) {
            echo do_shortcode('[woo_multi_currency_layout7]');
        }

        if (is_proshop() && is_user_logged_in()) {
            echo do_shortcode('[woo_multi_currency_layout7]');
        }

        if (is_proshop()) {
            if (is_user_logged_in()) {
                storefront_header_cart();
            }
        } else {
            storefront_header_cart();
        }

        if (!is_proshop()) {
            echo '<a href="' . wc_get_page_permalink('shop') . '" class="btn btn-primary btn-lg ml-4">' . pll__('shop_now') . '</a>';
        }
        ?>
    </div>
    <?php
}

function display_desc_in_product_archives()
{
    $excerpt = get_the_excerpt();
    if (!empty($excerpt)) {
        echo '<div class="product-excerpt">';
        the_excerpt();
        echo '</div>';
    }

}

function filter_projects()
{


    $postType = $_POST['type'];
    $catSlug = $_POST['category'];
    $taxQuery = array(
        array(
            'taxonomy' => 'product_cat',
            'field' => 'slug',
            'terms' => $catSlug
        )
    );

    $ajaxposts = new WP_Query([
        'post_type' => $postType,
        'orderby' => 'menu_order',
        'order' => 'ASC',
        'posts_per_page' => -1,
        'lang' => pll_current_language(),
        'tax_query' => $catSlug ? $taxQuery : null
    ]);


    $response = '';

    if ($ajaxposts->have_posts()) {
        while ($ajaxposts->have_posts()) : $ajaxposts->the_post();
            $response .= get_template_part('content', 'product');
        endwhile;
    } else {
        $response = 'empty';
    }

    echo $response;
    exit;
}

add_action('wp_ajax_filter_projects', 'filter_projects');
add_action('wp_ajax_nopriv_filter_projects', 'filter_projects');

add_action('after_setup_theme', function () {
    if (!function_exists('pll_register_string')) return false;

    $context = 'labels';
    pll_register_string('quantity', 'Ilość:', $context);
});

function wc_custom_shipping_package_name($name)
{
    if (is_checkout()) return '<h3>' . pll__('shipping_method') . '</h3>';

    return $name;
}

add_filter('woocommerce_shipping_package_name', 'wc_custom_shipping_package_name');

function wc_custom_woocommerce_billing_fields($fields)
{
    $fields['billing']['billing_name_or_company'] = array(
        'label' => pll__('first_last_company_name'), // Add custom field label
        'placeholder' => pll__('first_last_company_name'), // Add custom field placeholder
        'required' => true, // if field is required or not
        'priority' => 10
    );

    //order fields
    $arOrderedFields = array(
        'billing_name_or_company' => 1,
        'billing_vat_number' => 1,
        'billing_country' => 1,
        'billing_address_1' => 1,
        'billing_postcode' => 1,
        'billing_city' => 1,
        'billing_email' => 1,
        'billing_phone' => 1,
        'billing_state' => 1,
    );

    foreach ($fields['billing'] as $key => $field) {

        if (isset($arOrderedFields[$key])) {
            //if (($key == 'billing_country') && ('pl' == substr(get_bloginfo ( 'language' ), 0, 2))) { $field['country'] == 'PL'; }

            switch ($key) {
                case 'billing_vat_number':
                    {
                        $field['priority'] = 20;
                        $field['data-priority'] = 20;
                    }
                    break;

                case 'billing_phone':
                    {
                        $field['priority'] = 110;
                        $field['data-priority'] = 110;
                    }
                    break;
            }

            $arOrderedFields[$key] = $field;
        }
    }

    reset($arOrderedFields);
    $fields['billing'] = $arOrderedFields;

    return $fields;
}

add_filter('woocommerce_checkout_fields', 'wc_custom_woocommerce_billing_fields');

//Custom function to display cart link in the menu to avoid redirection to cart page and to invoke popup
function storefront_cart_link()
{
    if (!storefront_woo_cart_available()) {
        return;
    }
    $cartCount = WC()->cart->get_cart_contents_count();

    echo '<a class="cart-contents" title="';
    esc_attr_e('View your shopping cart', 'storefront');
    echo '">';
    if ($cartCount > 0) {
        echo '<span class="cart-badge">';
        echo $cartCount;
        echo '</span>';
    }
    echo '</a>';
}

// TODO: third checkou step is disabled
//function tedee_confirm_order_on_checkout($data)
//{
//    if (isset($_POST['tedee-order-confirmed']) && ($_POST['tedee-order-confirmed'] == 'no')) wc_add_notice(pll__('confirm_order'), 'error', array('hide' => 1));
//}
//
//add_action('woocommerce_after_checkout_validation', 'tedee_confirm_order_on_checkout');

function tedee_ajax_added_to_cart($product_id)
{
    if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
    if (isset($_SESSION)) $_SESSION['tedde_added_to_cart_product_id'] = $product_id;
}

add_action('woocommerce_ajax_added_to_cart', 'tedee_ajax_added_to_cart', 10, 1);

function tedee_get_added_to_cart_popup()
{
    include get_theme_file_path() . '/woocommerce/cart/added-to-cart-popup.php';
    if (defined('DOING_AJAX') && DOING_AJAX) exit;
}

add_action('wp_ajax_get_added_to_cart_popup', 'tedee_get_added_to_cart_popup');
add_action('wp_ajax_nopriv_get_added_to_cart_popup', 'tedee_get_added_to_cart_popup');

add_action('woocommerce_after_single_product', 'tedee_get_added_to_cart_popup');

function tedee_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    if (isset($_SESSION)) {
        $_SESSION['tedde_added_to_cart_product_id'] = $product_id;
    }
}

add_action('woocommerce_add_to_cart', 'tedee_add_to_cart', 10, 6);

// change woocommerce thumbnail and main image size
add_filter('woocommerce_gallery_thumbnail_size', function ($size) {
    return 'shop-thumb';
});

add_filter('woocommerce_gallery_image_size', function ($size) {
    return 'shop-main';
});


// Fill default order name fields with "billing_name_or_company" data
add_filter('woocommerce_checkout_posted_data', 'wpdesk_woocommerce_checkout_posted_data', 1000);
function wpdesk_woocommerce_checkout_posted_data($data)
{
    if ($data['billing_first_name'] == null) {
        $data['billing_first_name'] = $data['billing_name_or_company'];
    }
    if ($data['billing_last_name'] == null) {
        $data['billing_last_name'] = ' ';
    }
    if ($data['shipping_first_name'] == null) {
        $data['shipping_first_name'] = $data['billing_name_or_company'];
    }
    if ($data['shipping_last_name'] == null) {
        $data['shipping_last_name'] = ' ';
    }
    if ($data['billing_nip'] == null) {
        $data['billing_nip'] = $data['billing_vat_number'];
    }
    return $data;
}


// Change set discount name on invoice

if(!is_proshop()){
    add_filter('woocommerce_wfirma_invoice_data', 'wfirma_invoice_data', 10, 2);
}

function wfirma_invoice_data($data, \WC_Order $order)
{
    $items = $order->get_items();
    foreach ($items as $item_id => $item) {
        if ($item->meta_exists('_woosb_ids')) {
            foreach ($data['invoicecontents'] as $key => $invoicecontent) {
                if ($invoicecontent['invoicecontent']['name'] == $item->get_name()) {
                    $data['invoicecontents'][$key]['invoicecontent']['name'] = 'Discount';
                }
            }
            $item->set_name('Discount');
            $item->save();
        }
    }
    return $data;
}


add_action('init', 'create_topics_hierarchical_taxonomy', 0);

function create_topics_hierarchical_taxonomy()
{

// Knowledge Base topics taxonomy labels for the GUI
    $labels = array(
        'name' => _x('Topics', 'taxonomy general name'),
        'singular_name' => _x('Topic', 'taxonomy singular name'),
        'search_items' => __('Search Topics'),
        'popular_items' => __('Popular Topics'),
        'all_items' => __('All Topics'),
        'parent_item' => __('Parent Topic'),
        'parent_item_colon' => __('Parent Topic:'),
        'edit_item' => __('Edit Topic'),
        'update_item' => __('Update Topic'),
        'add_new_item' => __('Add New Topic'),
        'new_item_name' => __('New Topic Name'),
        'separate_items_with_commas' => __('Separate topics with commas'),
        'add_or_remove_items' => __('Add or remove topics'),
        'choose_from_most_used' => __('Choose from the most used topics'),
        'menu_name' => __('Topics'),
    );

// Register Knowledge Base topics taxonomy
    register_taxonomy('topics', 'knowledge_base', array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'update_count_callback' => '_update_post_term_count',
        'query_var' => true,
        'rewrite' => array('slug' => 'topic'),
    ));
}


// Search by post type
function custom_search_form($form, $value = "Search", $post_type = 'post')
{
    $form_value = (isset($value)) ? $value : esc_attr(apply_filters('the_search_query', get_search_query()));
    $form = '<form method="get" id="searchform" class="search-form" action="' . get_option('home') . '/" >
    <div>
        <input type="hidden" name="post_type" value="' . $post_type . '" />
        <input type="text" class="search-field" placeholder="' . esc_attr(__('Search')) . '" value="" name="s" id="s" />
        <input type="submit" id="searchsubmit" class="search-submit" value="' . esc_attr(__('Search')) . '" />
    </div>
    </form>';
    return $form;
}

// Archive titles without archive type
add_filter('get_the_archive_title', function ($title) {
    if (is_category()) {
        $title = single_cat_title('', false);
    } elseif (is_tag()) {
        $title = single_tag_title('', false);
    } elseif (is_author()) {
        $title = '<span class="vcard">' . get_the_author() . '</span>';
    } elseif (is_tax()) { //for custom post types
        $title = sprintf(__('%1$s'), single_term_title('', false));
    } elseif (is_post_type_archive()) {
        $title = post_type_archive_title('', false);
    }
    return $title;
});

function show_me_debug()
{

    return !empty($_GET['show_me_debug']);
}


function kia_display_order_data_in_admin($order)
{ ?>
    <div class="order_data_column">
        <h4><?php _e('GA'); ?></h4>
        <?php
        $url = $order->get_checkout_order_received_url();
        $tracked = get_post_meta($order->get_id(), '_ga_tracked', true);
        if ($tracked) {
            echo 'tracked';
        } else {
            echo '<small>' . $url . '</small>';
        }
        ?>
    </div>
<?php }

add_action('woocommerce_admin_order_data_after_order_details', 'kia_display_order_data_in_admin');



function my_custom_show_sale_price_at_cart( $old_display, $cart_item, $cart_item_key ) {

    /** @var WC_Product $product */
    $product = $cart_item['data'];

    if ( $product ) {

        return $product->get_price()?$product->get_price_html():$old_display;
    }

    return $old_display;

}
add_filter( 'woocommerce_cart_item_price', 'my_custom_show_sale_price_at_cart', 10, 3 );



