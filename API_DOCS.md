# 🐱 Your小窝 - API 文档

## 基础信息

- **Base URL:** `https://你的域名/api.php?path=`
- **认证方式:** API Key（通过 `X-API-Key` 请求头）
- **数据格式:** JSON
- **字符编码:** UTF-8

---

## 通用参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `page` | int | 否 | 页码，默认 1 |
| `per_page` | int | 否 | 每页数量，默认 10，最大 100 |
| `status` | string | 否 | `published`（已发布）/ `draft`（草稿） |

---

## 1. 健康检查

**GET** `/health`

无需认证。

```bash
curl https://你的域名/api.php?path=health
```

**响应:**
```json
{
  "status": "ok",
  "time": "2026-05-04 08:00:00"
}
```

---

## 2. 文章 API

### 2.1 获取文章列表

**GET** `/posts`

无需认证（仅返回已发布文章）。

**查询参数:**

| 参数 | 类型 | 说明 |
|------|------|------|
| `status` | string | `published` 或 `draft`（需认证） |
| `category_id` | int | 按分类过滤 |
| `tag_id` | int | 按标签过滤 |
| `search` | string | 搜索标题和内容 |
| `page` | int | 页码 |
| `per_page` | int | 每页数量 |

```bash
curl https://你的域名/api.php?path=posts
```

**响应:**
```json
{
  "data": [
    {
      "id": 4,
      "title": "从零搭建 PHP 博客",
      "slug": "zero-to-php-blog",
      "content": "...",
      "excerpt": "...",
      "cover_image": "",
      "category_name": "技术分享",
      "category_slug": "tech",
      "status": "published",
      "view_count": 42,
      "tags": ["php", "nginx", "mysql"],
      "tag_names": ["PHP", "Nginx", "MySQL"],
      "created_at": "2026-05-03 21:55:44",
      "published_at": "2026-05-03 21:55:44"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 10,
    "total": 1,
    "total_pages": 1
  }
}
```

### 2.2 获取单篇文章

**GET** `/posts/{id}`

无需认证。自动增加浏览量。

```bash
curl https://你的域名/api.php?path=posts/4
```

**响应:**
```json
{
  "data": {
    "id": 4,
    "title": "从零搭建 PHP 博客",
    ...
  }
}
```

### 2.3 创建文章

**POST** `/posts`

需要 API Key。

**请求体:**
```json
{
  "title": "新文章标题",
  "content": "<h2>文章内容</h2><p>正文...</p>",
  "slug": "new-post",
  "excerpt": "摘要内容",
  "cover_image": "https://example.com/cover.jpg",
  "category_id": 1,
  "status": "draft",
  "tags": ["php", "教程"]
}
```

```bash
curl -X POST https://你的域名/api.php?path=posts \
  -H "X-API-Key: your_api_key" \
  -H "Content-Type: application/json" \
  -d '{"title":"新文章","content":"<p>内容</p>"}'
```

**响应:**
```json
{
  "message": "文章创建成功",
  "post_id": 11
}
```

### 2.4 更新文章

**PUT/PATCH** `/posts/{id}`

需要 API Key。

**请求体:** 只传需要更新的字段

```bash
curl -X PATCH https://你的域名/api.php?path=posts/4 \
  -H "X-API-Key: your_api_key" \
  -H "Content-Type: application/json" \
  -d '{"title":"修改后的标题","status":"published"}'
```

**响应:**
```json
{
  "message": "文章更新成功"
}
```

### 2.5 删除文章

**DELETE** `/posts/{id}`

需要 API Key。

```bash
curl -X DELETE https://你的域名/api.php?path=posts/4 \
  -H "X-API-Key: your_api_key"
```

**响应:**
```json
{
  "message": "文章已删除"
}
```

---

## 3. 分类 API

### 3.1 获取分类列表

**GET** `/categories`

无需认证。

```bash
curl https://你的域名/api.php?path=categories
```

