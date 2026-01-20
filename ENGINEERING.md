# MiracleLand 后端工程文档

本文档详细说明 MiracleLand WordPress Headless CMS 的技术架构、实现细节和开发规范。

**版本：** 1.0.0  
**开发环境：** Windows + XAMPP  
**生产环境：** Ubuntu 22.04 + 宝塔面板 + Nginx  
**最后更新：** 2026-01-21

---

## 目录

- [系统架构](#系统架构)
- [技术栈](#技术栈)
- [开发环境配置](#开发环境配置)
- [数据库设计](#数据库设计)
- [自定义内容类型](#自定义内容类型)
- [REST API 设计](#rest-api-设计)
- [数据格式规范](#数据格式规范)
- [插件开发](#插件开发)
- [安全配置](#安全配置)
- [性能优化](#性能优化)
- [部署指南](#部署指南)
- [开发规范](#开发规范)
- [故障排查](#故障排查)

---

## 系统架构

### 整体架构

```
┌─────────────────────────────────────────────────────────┐
│                    前端应用层                             │
│              Vue 3 + Vite + Vue Router                   │
│         (Netlify/Vercel 静态托管)                        │
└────────────────────┬────────────────────────────────────┘
                     │ HTTPS REST API
                     │ (CORS 跨域)
┌────────────────────▼────────────────────────────────────┐
│                   API 网关层                              │
│              Nginx (反向代理 + SSL)                       │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│                 WordPress 应用层                          │
│         Headless CMS + REST API + 自定义插件              │
│  ┌─────────────────────────────────────────────────┐    │
│  │ WordPress Core 6.x                               │    │
│  ├─────────────────────────────────────────────────┤    │
│  │ MiracleLand Core Plugin                          │    │
│  │ - Custom Post Types (OC, Creations)             │    │
│  │ - REST API Extensions                           │    │
│  │ - Custom Fields (ACF)                           │    │
│  │ - CORS Configuration                            │    │
│  └─────────────────────────────────────────────────┘    │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│                  数据存储层                               │
│               MySQL 5.7+ Database                        │
│  ┌──────────────────────────────────────────────────┐   │
│  │ Tables: ml_posts, ml_postmeta, ml_terms, ...    │   │
│  └──────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│                   媒体存储                                │
│           WordPress Media Library + CDN                  │
│       (本地存储 / 阿里云OSS / 七牛云)                      │
└─────────────────────────────────────────────────────────┘
```

### 前后端分离架构

- **前端**：独立部署在 Netlify/Vercel，通过 HTTPS 访问后端 API
- **后端**：WordPress 仅作为 Headless CMS，不渲染前端页面
- **通信**：基于 WordPress REST API，JSON 数据格式
- **认证**：当前无需认证，未来可扩展 JWT 或 Application Passwords

---

## 技术栈

### 后端技术

| 技术 | 版本 | 用途 |
|------|------|------|
| WordPress | 6.9+ | Headless CMS 核心 |
| PHP | 7.4+ | 服务器端语言 |
| MySQL | 5.7+ | 关系型数据库 |
| Apache (XAMPP) | 2.4+ | Web 服务器（开发） |
| Nginx | 1.24+ | Web 服务器（生产） |
| Advanced Custom Fields (ACF) | Pro | 自定义字段管理 |

### 开发工具

| 工具 | 用途 |
|------|------|
| XAMPP | 本地 PHP + MySQL + Apache 环境 |
| phpMyAdmin | 数据库管理 |
| VS Code | 代码编辑器 |
| Postman | API 测试工具 |
| Git | 版本控制 |

### 生产环境

| 组件 | 说明 |
|------|------|
| 操作系统 | Ubuntu 22.04 LTS |
| 服务器管理 | 宝塔面板 |
| Web 服务器 | Nginx 1.24+ |
| PHP-FPM | PHP 7.4+ FastCGI |
| SSL 证书 | Let's Encrypt |
| 域名 | api.miracleland.com |

---

## 开发环境配置

### XAMPP 安装与配置

#### 1. 安装 XAMPP

1. 下载 XAMPP：https://www.apachefriends.org/
2. 安装到 `C:\xampp\`
3. 启动 XAMPP 控制面板

#### 2. 配置 PHP

编辑 `C:\xampp\php\php.ini`：

```ini
; 文件上传限制
upload_max_filesize = 20M
post_max_size = 25M
max_execution_time = 300
memory_limit = 256M

; 时区设置
date.timezone = Asia/Shanghai

; 错误显示（开发环境）
display_errors = On
error_reporting = E_ALL

; 多字节字符串支持
extension=mbstring
extension=mysqli
```

重启 Apache 使配置生效。

#### 3. 启用 mod_rewrite

编辑 `C:\xampp\apache\conf\httpd.conf`：

```apache
# 确保以下模块已启用
LoadModule rewrite_module modules/mod_rewrite.so

# 找到以下部分并修改
<Directory "C:/xampp/htdocs">
    AllowOverride All
    Require all granted
</Directory>
```

重启 Apache。

#### 4. 虚拟主机配置（可选）

编辑 `C:\xampp\apache\conf\extra\httpd-vhosts.conf`：

```apache
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/miracleland_backend/wordpress"
    ServerName miracleland.local
    
    <Directory "C:/xampp/htdocs/miracleland_backend/wordpress">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

编辑 `C:\Windows\System32\drivers\etc\hosts`：

```
127.0.0.1 miracleland.local
```

### MySQL 数据库配置

#### 1. 创建数据库

访问 phpMyAdmin：`http://localhost/phpmyadmin`

```sql
CREATE DATABASE miracleland 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;
```

#### 2. 数据库用户（开发环境）

XAMPP 默认配置：
- 用户名：`root`
- 密码：（空）
- 主机：`localhost` 或 `127.0.0.1`

### WordPress 配置

#### 1. 创建 wp-config.php

复制 `wp-config-sample.php` 为 `wp-config.php`，编辑内容：

```php
<?php
// 数据库配置
define('DB_NAME', 'miracleland');
define('DB_USER', 'root');
define('DB_PASSWORD', '');  // XAMPP 默认为空
define('DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');

// 数据库表前缀
$table_prefix = 'ml_';

// 安全密钥 - 从 https://api.wordpress.org/secret-key/1.1/salt/ 获取
define('AUTH_KEY',         'put your unique phrase here');
define('SECURE_AUTH_KEY',  'put your unique phrase here');
define('LOGGED_IN_KEY',    'put your unique phrase here');
define('NONCE_KEY',        'put your unique phrase here');
define('AUTH_SALT',        'put your unique phrase here');
define('SECURE_AUTH_SALT', 'put your unique phrase here');
define('LOGGED_IN_SALT',   'put your unique phrase here');
define('NONCE_SALT',       'put your unique phrase here');

// 开发环境调试配置
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors', 0);

// 禁用文件编辑（可选，生产环境推荐）
// define('DISALLOW_FILE_EDIT', true);

// 禁用 XML-RPC
add_filter('xmlrpc_enabled', '__return_false');

// WordPress 地址
define('WP_HOME', 'http://localhost/miracleland_backend/wordpress');
define('WP_SITEURL', 'http://localhost/miracleland_backend/wordpress');

// 内存限制
define('WP_MEMORY_LIMIT', '256M');

// 自动保存间隔（秒）
define('AUTOSAVE_INTERVAL', 300);

// 文章修订版本数量限制
define('WP_POST_REVISIONS', 3);

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

require_once ABSPATH . 'wp-settings.php';
```

#### 2. .htaccess 配置

WordPress 根目录 `.htaccess`：

```apache
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /miracleland_backend/wordpress/
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /miracleland_backend/wordpress/index.php [L]
</IfModule>
# END WordPress

# 安全配置
<Files wp-config.php>
    order allow,deny
    deny from all
</Files>

# 防止目录浏览
Options -Indexes

# 保护 .htaccess 文件
<Files .htaccess>
    order allow,deny
    deny from all
</Files>
```

---

## 数据库设计

### 表结构概述

WordPress 使用以下核心表（前缀 `ml_`）：

| 表名 | 用途 |
|------|------|
| `ml_posts` | 存储所有文章类型（OC、二创、页面等） |
| `ml_postmeta` | 存储文章的自定义字段 |
| `ml_terms` | 存储分类和标签 |
| `ml_term_taxonomy` | 分类法定义 |
| `ml_term_relationships` | 文章与分类/标签的关系 |
| `ml_users` | 用户信息 |
| `ml_usermeta` | 用户元数据 |
| `ml_options` | WordPress 配置选项 |

### 自定义字段存储

#### OC 自定义字段（存储在 ml_postmeta）

| meta_key | meta_value 类型 | 说明 |
|----------|----------------|------|
| `oc_id` | string | OC 编号（如"舰长001"） |
| `route_id` | string | 路由参数（如"001"） |
| `full_image` | string (URL) | 全身立绘图片 URL |
| `age` | string | 年龄 |
| `personality` | string | 性格描述 |
| `abilities` | string | 能力描述 |
| `likes` | string | 喜好 |
| `background` | text (HTML) | 背景故事（富文本） |

#### Creations 自定义字段（存储在 ml_postmeta）

| meta_key | meta_value 类型 | 说明 |
|----------|----------------|------|
| `author_name` | string | 作者名 |
| `creation_category` | string | 分类（视频/歌曲/手工/照片/绘画） |
| `creation_date` | date | 创作日期 (YYYY-MM-DD) |
| `tags` | array (serialized) | 标签数组 |
| `related_oc_id` | string | 关联 OC ID |
| `related_oc_name` | string | 关联 OC 名称 |
| `video_url` | string (URL) | 视频链接 |
| `audio_url` | string (URL) | 音频链接 |
| `image_url` | string (URL) | 图片 URL |
| `cover_image` | string (URL) | 封面缩略图 URL |

### 数据库索引优化

为提高查询性能，建议添加以下索引：

```sql
-- OC ID 索引
ALTER TABLE ml_postmeta 
ADD INDEX idx_oc_id (meta_key(20), meta_value(20));

-- 创作日期索引
ALTER TABLE ml_postmeta 
ADD INDEX idx_creation_date (meta_key(30), meta_value(20));

-- 关联 OC 索引
ALTER TABLE ml_postmeta 
ADD INDEX idx_related_oc (meta_key(20), meta_value(20));
```

---

## 自定义内容类型

### 插件结构

```
wp-content/plugins/miracleland-core/
├── miracleland-core.php          # 主插件文件
├── includes/
│   ├── post-types.php            # 自定义文章类型注册
│   ├── taxonomies.php            # 分类法注册
│   ├── rest-api.php              # REST API 扩展
│   ├── cors.php                  # CORS 配置
│   └── acf-fields.php            # ACF 字段配置
├── assets/
│   ├── css/
│   └── js/
└── README.md
```

### 主插件文件

`miracleland-core.php`：

```php
<?php
/**
 * Plugin Name: MiracleLand Core
 * Plugin URI: https://miracleland.com
 * Description: MiracleLand 自定义内容类型和 REST API 扩展
 * Version: 1.0.0
 * Author: MiracleLand Team
 * Author URI: https://miracleland.com
 * License: GPL v2 or later
 * Text Domain: miracleland
 * Domain Path: /languages
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// 定义常量
define('MIRACLELAND_VERSION', '1.0.0');
define('MIRACLELAND_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MIRACLELAND_PLUGIN_URL', plugin_dir_url(__FILE__));

// 引入核心文件
require_once MIRACLELAND_PLUGIN_DIR . 'includes/post-types.php';
require_once MIRACLELAND_PLUGIN_DIR . 'includes/taxonomies.php';
require_once MIRACLELAND_PLUGIN_DIR . 'includes/rest-api.php';
require_once MIRACLELAND_PLUGIN_DIR . 'includes/cors.php';

// ACF 字段配置（如果使用 ACF）
if (class_exists('ACF')) {
    require_once MIRACLELAND_PLUGIN_DIR . 'includes/acf-fields.php';
}

// 插件激活钩子
register_activation_hook(__FILE__, 'miracleland_activate');
function miracleland_activate() {
    // 刷新重写规则
    flush_rewrite_rules();
}

// 插件停用钩子
register_deactivation_hook(__FILE__, 'miracleland_deactivate');
function miracleland_deactivate() {
    flush_rewrite_rules();
}
```

### OC 自定义文章类型

`includes/post-types.php`：

```php
<?php
/**
 * 注册自定义文章类型
 */

// 注册 OC 自定义文章类型
add_action('init', 'miracleland_register_oc_post_type');
function miracleland_register_oc_post_type() {
    $labels = array(
        'name'                  => '舰长 OC',
        'singular_name'         => 'OC',
        'menu_name'             => '舰长 OC',
        'add_new'               => '添加新 OC',
        'add_new_item'          => '添加新 OC',
        'edit_item'             => '编辑 OC',
        'new_item'              => '新 OC',
        'view_item'             => '查看 OC',
        'search_items'          => '搜索 OC',
        'not_found'             => '未找到 OC',
        'not_found_in_trash'    => '回收站中未找到 OC',
    );

    $args = array(
        'labels'                => $labels,
        'public'                => true,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'query_var'             => true,
        'rewrite'               => array('slug' => 'oc'),
        'capability_type'       => 'post',
        'has_archive'           => true,
        'hierarchical'          => false,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-admin-users',
        'supports'              => array('title', 'editor', 'thumbnail', 'custom-fields'),
        
        // REST API 配置
        'show_in_rest'          => true,
        'rest_base'             => 'oc',
        'rest_controller_class' => 'WP_REST_Posts_Controller',
    );

    register_post_type('oc', $args);
}

// 注册 Creations 自定义文章类型
add_action('init', 'miracleland_register_creations_post_type');
function miracleland_register_creations_post_type() {
    $labels = array(
        'name'                  => '二创内容',
        'singular_name'         => '二创',
        'menu_name'             => '二创内容',
        'add_new'               => '添加二创',
        'add_new_item'          => '添加新二创',
        'edit_item'             => '编辑二创',
        'new_item'              => '新二创',
        'view_item'             => '查看二创',
        'search_items'          => '搜索二创',
        'not_found'             => '未找到二创',
        'not_found_in_trash'    => '回收站中未找到二创',
    );

    $args = array(
        'labels'                => $labels,
        'public'                => true,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'query_var'             => true,
        'rewrite'               => array('slug' => 'creations'),
        'capability_type'       => 'post',
        'has_archive'           => true,
        'hierarchical'          => false,
        'menu_position'         => 6,
        'menu_icon'             => 'dashicons-art',
        'supports'              => array('title', 'editor', 'thumbnail', 'custom-fields'),
        
        // REST API 配置
        'show_in_rest'          => true,
        'rest_base'             => 'creations',
        'rest_controller_class' => 'WP_REST_Posts_Controller',
    );

    register_post_type('creations', $args);
}
```

### 分类法注册

`includes/taxonomies.php`：

```php
<?php
/**
 * 注册分类法（Taxonomy）
 */

add_action('init', 'miracleland_register_creation_category_taxonomy');
function miracleland_register_creation_category_taxonomy() {
    $labels = array(
        'name'              => '二创分类',
        'singular_name'     => '分类',
        'search_items'      => '搜索分类',
        'all_items'         => '所有分类',
        'parent_item'       => '父级分类',
        'parent_item_colon' => '父级分类：',
        'edit_item'         => '编辑分类',
        'update_item'       => '更新分类',
        'add_new_item'      => '添加新分类',
        'new_item_name'     => '新分类名称',
        'menu_name'         => '分类',
    );

    $args = array(
        'labels'            => $labels,
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'creation-category'),
        
        // REST API 配置
        'show_in_rest'      => true,
        'rest_base'         => 'creation_categories',
    );

    register_taxonomy('creation_category', array('creations'), $args);
    
    // 添加默认分类项
    $default_terms = array('视频', '歌曲', '手工', '照片', '绘画');
    foreach ($default_terms as $term) {
        if (!term_exists($term, 'creation_category')) {
            wp_insert_term($term, 'creation_category');
        }
    }
}
```

---

## REST API 设计

### 标准端点

#### OC 端点

```
GET  /wp-json/wp/v2/oc              # 获取 OC 列表
GET  /wp-json/wp/v2/oc/{id}         # 获取单个 OC
POST /wp-json/wp/v2/oc              # 创建 OC（需认证）
PUT  /wp-json/wp/v2/oc/{id}         # 更新 OC（需认证）
DELETE /wp-json/wp/v2/oc/{id}       # 删除 OC（需认证）
```

**查询参数：**
- `per_page`: 每页数量（默认 10）
- `page`: 页码（从 1 开始）
- `orderby`: 排序字段（`date`, `title`, `modified`）
- `order`: 排序方向（`asc`, `desc`）

#### Creations 端点

```
GET  /wp-json/wp/v2/creations       # 获取二创列表
GET  /wp-json/wp/v2/creations/{id}  # 获取单个二创
POST /wp-json/wp/v2/creations       # 创建二创（需认证）
PUT  /wp-json/wp/v2/creations/{id}  # 更新二创（需认证）
DELETE /wp-json/wp/v2/creations/{id} # 删除二创（需认证）
```

**查询参数：**
- `per_page`: 每页数量
- `page`: 页码
- `category`: 按分类筛选（ID 或 slug）
- `oc_id`: 按关联 OC ID 筛选
- `orderby`: 排序字段
- `order`: 排序方向

#### 世界观端点

```
GET /wp-json/wp/v2/pages?slug=world  # 获取世界观页面
```

### 自定义端点

`includes/rest-api.php`：

```php
<?php
/**
 * REST API 扩展
 */

// 注册自定义端点
add_action('rest_api_init', 'miracleland_register_rest_routes');
function miracleland_register_rest_routes() {
    // OC 关联二创端点
    register_rest_route('miracleland/v1', '/oc/(?P<id>[a-zA-Z0-9-]+)/creations', array(
        'methods'  => 'GET',
        'callback' => 'miracleland_get_oc_creations',
        'args'     => array(
            'id' => array(
                'required' => true,
                'validate_callback' => function($param, $request, $key) {
                    return !empty($param);
                }
            ),
        ),
    ));
}

// 获取 OC 关联的所有二创
function miracleland_get_oc_creations($request) {
    $oc_id = $request['id'];
    
    $args = array(
        'post_type'      => 'creations',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => 'related_oc_id',
                'value'   => $oc_id,
                'compare' => '='
            )
        )
    );
    
    $query = new WP_Query($args);
    $creations = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $creations[] = miracleland_format_creation_response(get_the_ID());
        }
        wp_reset_postdata();
    }
    
    return new WP_REST_Response($creations, 200);
}

// 格式化二创响应数据
function miracleland_format_creation_response($post_id) {
    $post = get_post($post_id);
    
    return array(
        'id'              => $post_id,
        'title'           => get_the_title($post_id),
        'author'          => get_post_meta($post_id, 'author_name', true),
        'category'        => get_post_meta($post_id, 'creation_category', true),
        'date'            => get_post_meta($post_id, 'creation_date', true),
        'description'     => $post->post_content,
        'tags'            => maybe_unserialize(get_post_meta($post_id, 'tags', true)),
        'ocId'            => get_post_meta($post_id, 'related_oc_id', true),
        'ocName'          => get_post_meta($post_id, 'related_oc_name', true),
        'videoUrl'        => get_post_meta($post_id, 'video_url', true),
        'audioUrl'        => get_post_meta($post_id, 'audio_url', true),
        'imageUrl'        => get_post_meta($post_id, 'image_url', true),
        'coverImage'      => get_the_post_thumbnail_url($post_id, 'full'),
    );
}

// 自定义字段暴露到 REST API
add_action('rest_api_init', 'miracleland_register_rest_fields');
function miracleland_register_rest_fields() {
    // OC 自定义字段
    $oc_fields = array('oc_id', 'route_id', 'full_image', 'age', 'personality', 'abilities', 'likes', 'background');
    foreach ($oc_fields as $field) {
        register_rest_field('oc', $field, array(
            'get_callback'    => function($object) use ($field) {
                return get_post_meta($object['id'], $field, true);
            },
            'update_callback' => function($value, $object) use ($field) {
                return update_post_meta($object->ID, $field, $value);
            },
            'schema'          => array(
                'type'        => 'string',
                'context'     => array('view', 'edit'),
            ),
        ));
    }
    
    // Creations 自定义字段
    $creation_fields = array(
        'author_name', 'creation_category', 'creation_date', 'tags',
        'related_oc_id', 'related_oc_name', 'video_url', 'audio_url',
        'image_url', 'cover_image'
    );
    foreach ($creation_fields as $field) {
        register_rest_field('creations', $field, array(
            'get_callback'    => function($object) use ($field) {
                $value = get_post_meta($object['id'], $field, true);
                // 特殊处理 tags 字段
                if ($field === 'tags' && is_serialized($value)) {
                    return maybe_unserialize($value);
                }
                return $value;
            },
            'update_callback' => function($value, $object) use ($field) {
                // 特殊处理 tags 字段
                if ($field === 'tags' && is_array($value)) {
                    $value = serialize($value);
                }
                return update_post_meta($object->ID, $field, $value);
            },
            'schema'          => array(
                'type'        => $field === 'tags' ? 'array' : 'string',
                'context'     => array('view', 'edit'),
            ),
        ));
    }
}
```

### CORS 配置

`includes/cors.php`：

```php
<?php
/**
 * CORS 跨域配置
 */

add_filter('rest_pre_serve_request', 'miracleland_cors_headers', 10, 4);
function miracleland_cors_headers($served, $result, $request, $server) {
    // 允许的源（生产环境应限制为特定域名）
    header('Access-Control-Allow-Origin: *');
    
    // 允许的 HTTP 方法
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    
    // 允许的请求头
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-WP-Nonce');
    
    // 预检请求缓存时间（秒）
    header('Access-Control-Max-Age: 3600');
    
    // 允许携带凭证
    header('Access-Control-Allow-Credentials: true');
    
    // 处理 OPTIONS 预检请求
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        status_header(200);
        exit();
    }
    
    return $served;
}

// 生产环境：限制 CORS 为特定域名
if (!WP_DEBUG) {
    add_filter('rest_pre_serve_request', function($served, $result, $request, $server) {
        $allowed_origins = array(
            'https://miracleland.com',
            'https://www.miracleland.com',
        );
        
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        
        if (in_array($origin, $allowed_origins)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        }
        
        return $served;
    }, 10, 4);
}
```

---

## 数据格式规范

### OC 响应格式

```json
{
  "id": "舰长001",
  "name": "星辰旅者",
  "routeId": "001",
  "fullImage": "http://localhost/miracleland_backend/wordpress/wp-content/uploads/2026/01/oc-001.jpg",
  "story": "<p>在奇迹之地的星空下，星辰旅者诞生于时光的交汇处...</p>",
  "age": "永恒",
  "personality": "温柔而坚定，充满好奇心，对未知充满向往",
  "abilities": "星光导航、时空感知、情感共鸣",
  "likes": "观星、收集故事、与旅行者交谈",
  "background": "<p>详细背景故事...</p>"
}
```

### Creations 响应格式

```json
{
  "id": 1,
  "title": "星辰之歌",
  "author": "旅行者A",
  "category": "歌曲",
  "date": "2026-01-15",
  "description": "<p>为星辰旅者创作的主题曲...</p>",
  "tags": ["原创", "催泪", "治愈"],
  "ocId": "舰长001",
  "ocName": "星辰旅者",
  "videoUrl": null,
  "audioUrl": "https://example.com/audio/star-song.mp3",
  "imageUrl": null,
  "coverImage": "http://localhost/miracleland_backend/wordpress/wp-content/uploads/2026/01/cover.jpg"
}
```

### 世界观页面格式

```json
{
  "id": 10,
  "slug": "world",
  "title": {
    "rendered": "奇迹之地世界观"
  },
  "content": {
    "rendered": "<h2>欢迎来到奇迹之地</h2><p>这是一个充满奇迹的世界...</p>"
  },
  "featured_media": 15
}
```

---

## 安全配置

### 1. 隐藏 WordPress 版本

```php
// 移除版本号
remove_action('wp_head', 'wp_generator');

// 移除 RSS 中的版本号
add_filter('the_generator', '__return_false');
```

### 2. 禁用 XML-RPC

```php
add_filter('xmlrpc_enabled', '__return_false');
```

### 3. 限制登录尝试

安装插件：Limit Login Attempts Reloaded

### 4. 文件权限

```bash
# 目录权限
find /path/to/wordpress/ -type d -exec chmod 755 {} \;

# 文件权限
find /path/to/wordpress/ -type f -exec chmod 644 {} \;

# wp-config.php 权限
chmod 600 wp-config.php
```

### 5. 数据库安全

```sql
-- 创建专用数据库用户（生产环境）
CREATE USER 'ml_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON miracleland.* TO 'ml_user'@'localhost';
FLUSH PRIVILEGES;
```

---

## 性能优化

### 1. 对象缓存

安装 Redis 对象缓存插件：

```bash
# Ubuntu 安装 Redis
sudo apt install redis-server
sudo systemctl enable redis-server
```

WordPress 配置：

```php
// wp-config.php
define('WP_CACHE', true);
define('WP_CACHE_KEY_SALT', 'miracleland_');
```

### 2. 数据库查询优化

```php
// 减少数据库查询
add_filter('rest_prepare_oc', function($response, $post, $request) {
    // 一次性加载所有 meta
    $meta = get_post_meta($post->ID);
    // 处理数据...
    return $response;
}, 10, 3);
```

### 3. API 缓存

```php
add_filter('rest_post_dispatch', function($response, $server, $request) {
    // 为 GET 请求添加缓存头
    if ($request->get_method() === 'GET') {
        $response->header('Cache-Control', 'public, max-age=300');
        $response->header('Expires', gmdate('D, d M Y H:i:s', time() + 300) . ' GMT');
    }
    return $response;
}, 10, 3);
```

---

## 部署指南

### 生产环境部署步骤

1. **备份数据库**
2. **上传文件到服务器**
3. **配置 Nginx**
4. **配置 SSL**
5. **优化 PHP-FPM**
6. **设置定时任务**
7. **配置防火墙**
8. **启用监控**

详见 [DEPLOY.md](DEPLOY.md)

---

## 开发规范

### 代码风格

- 遵循 WordPress Coding Standards
- 使用 PSR-4 自动加载（插件开发）
- 函数和类名使用前缀 `miracleland_` 避免冲突

### Git 工作流

```bash
# 分支命名
feature/oc-custom-fields
bugfix/api-response-format
hotfix/cors-headers

# 提交信息格式
feat: 添加 OC 自定义字段 REST API 支持
fix: 修复二创分类筛选问题
docs: 更新 API 文档
```

### 测试

```bash
# API 测试（使用 curl）
curl -X GET "http://localhost/miracleland_backend/wordpress/wp-json/wp/v2/oc"

# 使用 Postman 进行完整测试
```

---

## 故障排查

### 1. REST API 返回 404

**原因：** 固定链接未正确配置或 `.htaccess` 问题

**解决：**
```bash
# 检查 .htaccess 是否存在
# 在 WordPress 后台「设置 → 固定链接」点击保存
# 检查 Apache mod_rewrite 是否启用
```

### 2. CORS 错误

**原因：** 跨域请求被阻止

**解决：**
```php
// 检查 includes/cors.php 是否正确加载
// 确保 header() 在输出任何内容之前调用
```

### 3. 自定义字段未显示在 API

**原因：** REST API 字段注册未生效

**解决：**
```php
// 检查 register_rest_field() 是否正确调用
// 确保 'show_in_rest' => true 已设置
// 清空缓存并刷新固定链接
```

### 4. 图片上传失败

**原因：** 文件权限或 PHP 上传限制

**解决：**
```bash
# 检查目录权限
chmod 755 wp-content/uploads/

# 检查 php.ini 配置
upload_max_filesize = 20M
post_max_size = 25M
```

---

## 附录

### 常用 WP-CLI 命令

```bash
# 刷新固定链接
wp rewrite flush

# 搜索替换 URL
wp search-replace 'http://localhost' 'https://api.miracleland.com'

# 导出数据库
wp db export backup.sql

# 清空缓存
wp cache flush
```

### 调试日志位置

- WordPress 调试日志：`wp-content/debug.log`
- Apache 错误日志：`C:\xampp\apache\logs\error.log`
- PHP 错误日志：`C:\xampp\php\logs\php_error_log`

---

**文档版本：** 1.0.0  
**维护者：** MiracleLand Team  
**最后更新：** 2026-01-21
