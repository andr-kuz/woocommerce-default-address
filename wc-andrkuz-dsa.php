<?php

/**
 * Plugin Name: WooCommerce: Адрес доставки по умолчанию
 * Description: Плагин заполняет поля адреса по умолчанию для мгновенного вывода всех способов доставки. Настройка плагина: Админ-панель Wordpress -> Настройки -> WooCommerce Default Address Fields
 * Version: 1.0.0
 * Author: Andrey Kuznetsov
 * Author URI: https://andrkuz.ru
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Admin Panel part Starts
 */

add_action( 'admin_menu', 'dsa_add_settings_page' );

function dsa_add_settings_page() {
  add_options_page( 'Woocommerce Default Address Fields', 'Woocommerce Default Address Fields', 'manage_options', 'dsa-andrkuz-plugin', 'dsa_render_plugin_settings_page' );
}

function dsa_render_plugin_settings_page() {
?>
    <h2>Укажите адрес доставки по умолчанию</h2>
    <form action="options.php" method="post">
<?php 
  settings_fields( 'dsa_plugin_options' );
  do_settings_sections( 'dsa_plugin' ); ?>
        <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save' ); ?>">
    </form>
<?php
}

function dsa_register_settings() {
  register_setting( 'dsa_plugin_options', 'dsa_plugin_options', 'dsa_plugin_options_validate' );

  add_settings_section( 'settings', '', 'dsa_plugin_section_text', 'dsa_plugin' );
  add_settings_field( 'dsa_plugin_setting_country', 'Страна', 'dsa_plugin_setting_country', 'dsa_plugin', 'settings' );
  add_settings_field( 'dsa_plugin_setting_postcode', 'Индекс', 'dsa_plugin_setting_postcode', 'dsa_plugin', 'settings' );
  add_settings_field( 'dsa_plugin_setting_city', 'Город', 'dsa_plugin_setting_city', 'dsa_plugin', 'settings' );
}

add_action( 'admin_init', 'dsa_register_settings' );

function dsa_plugin_section_text() {
  echo '<p>Некоторые способы доставки не отображаются пока не будет введен адрес доставки. Эти настройки позволяют отобразить их.</p>';
}

function dsa_plugin_setting_country() {
  $options = get_option( 'dsa_plugin_options' );
  echo "<select id='dsa_plugin_setting_country' name='dsa_plugin_options[country]'>";
  foreach (WC()->countries->get_shipping_countries() as $key => $country) {
    echo "<option value='". esc_attr($key) ."' ". selected($options['country'], $key) .">". $country ."</option>";
  }
  echo "</select>";
}

function dsa_plugin_setting_postcode() {
  $options = get_option( 'dsa_plugin_options' );
  echo "<input id='dsa_plugin_setting_postcode' name='dsa_plugin_options[postcode]' type='text' value='" . esc_attr( $options['postcode'] ) . "' />";
}

function dsa_plugin_setting_city() {
  $options = get_option( 'dsa_plugin_options' );
  echo "<input id='dsa_plugin_setting_city' name='dsa_plugin_options[city]' type='text' value='" . esc_attr( $options['city'] ) . "' />";
}


function dsa_plugin_options_validate( $input ) {
  try {
    $postcode =  wp_unslash(trim($input['postcode']));
    $country =  wp_unslash(trim($input['country']));
    if ( $postcode && ! WC_Validation::is_postcode( $postcode, $country ) ) {
      throw new Exception( __( 'Please enter a valid postcode / ZIP.', 'woocommerce' ) );
    }
  } catch ( Exception $e ) {
    if ( ! empty( $e ) ) {
      wp_die( $e->getMessage(), 'error' );
    }
  }
  return $input;
}

/**
 * Admin Panel part Ends
 */
 
/**
 * Backend approach
 */

/* add_filter( 'woocommerce_default_address_fields', 'set_default_address', 0); */

/* function set_default_address($fields) { */
/*   $options = get_option( 'dsa_plugin_options' ); */
/*   WC()->customer->set_shipping_postcode($options['postcode']); */
/*   WC()->customer->set_billing_postcode($options['postcode']); */
/*   WC()->customer->set_shipping_city($options['city']); */
/*   WC()->customer->set_billing_city($options['city']); */
/*   return $fields; */
/*  } */

/**
 * Frontend approach
 */

add_action( 'wp_footer', 'dsa_footer', 9999 );

function dsa_footer() {
  $options = get_option( 'dsa_plugin_options' );

  if ( is_cart () ) {
    $shipping_postcode = (WC()->customer->get_shipping_postcode()) ? WC()->customer->get_shipping_postcode() : $options['postcode'];
    $shipping_city = (WC()->customer->get_shipping_city()) ? WC()->customer->get_shipping_city() : $options['city'];
    echo "<script>
      if (document.querySelector('#calc_shipping_postcode') && document.querySelector('#calc_shipping_city')) {
        document.querySelector('#calc_shipping_postcode').value = '{$shipping_postcode}';
        document.querySelector('#calc_shipping_city').value = '{$shipping_city}';
        jQuery(function($){
          $('form.woocommerce-shipping-calculator button[type=\'submit\']').trigger('click');
        });
      }
    </script>";
  } elseif ( is_checkout() ) {
    $billing_postcode = (WC()->customer->get_billing_postcode()) ? WC()->customer->get_billing_postcode() : $options['postcode'];
    $billing_city = (WC()->customer->get_billing_city()) ? WC()->customer->get_billing_city() : $options['city'];
    echo "<script>
      if (document.querySelector('#billing_postcode') && document.querySelector('#billing_city')) {
        document.querySelector('#billing_postcode').value = '{$billing_postcode}';
        document.querySelector('#billing_city').value = '{$billing_city}';
        jQuery(function($){
          $(document.body).trigger('update_checkout');
        });
      }
</script>";
   }
}
