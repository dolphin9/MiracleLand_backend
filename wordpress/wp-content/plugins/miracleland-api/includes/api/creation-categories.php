<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function ml_get_categories() {
    global $wpdb;
    return rest_ensure_response(
        $wpdb->get_results( "SELECT * FROM `{$wpdb->prefix}ml_creation_categories` ORDER BY category_id" )
    );
}

function ml_create_category( WP_REST_Request $req ) {
    global $wpdb;
    $name = sanitize_text_field( $req->get_param('name') );
    if ( empty( $name ) ) {
        return new WP_Error( 'missing_name', '类别名称不能为空', [ 'status' => 400 ] );
    }
    $wpdb->insert( $wpdb->prefix . 'ml_creation_categories', [ 'name' => $name ] );
    return rest_ensure_response( [ 'category_id' => $wpdb->insert_id ] );
}

function ml_update_category( WP_REST_Request $req ) {
    global $wpdb;
    $result = $wpdb->update(
        $wpdb->prefix . 'ml_creation_categories',
        [ 'name' => sanitize_text_field( $req->get_param('name') ) ],
        [ 'category_id' => $req['category_id'] ]
    );
    return $result !== false ? rest_ensure_response( [ 'updated' => true ] ) : ml_not_found();
}

function ml_delete_category( WP_REST_Request $req ) {
    global $wpdb;
    $result = $wpdb->delete( $wpdb->prefix . 'ml_creation_categories', [ 'category_id' => $req['category_id'] ] );
    return $result ? rest_ensure_response( [ 'deleted' => true ] ) : ml_not_found();
}