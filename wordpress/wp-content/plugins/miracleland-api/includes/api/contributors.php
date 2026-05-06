<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function ml_get_contributors( WP_REST_Request $req ) {
    global $wpdb;
    $table = $wpdb->prefix . 'ml_creation_contributors';
    $rows  = $wpdb->get_results( $wpdb->prepare(
        "SELECT c.uid, m.nickname, c.duty, c.created_at
         FROM `$table` c
         LEFT JOIN `{$wpdb->prefix}ml_members` m ON m.uid = c.uid
         WHERE c.creation_id = %d",
        $req['creation_id']
    ) );
    return rest_ensure_response( $rows );
}

function ml_add_contributor( WP_REST_Request $req ) {
    global $wpdb;
    $wpdb->replace( $wpdb->prefix . 'ml_creation_contributors', [
        'creation_id' => (int) $req['creation_id'],
        'uid'         => (int) $req->get_param('uid'),
        'duty'        => sanitize_text_field( $req->get_param('duty') ),
    ] );
    return rest_ensure_response( [ 'added' => true ] );
}

function ml_remove_contributor( WP_REST_Request $req ) {
    global $wpdb;
    $result = $wpdb->delete( $wpdb->prefix . 'ml_creation_contributors', [
        'creation_id' => $req['creation_id'],
        'uid'         => $req['uid'],
    ] );
    return $result ? rest_ensure_response( [ 'deleted' => true ] ) : ml_not_found();
}