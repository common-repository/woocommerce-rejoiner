<?php
/**
 * Rejoiner Integration
 *
 * Allows Rejoiner tracking code to be inserted into store pages.
 *
 * @class 		WC_Rejoiner
 * @extends		WC_Integration
 */

class WC_Rejoiner extends WC_Integration {

	protected string $sess;
	protected string $rejoiner_src;
	protected string $rejoiner_baseurl;
	protected string $rejoiner_id;
	protected string $rejoiner_domain_name;
	protected string $rejoiner_api_key;
	protected string $rejoiner_send_to_list;
	protected string $rejoiner_list_id;
	protected string $rejoiner_optin_accept;
	protected string $rejoiner_optin_field_label;
	protected string $rejoiner_optin_status;
	protected string $rejoiner_optin_list_id;
	protected string $rejoiner_optin_show_checkout;
	protected string $rejoiner_optin_show_account;
	protected string $rejoiner_optin_show_registration;

	public function __construct() {

		if( isset( $_COOKIE['wp_woocommerce_session_' . COOKIEHASH ] ) ) {

			$this->sess = $_COOKIE['wp_woocommerce_session_' . COOKIEHASH ];

		} else {

			$this->sess = false;

		}

		$this->id = 'wc_rejoiner';
		$this->method_title = __( 'Rejoiner', 'woocommerce' );
		$this->method_description = __( 'Find these details on the Implementation page inside your Rejoiner dashboard.', 'woocommerce' );

		$this->rejoiner_src = 'https://cdn.rejoiner.com/js/v4/rj2.lib.js';
		$this->rejoiner_baseurl = 'https://rj2.rejoiner.com';

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->rejoiner_id = $this->get_option( 'rejoiner_id' );
		$this->rejoiner_domain_name = $this->get_option( 'rejoiner_domain_name' );
		$this->rejoiner_api_key = $this->get_option( 'rejoiner_api_key' );
		$this->rejoiner_send_to_list = $this->get_option( 'rejoiner_send_to_list_title' );
		$this->rejoiner_list_id = $this->get_option( 'rejoiner_list_id' );

		$this->rejoiner_optin_accept = $this->get_option( 'rejoiner_optin_accept' );
		$this->rejoiner_optin_field_label = $this->get_option( 'rejoiner_optin_field_label' );
		$this->rejoiner_optin_status = $this->get_option( 'rejoiner_optin_status' );
		$this->rejoiner_optin_list_id = apply_filters( 'wc_rejoiner_optin_list_id', $this->get_option( 'rejoiner_optin_list_id' ) );
		$this->rejoiner_optin_show_checkout = $this->get_option( 'rejoiner_optin_show_checkout' );
		$this->rejoiner_optin_show_account = $this->get_option( 'rejoiner_optin_show_account' );
		$this->rejoiner_optin_show_registration = $this->get_option( 'rejoiner_optin_show_registration' );

		// Actions
		add_action( 'woocommerce_update_options_integration_wc_rejoiner', array( $this, 'process_admin_options') );
		add_action( 'wp_loaded', array( $this, 'refill_cart' ) );

		// Tracking code
		add_action( 'wp_footer', array( $this, 'rejoiner_tracking_code' ) );

		// REST conversion
		add_action( 'woocommerce_payment_complete', array( $this, 'rejoiner_rest_convert' ), 1, 1 );

		// JS conversion
		add_action( 'woocommerce_thankyou', array( $this, 'rejoiner_conversion_code' ), 2, 1 );

		// AJAX callback
		add_action( 'wp_ajax_rejoiner_sync', array( $this, 'rejoiner_sync' ) );
		add_action( 'wp_ajax_nopriv_rejoiner_sync', array( $this, 'rejoiner_sync' ) );

		// Accept Marketing
		if( $this->rejoiner_optin_accept == 'yes' ) {

			if( $this->rejoiner_optin_show_checkout == 'yes' ) {
				add_filter( 'woocommerce_checkout_fields' , array( $this, 'rejoiner_checkout_fields' ) );
				add_action( 'woocommerce_checkout_order_processed', array( $this, 'rejoiner_save_checkout' ), 10, 3 );
			}

			if( $this->rejoiner_optin_show_registration == 'yes' ) {
				add_action( 'woocommerce_register_form', array( $this, 'rejoiner_register_fields' ) );
				add_action( 'woocommerce_created_customer', array( $this, 'rejoiner_save_register' ) );
			}

			if( $this->rejoiner_optin_show_account == 'yes' ) {
				add_action( 'woocommerce_edit_account_form_start', array( $this, 'rejoiner_account_fields' ) );
				add_action( 'woocommerce_save_account_details', array( $this, 'rejoiner_save_account' ) );
			}

		}

		// Promos
		add_filter( 'rejoiner_returnurl', array( $this, 'append_promo_returnurl' ), 1000, 3 );

	}

