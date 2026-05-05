<?php
session_start();
require_once "../config.php";
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }


$pdo = getDB();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['action'] ?? '';
    if ($a === 'approve') {
        $pdo->prepare("UPDATE comments SET status='approved' WHERE id=?")->execute([intval($_POST['id'])]);
        $msg = '<div class="msg msg-success">评论已通过！</div>';
    } elseif ($a === 'spam') {
        $pdo->prepare("UPDATE comments SET status='spam' WHERE id=?")->execute([intval($_POST['id'])]);
        $msg = '<div class="msg msg-success">评论已标记为垃圾！</div>';
    } elseif ($a === 'delete') {
        $pdo->prepare("DELETE FROM comments WHERE id=?")->execute([intval($_POST['id'])]);
        $msg = '<div class="msg msg-success">评论已删除！</div>';
    } elseif ($a === 'approve_all') {
        $pdo->query("UPDATE comments SET status='approved' WHERE status='pending'");
        $msg = '<div class="msg msg-success">所有待审评论已通过！</div>';
    }
}
$filter = $_GET['filter'] ?? 'all';
$sql = "SELECT c.*, p.title as post_title FROM comments c LEFT JOIN posts p ON c.post_id=p.id";
if ($filter === 'pending') $sql .= " WHERE c.status='pending'";
elseif ($filter === 'approved') $sql .= " WHERE c.status='approved'";
elseif ($filter === 'spam') $sql .= " WHERE c.status='spam'";
$sql .= " ORDER BY c.created_at DESC LIMIT 200";
$comments = $pdo->query($sql)->fetchAll();
$cnt_all = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$cnt_pending = $pdo->query("SELECT COUNT(*) FROM comments WHERE status='pending'")->fetchColumn();
$cnt_approved = $pdo->query("SELECT COUNT(*) FROM comments WHERE status='approved'")->fetchColumn();
$cnt_spam = $pdo->query("SELECT COUNT(*) FROM comments WHERE status='spam'")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>评论管理</title>
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
<h2>💭 评论管理</h2>
<?php if($msg) echo $msg; ?>
<div class="top-bar">
    <div style="display:flex; gap:6px; flex-wrap:wrap;">
        <a href="?filter=all" class="btn <?php if($filter==='all') echo 'btn-primary'; else echo 'btn-secondary'; ?>">全部 (<?php echo $cnt_all; ?>)</a>
        <a href="?filter=pending" class="btn <?php if($filter==='pending') echo 'btn-primary'; else echo 'btn-secondary'; ?>">待审 (<?php echo $cnt_pending; ?>)</a>
        <a href="?filter=approved" class="btn <?php if($filter==='approved') echo 'btn-primary'; else echo 'btn-secondary'; ?>">已过 (<?php echo $cnt_approved; ?>)</a>
        <a href="?filter=spam" class="btn <?php if($filter==='spam') echo 'btn-primary'; else echo 'btn-secondary'; ?>">垃圾 (<?php echo $cnt_spam; ?>)</a>
    </div>
    <?php if ($cnt_pending > 0): ?>
    <form method="post" style="display:inline;"><input type="hidden" name="action" value="approve_all"><button type="submit" class="btn btn-primary" onclick="return confirm('一键通过所有待审评论？')">一键通过</button></form>
    <?php endif; ?>
</div>
<?php if(count($comments)): ?>
<table>
<thead><tr><th>ID</th><th>文章</th><th>昵称</th><th>内容</th><th>状态</th><th>时间</th><th>操作</th></tr></thead>
<tbody>
<?php foreach($comments as $c): ?>
<tr>
    <td><?php echo $c['id']; ?></td>
    <td style="max-width:140px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
        <a href="../post.php?id=<?php echo $c['post_id']; ?>" target="_blank" style="color:#00f5ff; font-size:0.82rem;"><?php echo e(substr($c['post_title']??'已删',0,18)); ?></a>
    </td>
    <td><?php echo e($c['author']); ?></td>
    <td style="max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:rgba(255,255,255,0.6);"><?php echo e($c['content']); ?></td>
    <td><span class="badge <?php if($c['status']==='approved') echo 'badge-approved'; elseif($c['status']==='pending') echo 'badge-pending'; else echo 'badge-spam'; ?>"><?php echo $c['status']; ?></span></td>
    <td style="color:rgba(255,255,255,0.35); font-size:0.78rem;"><?php echo $c['created_at']; ?></td>
    <td class="actions">
        <?php if ($c['status'] !== 'approved'): ?>
        <form method="post" style="display:inline;"><input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?php echo $c['id']; ?>"><button type="submit" class="btn btn-primary btn-sm">通过</button></form>
        <?php endif; ?>
        <?php if ($c['status'] !== 'spam'): ?>
        <form method="post" style="display:inline;"><input type="hidden" name="action" value="spam"><input type="hidden" name="id" value="<?php echo $c['id']; ?>"><button type="submit" class="btn btn-secondary btn-sm">垃圾</button></form>
        <?php endif; ?>
        <form method="post" style="display:inline;" onsubmit="return confirm('确认删除？')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $c['id']; ?>"><button type="submit" class="btn btn-danger btn-sm">删除</button></form>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?><div class="empty">暂无评论</div><?php endif; ?>
</div>
    </div>
</body>
</html>