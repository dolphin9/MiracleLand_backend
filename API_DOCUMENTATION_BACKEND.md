# MiracleLand API 文档（后端维护版）

## 1. 适用范围

面向后端工程 MiracleLand_backend，聚焦：
- 路由命名空间与注册方式
- 权限策略
- 数据表与接口关系
- 字段校验和错误返回

前端对接视角请看：
- API_DOCUMENTATION_FRONTEND.md

---

## 2. 后端实现基线

- 运行环境：WordPress + 自定义插件 `miracleland-api`
- 主命名空间：`miracleland/v1`
- 实际入口前缀：`/wp-json/miracleland/v1`

核心代码位置：
- `wordpress/wp-content/plugins/miracleland-api/miracleland-api.php`
- `wordpress/wp-content/plugins/miracleland-api/includes/rest-api.php`

---

## 3. 路由与权限矩阵

权限函数：
- `__return_true`：公开访问
- `ml_admin_only`：管理员（`manage_options`）
- `is_user_logged_in`：登录用户

路由清单：

1. Members
- `GET /members` admin
- `POST /members` admin
- `GET /members/{uid}` admin
- `PUT /members/{uid}` admin
- `DELETE /members/{uid}` admin

2. OC
- `GET /oc` public
- `POST /oc` admin
- `GET /oc/{oc_id}` public
- `PUT /oc/{oc_id}` admin
- `DELETE /oc/{oc_id}` admin

3. Creation Categories
- `GET /creation-categories` public
- `POST /creation-categories` admin
- `PUT /creation-categories/{category_id}` admin
- `DELETE /creation-categories/{category_id}` admin

4. Creations
- `GET /creations` public
- `POST /creations` admin
- `GET /creations/{creation_id}` public
- `PUT /creations/{creation_id}` admin
- `DELETE /creations/{creation_id}` admin

5. Contributors
- `GET /creations/{creation_id}/contributors` public
- `POST /creations/{creation_id}/contributors` admin
- `DELETE /creations/{creation_id}/contributors/{uid}` admin

6. Comments
- `GET /creations/{creation_id}/comments` public
- `POST /creations/{creation_id}/comments` logged-in
- `DELETE /comments/{comment_id}` admin
- 备注：comments 处理函数文件当前为空，路由已声明但不可用

---

## 4. 数据表设计（插件建表）

由插件激活钩子 `ml_create_tables` 创建：

- `wp_ml_members`
- `wp_ml_oc`
- `wp_ml_creation_categories`
- `wp_ml_creations`
- `wp_ml_creation_contributors`
- `wp_ml_creation_comments`

说明：
- `oc.uid` 关联成员 `members.uid`
- `creation_contributors` 为多对多关系表
- `creation_comments` 绑定成员与作品

---

## 5. 关键业务规则

## 5.1 Members

- `nickname`、`role_name` 为必填，缺失返回：
  - `missing_fields` (400)

## 5.2 OC

- 创建 OC 时会校验 `uid` 是否存在于成员表：
  - 不存在返回 `invalid_uid` (400)

## 5.3 Creation Categories

- 创建分类时 `name` 不能为空：
  - `missing_name` (400)

## 5.4 Creations

- 创建二创时 `video_url/audio_url/image_url` 至少一项非空：
  - 全空返回 `no_content` (400)

- 列表接口支持：
  - `page` 默认 `1`
  - `per_page` 默认 `20`，区间 `1-100`
  - `category_id` 可选筛选

- 删除二创时会删除关联关系：
  - `ml_creation_contributors`
  - `ml_creation_comments`

---

## 6. 通用错误返回

统一 not found 工具函数：
- `ml_not_found()` -> `not_found` (404)

典型错误响应：

```json
{
  "code": "not_found",
  "message": "记录不存在",
  "data": {
    "status": 404
  }
}
```

---

## 7. 待办与风险

1. 评论模块未实现
- 文件：`includes/api/comments.php` 为空
- 影响：评论查询、创建、删除路由会回调缺失

2. 鉴权方式规范化
- 当前联调多用 Basic Auth
- 建议明确生产方案（Cookie + Nonce 或应用密码）

3. OpenAPI 规范缺失
- 建议补充 Swagger/OpenAPI，减少前后端字段误差

---

## 8. 维护建议

1. 每次新增路由时同步更新：
- 本文档
- Bruno 集合
- 前端对接版文档

2. 每次字段变更时同步更新：
- 数据表注释
- 响应示例
- 前端映射说明

3. 版本标记建议：
- 文档头部记录插件版本与日期

---

文档版本：v1.0
对应插件：miracleland-api 1.0.0
最后更新：2026-05-17
