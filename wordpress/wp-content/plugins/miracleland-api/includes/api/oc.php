<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function ml_get_oc_list( WP_REST_Request $req ) {
    global $wpdb;
    $table = $wpdb->prefix . 'ml_oc';
    $rows  = $wpdb->get_results( "SELECT * FROM `$table` ORDER BY created_at DESC" );
    return rest_ensure_response( $rows );
}

function ml_get_oc( WP_REST_Request $req ) {
    global $wpdb;
    $table = $wpdb->prefix . 'ml_oc';
    $row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$table` WHERE oc_id = %d", $req['oc_id'] ) );
    return $row ? rest_ensure_response( $row ) : ml_not_found();
}

function ml_create_oc( WP_REST_Request $req ) {
    global $wpdb;
    $table = $wpdb->prefix . 'ml_oc';
    $uid   = (int) $req->get_param('uid');
    $exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT uid FROM `{$wpdb->prefix}ml_members` WHERE uid = %d", $uid
    ) );
    if ( ! $exists ) {
        return new WP_Error( 'invalid_uid', '成员不存在', [ 'status' => 400 ] );
    }
    $wpdb->insert( $table, [
        'uid'              => $uid,
        'portrait_url'     => esc_url_raw( $req->get_param('portrait_url') ) ?: null,
        'description_text' => wp_kses_post( $req->get_param('description_text') ) ?: null,
    ] );
    return rest_ensure_response( [ 'oc_id' => $wpdb->insert_id ] );
}

function ml_update_oc( WP_REST_Request $req ) {
    global $wpdb;
    $table = $wpdb->prefix . 'ml_oc';
    $data  = array_filter( [
        'portrait_url'     => esc_url_raw( $req->get_param('portrait_url') ) ?: null,
        'description_text' => wp_kses_post( $req->get_param('description_text') ) ?: null,
    ], fn($v) => $v !== null );
    $result = $wpdb->update( $table, $data, [ 'oc_id' => $req['oc_id'] ] );
    return $result !== false ? rest_ensure_response( [ 'updated' => true ] ) : ml_not_found();
}

function ml_delete_oc( WP_REST_Request $req ) {
    global $wpdb;
    $result = $wpdb->delete( $wpdb->prefix . 'ml_oc', [ 'oc_id' => $req['oc_id'] ] );
    return $result ? rest_ensure_response( [ 'deleted' => true ] ) : ml_not_found();
}