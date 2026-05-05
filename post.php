<?php
/**
 * YOUR_NAME - 文章详情
 */
require_once 'config.php';

$pdo = getDB();
$settings = getSettings();
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: index.php');
    exit;
}

// 获取文章
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug,
                              GROUP_CONCAT(t.name SEPARATOR ', ') as tag_names
                       FROM posts p
                       LEFT JOIN categories c ON p.category_id = c.id
                       LEFT JOIN post_tags pt ON p.id = pt.post_id
                       LEFT JOIN tags t ON pt.tag_id = t.id
                       WHERE p.slug = :slug AND p.status = 'published'
                       GROUP BY p.id");
$stmt->execute(['slug' => $slug]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head><meta charset="UTF-8"><title>文章不存在</title><link rel="stylesheet" href="assets/css/style.css"></head>
    <body><div class="container not-found"><h1>🔍 文章不存在</h1><p>这篇文章可能已被删除或尚未发布。</p><a href="index.php" class="btn btn-primary">返回首页</a></div></body>
    </html>
    <?php
    exit;
}

// 获取评论
$comments = $pdo->prepare("SELECT * FROM comments WHERE post_id = :post_id AND status = 'approved' ORDER BY created_at DESC");
$comments->execute(['post_id' => $post['id']]);
$comments = $comments->fetchAll();

// 处理评论提交
$commentMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $website = $_POST['website'] ?? '';
    $content = $_POST['content'] ?? '';
    
    if (!empty($name) && !empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, name, email, website, content, ip) VALUES (:post_id, :name, :email, :website, :content, :ip)");
        $stmt->execute([
            'post_id' => $post['id'], 'name' => $name, 'email' => $email,
            'website' => $website, 'content' => $content, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
        $commentMsg = '评论提交成功！待管理员审核后显示。';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($post['title']) ?> - <?= e($settings['site_name'] ?? 'YOUR_NAME') ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <canvas id="particles"></canvas>
    <?php include 'includes/navbar.php'; ?>

    <main class="container article-container">
        <article class="article">
            <?php if ($post['cover_image']): ?>
            <div class="article-cover" style="background-image: url('<?= e($post['cover_image']) ?>')"></div>
            <?php endif; ?>
            
            <div class="article-header">
                <div class="article-meta-top">
                    <span class="article-category"><?= e($post['category_name'] ?? '未分类') ?></span>
                    <span class="article-date">📅 <?= date('Y-m-d', strtotime($post['published_at'] ?? $post['created_at'])) ?></span>
                    <span class="article-views">👁️ <?= $post['view_count'] ?> 次阅读</span>
                </div>
                <h1 class="article-title"><?= e($post['title']) ?></h1>
                <?php if (!empty($post['tag_names'])): ?>
                <div class="article-tags">
                    <?php foreach (explode(',', $post['tag_names']) as $tag): ?>
                        <span class="tag"><?= e(trim($tag)) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="article-content">
                <?= $post['content'] ?>
            </div>
        </article>

        <!-- 评论区 -->
        <section class="comments-section">
            <h2>💬 评论 (<?= count($comments) ?>)</h2>
            
            <?php if ($commentMsg): ?>
                <div class="alert alert-success"><?= e($commentMsg) ?></div>
            <?php endif; ?>

            <!-- 评论列表 -->
            <?php if (!empty($comments)): ?>
            <div class="comment-list">
                <?php foreach ($comments as $comment): ?>
                <div class="comment-item">
                    <div class="comment-header">
                        <strong><?= e($comment['name']) ?></strong>
                        <span class="comment-date"><?= date('Y-m-d H:i', strtotime($comment['created_at'])) ?></span>
                    </div>
                    <div class="comment-content"><?= e($comment['content']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- 评论表单 -->
            <div class="comment-form-container">
                <h3>发表评论</h3>
                <form method="POST" class="comment-form">
                    <div class="form-row">
                        <div class="form-group">
                            <input type="text" name="name" placeholder="你的昵称 *" required>
                        </div>
                        <div class="form-group">
                            <input type="email" name="email" placeholder="邮箱 (不公开)">
                        </div>
                    </div>
                    <div class="form-group">
                        <input type="url" name="website" placeholder="个人网站">
                    </div>
                    <div class="form-group">
                        <textarea name="content" rows="4" placeholder="写下你的评论..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">提交评论</button>
                </form>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>