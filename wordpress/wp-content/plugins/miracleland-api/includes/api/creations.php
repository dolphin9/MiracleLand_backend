<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function ml_get_creations( WP_REST_Request $req ) {
    global $wpdb;
    $table    = $wpdb->prefix . 'ml_creations';
    $page     = max( 1, (int) $req->get_param('page') ?: 1 );
    $per_page = min( 100, max( 1, (int) $req->get_param('per_page') ?: 20 ) );
    $offset   = ( $page - 1 ) * $per_page;
    $where    = '';
    $args     = [];
    if ( $cat = (int) $req->get_param('category_id') ) {
        $where  = 'WHERE category_id = %d';
        $args[] = $cat;
    }
    $args[] = $per_page;
    $args[] = $offset;
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM `$table` $where ORDER BY published_at DESC LIMIT %d OFFSET %d", ...$args
    ) );
    return rest_ensure_response( $rows );
}

function ml_get_creation( WP_REST_Request $req ) {
    global $wpdb;
    $table = $wpdb->prefix . 'ml_creations';
    $row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$table` WHERE creation_id = %d", $req['creation_id'] ) );
    return $row ? rest_ensure_response( $row ) : ml_not_found();
}

function ml_create_creation( WP_REST_Request $req ) {
    global $wpdb;
    $video = esc_url_raw( $req->get_param('video_url') ) ?: null;
    $audio = esc_url_raw( $req->get_param('audio_url') ) ?: null;
    $image = esc_url_raw( $req->get_param('image_url') ) ?: null;
    if ( ! $video && ! $audio && ! $image ) {
        return new WP_Error( 'no_content', '视频链接、音频链接、图片链接至少填写一项', [ 'status' => 400 ] );
    }
    $wpdb->insert( $wpdb->prefix . 'ml_creations', [
        'category_id'     => (int) $req->get_param('category_id'),
        'title'           => sanitize_text_field( $req->get_param('title') ),
        'published_at'    => sanitize_text_field( $req->get_param('published_at') ),
        'summary'         => sanitize_textarea_field( $req->get_param('summary') ) ?: null,
        'cover_image_url' => esc_url_raw( $req->get_param('cover_image_url') ) ?: null,
        'video_url'       => $video,
        'audio_url'       => $audio,
        'image_url'       => $image,
    ] );
    return rest_ensure_response( [ 'creation_id' => $wpdb->insert_id ] );
}

function ml_update_creation( WP_REST_Request $req ) {
    global $wpdb;
    $table = $wpdb->prefix . 'ml_creations';
    $data  = array_filter( [
        'title'           => sanitize_text_field( $req->get_param('title') ) ?: null,
        'summary'         => sanitize_textarea_field( $req->get_param('summary') ),
        'cover_image_url' => esc_url_raw( $req->get_param('cover_image_url') ),
        'video_url'       => esc_url_raw( $req->get_param('video_url') ),
        'audio_url'       => esc_url_raw( $req->get_param('audio_url') ),
        'image_url'       => esc_url_raw( $req->get_param('image_url') ),
    ], fn($v) => $v !== null );
    $result = $wpdb->update( $table, $data, [ 'creation_id' => $req['creation_id'] ] );
    return $result !== false ? rest_ensure_response( [ 'updated' => true ] ) : ml_not_found();
}

function ml_delete_creation( WP_REST_Request $req ) {
    global $wpdb;
    $wpdb->delete( $wpdb->prefix . 'ml_creation_contributors', [ 'creation_id' => $req['creation_id'] ] );
    $wpdb->delete( $wpdb->prefix . 'ml_creation_comments',     [ 'creation_id' => $req['creation_id'] ] );
    $result = $wpdb->delete( $wpdb->prefix . 'ml_creations', [ 'creation_id' => $req['creation_id'] ] );
    return $result ? rest_ensure_response( [ 'deleted' => true ] ) : ml_not_found();
}