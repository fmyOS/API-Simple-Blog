<?php
session_start();
require_once "../config.php";
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }


$pdo = getDB();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['action'] ?? '';
    if ($a === 'add') {
        $pdo->prepare("INSERT INTO links (name, url, logo, description, status, sort_order) VALUES (?,?,?,?,?,?)")
            ->execute([trim($_POST['name']), trim($_POST['url']), trim($_POST['logo']), trim($_POST['description']), $_POST['status'], intval($_POST['sort_order'])]);
        $msg = '<div class="msg msg-success">友链添加成功！</div>';
    } elseif ($a === 'update') {
        $pdo->prepare("UPDATE links SET name=?, url=?, logo=?, description=?, status=?, sort_order=? WHERE id=?")
            ->execute([trim($_POST['name']), trim($_POST['url']), trim($_POST['logo']), trim($_POST['description']), $_POST['status'], intval($_POST['sort_order']), intval($_POST['id'])]);
        $msg = '<div class="msg msg-success">友链更新成功！</div>';
    } elseif ($a === 'delete') {
        $pdo->prepare("DELETE FROM links WHERE id=?")->execute([intval($_POST['id'])]);
        $msg = '<div class="msg msg-success">友链已删除！</div>';
    }
}
$links = $pdo->query("SELECT * FROM links ORDER BY sort_order ASC, id DESC")->fetchAll();
$edit = isset($_GET['edit']) ? $pdo->query("SELECT * FROM links WHERE id=".intval($_GET['edit']))->fetch() : null;

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>友链管理</title>
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
<h2>🔗 友链列表</h2>
<?php if($msg) echo $msg; ?>
<div class="top-bar"><button type="button" class="btn btn-primary" onclick="document.getElementById('add-form').style.display=document.getElementById('add-form').style.display==='none'?'block':'none'">+ 添加友链</button></div>
<div id="add-form" style="display:none; background:rgba(0,0,0,0.3); border:1px solid rgba(0,245,255,0.15); border-radius:10px; padding:20px; margin-bottom:20px;">
<form method="post">
<input type="hidden" name="action" value="add">
<div class="form-row">
    <div class="form-group"><label>网站名称 *</label><input name="name" required></div>
    <div class="form-group"><label>网址 *</label><input name="url" required></div>
</div>
<div class="form-row">
    <div class="form-group"><label>Logo URL</label><input name="logo" placeholder="https://..."></div>
    <div class="form-group"><label>排序</label><input name="sort_order" type="number" value="0"></div>
</div>
<div class="form-group"><label>描述</label><textarea name="description" rows="2"></textarea></div>
<div class="form-group"><label>状态</label><select name="status"><option value="active">显示</option><option value="hidden">隐藏</option></select></div>
<div class="form-actions"><button type="submit" class="btn btn-primary">添加</button><button type="button" class="btn btn-secondary" onclick="document.getElementById('add-form').style.display='none'">取消</button></div>
</form>
</div>
<?php if($edit): ?>
<div style="background:rgba(0,200,100,0.08); border:1px solid rgba(0,200,100,0.25); border-radius:10px; padding:20px; margin-bottom:20px;">
<form method="post">
<input type="hidden" name="action" value="update">
<input type="hidden" name="id" value="<?php echo $edit['id']; ?>">
<div class="form-row">
    <div class="form-group"><label>网站名称 *</label><input name="name" value="<?php echo e($edit['name']); ?>" required></div>
    <div class="form-group"><label>网址 *</label><input name="url" value="<?php echo e($edit['url']); ?>" required></div>
</div>
<div class="form-row">
    <div class="form-group"><label>Logo URL</label><input name="logo" value="<?php echo e($edit['logo']); ?>"></div>
    <div class="form-group"><label>排序</label><input name="sort_order" type="number" value="<?php echo $edit['sort_order']; ?>"></div>
</div>
<div class="form-group"><label>描述</label><textarea name="description" rows="2"><?php echo e($edit['description']); ?></textarea></div>
<div class="form-group"><label>状态</label>
<select name="status">
    <option value="active" <?php if($edit['status']==='active') echo 'selected'; ?>>显示</option>
    <option value="hidden" <?php if($edit['status']==='hidden') echo 'selected'; ?>>隐藏</option>
</select>
</div>
<div class="form-actions"><button type="submit" class="btn btn-primary">保存修改</button><a href="links.php" class="btn btn-secondary">取消</a></div>
</form>
</div>
<?php endif; ?>
<?php if(count($links)): ?>
<table>
<thead><tr><th>ID</th><th>名称</th><th>网址</th><th>状态</th><th>排序</th><th>操作</th></tr></thead>
<tbody>
<?php foreach($links as $l): ?>
<tr>
    <td><?php echo $l['id']; ?></td>
    <td><?php echo e($l['name']); ?></td>
    <td><a href="<?php echo e($l['url']); ?>" target="_blank" style="color:#00f5ff;"><?php echo e(substr($l['url'],0,45)); ?></a></td>
    <td><span class="badge <?php echo $l['status']==='active'?'badge-active':'badge-hidden'; ?>"><?php echo $l['status']==='active'?'显示':'隐藏'; ?></span></td>
    <td><?php echo $l['sort_order']; ?></td>
    <td class="actions">
        <a href="?edit=<?php echo $l['id']; ?>" class="btn btn-secondary btn-sm">编辑</a>
        <form method="post" style="display:inline;" onsubmit="return confirm('确认删除？')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $l['id']; ?>"><button type="submit" class="btn btn-danger btn-sm">删除</button></form>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?><div class="empty">暂无友链</div><?php endif; ?>
</div>
    </div>
</body>
</html>