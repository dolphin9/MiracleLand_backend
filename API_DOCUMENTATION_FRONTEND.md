# MiracleLand API 文档（前端对接版）

## 1. 适用范围

面向前端工程 miracleland，聚焦：
- 哪些接口可直接调用
- 每个页面该调哪些接口
- 参数、响应字段和错误处理要点

后端维护细节（路由注册、权限回调、数据表）请看：
- API_DOCUMENTATION_BACKEND.md

---

## 2. 基础约定

### 2.1 Base URL

本地开发：
- `http://localhost/miracleland_backend/wordpress/wp-json/miracleland/v1`

生产环境（示例）：
- `https://api.miracleland.com/wp-json/miracleland/v1`

前端建议使用环境变量：

```env
VITE_API_BASE=http://localhost/miracleland_backend/wordpress/wp-json/miracleland/v1
```

### 2.2 数据格式

- 请求：`application/json`
- 响应：`application/json`
- 编码：`UTF-8`

### 2.3 权限

- 公开读取：多数 `GET`
- 管理员写入：多数 `POST/PUT/DELETE`
- 评论创建：设计为登录用户（当前后端未实现）

---

## 3. 页面到接口映射

## 3.1 OC 页面

建议页面：
- OC 列表页
- OC 详情页

接口：
- `GET /oc` 获取 OC 列表
- `GET /oc/{oc_id}` 获取 OC 详情

响应字段（核心）：
- `oc_id`
- `uid`
- `portrait_url`
- `description_text`
- `created_at`
- `updated_at`

## 3.2 二创页面

建议页面：
- 二创列表页
- 二创详情页

接口：
- `GET /creations?page=1&per_page=20&category_id=2`
- `GET /creations/{creation_id}`

响应字段（核心）：
- `creation_id`
- `category_id`
- `title`
- `published_at` 
- `summary`
- `cover_image_url`
- `video_url`
- `audio_url`
- `image_url`

## 3.3 二创分类筛选

接口：
- `GET /creation-categories`

响应字段（核心）：
- `category_id`
- `name`

前端筛选建议：
- 用 `category_id` 作为筛选值
- 不要用 `category` 字符串作为请求参数

## 3.4 二创贡献者

接口：
- `GET /creations/{creation_id}/contributors`

响应字段（核心）：
- `uid`
- `nickname`
- `duty`
- `created_at`

## 3.5 评论（当前不可用）

路由已声明：
- `GET /creations/{creation_id}/comments`
- `POST /creations/{creation_id}/comments`
- `DELETE /comments/{comment_id}`

当前状态：
- 后端处理函数未实现，暂不可对接。

---

## 4. 请求示例（Axios）

```js
import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE,
  timeout: 10000,
})

export const getOCs = () => api.get('/oc')

export const getCreations = (params = {}) => api.get('/creations', { params })

export const getCreationCategories = () => api.get('/creation-categories')

export const getContributors = (creationId) =>
  api.get(`/creations/${creationId}/contributors`)
```

---

## 5. 错误处理约定

后端业务错误通常为 `WP_Error` 结构：

```json
{
  "code": "error_code",
  "message": "错误描述",
  "data": {
    "status": 400
  }
}
```

前端建议：
- 优先展示 `message`
- 使用 `data.status` 区分 4xx/5xx
- 对 `404` 做空态处理，对 `400` 做表单提示

---

## 6. 前端接入建议（落地）

1. 新建 services 分层：
- `ocApi.js`
- `creationApi.js`
- `categoryApi.js`
- `contributorApi.js`
- `commentApi.js`（先占位）

2. 统一映射字段：
- 列表项唯一键统一用 `oc_id` 或 `creation_id`

3. 处理媒体兜底：
- `video_url/audio_url/image_url` 可能为空，需要兜底 UI

4. 评论功能：
- 在后端实现前先隐藏评论写入入口

---

文档版本：v1.0
最后更新：2026-05-17
