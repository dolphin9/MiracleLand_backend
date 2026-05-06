<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function ml_get_members( WP_REST_Request $req ) {
    global $wpdb;
    $table    = $wpdb->prefix . 'ml_members';
    $page     = max( 1, (int) $req->get_param('page') ?: 1 );
    $per_page = min( 100, max( 1, (int) $req->get_param('per_page') ?: 20 ) );
    $offset   = ( $page - 1 ) * $per_page;
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT uid, nickname, role_name, phone, address, note, created_at FROM `$table` LIMIT %d OFFSET %d",
        $per_page, $offset
    ) );
    return rest_ensure_response( $rows );
}

function ml_get_member( WP_REST_Request $req ) {
    global $wpdb;
    $table = $wpdb->prefix . 'ml_members';
    $row   = $wpdb->get_row( $wpdb->prepare(
        "SELECT uid, nickname, role_name, phone, address, note, created_at FROM `$table` WHERE uid = %d",
        $req['uid']
    ) );
    return $row ? rest_ensure_response( $row ) : ml_not_found();
}

function ml_create_member( WP_REST_Request $req ) {
    global $wpdb;
    $table = $wpdb->prefix . 'ml_members';
    $data  = [
        'nickname'  => sanitize_text_field( $req->get_param('nickname') ),
        'role_name' => sanitize_text_field( $req->get_param('role_name') ),
        'phone'     => sanitize_text_field( $req->get_param('phone') ) ?: null,
        'address'   => sanitize_text_field( $req->get_param('address') ) ?: null,
        'note'      => sanitize_textarea_field( $req->get_param('note') ) ?: null,
    ];
    if ( empty( $data['nickname'] ) || empty( $data['role_name'] ) ) {
        return new WP_Error( 'missing_fields', '昵称和身份为必填项', [ 'status' => 400 ] );
    }
    $wpdb->insert( $table, $data );
    return rest_ensure_response( [ 'uid' => $wpdb->insert_id ] );
}

function ml_update_member( WP_REST_Request $req ) {
    global $wpdb;
    $table = $wpdb->prefix . 'ml_members';
    $data  = array_filter( [
        'nickname'  => sanitize_text_field( $req->get_param('nickname') ) ?: null,
        'role_name' => sanitize_text_field( $req->get_param('role_name') ) ?: null,
        'phone'     => sanitize_text_field( $req->get_param('phone') ),
        'address'   => sanitize_text_field( $req->get_param('address') ),
        'note'      => sanitize_textarea_field( $req->get_param('note') ),
    ], fn($v) => $v !== null );
    $result = $wpdb->update( $table, $data, [ 'uid' => $req['uid'] ] );
    return $result !== false ? rest_ensure_response( [ 'updated' => true ] ) : ml_not_found();
}

function ml_delete_member( WP_REST_Request $req ) {
    global $wpdb;
    $result = $wpdb->delete( $wpdb->prefix . 'ml_members', [ 'uid' => $req['uid'] ] );
    return $result ? rest_ensure_response( [ 'deleted' => true ] ) : ml_not_found();
}