	function init_form_fields() {

		$this->form_fields = array(

			'rejoiner_id' => array(
				'title' 			=> __( 'Rejoiner Account', 'woocommerce' ),
				'description' 		=> __( 'Enter your unique Site ID', 'woocommerce' ),
				'type' 				=> 'text',
		    	'default' 			=> ''
			),
			'rejoiner_domain_name' => array(
				'title' 			=> __( 'Set Domain Name', 'woocommerce' ),
				'description' 		=> __( 'Enter your domain for the tracking code. Example: .domain.com or www.domain.com', 'woocommerce' ),
				'type' 				=> 'text',
		    	'default' 			=> ''
			),
			'rejoiner_api_key' => array(
				'title' 			=> __( 'Rejoiner API Key', 'woocommerce' ),
				'description' 		=> __( 'Enter your API Key', 'woocommerce' ),
				'type' 				=> 'text',
		    	'default' 			=> ''
			),
			'rejoiner_conv_cust_title' => array(
	            'title'     => 'Send Converted Customers to a List',
	            'type'     => 'title',
			),
			'rejoiner_send_to_list_title' => array(
				'title' 			=> __( 'Send New Customers to Rejoiner', 'woocommerce' ),
				'description' 		=> '',
				'type' 				=> 'checkbox',
				'label'				=> 'Send all new customers who complete an order to Rejoiner',
		    	'default' 			=> ''
			),
			'rejoiner_list_id' => array(
				'title' 			=> __( 'Rejoiner List ID', 'woocommerce' ),
				'description' 		=> __( 'Enter the ID of the Rejoiner List you would like to use.', 'woocommerce' ),
				'type' 				=> 'text',
		    	'default' 			=> ''
			),
			'rejoiner_accpt_mktg_title' => array(
	            'title'     => 'Accept Marketing Feature',
	            'type'     => 'title',
			),
			'rejoiner_optin_accept' => array(
				'title' 			=> __( 'Accepts Marketing Permissions Capture', 'woocommerce' ),
				'description' 		=> 'This will send the email addresses of all customers who opt-in to receive marketing from you to Rejoiner',
				'type' 				=> 'checkbox',
				'label'				=> 'Yes, enable Rejoiner to gather marketing permissions from customers at checkout.',
		    	'default' 			=> ''
			),
			'rejoiner_optin_field_label' => array(
				'title' 			=> __( 'Opt-In Field Label', 'woocommerce' ),
				'description' 		=> __( 'The text you want to use for your opt-in.', 'woocommerce' ),
				'type' 				=> 'text',
		    	'default' 			=> 'Subscribe to our newsletter'
			),
			'rejoiner_optin_status' => array(
				'title' 			=> __( 'Opt-In Status', 'woocommerce' ),
				'description' 		=> __( 'Default behavior of opt-in checkbox.', 'woocommerce' ),
				'type' 				=> 'select',
		    	'default' 			=> 'disabled',
		    	'options'			=> array(
			    	'disabled' => 'Disabled by default',
			    	'enabled' => 'Enabled by default'
		    	)
			),
			'rejoiner_optin_list_id' => array(
				'title' 			=> __( 'Accepts Marketing Rejoiner List ID', 'woocommerce' ),
				'description' 		=> __( 'Enter the ID of the Rejoiner List you would like to use.', 'woocommerce' ),
				'type' 				=> 'text',
		    	'default' 			=> ''
			),
			'rejoiner_optin_show_checkout' => array(
				'title' 			=> __( 'Opt-In Options', 'woocommerce' ),
				'type' 				=> 'checkbox',
				'label'				=> 'Show Opt-In on Checkout',
		    	'default' 			=> 'yes'
			),
			'rejoiner_optin_show_account' => array(
				'type' 				=> 'checkbox',
				'label'				=> 'Show Opt-In on Account Details',
			),
			'rejoiner_optin_show_registration' => array(
				'type' 				=> 'checkbox',
				'label'				=> 'Show Opt-In on Registration Form',
			)

		);

    }

	function rejoiner_checkout_fields( $fields ) {

		$default = ( $this->rejoiner_optin_status == 'enabled' ) ? 1 : 0 ;

		$fields['billing']['rejoiner_optin'] = array(
			'type'		=> 'checkbox',
			'label'     => $this->rejoiner_optin_field_label,
			'required'  => false,
			'class'     => array('form-row-wide'),
			'default'   => $default
		);

		return $fields;
	}

    function rejoiner_register_fields() {
		?>
		<p class="form-row form-row-wide">
        	<label for="rejoiner_optin">
        		<input type="checkbox" class="input-checkbox" name="rejoiner_optin" id="rejoiner_optin" value="subscribe" <?php $this->rejoiner_get_checked(true); ?> />
        		<?php echo $this->rejoiner_optin_field_label; ?>
        	</label>
        </p>
        <div class="clear"></div>
        <?php
    }

	function rejoiner_account_fields() {
		?>
		<p class="form-row form-row-wide">
        	<label for="rejoiner_optin">
        		<input type="checkbox" class="input-checkbox" name="rejoiner_optin" id="rejoiner_optin" value="subscribe" <?php $this->rejoiner_get_checked_account(true); ?> />
        		<?php echo $this->rejoiner_optin_field_label; ?>
        	</label>
        </p>
        <div class="clear"></div>
        <?php
    }


	function rejoiner_save_register( $customer_id ) {
	    if ( isset( $_POST['rejoiner_optin'] ) ) {
		    $this->rejoiner_rest_subscribe( $_POST['email'], $this->rejoiner_optin_list_id, null );
			update_user_meta( $customer_id, 'rejoiner_subscribe', date('U') );
	    }
	}

