<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once __DIR__ . '/api/members.php';
require_once __DIR__ . '/api/oc.php';
require_once __DIR__ . '/api/creation-categories.php';
require_once __DIR__ . '/api/creations.php';
require_once __DIR__ . '/api/contributors.php';
require_once __DIR__ . '/api/comments.php';

add_action( 'rest_api_init', 'ml_register_routes' );

function ml_register_routes() {
    $ns = 'miracleland/v1';

    register_rest_route( $ns, '/members', [
        [ 'methods' => 'GET',  'callback' => 'ml_get_members',   'permission_callback' => 'ml_admin_only' ],
        [ 'methods' => 'POST', 'callback' => 'ml_create_member', 'permission_callback' => 'ml_admin_only' ],
    ]);
    register_rest_route( $ns, '/members/(?P<uid>\d+)', [
        [ 'methods' => 'GET',    'callback' => 'ml_get_member',    'permission_callback' => 'ml_admin_only' ],
        [ 'methods' => 'PUT',    'callback' => 'ml_update_member', 'permission_callback' => 'ml_admin_only' ],
        [ 'methods' => 'DELETE', 'callback' => 'ml_delete_member', 'permission_callback' => 'ml_admin_only' ],
    ]);

    register_rest_route( $ns, '/oc', [
        [ 'methods' => 'GET',  'callback' => 'ml_get_oc_list', 'permission_callback' => '__return_true' ],
        [ 'methods' => 'POST', 'callback' => 'ml_create_oc',   'permission_callback' => 'ml_admin_only' ],
    ]);
    register_rest_route( $ns, '/oc/(?P<oc_id>\d+)', [
        [ 'methods' => 'GET',    'callback' => 'ml_get_oc',    'permission_callback' => '__return_true'  ],
        [ 'methods' => 'PUT',    'callback' => 'ml_update_oc', 'permission_callback' => 'ml_admin_only' ],
        [ 'methods' => 'DELETE', 'callback' => 'ml_delete_oc', 'permission_callback' => 'ml_admin_only' ],
    ]);

    register_rest_route( $ns, '/creation-categories', [
        [ 'methods' => 'GET',  'callback' => 'ml_get_categories',  'permission_callback' => '__return_true' ],
        [ 'methods' => 'POST', 'callback' => 'ml_create_category', 'permission_callback' => 'ml_admin_only' ],
    ]);
    register_rest_route( $ns, '/creation-categories/(?P<category_id>\d+)', [
        [ 'methods' => 'PUT',    'callback' => 'ml_update_category', 'permission_callback' => 'ml_admin_only' ],
        [ 'methods' => 'DELETE', 'callback' => 'ml_delete_category', 'permission_callback' => 'ml_admin_only' ],
    ]);

    register_rest_route( $ns, '/creations', [
        [ 'methods' => 'GET',  'callback' => 'ml_get_creations',   'permission_callback' => '__return_true' ],
        [ 'methods' => 'POST', 'callback' => 'ml_create_creation', 'permission_callback' => 'ml_admin_only' ],
    ]);
    register_rest_route( $ns, '/creations/(?P<creation_id>\d+)', [
        [ 'methods' => 'GET',    'callback' => 'ml_get_creation',    'permission_callback' => '__return_true'  ],
        [ 'methods' => 'PUT',    'callback' => 'ml_update_creation', 'permission_callback' => 'ml_admin_only' ],
        [ 'methods' => 'DELETE', 'callback' => 'ml_delete_creation', 'permission_callback' => 'ml_admin_only' ],
    ]);

    register_rest_route( $ns, '/creations/(?P<creation_id>\d+)/contributors', [
        [ 'methods' => 'GET',  'callback' => 'ml_get_contributors', 'permission_callback' => '__return_true' ],
        [ 'methods' => 'POST', 'callback' => 'ml_add_contributor',  'permission_callback' => 'ml_admin_only' ],
    ]);
    register_rest_route( $ns, '/creations/(?P<creation_id>\d+)/contributors/(?P<uid>\d+)', [
        [ 'methods' => 'DELETE', 'callback' => 'ml_remove_contributor', 'permission_callback' => 'ml_admin_only' ],
    ]);

    register_rest_route( $ns, '/creations/(?P<creation_id>\d+)/comments', [
        [ 'methods' => 'GET',  'callback' => 'ml_get_comments',   'permission_callback' => '__return_true' ],
        [ 'methods' => 'POST', 'callback' => 'ml_create_comment', 'permission_callback' => 'is_user_logged_in' ],
    ]);
    register_rest_route( $ns, '/comments/(?P<comment_id>\d+)', [
        [ 'methods' => 'DELETE', 'callback' => 'ml_delete_comment', 'permission_callback' => 'ml_admin_only' ],
    ]);
}

function ml_admin_only() {
    return current_user_can( 'manage_options' );
}

function ml_not_found() {
    return new WP_Error( 'not_found', '记录不存在', [ 'status' => 404 ] );
}