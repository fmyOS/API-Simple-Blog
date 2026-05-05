<?php
session_start();
require_once "../config.php";
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }


$pdo = getDB();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['action'] ?? '';
    if ($a === 'create') {
        $name = trim($_POST['name']);
        $new_key = bin2hex(random_bytes(32));
        $expires = $_POST['expires_at'] ?: null;
        try {
            $pdo->prepare("INSERT INTO api_keys (api_key, name, expires_at) VALUES (?,?,?)")->execute([$new_key, $name, $expires]);
            $msg = '<div class="msg msg-success">API Key 创建成功！<br><code style="color:#00f5ff; font-size:0.85rem; word-break:break-all; margin-top:8px; display:block;">' . e($new_key) . '</code></div>';
        } catch (PDOException $e) {
            $msg = '<div class="msg msg-error">创建失败。</div>';
        }
    } elseif ($a === 'delete') {
        $pdo->prepare("DELETE FROM api_keys WHERE id=?")->execute([intval($_POST['id'])]);
        $msg = '<div class="msg msg-success">Key 已删除！</div>';
    }
}
$keys = $pdo->query("SELECT * FROM api_keys ORDER BY created_at DESC")->fetchAll();

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>API密钥管理</title>
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
<h2>🔑 API 密钥管理</h2>
<?php if($msg) echo $msg; ?>
<div class="top-bar"><button type="button" class="btn btn-primary" onclick="document.getElementById('create-form').style.display=document.getElementById('create-form').style.display==='none'?'block':'none'">+ 创建新 Key</button></div>
<div id="create-form" style="display:none; background:rgba(0,0,0,0.3); border:1px solid rgba(0,245,255,0.15); border-radius:10px; padding:20px; margin-bottom:20px;">
<form method="post">
<input type="hidden" name="action" value="create">
<div class="form-row">
    <div class="form-group"><label>名称/用途 *</label><input name="name" required placeholder="例如：友链检查脚本"></div>
    <div class="form-group"><label>过期时间（留空永不过期）</label><input name="expires_at" type="datetime-local"></div>
</div>
<div class="form-actions"><button type="submit" class="btn btn-primary">创建</button><button type="button" class="btn btn-secondary" onclick="document.getElementById('create-form').style.display='none'">取消</button></div>
</form>
</div>
<?php if(count($keys)): ?>
<table>
<thead><tr><th>ID</th><th>名称</th><th>Key（前8位）</th><th>请求数</th><th>最近使用</th><th>过期时间</th><th>创建时间</th><th>操作</th></tr></thead>
<tbody>
<?php foreach($keys as $k): ?>
<tr>
    <td><?php echo $k['id']; ?></td>
    <td><?php echo e($k['name']); ?></td>
    <td><code style="color:#00f5ff; font-size:0.82rem;"><?php echo e(substr($k['api_key'],0,8)); ?>...</code></td>
    <td><?php echo number_format($k['request_count']); ?></td>
    <td style="color:rgba(255,255,255,0.4); font-size:0.8rem;"><?php echo $k['last_used'] ?? '从未'; ?></td>
    <td>
        <?php if ($k['expires_at']): ?>
            <span class="badge <?php echo strtotime($k['expires_at']) > time() ? 'badge-active' : 'badge-spam'; ?>"><?php echo $k['expires_at']; ?></span>
        <?php else: ?><span class="badge badge-active">永不过期</span><?php endif; ?>
    </td>
    <td style="color:rgba(255,255,255,0.35); font-size:0.8rem;"><?php echo $k['created_at']; ?></td>
    <td class="actions">
        <form method="post" style="display:inline;" onsubmit="return confirm('确认删除？')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $k['id']; ?>"><button type="submit" class="btn btn-danger btn-sm">删除</button></form>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?><div class="empty">暂无 API Key</div><?php endif; ?>
</div>
    </div>
</body>
</html>