	function rejoiner_save_account() {
	    if ( isset( $_POST['rejoiner_optin'] ) ) {
		    $this->rejoiner_rest_subscribe( $_POST['account_email'], $this->rejoiner_optin_list_id, $_POST['account_first_name'] );
			update_user_meta( get_current_user_id(), 'rejoiner_subscribe', date('U') );
	    } else {
		    if( !empty( get_user_meta( get_current_user_id(), 'rejoiner_subscribe', true ) ) ) {
			    $this->rejoiner_rest_remove( $_POST['account_email'], $this->rejoiner_optin_list_id );
				update_user_meta( get_current_user_id(), 'rejoiner_unsubscribe', date('U') );
		    }
	    }
	}

	function rejoiner_save_checkout( $order_id, $posted_data, $order ) {
	    if ( isset( $_POST['rejoiner_optin'] ) ) {
		    $this->rejoiner_rest_subscribe( $_POST['billing_email'], $this->rejoiner_optin_list_id, $_POST['billing_first_name'] );
			update_post_meta( $order_id, 'rejoiner_subscribe', date('U') );

			if( get_current_user_id() > 0 ) {
				update_user_meta( get_current_user_id(), 'rejoiner_subscribe', date('U') );
			}

	    }
	}

	function rejoiner_get_checked( $echo = false ) {

		$checked = ( $this->rejoiner_optin_status == 'enabled' ) ? 'checked=checked' : '' ;

		if( $echo == true ) {

			echo $checked;

		} else {

			return $checked;

		}

	}

	function rejoiner_get_checked_account( $echo = false ) {

		$subscribe = get_user_meta( get_current_user_id(), 'rejoiner_subscribe', true );
		$unsubscribe = get_user_meta( get_current_user_id(), 'rejoiner_unsubscribe', true );

		if( $subscribe > $unsubscribe ) {

			$checked = 'checked=checked';

		} else {

			$checked = '';

		}

		if( $echo == true ) {

			echo $checked;

		} else {

			return $checked;

		}

	}