**响应:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "技术分享",
      "slug": "tech",
      "description": "技术相关文章",
      "created_at": "2026-05-03 19:00:00"
    }
  ]
}
```

### 3.2 创建分类

**POST** `/categories`

需要 API Key。

```bash
curl -X POST https://你的域名/api.php?path=categories \
  -H "X-API-Key: your_api_key" \
  -H "Content-Type: application/json" \
  -d '{"name":"新分类","description":"分类描述"}'
```

**响应:**
```json
{
  "message": "分类创建成功",
  "id": 4
}
```

---

## 4. 标签 API

### 4.1 获取标签列表

**GET** `/tags`

无需认证。

```bash
curl https://你的域名/api.php?path=tags
```

**响应:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "PHP",
      "slug": "php",
      "created_at": "2026-05-03 19:00:00"
    }
  ]
}
```

### 4.2 创建标签

**POST** `/tags`

需要 API Key。

```bash
curl -X POST https://你的域名/api.php?path=tags \
  -H "X-API-Key: your_api_key" \
  -H "Content-Type: application/json" \
  -d '{"name":"新标签"}'
```

**响应:**
```json
{
  "message": "标签创建成功",
  "id": 10
}
```

---

## 5. 评论 API

### 5.1 获取评论

**GET** `/comments?post_id={id}`

无需认证（仅返回已审核通过的评论）。

```bash
curl "https://你的域名/api.php?path=comments&post_id=4"
```

**响应:**
```json
{
  "data": [
    {
      "id": 1,
      "post_id": 4,
      "name": "用户",
      "email": "",
      "website": "",
      "content": "好文章！",
      "status": "approved",
      "ip": "1.2.3.4",
      "created_at": "2026-05-04 08:00:00"
    }
  ]
}
```

### 5.2 提交评论

**POST** `/comments`

无需认证。

```bash
curl -X POST https://你的域名/api.php?path=comments \
  -H "Content-Type: application/json" \
  -d '{"post_id":4,"name":"访客","email":"","content":"好文章！"}'
```

**响应:**
```json
{
  "message": "评论提交成功",
  "id": 5
}
```

---

## 6. 留言板 API

**GET** `/guestbook` - 获取留言列表
**POST** `/guestbook` - 提交留言

需要 API Key（POST）。

---

## 7. 友链 API

**GET** `/links` - 获取友链列表

无需认证。

```bash
curl https://你的域名/api.php?path=links
```

---

## 8. 联系方式 API

**GET** `/contact_info` - 获取联系方式

无需认证。

---

## 错误码

| HTTP 状态码 | 说明 |
|-------------|------|
| 200 | 成功 |
| 201 | 创建成功 |
| 400 | 请求参数错误 |
| 401 | 未认证 / API Key 无效 |
| 404 | 资源不存在 |
| 405 | 请求方法不允许 |
| 500 | 服务器内部错误 |

---

## 获取 API Key

通过数据库查询或后台管理界面生成。

```sql
INSERT INTO api_keys (name, `key`, expires_at) 
VALUES ('我的应用', 'your_random_key_here', NULL);
```

---

## 文件结构

```
w-tw.cn/
├── index.php          # 首页 + 文章列表
├── post.php           # 文章详情
├── guestbook.php      # 留言板
├── contact.php        # 联系方式
├── about.php          # 关于
├── links.php          # 友链
├── api.php            # API 路由（统一入口）
├── api_visitor.php    # 访客计数 API
├── config.php         # 配置 + 数据库连接
├── install.php        # 安装脚本
├── post.php           # 发布文章
├── includes/
│   ├── navbar.php     # 导航栏模板
│   └── footer.php     # 页脚模板
├── assets/
│   ├── css/
│   │   └── style.css  # 样式文件
│   └── js/
│       └── particles.js  # 粒子效果
```

---

## 数据库表结构

| 表名 | 说明 |
|------|------|
| `posts` | 文章 |
| `categories` | 分类 |
| `tags` | 标签 |
| `post_tags` | 文章标签关联 |
| `comments` | 评论 |
| `guestbook` | 留言板 |
| `links` | 友链 |
| `settings` | 站点设置 |
| `contact_info` | 联系方式 |
| `api_keys` | API 密钥 |
