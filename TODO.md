# MiracleLand 后端开发任务清单

本文档详细列出 MiracleLand WordPress Headless CMS 后端的开发任务和进度跟踪。

**项目状态：** 初始阶段 - WordPress 已下载但未配置  
**前端状态：** Vue 3 UI 完成 95%，等待 API 集成  
**开发环境：** Windows + XAMPP（本地开发）  
**最后更新：** 2026-01-21

---

## 阶段 1：WordPress 核心配置

### 1.1 数据库与基础安装
- [ ] 启动 XAMPP 控制面板，启动 Apache 和 MySQL 服务
- [ ] 在 phpMyAdmin (`http://localhost/phpmyadmin`) 创建数据库（如 `miracleland`）
  - 字符集选择：`utf8mb4_unicode_ci`
  - 用户：默认使用 `root`，密码为空（或自定义密码）
- [ ] 复制 `wp-config-sample.php` 为 `wp-config.php`
- [ ] 配置数据库连接信息：
  - `DB_NAME`: `miracleland`
  - `DB_USER`: `root`
  - `DB_PASSWORD`: （XAMPP默认为空）
  - `DB_HOST`: `localhost` 或 `127.0.0.1`
- [ ] 生成并添加 WordPress 安全密钥（https://api.wordpress.org/secret-key/1.1/salt/）
- [ ] 设置数据库表前缀（建议：`ml_` 而不是默认 `wp_`）
- [ ] 访问 `http://localhost/miracleland_backend/wordpress/wp-admin/install.php` 完成安装
- [ ] 设置管理员账户和站点信息

### 1.2 固定链接与 REST API 配置
- [ ] 在「设置 → 固定链接」中设置为「朴素」或「自定义」（`/%postname%/`）
- [ ] 验证 REST API 可访问：`http://localhost/miracleland_backend/wordpress/wp-json/wp/v2/posts`
- [ ] 测试 REST API 返回 JSON 格式正确
- [ ] 如遇到 404 错误，检查 Apache `.htaccess` 文件和 `mod_rewrite` 模块是否启用

### 1.3 CORS 跨域配置
- [ ] 在主题 `functions.php` 或自定义插件中添加 CORS 头部
  ```php
  add_filter('rest_pre_serve_request', function($served, $result, $request, $server) {
      header('Access-Control-Allow-Origin: *');
      header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
      header('Access-Control-Allow-Headers: Content-Type, Authorization');
      return $served;
  }, 10, 4);
  ```
- [ ] 测试前端开发环境（`http://localhost:5173`）可访问 API
- [ ] **开发调试：** 启用 WordPress 调试模式（`wp-config.php`）：
  ```php
  define('WP_DEBUG', true);
  define('WP_DEBUG_LOG', true);
  define('WP_DEBUG_DISPLAY', false);
  ```
  调试日志位置：`wordpress/wp-content/debug.log`

### 1.4 基础安全与优化
- [ ] 禁用 XML-RPC（`add_filter('xmlrpc_enabled', '__return_false');`）
- [ ] 隐藏 WordPress 版本号
- [ ] 配置 `wp-config.php` 禁用文件编辑（`define('DISALLOW_FILE_EDIT', true);`）（生产环境推荐，开发环境可选）
- [ ] 设置文件上传大小限制（编辑 XAMPP `php.ini`）：
  - 路径：`C:\xampp\php\php.ini`
  - `upload_max_filesize = 20M`
  - `post_max_size = 25M`
  - `memory_limit = 256M`
  - 修改后重启 Apache

---

## 阶段 2：自定义内容类型开发

### 2.1 创建 MiracleLand 核心插件
- [ ] 在 `wp-content/plugins/` 创建 `miracleland-core/` 目录
- [ ] 创建主插件文件 `miracleland-core.php` 并添加插件头部信息
- [ ] 激活插件并验证在后台显示

### 2.2 注册「舰长 OC」自定义文章类型
- [ ] 注册 `oc` 自定义文章类型（`register_post_type()`）
  - 支持：title, editor, thumbnail, custom-fields
  - 在 REST API 中显示：`'show_in_rest' => true`
  - REST 基础路径：`'rest_base' => 'oc'`