	function rejoiner_tracking_code() {

		global $rjconverted;

		$current_user = wp_get_current_user();

		if( $current_user instanceof WP_User && !empty( $current_user->user_email ) )
			$current_user_email = $current_user->user_email;
		else
			$current_user_email = false;

		if( ( is_cart() || is_checkout() ) && $rjconverted != true ) {

			global $woocommerce;

			$items = array();
			$savecart = array();

			foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {

				$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

				if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {

					if( $_thumb_id = get_post_thumbnail_id( $_product->get_id() ) ) {

    				    $thumb_id = $_thumb_id;

    				} else {

    					$thumb_id = get_post_thumbnail_id( wp_get_post_parent_id( $_product->get_id() ) );

    				}

					$thumb_size = apply_filters( 'wc_rejoiner_thumb_size', 'shop_thumbnail' );

					$thumb_url = wp_get_attachment_image_src( $thumb_id, $thumb_size, true );

					$product_cats = get_the_terms( $_product->get_id(), 'product_cat');

					if( is_array( $product_cats ) ) {

    					foreach( $product_cats as $cat ) {

		    				$cats[] = $cat->slug;

	    				}

	    				$product_cats_json = json_encode( $cats );

    				} else {

    				    $product_cats_json = null;

    				}

					if( !empty($thumb_url[0]) ) {

						$image = $thumb_url[0];

					} else {

						$image = wc_placeholder_img( 'shop_thumbnail' );

					}

					if( $_product->get_id() > 0 ) {

						$variantname = '';

						foreach ( $cart_item['variation'] as $name => $value ) {

		                      if ( '' === $value )
		                          continue;

		                      $taxonomy = wc_attribute_taxonomy_name( str_replace( 'attribute_pa_', '', urldecode( $name ) ) );

		                      if ( taxonomy_exists( $taxonomy ) ) {
		                          $term = get_term_by( 'slug', $value, $taxonomy );
		                          if ( ! is_wp_error( $term ) && $term && $term->name ) {
		                              $value = $term->name;
		                          }
		                          $label = wc_attribute_label( $taxonomy );

		                      } else {
		                         $value = apply_filters( 'woocommerce_variation_option_name', $value );
		                         $product_attributes = $cart_item['data']->get_attributes();
		                         if ( isset( $product_attributes[ str_replace( 'attribute_', '', $name ) ] ) ) {
		                             $label = wc_attribute_label( $product_attributes[ str_replace( 'attribute_', '', $name ) ]['name'] );
		                         } else {
		                             $label = $name;
		                         }
		                     }

							 $variantname.= ', ' . $label . ': ' . $value;
		                     $item_data[$name] = $value;

		                }

		                $variant = apply_filters( 'wc_rejoiner_cart_item_variant', $variantname );
		                $item_name = apply_filters( 'woocommerce_cart_item_name', $_product->get_title(), $cart_item, $cart_item_key ) . $variant;

		                $_item = array(
							'product_id' => $_product->get_id(),
							'name' => $this->escape_for_json( apply_filters( 'wc_rejoiner_cart_item_name', $item_name, $_product ) ),
							'item_qty' => $cart_item['quantity'],
							'price' => $this->format_money( $_product->get_price() ),
							'qty_price' => $this->format_money( $cart_item['line_total'] ),
							'image_url' => $this->format_image_url( $image ),
							'product_url' => get_permalink( $_product->get_id() ),
							'category' => $product_cats_json
						);

						$attributes = apply_filters( 'wc_rejoiner_cart_item_attributes', null, $item_data );

						if( is_array( $attributes ) ) {
							$_item['attribute_name'] = $attributes['attribute_name'];
							$_item['attribute_value'] = $this->escape_for_json( $attributes['attribute_value'] );
						}

		                $items[] = $_item;

	   					$savecart[] = array(
							'product_id' => $_product->get_id(),
							'item_qty' => $cart_item['quantity'],
							'variation_data' => $item_data
						);

					} else {

						$item_name = apply_filters( 'woocommerce_cart_item_name', $_product->get_title(), $cart_item, $cart_item_key);

						$items[] = array(
							'product_id' => $_product->get_id(),
							'name' => $this->escape_for_json( apply_filters( 'wc_rejoiner_cart_item_name', $item_name, $_product ) ),
							'item_qty' => $cart_item['quantity'],
							'price' => $this->format_money( $_product->get_price() ),
							'qty_price' => $this->format_money( $cart_item['line_total'] ),
							'image_url' => $this->format_image_url( $image ),
							'product_url' => get_permalink( $_product->get_id() ),
							'category' => $product_cats_json
						);

						$savecart[] = array(
							'product_id' => $_product->get_id(),
							'item_qty' => $cart_item['quantity']
						);

					}

				}

			}

			foreach ( WC()->cart->get_coupons() as $code => $coupon ) :
				$coupons[] = esc_attr( sanitize_title( $code ) );
			endforeach;

			set_transient( 'rjcart_' . $this->sess, $savecart, 168 * HOUR_IN_SECONDS);

			$cartdata = array(
				'cart_value' =>  $this->format_money( $woocommerce->cart->total ),
				'cart_item_count' => $woocommerce->cart->cart_contents_count,
			);

			if( !empty( $coupons ) )
				$cartdata['promo'] = implode( ',', $coupons );

			$js = $this->build_rejoiner_push( $items, $cartdata, $current_user_email );

		} elseif( $rjconverted != true ) {

			$rejoiner_id = $this->rejoiner_id;
			$rejoiner_domain_name = $this->rejoiner_domain_name;

			$rejoiner_script = $this->rejoiner_src;

			if( !empty( $rejoiner_id ) && !empty( $rejoiner_domain_name ) ) {

				$js = <<<EOF
<!-- Rejoiner Tracking - added by WooCommerceRejoiner -->

<script type='text/javascript'>
var _rejoiner = _rejoiner || [];
_rejoiner.push(['setAccount', '{$rejoiner_id}']);
_rejoiner.push(['setDomain', '{$rejoiner_domain_name}']);

(function() {
    var s = document.createElement('script'); s.type = 'text/javascript';
    s.async = true;
    s.src = '{$rejoiner_script}';
    var x = document.getElementsByTagName('script')[0];
    x.parentNode.insertBefore(s, x);
})();

EOF;

				if( $current_user_email != false ) {
					$js.= "_rejoiner.push(['setCustomerEmail', { 'email' : '$current_user_email' } ]);";
				}

				$sessionmetadata = apply_filters( 'rejoiner_sessionmetadata', false, $this->sess, WC()->cart->get_cart() );

				if( $sessionmetadata ) {
					$sessionmetadataencoded = $this->rejoiner_encode( $sessionmetadata );
					$js.= "_rejoiner.push(['setSessionMetadata', $sessionmetadataencoded]);";
				}

				if( is_product() ) {

					$_product = wc_get_product( get_the_ID() );
					$product_id = $_product->get_id();

					$name = $this->escape_for_json( apply_filters( 'wc_rejoiner_cart_item_name', $_product->get_name(), $_product ) );
					$product_url = get_permalink( $product_id );

					$thumb_id = get_post_thumbnail_id( $product_id );
					$thumb_size = apply_filters( 'wc_rejoiner_thumb_size', 'shop_thumbnail' );
					$thumb_url = wp_get_attachment_image_src( $thumb_id, $thumb_size, true );
					$thumb_url = $thumb_url[0];

					$product_cats = get_the_terms( $product_id, 'product_cat');
					$product_cats_json = '';

					if( is_array( $product_cats ) ) {

    					foreach( $product_cats as $cat ) {

	    					$cats[] = $cat->slug;

    					}

    					$product_cats_json = "'category':" . json_encode( $cats ) .',';

    				}

					$price = $this->format_money( $_product->get_price() );

					$js.= "
	_rejoiner.push(['trackProductView', {
	    'product_id': '$product_id',
	    'name': '$name',
	    $product_cats_json
	    'price': $price,
	    'product_url': '$product_url',
	    'image_url': '$thumb_url'
	}]);
					";

				}

			}

			$js.= '</script>
			<!-- End Rejoiner Tracking -->';

		}

		if( isset( $js ) )
			echo $js;

	}

