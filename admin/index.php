<?php
session_start();
require_once "../config.php";
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$pdo = getDB();
$stats = [];
$stats['posts']      = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$stats['published']  = $pdo->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn();
$stats['categories'] = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$stats['tags']       = $pdo->query("SELECT COUNT(*) FROM tags")->fetchColumn();
$stats['comments']   = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$stats['pending_c']  = $pdo->query("SELECT COUNT(*) FROM comments WHERE status='pending'")->fetchColumn();
$stats['guestbook']  = $pdo->query("SELECT COUNT(*) FROM guestbook")->fetchColumn();
$stats['pending_g']  = $pdo->query("SELECT COUNT(*) FROM guestbook WHERE status='pending'")->fetchColumn();
$stats['links']      = $pdo->query("SELECT COUNT(*) FROM links")->fetchColumn();
$stats['api_keys']   = $pdo->query("SELECT COUNT(*) FROM api_keys")->fetchColumn();

$recent_posts = $pdo->query("SELECT id, title, status, created_at FROM posts ORDER BY created_at DESC LIMIT 8")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>管理后台</title>
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

.quick-links { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; }
.quick-link { background: rgba(0,245,255,0.06); border: 1px solid rgba(0,245,255,0.15); border-radius: 10px; padding: 16px; text-align: center; text-decoration: none; color: rgba(255,255,255,0.8); font-size: 0.9rem; transition: all 0.2s; display: block; }
.quick-link:hover { background: rgba(0,245,255,0.12); border-color: rgba(0,245,255,0.4); color: #00f5ff; transform: translateY(-2px); }
.quick-link .icon { font-size: 1.4rem; display: block; margin-bottom: 6px; }
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
    <div class="stats">
        <div class="stat-card"><div class="num"><?php echo $stats['posts']; ?></div><div class="label">全部文章</div><div class="sub">已发布 <?php echo $stats['published']; ?></div></div>
        <div class="stat-card"><div class="num"><?php echo $stats['categories']; ?></div><div class="label">分类</div></div>
        <div class="stat-card"><div class="num"><?php echo $stats['tags']; ?></div><div class="label">标签</div></div>
        <div class="stat-card"><div class="num"><?php echo $stats['comments']; ?></div><div class="label">评论</div><div class="sub">待审 <?php echo $stats['pending_c']; ?></div></div>
        <div class="stat-card"><div class="num"><?php echo $stats['guestbook']; ?></div><div class="label">留言</div><div class="sub">待审 <?php echo $stats['pending_g']; ?></div></div>
        <div class="stat-card"><div class="num"><?php echo $stats['links']; ?></div><div class="label">友链</div></div>
        <div class="stat-card"><div class="num"><?php echo $stats['api_keys']; ?></div><div class="label">API Key</div></div>
    </div>

    <div class="section">
    <h2>⚡ 快捷操作</h2>
    <div class="quick-links">
        <a href="edit.php" class="quick-link"><span class="icon">📝</span>新建文章</a>
        <a href="links.php" class="quick-link"><span class="icon">🔗</span>友链管理</a>
        <a href="guestbook_admin.php" class="quick-link"><span class="icon">💬</span>留言<?php if($stats['pending_g']>0) echo '<span style="color:#ffc800;"> ('.$stats['pending_g'].')</span>'; ?></a>
        <a href="categories.php" class="quick-link"><span class="icon">📂</span>分类管理</a>
        <a href="tags.php" class="quick-link"><span class="icon">🏷️</span>标签管理</a>
        <a href="comments.php" class="quick-link"><span class="icon">💭</span>评论<?php if($stats['pending_c']>0) echo '<span style="color:#ffc800;"> ('.$stats['pending_c'].')</span>'; ?></a>
        <a href="settings.php" class="quick-link"><span class="icon">⚙️</span>网站设置</a>
        <a href="api_keys_admin.php" class="quick-link"><span class="icon">🔑</span>API密钥</a>
    </div>
    </div>

    <div class="section">
    <h2>📝 最近文章</h2>
    <table>
        <thead><tr><th>ID</th><th>标题</th><th>状态</th><th>时间</th><th>操作</th></tr></thead>
        <tbody>
        <?php foreach ($recent_posts as $p): ?>
            <tr>
                <td><?php echo $p['id']; ?></td>
                <td><?php echo htmlspecialchars($p['title']); ?></td>
                <td><span class="badge badge-<?php echo $p['status']; ?>"><?php echo $p['status']; ?></span></td>
                <td style="color:rgba(255,255,255,0.4); font-size:0.82rem;"><?php echo $p['created_at']; ?></td>
                <td class="actions">
                    <a href="../post.php?id=<?php echo $p['id']; ?>" class="btn btn-primary btn-sm" target="_blank">查看</a>
                    <a href="edit.php?id=<?php echo $p['id']; ?>" class="btn btn-primary btn-sm">编辑</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    </div>
</body>
</html>