- [ ] 创建自定义字段（使用 ACF 或原生 `register_meta()`）：
  - `oc_id`（文本）：如「舰长001」
  - `route_id`（文本）：如「001」
  - `full_image`（图片 URL）：全身立绘
  - `age`（文本）：年龄
  - `personality`（文本）：性格
  - `abilities`（文本）：能力
  - `likes`（文本）：喜好
  - `background`（富文本）：背景故事
- [ ] 自定义字段暴露到 REST API（`register_rest_field()` 或 ACF REST API 设置）
- [ ] 在 WordPress 后台测试创建 OC 条目

### 2.3 注册「二创内容」自定义文章类型
- [ ] 注册 `creations` 自定义文章类型
  - 支持：title, editor, thumbnail, custom-fields
  - 在 REST API 中显示
  - REST 基础路径：`'rest_base' => 'creations'`
- [ ] 创建自定义字段：
  - `author_name`（文本）：作者名
  - `creation_category`（选择）：视频/歌曲/手工/照片/绘画
  - `creation_date`（日期）：创作日期
  - `tags`（标签/文本）：标签数组
  - `related_oc_id`（文本）：关联 OC ID
  - `related_oc_name`（文本）：关联 OC 名称
  - `video_url`（URL）：视频链接（Bilibili/YouTube）
  - `audio_url`（URL）：音频链接
  - `image_url`（图片）：照片/绘画 URL
  - `cover_image`（图片）：封面缩略图
- [ ] 自定义字段暴露到 REST API
- [ ] 注册分类法（Taxonomy）`creation_category`
  - 分类项：视频、歌曲、手工、照片、绘画
  - 在 REST API 中显示
- [ ] 在 WordPress 后台测试创建二创条目

### 2.4 「世界观与设定」内容配置
- [ ] 创建 Page：「世界观」（slug: `world`）
- [ ] 添加富文本内容：奇迹之地背景故事
- [ ] 添加九运角色介绍区块
- [ ] 配置特色图片和媒体资源
- [ ] 验证通过 REST API 可访问：`/wp-json/wp/v2/pages?slug=world`

---

## 阶段 3：REST API 扩展开发

### 3.1 自定义 API 端点
- [ ] 为 OC 列表添加查询参数支持
  - 分页：`per_page`, `page`
  - 排序：`orderby`, `order`
- [ ] 为二创列表添加查询参数支持
  - 按分类筛选：`category`
  - 按 OC ID 筛选：`oc_id`
  - 分页和排序
- [ ] 创建关联查询端点：`/wp-json/miracleland/v1/oc/{id}/creations`
  - 返回指定 OC 关联的所有二创内容

### 3.2 响应数据格式优化
- [ ] 自定义 REST 响应，确保字段名与前端 mock 数据一致
  - OC 响应格式示例：
    ```json
    {
      "id": "舰长001",
      "name": "星辰旅者",
      "routeId": "001",
      "fullImage": "https://...",
      "story": "<p>...</p>",
      "age": "永恒",
      "personality": "...",
      "abilities": "...",
      "likes": "...",
      "background": "..."
    }
    ```
  - Creations 响应格式与前端 `mockCreations.js` 匹配
- [ ] 添加媒体 URL 完整路径（相对路径转绝对路径）
- [ ] 优化响应性能（移除不必要字段）

### 3.3 错误处理与验证
- [ ] 添加请求参数验证
- [ ] 统一错误响应格式
- [ ] 添加 404 处理（资源不存在）
- [ ] 添加权限检查（如需要）

---

## 阶段 4：媒体库配置与内容管理

### 4.1 媒体上传配置
- [ ] 确认 XAMPP `php.ini` 上传限制已配置（见阶段 1.4）
- [ ] 配置 WordPress 媒体设置（「设置 → 媒体」）
  - 缩略图：300x300
  - 中等尺寸：768x768
  - 大尺寸：1024x1024
- [ ] 确保 `wordpress/wp-content/uploads/` 目录有写入权限
- [ ] 安装图片优化插件（如 Smush 或 ShortPixel）（可选，开发环境不强制）