	function rejoiner_sync() {

		global $woocommerce;

		$cart = array(
			'cart_value' =>  $this->format_money( $woocommerce->cart->total ),
			'cart_item_count' => $woocommerce->cart->cart_contents_count,
		);

		foreach ( WC()->cart->get_coupons() as $code => $coupon ) :
			$coupons[] = esc_attr( sanitize_title( $code ) );
		endforeach;

		if( !empty( $coupons ) )
			$cart['promo'] = implode( ',', $coupons );

		$returnUrl = wc_get_cart_url() . '?rjcart=' . $this->sess;
		$cart['return_url'] = apply_filters( 'rejoiner_returnurl', $returnUrl, $this->sess, $cart );

		wp_send_json( $cart );

	}

	function format_money( $number ) {

		$incents = $number * 100;
		$incents = intval( $incents );
		return $incents;

	}

	function format_description( $text ) {

		$text = str_replace( "'", "\'", strip_tags( $text ) );
		$text = str_replace( array("\r", "\n"), "", $text );

		return $text;

	}

	function format_image_url( $url ) {

		if( stripos( $url, 'http' ) === false ) {

			$url = get_site_url() . $url;

		}

		return $url;

	}

	function escape_for_json( $str ) {

		return str_ireplace( "'", "\'", $str );

	}

	function build_rejoiner_push( $items, $cart, $current_user_email ) {

		global $woocommerce, $rjremoved;

		$rejoiner_id = $this->rejoiner_id;
		$rejoiner_domain_name = $this->rejoiner_domain_name;
		$ajaxurl = admin_url( 'admin-ajax.php' );

		$returnUrl = wc_get_cart_url() . '?rjcart=' . $this->sess;

		$cart['return_url'] = apply_filters( 'rejoiner_returnurl', $returnUrl, $this->sess, $cart );

		$cartdata = $this->rejoiner_encode( $cart );
		$cartjs = "_rejoiner.push(['setCartData', $cartdata]);";
		$itemjs = '';
		$emailjs = '';
		$sessionjs = '';

		$sessionmetadata = apply_filters( 'rejoiner_sessionmetadata', false, $this->sess, $cart );

		if( $sessionmetadata ) {
			$sessionmetadataencoded = $this->rejoiner_encode( $sessionmetadata );
			$sessionjs = "_rejoiner.push(['setSessionMetadata', $sessionmetadataencoded]);";
		}

		if( $current_user_email != false )
			$emailjs = "_rejoiner.push(['setCustomerEmail', { 'email' : '$current_user_email' } ]);";

		foreach( $items as $item ) {

			$data = $this->rejoiner_encode( $item );
			$itemjs.= "_rejoiner.push(['setCartItem', $data]);\r\n";

		}

		$rejoiner_script = $this->rejoiner_src;

		if( !empty( $rejoiner_id ) && !empty( $rejoiner_domain_name ) ) {

			$js = <<<EOF
<!-- Rejoiner Tracking - added by WooCommerceRejoiner -->

<script type='text/javascript'>
	var _rejoiner = _rejoiner || [];
	_rejoiner.push(['setAccount', '{$rejoiner_id}']);
	_rejoiner.push(['setDomain', '{$rejoiner_domain_name}']);

	(function() {
	    var s = document.createElement('script'); s.type = 'text/javascript';
	    s.async = true;
	    s.src = '{$rejoiner_script}';
	    var x = document.getElementsByTagName('script')[0];
	    x.parentNode.insertBefore(s, x);
	})();

    $cartjs
    $itemjs
    $emailjs
    $sessionjs

	(function ($,r) {
	    var Rejoiner = {
	        removeInProgress: false,
	        init: function() {
	            console.log('initialized Rejoiner');
	            $(document).ready(function(){
	                $( document ).on(
	                    'click',
	                    'td.product-remove > a',
	                    Rejoiner.beginItemRemove
	                );
	                $( document ).on(
	                    'added_to_cart updated_wc_div updated_shipping_method updated_cart_totals',
	                    Rejoiner.requestUpdates
	                );
	            });
	        },
	        beginItemRemove: function(e) {
	            Rejoiner.removeInProgress = $(this).data('product_id');
	        },
	        requestUpdates: function(e) {
	            if (Rejoiner.removeInProgress !== false) {
	                console.log('removing item '+Rejoiner.removeInProgress+' from cart');
	                r.push(['removeCartItem', {product_id: Rejoiner.removeInProgress}]);
	                Rejoiner.removeInProgress = false;
	                console.log('requesting update setCartData');
	                $.post(
	                    '$ajaxurl',
	                    {action: 'rejoiner_sync'},
	                    Rejoiner.updateCartData
	                );
	            }
	        },
	        updateCartData: function(data) {
	            r.push(['setCartData', data]);
	            console.log( 'updated cart data with:');
	            console.log( data );
	        }
	    };

	    Rejoiner.init();

	})(jQuery,_rejoiner);

	(function ($,r) {
	    var RejoinerPromo = {
	        init: function() {
		        console.log('initialized RejoinerPromo');
	            $(document).ready(function(){
	                $( document ).on(
	                    'updated_cart_totals',
	                    RejoinerPromo.requestUpdates
	                );
	            });
	        },
	        requestUpdates: function(e) {
	            console.log('requesting promo update setCartData');
                $.post(
                    '$ajaxurl',
                    {action: 'rejoiner_sync'},
                    RejoinerPromo.updateCartData
                );
	        },
	        updateCartData: function(data) {
	            r.push(['setCartData', data]);
	            console.log( 'updated cart promo data with:');
	            console.log( data );
	        }
	    };

	    RejoinerPromo.init();

	})(jQuery,_rejoiner);

</script>
<!-- End Rejoiner Tracking -->
EOF;

		} else {

			$js = "\r\n<!-- WooCommerce Rejoiner ERROR: You must enter your details on the integrations settings tab. -->\r\n";

		}

		return $js;

	}

