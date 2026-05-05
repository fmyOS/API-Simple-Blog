<?php
session_start();
require_once "../config.php";
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$pdo = getDB();
$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $post = null;
}

$categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = $_POST['content'];
    $excerpt = trim($_POST['excerpt']);
    $category_id = intval($_POST['category_id']);
    $cover_image = trim($_POST['cover_image']);
    $status = $_POST['status'];
    $slug = generateSlug($title);
    
    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE posts SET title=?, slug=?, content=?, excerpt=?, category_id=?, cover_image=?, status=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$title, $slug, $content, $excerpt, $category_id, $cover_image, $status, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO posts (title, slug, content, excerpt, category_id, cover_image, status) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$title, $slug, $content, $excerpt, $category_id, $cover_image, $status]);
        $id = $pdo->lastInsertId();
    }
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $id ? '编辑' : '新建'; ?>文章 - <?php echo SITE_NAME; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f0f1a; color: #e8e8f0; }
        .header { background: rgba(20,20,35,0.98); border-bottom: 1px solid rgba(0,245,255,0.2); padding: 0 30px; display: flex; align-items: center; height: 70px; }
        .header h1 { color: #00f5ff; font-size: 1.3rem; }
        .header a { color: rgba(255,255,255,0.7); text-decoration: none; margin-left: 20px; }
        .container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; color: rgba(255,255,255,0.7); margin-bottom: 8px; font-size: 0.9rem; }
        input, select, textarea { width: 100%; padding: 12px 16px; background: rgba(0,0,0,0.4); border: 1px solid rgba(0,245,255,0.3); border-radius: 8px; color: #e8e8f0; font-size: 1rem; outline: none; font-family: inherit; }
        input:focus, select:focus, textarea:focus { border-color: #00f5ff; }
        textarea { min-height: 300px; resize: vertical; }
        .btn { padding: 14px 30px; background: linear-gradient(135deg, #00f5ff, #00a8cc); border: none; border-radius: 8px; color: #0f0f1a; font-size: 1rem; font-weight: bold; cursor: pointer; }
        .btn:hover { box-shadow: 0 8px 25px rgba(0,245,255,0.4); }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo $id ? '📝 编辑文章' : '➕ 新建文章'; ?></h1>
        <a href="index.php">← 返回后台</a>
    </div>
    <div class="container">
        <form method="POST">
            <div class="form-group">
                <label>标题</label>
                <input name="title" value="<?php echo htmlspecialchars($post['title'] ?? ''); ?>" required>
            </div>
            <div class="row">
                <div class="form-group">
                    <label>分类</label>
                    <select name="category_id">
                        <?php foreach($categories as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php if(($post['category_id'] ?? '') == $c['id']) echo 'selected'; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>状态</label>
                    <select name="status">
                        <option value="draft" <?php if(($post['status'] ?? '') === 'draft') echo 'selected'; ?>>草稿</option>
                        <option value="published" <?php if(($post['status'] ?? '') === 'published') echo 'selected'; ?>>已发布</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>封面图URL</label>
                <input name="cover_image" value="<?php echo htmlspecialchars($post['cover_image'] ?? ''); ?>" placeholder="留空使用默认图">
            </div>
            <div class="form-group">
                <label>摘要</label>
                <textarea name="excerpt"><?php echo htmlspecialchars($post['excerpt'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>内容</label>
                <textarea name="content"><?php echo htmlspecialchars($post['content'] ?? ''); ?></textarea>
            </div>
            <button type="submit" class="btn">保存文章</button>
        </form>
    </div>
</body>
</html>