### 4.2 视频/音频嵌入支持
- [ ] 配置 oEmbed 支持 Bilibili 视频嵌入
  ```php
  wp_oembed_add_provider('#https?://(?:www\.)?bilibili\.com/video/.*#i', 
      'https://api.bilibili.com/oembed', true);
  ```
- [ ] 测试 YouTube、Bilibili 视频链接自动嵌入
- [ ] 为音频文件配置 WordPress 默认播放器支持

### 4.3 内容录入与测试数据
- [ ] 录入至少 3 个 OC 角色（包含完整字段和图片）
- [ ] 录入至少 10 个二创内容（覆盖所有分类）
- [ ] 建立 OC 与二创的关联关系
- [ ] 上传测试图片和视频链接
- [ ] 验证所有内容通过 REST API 正确返回

### 4.4 内容审核与权限
- [ ] 配置用户角色和权限（如需要用户投稿功能）
  - 编辑角色：可创建和编辑 OC/二创
  - 投稿角色：仅可创建二创，待审核
- [ ] 设置内容审核工作流（如需要）
- [ ] 配置垃圾内容过滤（Akismet）

---

## 阶段 5：性能优化与缓存（开发阶段可跳过，生产环境必需）

### 5.1 API 缓存配置（生产环境）
- [ ] 安装 Redis 或 Memcached 对象缓存
- [ ] 配置 WordPress 对象缓存插件（如 Redis Object Cache）
- [ ] 为 REST API 响应添加 HTTP 缓存头
  ```php
  add_filter('rest_post_dispatch', function($response) {
      $response->header('Cache-Control', 'public, max-age=300');
      return $response;
  });
  ```
- [ ] 测试缓存生效（检查响应头）

### 5.2 数据库优化
- [ ] 为自定义字段添加数据库索引
- [ ] 优化复杂查询（如 OC-二创关联查询）
- [ ] 启用 MySQL 查询缓存
- [ ] 定期清理 WordPress 修订版本和垃圾数据

### 5.3 CDN 与静态资源
- [ ] 配置 CDN 加速媒体文件（如七牛云、阿里云 OSS）
- [ ] 启用 Gzip 压缩
- [ ] 配置浏览器缓存策略
- [ ] 优化图片加载（WebP 格式、懒加载）

---

## 阶段 6：前端集成支持

### 6.1 环境变量与配置
- [ ] 配置前端 API 基础 URL
  - **开发环境**（当前）：`http://localhost/miracleland_backend/wordpress/wp-json/wp/v2`
  - 生产环境（未来）：`https://api.miracleland.com/wp-json/wp/v2`
- [ ] 前端创建 `.env.development` 文件：
  ```
  VITE_API_BASE=http://localhost/miracleland_backend/wordpress/wp-json/wp/v2
  ```
- [ ] 前端创建 `.env.production` 文件：
  ```
  VITE_API_BASE=https://api.miracleland.com/wp-json/wp/v2
  ```

### 6.2 API 文档编写
- [ ] 编写完整 API 接口文档（Markdown 或 Swagger）
  - 列出所有端点
  - 请求参数说明
  - 响应格式示例
  - 错误码说明
- [ ] 提供 Postman Collection 供测试

### 6.3 前端集成测试支持
- [ ] 提供测试账号（如需要认证）
- [ ] 确保 CORS 配置正确无跨域问题
- [ ] 协助前端排查 API 调用问题
- [ ] 验证前端数据展示正确

---

## 阶段 7：生产环境部署（开发完成后进行）

### 7.1 生产服务器配置（Ubuntu + 宝塔面板 + Nginx）
**注意：** 此阶段在本地开发完成后，部署到生产服务器时执行

- [ ] 购买并配置域名（如 `api.miracleland.com`）
- [ ] 申请并安装 SSL 证书（Let's Encrypt 或付费证书）
- [ ] 配置 Nginx 虚拟主机
  ```nginx
  server {
      listen 443 ssl http2;
      server_name api.miracleland.com;
      root /www/wwwroot/miracleland_backend/wordpress;
      
      ssl_certificate /path/to/cert.pem;
      ssl_certificate_key /path/to/key.pem;
      
      location / {
          try_files $uri $uri/ /index.php?$args;
      }
      
      location ~ \.php$ {
          fastcgi_pass unix:/tmp/php-cgi.sock;
          fastcgi_index index.php;
          include fastcgi_params;
      }
  }
  ```