	function append_promo_returnurl( $returnUrl, $session, $cart  ) {

		if( isset( $cart['promo'] ) ) {

			$returnUrl = $returnUrl . '&rjpromo=' . $cart['promo'];

		}

		return $returnUrl;

	}


	function rejoiner_encode( $array ) {

		$json = '{';

		foreach( $array as $key => $val ) {

			if( is_array( json_decode( $val, true ) ) )
				$items[]= "'$key' : $val";
			else
				$items[]= "'$key' : '$val'";

		}

		$json.= implode( ', ', $items ) . '}';

		return $json;

	}

	function rejoiner_conversion_code( $order_id ) {

		global $rjconverted;

		$rjconverted = true;

		$rejoiner_id = $this->rejoiner_id;
		$rejoiner_domain_name = $this->rejoiner_domain_name;

		if ( !$rejoiner_id ) {
			return;
		}

        $order = wc_get_order( $order_id );

        $total = $this->format_money( $order->get_total() );
        $item_count = $order->get_item_count();

        $promos = $order->get_coupon_codes();
        if( is_array( $promos ) && ( count( $promos ) > 1 ) )
            $promo = implode( ',', $promos );
        elseif( is_array( $promos ) )
            $promo = array_pop( $promos );

        if( isset( $promo ) )
            $promojs = "'promo': '{$promo}',";
        else
            $promojs = '';

        $returnurl = $order->get_view_order_url();

        $line_items = $order->get_items();
        $items = array();

    	foreach ( $line_items as $item_key => $item ) {

    		$_product = $item->get_product();

    		$qty = $item['qty'];

    		$linetotal = $order->get_line_total( $item, true, true );

    		$thumb_id = get_post_thumbnail_id( $_product->get_id() );

			$thumb_size = apply_filters( 'wc_rejoiner_thumb_size', 'shop_thumbnail' );

			$thumb_url = wp_get_attachment_image_src( $thumb_id, $thumb_size, true );

			if( !empty($thumb_url[0]) ) {

				$image = $thumb_url[0];

			} else {

				$image = wc_placeholder_img( 'shop_thumbnail' );

			}

			$product_cats = get_the_terms( $_product->get_id(), 'product_cat');

			if( is_array( $product_cats ) ) {

				foreach( $product_cats as $cat ) {

    				$cats[] = $cat->slug;

				}

				$product_cats_json = json_encode( $cats );

			} else {

			    $product_cats_json = null;

			}

			$item_name = apply_filters( 'woocommerce_cart_item_name', $_product->get_title(), $item, $item_key );

      $_item = array(
				'product_id' => $_product->get_id(),
				'name' => $this->escape_for_json( apply_filters( 'wc_rejoiner_cart_item_name', $item_name, $_product ) ),
				'item_qty' => $qty,
				'price' => $this->format_money( $_product->get_price() ),
				'qty_price' => $this->format_money( $linetotal ),
				'image_url' => $this->format_image_url( $image ),
				'product_url' => get_permalink( $_product->get_id() ),
				'category' => $product_cats_json
			);

            $items[] = $this->rejoiner_encode( $_item );

    	}

    	$itemsjs = implode( ',', $items );

		$rejoiner_script = $this->rejoiner_src;

		$js = <<<EOF
<!-- Rejoiner JavaScript API Conversion - added by WooCommerce Rejoiner -->

<script type='text/javascript'>
	var _rejoiner = _rejoiner || [];
	_rejoiner.push(['setAccount', '{$rejoiner_id}']);
	_rejoiner.push(['setDomain', '{$rejoiner_domain_name}']);
	_rejoiner.push(['sendConversion', {
        cart_data: {
            'cart_value': {$total},
            'cart_item_count': {$item_count},
            'customer_order_number': '{$order_id}',
            {$promojs}
            'return_url': '{$returnurl}'
        },
        cart_items: [
            $itemsjs
        ]}
    ]);

	(function() {
	    var s = document.createElement('script');
	    s.type = 'text/javascript';
	    s.async = true;
	    s.src = '{$rejoiner_script}';
	    var x = document.getElementsByTagName('script')[0];
	    x.parentNode.insertBefore(s, x);
	})();
</script>

<!-- End Rejoiner JavaScript API Conversion -->
EOF;

		echo $js;

	}

