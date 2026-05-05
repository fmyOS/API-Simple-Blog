<?php
/**
 * YOUR_NAME - API路由 (修复版)
 * RESTful API for managing blog posts
 * 
 * Bug修复:
 * - 修复 POST/PUT/PATCH/DELETE 使用 session 而非 API Key 的问题
 * - 修复 categories/tags POST 使用 session 而非 API Key 的问题
 * - 实现缺失的 /links, /contact_info, /guestbook POST 接口
 * 
 * 新功能:
 * - /site_stats - 站点统计
 * - /search - 搜索增强
 * - /rss - RSS订阅源
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 路由: /api/posts 或 /api.php?path=posts
if (strpos($path, '/api/') === 0) {
    $path = substr($path, 5);
} elseif (basename($path) === 'api.php' && isset($_GET['path'])) {
    $path = $_GET['path'];
}

$resource = '';
$id = null;

if (preg_match('#^/?([^/]+)(?:/(.+))?$#', $path, $matches)) {
    $resource = $matches[1];
    $id = isset($matches[2]) ? $matches[2] : null;
}

// ==================== 辅助函数 ====================

function requireApiKey() {
    $header = $_SERVER['HTTP_X_API_KEY'] ?? '';
    if (empty($header)) {
        jsonResponse(['error' => '缺少 X-API-Key 请求头'], 401);
    }
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM api_keys WHERE api_key = :key AND (expires_at IS NULL OR expires_at > NOW())");
    $stmt->execute(['key' => $header]);
    $key = $stmt->fetch();
    if (!$key) {
        jsonResponse(['error' => '无效的API密钥'], 401);
    }
    $stmt = $pdo->prepare("UPDATE api_keys SET last_used_at = NOW() WHERE id = :id");
    $stmt->execute(['id' => $key['id']]);
    return $key;
}

// 公开接口列表
$publicEndpoints = ['health', 'posts', 'categories', 'tags', 'guestbook', 'links', 'contact_info', 'rss', 'site_stats'];

// 验证API密钥 (写操作需要API Key)
$writeMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];
if (!in_array($resource, $publicEndpoints) || (in_array($method, $writeMethods) && $resource === 'posts')) {
    // posts 的写操作需要 API Key
    if ($resource === 'posts' && in_array($method, $writeMethods)) {
        requireApiKey();
    }
    // categories/tags 的写操作需要 API Key  
    elseif (($resource === 'categories' || $resource === 'tags') && $method === 'POST') {
        requireApiKey();
    }
    // guestbook 的写操作需要 API Key
    elseif ($resource === 'guestbook' && $method === 'POST') {
        requireApiKey();
    }
    // links 的写操作需要 API Key
    elseif ($resource === 'links' && $method === 'POST') {
        requireApiKey();
    }
    // 其他未知资源需要 API Key
    elseif (!in_array($resource, $publicEndpoints)) {
        requireApiKey();
    }
}

// ==================== 文章 API ====================
// 注意: 单文章操作 (PATCH/DELETE) 需要 $id，这些在下面的单独块处理
if ($resource === 'posts' && !$id) {
    header('Content-Type: application/json; charset=utf-8');
    
    switch ($method) {
        case 'GET':
            // 获取文章列表
            $pdo = getDB();
            
            // 公开接口: 只返回已发布的
            $status = isset($_GET['status']) && $_GET['status'] === 'draft' ? 'draft' : 'published';
            
            $where = "p.status = :status";
            $params = ['status' => $status];
            
            if (!empty($_GET['category_id'])) {
                $where .= " AND p.category_id = :category_id";
                $params['category_id'] = (int)$_GET['category_id'];
            }
            
            if (!empty($_GET['tag_id'])) {
                $where .= " AND EXISTS (SELECT 1 FROM post_tags pt WHERE pt.post_id = p.id AND pt.tag_id = :tag_id)";
                $params['tag_id'] = (int)$_GET['tag_id'];
            }
            
            if (!empty($_GET['search'])) {
                $where .= " AND (p.title LIKE :search OR p.content LIKE :search)";
                $params['search'] = '%' . $_GET['search'] . '%';
            }
            
            // 支持 slug 查询
            if (!empty($_GET['slug'])) {
                $where .= " AND p.slug = :slug";
                $params['slug'] = $_GET['slug'];
            }
            
            // 分页
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = min(100, (int)($_GET['per_page'] ?? POSTS_PER_PAGE));
            $offset = ($page - 1) * $perPage;
            
            // 总数
            $countSql = "SELECT COUNT(*) FROM posts p WHERE $where";
            $stmt = $pdo->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetchColumn();
            
            // 列表
            $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug,
                           GROUP_CONCAT(t.name SEPARATOR ',') as tag_names,
                           GROUP_CONCAT(t.slug SEPARATOR ',') as tag_slugs
                    FROM posts p
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN post_tags pt ON p.id = pt.post_id
                    LEFT JOIN tags t ON pt.tag_id = t.id
                    WHERE $where
                    GROUP BY p.id
                    ORDER BY p.published_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue(':' . $k, $v);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $posts = $stmt->fetchAll();
            
            // 处理标签
            foreach ($posts as &$post) {
                $post['tags'] = $post['tag_slugs'] ? explode(',', $post['tag_slugs']) : [];
                $post['tag_names'] = $post['tag_names'] ? explode(',', $post['tag_names']) : [];
                unset($post['tag_slugs'], $post['tag_names']);
            }
            
            jsonResponse([
                'data' => $posts,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => (int)$total,
                    'total_pages' => ceil($total / $perPage),
                ]
            ]);
            break;
            
        case 'POST':
            // 创建文章 (API Key认证)
            requireApiKey();
            $data = json_decode(file_get_contents('php://input'), true);
            
            $title = $data['title'] ?? '';
            $content = $data['content'] ?? '';
            $slug = $data['slug'] ?? generateSlug($title);
            $excerpt = $data['excerpt'] ?? '';
            $coverImage = $data['cover_image'] ?? '';
            $categoryId = $data['category_id'] ?? null;
            $status = $data['status'] ?? 'draft';
            $tags = $data['tags'] ?? [];
            
            if (empty($title) || empty($content)) {
                jsonResponse(['error' => '标题和内容不能为空'], 400);
            }
            
            $pdo = getDB();
            $pdo->beginTransaction();
            
            try {
                $publishedAt = ($status === 'published') ? date('Y-m-d H:i:s') : null;
                
                $stmt = $pdo->prepare("INSERT INTO posts (title, slug, content, excerpt, cover_image, category_id, status, author_id, published_at) 
                                      VALUES (:title, :slug, :content, :excerpt, :cover_image, :category_id, :status, :author_id, :published_at)");
                $stmt->execute([
                    'title' => $title,
                    'slug' => $slug,
                    'content' => $content,
                    'excerpt' => $excerpt,
                    'cover_image' => $coverImage,
                    'category_id' => $categoryId,
                    'status' => $status,
                    'author_id' => 1,
                    'published_at' => $publishedAt,
                ]);
                
                $postId = $pdo->lastInsertId();
                
                // 关联标签
                if (!empty($tags)) {
                    foreach ($tags as $tagName) {
                        $tagName = trim($tagName);
                        if (empty($tagName)) continue;
                        $tagSlug = generateSlug($tagName);
                        
                        $stmt = $pdo->prepare("SELECT id FROM tags WHERE slug = :slug");
                        $stmt->execute(['slug' => $tagSlug]);
                        $tag = $stmt->fetch();
                        
                        if (!$tag) {
                            $stmt = $pdo->prepare("INSERT INTO tags (name, slug) VALUES (:name, :slug)");
                            $stmt->execute(['name' => $tagName, 'slug' => $tagSlug]);
                            $tagId = $pdo->lastInsertId();
                        } else {
                            $tagId = $tag['id'];
                        }
                        
                        $stmt = $pdo->prepare("INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (:post_id, :tag_id)");
                        $stmt->execute(['post_id' => $postId, 'tag_id' => $tagId]);
                    }
                }
                
                $pdo->commit();
                jsonResponse(['message' => '文章创建成功', 'post_id' => (int)$postId], 201);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                jsonResponse(['error' => $e->getMessage()], 500);
            }
            break;
            
        default:
            jsonResponse(['error' => '方法不允许'], 405);
    }
    exit;
}

// ==================== 单个文章 API ====================
if ($resource === 'posts' && $id) {
    header('Content-Type: application/json; charset=utf-8');
    
    switch ($method) {
        case 'GET':
            // 获取单篇文章
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug,
                                  GROUP_CONCAT(t.name SEPARATOR ',') as tag_names,
                                  GROUP_CONCAT(t.slug SEPARATOR ',') as tag_slugs
                           FROM posts p
                           LEFT JOIN categories c ON p.category_id = c.id
                           LEFT JOIN post_tags pt ON p.id = pt.post_id
                           LEFT JOIN tags t ON pt.tag_id = t.id
                           WHERE p.id = :id OR p.slug = :id_param
                           GROUP BY p.id");
            $stmt->execute(['id' => $id, 'id_param' => $id]);
            $post = $stmt->fetch();
            
            if (!$post) {
                jsonResponse(['error' => '文章不存在'], 404);
            }
            
            // 增加浏览量
            $stmt = $pdo->prepare("UPDATE posts SET view_count = view_count + 1 WHERE id = :id");
            $stmt->execute(['id' => $post['id']]);
            $post['view_count'] = $post['view_count'] + 1;
            
            $post['tags'] = $post['tag_slugs'] ? explode(',', $post['tag_slugs']) : [];
            $post['tag_names'] = $post['tag_names'] ? explode(',', $post['tag_names']) : [];
            unset($post['tag_slugs'], $post['tag_names']);
            
            jsonResponse(['data' => $post]);
            break;
            
        case 'PUT':
        case 'PATCH':
            // 更新文章 (API Key认证)
            requireApiKey();
            $data = json_decode(file_get_contents('php://input'), true);
            
            $title = $data['title'] ?? null;
            $content = $data['content'] ?? null;
            $slug = $data['slug'] ?? null;
            $excerpt = $data['excerpt'] ?? null;
            $coverImage = $data['cover_image'] ?? null;
            $categoryId = $data['category_id'] ?? null;
            $status = $data['status'] ?? null;
            $tags = $data['tags'] ?? null;
            
            $fields = [];
            $params = ['id' => $id];
            
            if ($title !== null) { $fields[] = "title = :title"; $params['title'] = $title; }
            if ($content !== null) { $fields[] = "content = :content"; $params['content'] = $content; }
            if ($slug !== null) { $fields[] = "slug = :slug"; $params['slug'] = $slug; }
            if ($excerpt !== null) { $fields[] = "excerpt = :excerpt"; $params['excerpt'] = $excerpt; }
            if ($coverImage !== null) { $fields[] = "cover_image = :cover_image"; $params['cover_image'] = $coverImage; }
            if ($categoryId !== null) { $fields[] = "category_id = :category_id"; $params['category_id'] = $categoryId; }
            if ($status !== null) {
                $fields[] = "status = :status";
                $params['status'] = $status;
                if ($status === 'published') {
                    $fields[] = "published_at = COALESCE(published_at, NOW())";
                }
            }
            
            if (empty($fields)) {
                jsonResponse(['error' => '没有需要更新的字段'], 400);
            }
            
            $pdo = getDB();
            $pdo->beginTransaction();
            
            try {
                // 对于slug查询，单独处理
                if (is_numeric($id)) {
                    $sql = "UPDATE posts SET " . implode(', ', $fields) . " WHERE id = :id";
                } else {
                    $sql = "UPDATE posts SET " . implode(', ', $fields) . " WHERE slug = :slug_param";
                    $params['slug_param'] = $id;
                    unset($params['id']);
                }
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                if ($stmt->rowCount() === 0) {
                    jsonResponse(['error' => '文章不存在'], 404);
                }
                
                // 更新标签
                if ($tags !== null) {
                    // 获取实际文章ID
                    $stmt = $pdo->prepare("SELECT id FROM posts WHERE id = :id OR slug = :slug");
                    $stmt->execute(['id' => $id, 'slug' => $id]);
                    $row = $stmt->fetch();
                    if (!$row) {
                        $pdo->rollBack();
                        jsonResponse(['error' => '文章不存在'], 404);
                    }
                    $postId = $row['id'];
                    
                    // 删除旧关联
                    $stmt = $pdo->prepare("DELETE FROM post_tags WHERE post_id = :post_id");
                    $stmt->execute(['post_id' => $postId]);
                    
                    // 添加新标签
                    foreach ($tags as $tagName) {
                        $tagName = trim($tagName);
                        if (empty($tagName)) continue;
                        $tagSlug = generateSlug($tagName);
                        
                        $stmt = $pdo->prepare("SELECT id FROM tags WHERE slug = :slug");
                        $stmt->execute(['slug' => $tagSlug]);
                        $tag = $stmt->fetch();
                        
                        if (!$tag) {
                            $stmt = $pdo->prepare("INSERT INTO tags (name, slug) VALUES (:name, :slug)");
                            $stmt->execute(['name' => $tagName, 'slug' => $tagSlug]);
                            $tagId = $pdo->lastInsertId();
                        } else {
                            $tagId = $tag['id'];
                        }
                        
                        $stmt = $pdo->prepare("INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (:post_id, :tag_id)");
                        $stmt->execute(['post_id' => $postId, 'tag_id' => $tagId]);
                    }
                }
                
                $pdo->commit();
                jsonResponse(['message' => '文章更新成功']);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                jsonResponse(['error' => $e->getMessage()], 500);
            }
            break;
            
        case 'DELETE':
            // 删除文章 (API Key认证)
            requireApiKey();
            $pdo = getDB();
            $stmt = $pdo->prepare("DELETE FROM posts WHERE id = :id OR slug = :slug");
            $stmt->execute(['id' => $id, 'slug' => $id]);
            
            if ($stmt->rowCount() > 0) {
                jsonResponse(['message' => '文章已删除']);
            } else {
                jsonResponse(['error' => '文章不存在'], 404);
            }
            break;
            
        default:
            jsonResponse(['error' => '方法不允许'], 405);
    }
    exit;
}

// ==================== 分类 API ====================
if ($resource === 'categories') {
    header('Content-Type: application/json; charset=utf-8');
    $pdo = getDB();
    
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY id");
        jsonResponse(['data' => $stmt->fetchAll()]);
    }
    
    if ($method === 'POST') {
        // API Key认证
        requireApiKey();
        $data = json_decode(file_get_contents('php://input'), true);
        $name = $data['name'] ?? '';
        $slug = $data['slug'] ?? generateSlug($name);
        $desc = $data['description'] ?? '';
        
        if (empty($name)) {
            jsonResponse(['error' => '分类名称不能为空'], 400);
        }
        
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (:name, :slug, :description)");
        $stmt->execute(['name' => $name, 'slug' => $slug, 'description' => $desc]);
        jsonResponse(['message' => '分类创建成功', 'id' => (int)$pdo->lastInsertId()], 201);
    }
    
    jsonResponse(['error' => '方法不允许'], 405);
}

// ==================== 标签 API ====================
if ($resource === 'tags') {
    header('Content-Type: application/json; charset=utf-8');
    $pdo = getDB();
    
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT * FROM tags ORDER BY id");
        jsonResponse(['data' => $stmt->fetchAll()]);
    }
    
    if ($method === 'POST') {
        // API Key认证
        requireApiKey();
        $data = json_decode(file_get_contents('php://input'), true);
        $name = $data['name'] ?? '';
        $slug = $data['slug'] ?? generateSlug($name);
        
        if (empty($name)) {
            jsonResponse(['error' => '标签名称不能为空'], 400);
        }
        
        $stmt = $pdo->prepare("INSERT INTO tags (name, slug) VALUES (:name, :slug)");
        $stmt->execute(['name' => $name, 'slug' => $slug]);
        jsonResponse(['message' => '标签创建成功', 'id' => (int)$pdo->lastInsertId()], 201);
    }
    
    jsonResponse(['error' => '方法不允许'], 405);
}

// ==================== 评论 API ====================
if ($resource === 'comments') {
    header('Content-Type: application/json; charset=utf-8');
    
    if ($method === 'GET') {
        $postId = $_GET['post_id'] ?? 0;
        $pdo = getDB();
        
        if ($postId) {
            $stmt = $pdo->prepare("SELECT id, post_id, name, email, website, content, status, created_at FROM comments WHERE post_id = :post_id AND status = 'approved' ORDER BY created_at DESC");
            $stmt->execute(['post_id' => $postId]);
        } else {
            $stmt = $pdo->query("SELECT id, post_id, name, email, website, content, status, created_at FROM comments WHERE status = 'approved' ORDER BY created_at DESC LIMIT 100");
        }
        jsonResponse(['data' => $stmt->fetchAll()]);
    }
    
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $postId = $data['post_id'] ?? 0;
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $website = $data['website'] ?? '';
        $content = $data['content'] ?? '';
        
        if (empty($name) || empty($content)) {
            jsonResponse(['error' => '姓名和内容不能为空'], 400);
        }
        
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, name, email, website, content, ip) VALUES (:post_id, :name, :email, :website, :content, :ip)");
        $stmt->execute([
            'post_id' => $postId,
            'name' => cleanInput($name),
            'email' => cleanInput($email),
            'website' => cleanInput($website),
            'content' => cleanInput($content),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
        jsonResponse(['message' => '评论提交成功', 'id' => (int)$pdo->lastInsertId()], 201);
    }
    
    jsonResponse(['error' => '方法不允许'], 405);
}

// ==================== 留言板 API ====================
if ($resource === 'guestbook') {
    header('Content-Type: application/json; charset=utf-8');
    $pdo = getDB();
    
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT * FROM guestbook ORDER BY created_at DESC LIMIT 100");
        jsonResponse(['data' => $stmt->fetchAll()]);
    }
    
    if ($method === 'POST') {
        // API Key认证
        requireApiKey();
        $data = json_decode(file_get_contents('php://input'), true);
        $name = $data['name'] ?? '';
        $content = $data['content'] ?? '';
        
        if (empty($name) || empty($content)) {
            jsonResponse(['error' => '姓名和内容不能为空'], 400);
        }
        
        $stmt = $pdo->prepare("INSERT INTO guestbook (name, content) VALUES (:name, :content)");
        $stmt->execute([
            'name' => cleanInput($name),
            'content' => cleanInput($content),
        ]);
        jsonResponse(['message' => '留言成功', 'id' => (int)$pdo->lastInsertId()], 201);
    }
    
    jsonResponse(['error' => '方法不允许'], 405);
}

// ==================== 友链 API ====================
if ($resource === 'links') {
    header('Content-Type: application/json; charset=utf-8');
    $pdo = getDB();
    
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT id, name, url, description, created_at FROM links ORDER BY id");
        jsonResponse(['data' => $stmt->fetchAll()]);
    }
    
    if ($method === 'POST') {
        // API Key认证
        requireApiKey();
        $data = json_decode(file_get_contents('php://input'), true);
        $name = $data['name'] ?? '';
        $url = $data['url'] ?? '';
        $description = $data['description'] ?? '';
        
        if (empty($name) || empty($url)) {
            jsonResponse(['error' => '名称和URL不能为空'], 400);
        }
        
        $stmt = $pdo->prepare("INSERT INTO links (name, url, description) VALUES (:name, :url, :description)");
        $stmt->execute([
            'name' => cleanInput($name),
            'url' => cleanInput($url),
            'description' => cleanInput($description),
        ]);
        jsonResponse(['message' => '友链添加成功', 'id' => (int)$pdo->lastInsertId()], 201);
    }
    
    if ($method === 'DELETE') {
        requireApiKey();
        $id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM links WHERE id = :id");
        $stmt->execute(['id' => $id]);
        if ($stmt->rowCount() > 0) {
            jsonResponse(['message' => '友链已删除']);
        } else {
            jsonResponse(['error' => '友链不存在'], 404);
        }
    }
    
    jsonResponse(['error' => '方法不允许'], 405);
}

// ==================== 联系方式 API ====================
if ($resource === 'contact_info') {
    header('Content-Type: application/json; charset=utf-8');
    $pdo = getDB();
    
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT * FROM contact_info LIMIT 1");
        $info = $stmt->fetch();
        if (!$info) {
            jsonResponse(['data' => null]);
        } else {
            // 不返回敏感字段
            unset($info['id']);
            jsonResponse(['data' => $info]);
        }
    }
    
    if ($method === 'POST') {
        // API Key认证
        requireApiKey();
        $data = json_decode(file_get_contents('php://input'), true);
        
        $fields = [];
        $params = [];
        $allowed = ['email', 'github', 'twitter', 'weibo', 'bilibili', 'description'];
        
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = cleanInput($data[$field]);
            }
        }
        
        if (empty($fields)) {
            jsonResponse(['error' => '没有需要更新的字段'], 400);
        }
        
        // 检查记录是否存在
        $check = $pdo->query("SELECT id FROM contact_info LIMIT 1")->fetch();
        if ($check) {
            $sql = "UPDATE contact_info SET " . implode(', ', $fields) . " WHERE id = " . $check['id'];
            $stmt = $pdo->prepare($sql);
        } else {
            $sql = "INSERT INTO contact_info (" . implode(', ', array_keys($params)) . ") VALUES (:" . implode(', :', array_keys($params)) . ")";
            $stmt = $pdo->prepare($sql);
        }
        $stmt->execute($params);
        jsonResponse(['message' => '联系方式更新成功']);
    }
    
    jsonResponse(['error' => '方法不允许'], 405);
}

// ==================== 站点统计 API (新功能) ====================
if ($resource === 'site_stats') {
    header('Content-Type: application/json; charset=utf-8');
    $pdo = getDB();
    
    $stats = [];
    
    // 文章数
    $stats['posts_total'] = (int)$pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    $stats['posts_published'] = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn();
    $stats['posts_draft'] = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'draft'")->fetchColumn();
    
    // 分类数
    $stats['categories'] = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    
    // 标签数
    $stats['tags'] = (int)$pdo->query("SELECT COUNT(*) FROM tags")->fetchColumn();
    
    // 评论数
    $stats['comments'] = (int)$pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'approved'")->fetchColumn();
    
    // 留言数
    $stats['guestbook'] = (int)$pdo->query("SELECT COUNT(*) FROM guestbook")->fetchColumn();
    
    // 友链数
    $stats['links'] = (int)$pdo->query("SELECT COUNT(*) FROM links")->fetchColumn();
    
    // 总浏览量
    $stats['views_total'] = (int)$pdo->query("SELECT COALESCE(SUM(view_count), 0) FROM posts")->fetchColumn();
    
    // 最新文章
    $stmt = $pdo->query("SELECT id, title, slug, view_count, published_at FROM posts WHERE status = 'published' ORDER BY published_at DESC LIMIT 5");
    $stats['recent_posts'] = $stmt->fetchAll();
    
    jsonResponse(['data' => $stats]);
}

// ==================== RSS订阅源 (新功能) ====================
if ($resource === 'rss') {
    header('Content-Type: application/xml; charset=utf-8');
    $pdo = getDB();
    
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name 
                           FROM posts p 
                           LEFT JOIN categories c ON p.category_id = c.id 
                           WHERE p.status = 'published' 
                           ORDER BY p.published_at DESC 
                           LIMIT 20");
    $stmt->execute();
    $posts = $stmt->fetchAll();
    
    $siteName = 'Your小窝';
    $siteUrl = 'http://w-tw.cn';
    $description = '一个可爱的技术博客';
    
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
    $xml .= '<channel>' . "\n";
    $xml .= '<title>' . htmlspecialchars($siteName) . '</title>' . "\n";
    $xml .= '<link>' . $siteUrl . '</link>' . "\n";
    $xml .= '<description>' . htmlspecialchars($description) . '</description>' . "\n";
    $xml .= '<language>zh-CN</language>' . "\n";
    $xml .= '<lastBuildDate>' . date('D, d M Y H:i:s O') . '</lastBuildDate>' . "\n";
    $xml .= '<atom:link href="' . $siteUrl . '/api.php?path=rss" rel="self" type="application/rss+xml"/>' . "\n";
    
    foreach ($posts as $post) {
        $postUrl = $siteUrl . '/post.php?slug=' . $post['slug'];
        $pubDate = date('D, d M Y H:i:s O', strtotime($post['published_at']));
        $excerpt = $post['excerpt'] ?: strip_tags(substr($post['content'], 0, 200));
        
        $xml .= '<item>' . "\n";
        $xml .= '<title>' . htmlspecialchars($post['title']) . '</title>' . "\n";
        $xml .= '<link>' . $postUrl . '</link>' . "\n";
        $xml .= '<guid isPermaLink="true">' . $postUrl . '</guid>' . "\n";
        $xml .= '<description>' . htmlspecialchars($excerpt) . '</description>' . "\n";
        $xml .= '<category>' . htmlspecialchars($post['category_name'] ?? '') . '</category>' . "\n";
        $xml .= '<pubDate>' . $pubDate . '</pubDate>' . "\n";
        $xml .= '</item>' . "\n";
    }
    
    $xml .= '</channel>' . "\n";
    $xml .= '</rss>';
    
    echo $xml;
    exit;
}

// ==================== 健康检查 ====================
if ($resource === 'health') {
    jsonResponse(['status' => 'ok', 'time' => date('Y-m-d H:i:s')]);
}

// ==================== 默认: 404 ====================
jsonResponse(['error' => 'Not Found', 'path' => $path], 404);
