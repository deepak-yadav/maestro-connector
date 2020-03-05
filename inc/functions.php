<?php

namespace Bluehost\Maestro;

use WP_Error;

/**
 * Stores a Bluehost Maestro key in user meta
 *
 * @since 1.0
 *
 * @param int    $user_id ID of the user to store the key for
 * @param string $key     The Maestro key generated by the platform
 *
 * @return int|false Meta ID on success, false on failure
 */
function add_maestro_key( $user_id, $key ) {

	return add_user_meta( $user_id, 'bh_maestro_key', $key, true );

}

/**
 * Gets the stored Maestro key for a user
 *
 * @since 1.0
 *
 * @param int $user_id ID of user to get maestro key from
 *
 * @return string|false The Maestro key from the database, or false if not found or empty
 */
function get_maestro_key( $user_id = 0 ) {

	// If not specified, use the current user
	if ( ! $user_id ) {
		$user_id = wp_get_current_user()->ID;
	}

	$key = get_user_meta( $user_id, 'bh_maestro_key', true );

	if ( ! $key ) {
		// If for some reason they meta key is falsey, go ahead and delete it to clean up
		delete_maestro_key( $user_id );
		return false;
	}

	return $key;
}

/**
 * Update an existing Maestro key
 *
 * @since 1.0
 *
 * @param int    $user_id ID of the user to update Maestro key for
 * @param string $key     New Maestro key to store
 *
 * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure.
 */
function update_maestro_key( $user_id, $key ) {

	$current_key = get_maestro_key( $user_id );

	// We don't have an existing key, so let's store a new one
	if ( ! $current_key ) {
		return add_maestro_key( $user_id, $key );
	}

	return update_user_meta( $user_id, 'bh_maestro_key', $key, $current_key );

}

/**
 * Delete the stored Maestro key
 *
 * @since 1.0
 *
 * @param int $user_id ID of the user to delete the key for
 *
 * @return boolean True on success, false on failure.
 */
function delete_maestro_key( $user_id ) {

	return delete_user_meta( $user_id, 'bh_maestro_key' );

}

/**
 * Sends Maestro key to platform, validates and returns Web Pro information
 *
 * @since 1.0
 *
 * @param string $key The Maestro key to check
 *
 * @return object Response data from Maestro Platform
 */
function get_maestro_info( $key ) {

	$args = array();

	// @todo Reach out to platform to get web pro information
	// $response = wp_remote_post( 'http:webpro.test/wp-json/bluehost/maestro/', $args );

	// return $response['body'];
	// @todo Remove temp test data
	// return array(
	// 	'key'      => $key,
	// 	'name'     => 'William Earnhardt',
	// 	'email'    => 'wearnhardt@gmail.com',
	// 	'location' => 'Cary, NC, USA',
	// );
	return array(
		'key'      => $key,
		'name'     => 'Tony Stark',
		'email'    => 'ironman@bh.test',
		'location' => 'Malibu, CA, USA',
	);

}

/**
 * Check whether a specific user is a maestro
 *
 * @since 1.0
 *
 * @param int User ID or WP_User object
 *
 * @return bool Whether the user is a maestro or not
 */
function is_user_maestro( $user_id = '' ) {

	// If not specified, use the current user
	if ( ! $user_id ) {
		$user = wp_get_current_user();
	} else {
		$user = get_userdata( $user_id );
	}

	// If they aren't a real user, then they definitely aren't a Maestro!
	if ( ! $user || ! $user->exists() ) {
		return false;
	}

	$key = get_maestro_key( $user->ID );

	// If the Maestro key exists and is truthy, this indicates Maestro status
	return (bool) $key;

}

/**
 * Revoke Maestro status from a user
 *
 * @since 1.0
 *
 * @param int $user_id ID of the user to revoke Maestro status
 *
 * @return true|WP_Error True on successful demotion, WP_Error if user is not a maestro
 */
function revoke_maestro( $user_id ) {

	// Let's make sure they are a Maestro first
	if ( ! is_user_maestro( $user_id ) ) {
		return new WP_Error(
			'user_not_maestro',
			__( 'User is not a Bluehost Maestro', 'bluehost-maestro' )
		);
	}

	$deleted = delete_maestro_key( $user_id );

	// Kick an error if we failed to delete the key for some reason
	if ( ! $deleted ) {
		return new WP_Error(
			'maestro_revoke_failed',
			__( 'Failed to revoke Maestro status', 'bluehost-maestro' )
		);
	}

	// If we successfully deleted a key, then let's also demote the user
	$user = get_userdata( $user_id );
	$user->set_role( 'subscriber' );

	// @todo Notify platform that connection is revoked

	return true;

}