	function rejoiner_rest_convert( $order_id ) {

		global $rjconverted;

		$rejoiner_id = $this->rejoiner_id;
		$rejoiner_domain_name = $this->rejoiner_domain_name;
		$rejoiner_api_key = $this->rejoiner_api_key;

		if( !$rejoiner_id || !$rejoiner_api_key ) {
			return;
		}

		$order = new WC_Order( $order_id );
		$email = $order->get_billing_email();
		$body = '{"email": "' . $email . '"}';

		$rejoiner_path = '/api/v1/' . $rejoiner_id . '/customer/convert/';
		$auth = 'Rejoiner ' . $rejoiner_api_key;

		$baseurl = $this->rejoiner_baseurl;
		$posturl = $baseurl . $rejoiner_path;

		$args = array(
		    'body' => $body,
		    'timeout' => '5',
		    'redirection' => '5',
		    'httpversion' => '1.0',
		    'blocking' => true,
		    'headers' => array(
			    'Authorization' => $auth,
			    'Content-Type' => 'application/json'
		    ),
		);

		$response = wp_remote_post( $posturl, $args );

		if( !is_wp_error( $response ) )
			$code = $response['response']['code'];

		if ( $code == 200 ) {

			$rjconverted = true;

			if( WP_DEBUG_LOG )
				error_log( "Rejoiner REST Conversion Success / HTTP Response: $code" );

		} else {

			$rjconverted = false;

			if( is_wp_error( $response ) ) {

				$error_message = $response->get_error_message();

				if( WP_DEBUG_LOG )
					error_log( "Rejoiner REST Conversion Error : $error_message" );

			} else {

				if( WP_DEBUG_LOG )
					error_log( "Rejoiner REST Conversion Error / HTTP Response: $code" );

			}

		}

		if( $this->rejoiner_send_to_list == 'yes' && !empty( $this->rejoiner_list_id ) ) {

			$payload = array(
				'email' => $email,
				'first_name' => $order->get_billing_first_name(),
				'last_name' => $order->get_billing_last_name(),
				'address1' => $order->get_billing_address_1(),
				'address2' => $order->get_billing_address_2(),
				'city' => $order->get_billing_city(),
				'state' => $order->get_billing_state(),
				'postal_code' => $order->get_billing_postcode(),
				'country' => $order->get_billing_country(),
				'phone' => $order->get_billing_phone()
			);

			$body = json_encode($payload);
			$rejoiner_path = '/api/v2/' . $rejoiner_id . '/lists/' . $this->rejoiner_list_id . '/contacts/';
			$auth = 'Rejoiner ' . $rejoiner_api_key;

			$baseurl = $this->rejoiner_baseurl;
			$posturl = $baseurl . $rejoiner_path;

			$args = array(
			    'body' => $body,
			    'timeout' => '5',
			    'redirection' => '5',
			    'httpversion' => '1.0',
			    'blocking' => true,
			    'headers' => array(
				    'Authorization' => $auth,
				    'Content-Type' => 'application/json'
			    ),
			);

			$response = wp_remote_post( $posturl, $args );

			if( !is_wp_error( $response ) )
				$code = $response['response']['code'];

			if ( $code == 200 ) {

				$rjconverted = true;

				if( WP_DEBUG_LOG )
					error_log( "Rejoiner REST Contact Add Success / HTTP Response: $code" );

			} else {

				$rjconverted = false;

				if( is_wp_error( $response ) ) {

					$error_message = $response->get_error_message();

					if( WP_DEBUG_LOG )
						error_log( "Rejoiner REST Contact Add Error : $error_message" );

				} else {

					if( WP_DEBUG_LOG )
						error_log( "Rejoiner REST Contact Add Error / HTTP Response: $code" );

				}

			}

		}


	}

	function rejoiner_rest_subscribe( $email, $list_id, $fname ) {

		$rejoiner_id = $this->rejoiner_id;
		$rejoiner_domain_name = $this->rejoiner_domain_name;
		$rejoiner_api_key = $this->rejoiner_api_key;

		if( !$rejoiner_id || !$rejoiner_api_key ) {
			return;
		}

		$body = '{"email": "' . $email . '","first_name": "' . $fname . '"}';
		$rejoiner_path = '/api/v1/' . $rejoiner_id . '/lists/' . $list_id . '/contacts/';
		$auth = 'Rejoiner ' . $rejoiner_api_key;

		$baseurl = $this->rejoiner_baseurl;
		$posturl = $baseurl . $rejoiner_path;

		$args = array(
		    'body' => $body,
		    'timeout' => '5',
		    'redirection' => '5',
		    'httpversion' => '1.0',
		    'blocking' => true,
		    'headers' => array(
			    'Authorization' => $auth,
			    'Content-Type' => 'application/json'
		    ),
		);

		$response = wp_remote_post( $posturl, $args );

		if( !is_wp_error( $response ) )
			$code = $response['response']['code'];

		if ( $code == 200 ) {

			if( WP_DEBUG_LOG )
				error_log( "Rejoiner REST Subscribe Success / HTTP Response: $code / $posturl $email, $list_id, $fname  " );

		} else {

			if( is_wp_error( $response ) ) {

				$error_message = $response->get_error_message();

				if( WP_DEBUG_LOG )
					error_log( "Rejoiner REST Subscribe Error : $error_message" );

			} else {

				if( WP_DEBUG_LOG )
					error_log( "Rejoiner REST Subscribe Error / HTTP Response: $code / $posturl $email, $list_id, $fname  " );

			}

		}

	}