- [ ] 配置 PHP-FPM 性能参数
- [ ] 启用 HTTPS 强制跳转

### 7.2 安全加固
- [ ] 更改默认管理员用户名（不使用 `admin`）
- [ ] 设置强密码策略
- [ ] 安装安全插件（如 Wordfence 或 Sucuri）
- [ ] 配置 IP 白名单限制后台访问（可选）
- [ ] 启用登录尝试限制
- [ ] 配置定期数据库备份（宝塔面板自动备份）
- [ ] 隐藏 `wp-config.php` 到上级目录

### 7.3 监控与日志
- [ ] 配置 Nginx 访问日志和错误日志
- [ ] 启用 PHP 错误日志
- [ ] 设置磁盘空间监控
- [ ] 配置性能监控（如 New Relic 或宝塔监控）
- [ ] 设置 API 响应时间监控

### 7.4 部署文档编写
- [ ] 编写 `DEPLOY.md` 部署文档
  - 服务器环境要求
  - 部署步骤详解
  - 配置文件示例
  - 常见问题排查
- [ ] 编写 `ENGINEERING.md` 技术文档
  - 系统架构图
  - 数据库设计
  - API 设计说明
  - 代码规范
- [ ] 更新 `README.md` 包含最新状态

---

## 阶段 8：维护与迭代

### 8.1 日常维护
- [ ] WordPress 核心更新
- [ ] 插件和主题更新
- [ ] 安全补丁及时应用
- [ ] 定期数据库优化
- [ ] 日志文件清理

### 8.2 性能监控
- [ ] 每周检查 API 响应时间
- [ ] 监控服务器资源使用（CPU、内存、磁盘）
- [ ] 分析慢查询并优化
- [ ] 监控 CDN 流量和费用

### 8.3 功能迭代
- [ ] 收集用户反馈
- [ ] 新增内容类型（如需要）
- [ ] 优化搜索功能
- [ ] 添加内容推荐算法（如需要）
- [ ] 实现用户投稿功能（Phase 4+）

---

## 技术决策记录

### 开发环境选择
**决策：** 使用 XAMPP 在 Windows 本地开发  
**理由：** 快速搭建本地开发环境，便于调试和测试  
**生产环境：** 未来部署到 Ubuntu + 宝塔面板 + Nginx  
**日期：** 2026-01-21

### ACF vs 原生 Custom Fields
**决策：** 使用 Advanced Custom Fields (ACF) Pro  
**理由：** 可视化配置快速，REST API 支持完善，后续维护方便  
**日期：** 待定

### 媒体托管策略
**决策：** 图片使用 WordPress 媒体库 + CDN，视频使用外链（Bilibili/YouTube）  
**理由：** 节省服务器存储空间和带宽成本  
**日期：** 待定

### 认证机制
**决策：** 当前不实现用户认证，仅管理员后台录入内容  
**理由：** 初期无用户投稿需求，简化开发  
**未来：** 如需用户投稿，可添加 JWT 认证 + Application Passwords  
**日期：** 待定

---

## 参考资源

### WordPress 开发文档
- [WordPress REST API 官方文档](https://developer.wordpress.org/rest-api/)
- [Custom Post Types 注册指南](https://developer.wordpress.org/plugins/post-types/)
- [ACF REST API 文档](https://www.advancedcustomfields.com/resources/rest-api/)
- [WordPress 性能优化指南](https://wordpress.org/support/article/optimization/)

### 本地开发环境
- [XAMPP 官方文档](https://www.apachefriends.org/)
- [XAMPP WordPress 安装指南](https://www.wpbeginner.com/wp-tutorials/how-to-install-wordpress-on-your-windows-computer-using-xampp/)
- [Apache mod_rewrite 配置](https://httpd.apache.org/docs/current/mod/mod_rewrite.html)

### 项目相关
- [前端项目：MiracleLand Vue 3 应用](../MiracleLand/)

---

**项目进度：** 0% 完成  
**预计完成时间：** 待评估  
**负责人：** 待定  
**最后审查：** 2026-01-21
