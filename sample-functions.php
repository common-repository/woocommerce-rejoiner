<?php

// Use this filter to modify the item name that is passed to Rejoiner

add_filter( 'wc_rejoiner_cart_item_name', 'my_wcrj_item_name' );

function my_wcrj_item_name( $name ) {

	return str_ireplace( array( '<br>','<br/>' ), '', $name );

}

// Use this filter to modify the description of the item variation which is appended to the the item title.

add_filter( 'wc_rejoiner_cart_item_variant', 'my_wcrj_item_variant' );

function my_wcrj_item_variant( $variantname ) {

	return null;

}

// Use this filter to specify the image size to be passed to rejoiner.
// You can return a named image size, eg: 'medium' or an array of dimensions, eg: array( 800, 600 )

add_filter( 'wc_rejoiner_thumb_size', 'my_wcrj_thumb_size' );

function my_wcrj_thumb_size( $size ) {

	return array( 800, 600 );

}

// Use this filter to pass select attribute name and value to setCartItem

add_filter( 'wc_rejoiner_cart_item_attributes', 'custom_filter_attributes', 10, 2 );

function custom_filter_attributes( $attributes, $itematts ) {

	foreach( $itematts as $key => $val ) {

		if( $key == 'attribute_flavor' ) {

			$attributes = array();
			$attributes['attribute_name'] = $key;
			$attributes['attribute_value'] = $val;

		}

	}

	return $attributes;

}

// Use this filter to pass session data to Rejoiner

add_filter( 'rejoiner_sessionmetadata', 'custom_session_metadata' );

function custom_session_metadata() {

	$sessiondata = array( 'customer_type' => 'wholesale' );

	return $sessiondata;

}

// Use this filter to modify the opt-in list ID, eg: different customer roles - different lists

add_filter( 'wc_rejoiner_optin_list_id', 'custom_optin_list', 10, 1 );

function custom_optin_list( $option ) {

	if( current_user_can( 'do_something' ) )
		return 'XXXXXXX';
	else
		return $option;

}