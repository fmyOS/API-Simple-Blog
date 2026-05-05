<?php
session_start();
require_once "../config.php";
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }


$pdo = getDB();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['action'] ?? '';
    if ($a === 'add') {
        $name = trim($_POST['name']);
        $slug = generateSlug($name);
        try {
            $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?,?,?)")->execute([$name, $slug, trim($_POST['description'])]);
            $msg = '<div class="msg msg-success">分类添加成功！</div>';
        } catch (PDOException $e) {
            $msg = '<div class="msg msg-error">添加失败：分类可能已存在。</div>';
        }
    } elseif ($a === 'update') {
        $pdo->prepare("UPDATE categories SET name=?, slug=?, description=? WHERE id=?")->execute([trim($_POST['name']), generateSlug($_POST['name']), trim($_POST['description']), intval($_POST['id'])]);
        $msg = '<div class="msg msg-success">分类更新成功！</div>';
    } elseif ($a === 'delete') {
        $cid = intval($_POST['id']);
        $pdo->prepare("UPDATE posts SET category_id=NULL WHERE category_id=?")->execute([$cid]);
        $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$cid]);
        $msg = '<div class="msg msg-success">分类已删除（文章已取消分类）！</div>';
    }
}
$categories = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM posts WHERE category_id=c.id) as post_count FROM categories c ORDER BY id ASC")->fetchAll();
$edit = isset($_GET['edit']) ? $pdo->query("SELECT * FROM categories WHERE id=".intval($_GET['edit']))->fetch() : null;

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>分类管理</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f0f1a; color: #e8e8f0; }
.header { background: rgba(20,20,35,0.98); border-bottom: 1px solid rgba(0,245,255,0.2); padding: 0 30px; display: flex; align-items: center; justify-content: space-between; height: 70px; position: sticky; top: 0; z-index: 100; }
.header h1 { color: #00f5ff; font-size: 1.3rem; text-shadow: 0 0 15px rgba(0,245,255,0.5); }
.header-right { display: flex; gap: 12px; align-items: center; }
.header a { color: rgba(255,255,255,0.7); text-decoration: none; padding: 8px 16px; border-radius: 6px; transition: all 0.2s; font-size: 0.9rem; }
.header a:hover { background: rgba(0,245,255,0.1); color: #00f5ff; }
.nav { background: rgba(20,20,35,0.95); border-bottom: 1px solid rgba(255,255,255,0.05); padding: 0 30px; display: flex; gap: 4px; flex-wrap: wrap; }
.nav a { color: rgba(255,255,255,0.6); text-decoration: none; padding: 10px 16px; border-radius: 6px; font-size: 0.88rem; transition: all 0.2s; }
.nav a:hover { background: rgba(0,245,255,0.08); color: #00f5ff; }
.nav a.active, .nav a.current { background: rgba(0,245,255,0.12); color: #00f5ff; }
.container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
.stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 30px; }
.stat-card { background: rgba(20,20,35,0.9); border: 1px solid rgba(0,245,255,0.2); border-radius: 12px; padding: 18px; text-align: center; }
.stat-card .num { font-size: 2rem; font-weight: bold; color: #00f5ff; text-shadow: 0 0 20px rgba(0,245,255,0.4); }
.stat-card .label { color: rgba(255,255,255,0.5); margin-top: 6px; font-size: 0.82rem; }
.stat-card .sub { color: rgba(255,200,0,0.7); font-size: 0.75rem; margin-top: 2px; }
.section { background: rgba(20,20,35,0.9); border: 1px solid rgba(0,245,255,0.2); border-radius: 12px; padding: 24px; margin-bottom: 20px; }
.section h2 { color: #00f5ff; margin-bottom: 20px; font-size: 1.1rem; }
.top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; padding: 12px 14px; border-bottom: 1px solid rgba(255,255,255,0.07); }
th { color: rgba(255,255,255,0.45); font-weight: 500; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.05em; }
tr:hover td { background: rgba(0,245,255,0.04); }
.badge { padding: 3px 10px; border-radius: 20px; font-size: 0.78rem; display: inline-block; }
.badge-active, .badge-published { background: rgba(0,245,255,0.18); color: #00f5ff; }
.badge-pending { background: rgba(255,200,0,0.18); color: #ffc800; }
.badge-approved { background: rgba(0,200,100,0.18); color: #00c864; }
.badge-spam, .badge-hidden { background: rgba(255,60,60,0.18); color: #ff6b6b; }
.badge-draft { background: rgba(255,200,0,0.18); color: #ffc800; }
.btn { padding: 6px 14px; border-radius: 6px; font-size: 0.83rem; text-decoration: none; display: inline-block; transition: all 0.2s; cursor: pointer; border: none; font-family: inherit; }
.btn-primary { background: #00f5ff; color: #0f0f1a; font-weight: 600; }
.btn-primary:hover { box-shadow: 0 4px 15px rgba(0,245,255,0.4); }
.btn-secondary { background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.7); border: 1px solid rgba(255,255,255,0.15); }
.btn-secondary:hover { background: rgba(255,255,255,0.14); color: #fff; }
.btn-danger { background: rgba(255,60,60,0.15); color: #ff6b6b; border: 1px solid rgba(255,60,60,0.3); }
.btn-danger:hover { background: rgba(255,60,60,0.25); }
.btn-sm { padding: 4px 10px; font-size: 0.78rem; }
.actions { display: flex; gap: 6px; flex-wrap: wrap; }
.form-group { margin-bottom: 18px; }
label { display: block; color: rgba(255,255,255,0.65); margin-bottom: 7px; font-size: 0.88rem; }
input, select, textarea { width: 100%; padding: 11px 14px; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; color: #e8e8f0; font-size: 0.92rem; transition: border-color 0.2s; }
input:focus, select:focus, textarea:focus { outline: none; border-color: #00f5ff; box-shadow: 0 0 0 2px rgba(0,245,255,0.1); }
textarea { resize: vertical; min-height: 100px; }
.form-actions { display: flex; gap: 10px; margin-top: 24px; }
.msg { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; }
.msg-success { background: rgba(0,200,100,0.15); border: 1px solid rgba(0,200,100,0.3); color: #00c864; }
.msg-error { background: rgba(255,60,60,0.15); border: 1px solid rgba(255,60,60,0.3); color: #ff6b6b; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.empty { text-align: center; padding: 40px; color: rgba(255,255,255,0.3); }
.info-table { width: 100%; }
.info-table td { padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
.info-table td:first-child { color: rgba(255,255,255,0.45); width: 200px; }

</style>
</head>
<body>
<div class="header">
    <h1>🐱 管理后台</h1>
    <div class="header-right">
        <a href="../index.php" target="_blank">查看首页</a>
        <a href="logout.php">退出</a>
    </div>
</div>

    <div class="nav">
        <a href="index.php">📊 概览</a>
        <a href="edit.php">📝 文章</a>
        <a href="links.php">🔗 友链</a>
        <a href="guestbook_admin.php">💬 留言</a>
        <a href="categories.php">📂 分类</a>
        <a href="tags.php">🏷️ 标签</a>
        <a href="comments.php">💭 评论</a>
        <a href="settings.php">⚙️ 设置</a>
        <a href="api_keys_admin.php">🔑 API</a>
    </div>
    <div class="container">

<div class="section">
<h2>📂 分类管理</h2>
<?php if($msg) echo $msg; ?>
<div class="top-bar"><button type="button" class="btn btn-primary" onclick="document.getElementById('add-form').style.display=document.getElementById('add-form').style.display==='none'?'block':'none'">+ 添加分类</button></div>
<div id="add-form" style="display:none; background:rgba(0,0,0,0.3); border:1px solid rgba(0,245,255,0.15); border-radius:10px; padding:20px; margin-bottom:20px;">
<form method="post">
<input type="hidden" name="action" value="add">
<div class="form-row"><div class="form-group"><label>分类名称 *</label><input name="name" required></div><div class="form-group"><label>描述</label><input name="description" placeholder="可选"></div></div>
<div class="form-actions"><button type="submit" class="btn btn-primary">添加</button><button type="button" class="btn btn-secondary" onclick="document.getElementById('add-form').style.display='none'">取消</button></div>
</form>
</div>
<?php if($edit): ?>
<div style="background:rgba(0,200,100,0.08); border:1px solid rgba(0,200,100,0.25); border-radius:10px; padding:20px; margin-bottom:20px;">
<form method="post">
<input type="hidden" name="action" value="update">
<input type="hidden" name="id" value="<?php echo $edit['id']; ?>">
<div class="form-row"><div class="form-group"><label>分类名称 *</label><input name="name" value="<?php echo e($edit['name']); ?>" required></div><div class="form-group"><label>描述</label><input name="description" value="<?php echo e($edit['description']); ?>"></div></div>
<div class="form-actions"><button type="submit" class="btn btn-primary">保存修改</button><a href="categories.php" class="btn btn-secondary">取消</a></div>
</form>
</div>
<?php endif; ?>
<?php if(count($categories)): ?>
<table>
<thead><tr><th>ID</th><th>名称</th><th>Slug</th><th>描述</th><th>文章数</th><th>操作</th></tr></thead>
<tbody>
<?php foreach($categories as $c): ?>
<tr>
    <td><?php echo $c['id']; ?></td>
    <td><?php echo e($c['name']); ?></td>
    <td style="color:rgba(255,255,255,0.4); font-size:0.82rem;"><?php echo e($c['slug']); ?></td>
    <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:rgba(255,255,255,0.5);"><?php echo e($c['description']); ?></td>
    <td><span class="badge badge-active"><?php echo $c['post_count']; ?></span></td>
    <td class="actions">
        <a href="?edit=<?php echo $c['id']; ?>" class="btn btn-secondary btn-sm">编辑</a>
        <form method="post" style="display:inline;" onsubmit="return confirm('确认删除？文章不会被删除但会取消分类。')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $c['id']; ?>"><button type="submit" class="btn btn-danger btn-sm">删除</button></form>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?><div class="empty">暂无分类</div><?php endif; ?>
</div>
    </div>
</body>
</html>