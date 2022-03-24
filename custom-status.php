<?php
/**
 * Plugin Name: Change Orders status
 * Description: Change all orders status to custom status after payment.
 * Version: 1.0.1
 * Author: Paweł Targosiński
 */


/**
 * Register new status
 **/
function register_awaiting_shipment_order_status()
{
    register_post_status('wc-awaiting-support', array(
        'label' => 'Awaiting support',
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Awaiting shipment <span class="count">(%s)</span>', 'Awaiting shipment <span class="count">(%s)</span>')
    ));

    register_post_status('wc-voucher', array(
        'label' => 'Voucher',
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Voucher <span class="count">(%s)</span>', 'Voucher <span class="count">(%s)</span>')
    ));


}

add_action('init', 'register_awaiting_shipment_order_status');

// Add to list of WC Order statuses
function add_awaiting_shipment_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-pending' === $key) {
            $new_order_statuses['wc-awaiting-support'] = 'Awaiting support';
            $new_order_statuses['wc-voucher'] = 'Voucher';
        }
    }

    return $new_order_statuses;
}

add_filter('wc_order_statuses', 'add_awaiting_shipment_to_order_statuses');


function get_product_count_in_order($order_id, $product_id)
{
    $count = 0;
    if (!empty($order_id)) {
        $order_object = wc_get_order($order_id);
        $order_items = $order_object->get_items(array('line_item'));
        foreach ($order_items as $item_id => $item) {
            if ($product_id == $item->get_product_id()) {
                $count += $item->get_quantity();
            }
        }
    }
    return $count;
}



//woocommerce_payment_complete_order_status

add_filter('woocommerce_payment_complete_order_status', 'wc_auto_complete_paid_order', 0, 2);
function wc_auto_complete_paid_order($status, $order_id)
{
//jeśli mamy produkt simple cylinder w koszyku ustawiamy awaiting support w przeciwnym razie normalny status


    $logger = wc_get_logger();

    $posts = get_posts(array(
        'post_type' => 'page',
        'fields' => 'ids',
        'nopaging' => true,
        'lang' => pll_get_post_language($order_id), // use language slug in the query
        'meta_key' => '_wp_page_template',
        'meta_value' => 'template-thank_you_for_your_order.php'
    ));

    $logger->info( wc_print_r( pll_get_post_language($order_id), true ), array( 'source' => 'awaiting-support' , 'language'=>1) );

    $logger->info( wc_print_r( $posts, true ), array( 'source' => 'awaiting-support' , 'posts array'=>1) );

    $template_page_id = $posts[0];
    $simple_cylinder_product = get_field('products_id', $template_page_id)['cylinder_to_remove'];

    $logger->info( wc_print_r( $simple_cylinder_product, true ), array( 'source' => 'awaiting-support', 'cylinder product'=>1 ) );

    $count = get_product_count_in_order($order_id, $simple_cylinder_product);

    $logger->info( wc_print_r($count, true ), array( 'source' => 'awaiting-support', 'count product'=>1 ) );


    //if voucher used for payment then change status to on-hold
    $order_object = wc_get_order($order_id);
    if($order_object && class_exists('WC_PDF_Product_Vouchers_Redemption_Handler')){
        $voucher = new WC_PDF_Product_Vouchers_Redemption_Handler();
        $coupon = $voucher->get_order_total_mpv_credit_used($order_object);
        if ($coupon>0){
            $logger->info("voucher used in payment ".$order_id, array( 'source' => 'awaiting-support' ) );
            return 'wc-voucher';
        }
    }

    if ($count) {
        $logger->info( wc_print_r( 'custom status', true ), array( 'source' => 'awaiting-support' ) );
        return 'wc-awaiting-support';
    } else {
        $logger->info("normal processing ".$order_id, array( 'source' => 'awaiting-support' ) );



    }
    return $status;
}



function gc_is_editable($editable, $order)
{
    if ($order->get_status() == 'awaiting-support') {
        $editable = true;
    }
    return $editable;
}

add_filter('wc_order_is_editable', 'gc_is_editable', 10, 2);


////////////////custom mail on status awaiting-support

add_action("woocommerce_order_status_changed", "tedee_publication_notification");

function tedee_publication_notification($order_id, $checkout = null)
{

    global $woocommerce;
    $order = new WC_Order($order_id);

    $status = $order->get_status();

    if ($status === 'awaiting-support') {
        // Create a mailer

        $mailer = $woocommerce->mailer();

        $lang = pll_get_post_language($order_id);




        //$subject = printf(pll__('on-hold-text'), esc_html( $order->get_order_number() ));
        $header = str_replace('%s', $order->get_id(), pll_translate_string('awaiting-support-title',$lang));
        $subject = str_replace('%s', $order->get_id(), pll_translate_string('awaiting-support-subject',$lang));
        $message_body = get_custom_email_html($order, $header, $mailer);
        $headers = "Content-Type: text/html\r\n";

        $emailAddress = $order->get_billing_email();

        // Client email, email subject and message.
        $mailer->send($emailAddress, $subject, $message_body, $headers);


    }

}

function get_custom_email_html($order, $heading = false, $mailer)
{

    $template = 'emails/email-awaiting-support.php';

    return wc_get_template_html($template, array(
        'order' => $order,
        'email_heading' => $heading,
        'sent_to_admin' => false,
        'plain_text' => false,
        'email' => $mailer
    ));

}