	function rejoiner_rest_remove( $email, $list_id ) {

		$rejoiner_id = $this->rejoiner_id;
		$rejoiner_domain_name = $this->rejoiner_domain_name;
		$rejoiner_api_key = $this->rejoiner_api_key;

		if( !$rejoiner_id || !$rejoiner_api_key ) {
			return;
		}

		$body = '{"email": "' . $email . '"}';
		$rejoiner_path = '/api/v1/' . $rejoiner_id . '/lists/' . $list_id . '/contacts/remove/';
		$auth = 'Rejoiner ' . $rejoiner_api_key;

		$baseurl = $this->rejoiner_baseurl;
		$posturl = $baseurl . $rejoiner_path;

		$args = array(
		    'body' => $body,
		    'timeout' => '5',
		    'redirection' => '5',
		    'httpversion' => '1.0',
		    'blocking' => true,
		    'headers' => array(
			    'Authorization' => $auth,
			    'Content-Type' => 'application/json'
		    ),
		);

		$response = wp_remote_post( $posturl, $args );

		if( !is_wp_error( $response ) )
			$code = $response['response']['code'];

		if ( $code == 200 ) {

			if( WP_DEBUG_LOG )
				error_log( "Rejoiner REST Remove from List Success / HTTP Response: $code / $posturl $email, $list_id  " );

		} else {

			if( is_wp_error( $response ) ) {

				$error_message = $response->get_error_message();

				if( WP_DEBUG_LOG )
					error_log( "Rejoiner REST Remove from List Error : $error_message" );

			} else {

				if( WP_DEBUG_LOG )
					error_log( "Rejoiner REST Remove from List Error / HTTP Response: $code / $posturl $email, $list_id, $fname  " );

			}

		}

	}

	function rejoiner_rest_unsubscribe( $email ) {

		$rejoiner_id = $this->rejoiner_id;
		$rejoiner_domain_name = $this->rejoiner_domain_name;
		$rejoiner_api_key = $this->rejoiner_api_key;

		if( !$rejoiner_id || !$rejoiner_api_key ) {
			return;
		}

		$body = '{ "email": "' . $email . '" }';

		$rejoiner_path = '/api/v1/' . $rejoiner_id . '/customer/unsubscribe/';
		$auth = 'Rejoiner ' . $rejoiner_api_key;

		$baseurl = $this->rejoiner_baseurl;
		$posturl = $baseurl . $rejoiner_path;

		$args = array(
		    'body' => $body,
		    'timeout' => '5',
		    'redirection' => '5',
		    'httpversion' => '1.0',
		    'blocking' => true,
		    'headers' => array(
			    'Authorization' => $auth,
			    'Content-Type' => 'application/json'
		    ),
		);

		$response = wp_remote_post( $posturl, $args );

		if( !is_wp_error( $response ) )
			$code = $response['response']['code'];

		if ( $code == 200 ) {

			if( WP_DEBUG_LOG )
				error_log( "Rejoiner REST Unsubscribe Success / HTTP Response: $code" );

		} else {

			if( is_wp_error( $response ) ) {

				$error_message = $response->get_error_message();

				if( WP_DEBUG_LOG )
					error_log( "Rejoiner REST Unsubscribe Error : $error_message" );

			} else {

				if( WP_DEBUG_LOG )
					error_log( "Rejoiner REST Unsubscribe Error / HTTP Response: $code" );

			}

		}

	}

	function refill_cart() {

		if ( isset( $_GET['rjcart'] ) ) {

			global $woocommerce;

			$this_sess = $_GET['rjcart'];

			$carturl = wc_get_cart_url();

			$rjcart = get_transient( 'rjcart_' . $this_sess );

			if( !empty( $rjcart ) ) {

				$woocommerce->cart->empty_cart();

				foreach( $rjcart as $product ) {

					if( !empty( $product['variation_id'] ) && $product['variation_id'] > 0 ) {

						$woocommerce->cart->add_to_cart(
							$product['product_id'],
							$product['item_qty'],
							$product['variation_id'],
							$product['variation_data']
						);

					} else {

						$woocommerce->cart->add_to_cart(
							$product['product_id'],
							$product['item_qty']
						);

					}

				}

				if( isset( $_GET['rjpromo'] ) ) {

					$promos = explode( ',', $_GET['rjpromo'] );

					foreach( $promos as $promo ) {

						if ( !WC()->cart->has_discount( $promo ) ) {
							WC()->cart->add_discount( $promo );
						}

					}

				}

				$utm_source = ( isset( $_GET['utm_source'] ) ) ? $_GET['utm_source'] : 'rejoiner' ;
				$utm_medium = ( isset( $_GET['utm_medium'] ) ) ? $_GET['utm_medium'] : 'email' ;
				$utm_campaign = ( isset( $_GET['utm_campaign'] ) ) ? $_GET['utm_campaign'] : 'email' ;
				$utm_content = ( isset( $_GET['utm_content'] ) ) ? $_GET['utm_content'] : 'default' ;

				header( "location:$carturl?utm_source=$utm_source&utm_medium=$utm_medium&utm_campaign=$utm_campaign&utm_content=$utm_content" );
				exit;

			}

		}

	}

}
?>