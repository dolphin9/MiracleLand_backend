<?php
/**
 * Plugin Name: MiracleLand API
 * Description: 为 MiracleLand 社区提供自定义数据表（ml_ 前缀）及 REST API 端点。
 * Version:     1.0.0
 * Author:      MiracleLand
 * License:     GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'ML_PLUGIN_VERSION', '1.0.0' );
define( 'ML_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// ── 激活时建表 ────────────────────────────────────────────────
register_activation_hook( __FILE__, 'ml_create_tables' );

function ml_create_tables() {
    global $wpdb;
    $c = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $p = $wpdb->prefix; // 通常为 wp_

    dbDelta( "CREATE TABLE {$p}ml_members (
        uid        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        nickname   VARCHAR(50)  NOT NULL,
        role_name  VARCHAR(50)  NOT NULL,
        phone      VARCHAR(32)  NULL,
        address    VARCHAR(255) NULL,
        note       TEXT         NULL,
        created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (uid),
        UNIQUE KEY uk_ml_members_phone (phone),
        KEY idx_ml_members_nickname (nickname)
    ) $c;" );

    dbDelta( "CREATE TABLE {$p}ml_oc (
        oc_id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        uid              BIGINT UNSIGNED NOT NULL,
        portrait_url     VARCHAR(500) NULL,
        description_text TEXT         NULL,
        created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (oc_id),
        KEY idx_ml_oc_uid (uid)
    ) $c;" );

    dbDelta( "CREATE TABLE {$p}ml_creation_categories (
        category_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name        VARCHAR(50)  NOT NULL,
        created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (category_id),
        UNIQUE KEY uk_ml_category_name (name)
    ) $c;" );

    dbDelta( "CREATE TABLE {$p}ml_creations (
        creation_id     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        category_id     INT UNSIGNED    NOT NULL,
        title           VARCHAR(200)    NOT NULL,
        published_at    DATETIME        NOT NULL,
        summary         TEXT            NULL,
        cover_image_url VARCHAR(500)    NULL,
        video_url       VARCHAR(500)    NULL,
        audio_url       VARCHAR(500)    NULL,
        image_url       VARCHAR(500)    NULL,
        created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (creation_id),
        KEY idx_ml_creations_cat_pub (category_id, published_at),
        KEY idx_ml_creations_pub (published_at)
    ) $c;" );

    dbDelta( "CREATE TABLE {$p}ml_creation_contributors (
        creation_id BIGINT UNSIGNED NOT NULL,
        uid         BIGINT UNSIGNED NOT NULL,
        duty        VARCHAR(100)    NOT NULL,
        created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (creation_id, uid),
        KEY idx_ml_contributors_uid (uid, creation_id)
    ) $c;" );

    dbDelta( "CREATE TABLE {$p}ml_creation_comments (
        comment_id   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        uid          BIGINT UNSIGNED NOT NULL,
        creation_id  BIGINT UNSIGNED NOT NULL,
        published_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        content_text TEXT            NOT NULL,
        PRIMARY KEY (comment_id),
        KEY idx_ml_comments_creation (creation_id, published_at),
        KEY idx_ml_comments_uid (uid, published_at)
    ) $c;" );

    update_option( 'ml_plugin_version', ML_PLUGIN_VERSION );
}

// ── 加载 REST 路由 ────────────────────────────────────────────
require_once ML_PLUGIN_DIR . 'includes/rest-api.php';