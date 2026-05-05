-- ============================================
-- Your Blog init.sql
-- ============================================
CREATE DATABASE IF NOT EXISTS blog_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE blog_db;

-- 用户表
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(20) DEFAULT 'admin',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 分类表
CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  description TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 标签表
CREATE TABLE IF NOT EXISTS tags (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 文章表
CREATE TABLE IF NOT EXISTS posts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL,
  content LONGTEXT NOT NULL,
  excerpt TEXT,
  cover_image VARCHAR(500) DEFAULT '',
  category_id INT UNSIGNED DEFAULT NULL,
  view_count INT UNSIGNED DEFAULT 0,
  status ENUM('draft','published') DEFAULT 'published',
  author_id INT UNSIGNED DEFAULT 1,
  published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY slug (slug),
  KEY category_id (category_id),
  KEY status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 文章标签关联表
CREATE TABLE IF NOT EXISTS post_tags (
  post_id INT UNSIGNED NOT NULL,
  tag_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (post_id,tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 评论表
CREATE TABLE IF NOT EXISTS comments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  post_id INT UNSIGNED NOT NULL,
  author VARCHAR(100) NOT NULL,
  email VARCHAR(255) DEFAULT '',
  content TEXT NOT NULL,
  status ENUM('pending','approved','spam') DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY post_id (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 链接表
CREATE TABLE IF NOT EXISTS links (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  url VARCHAR(500) NOT NULL,
  logo VARCHAR(500) DEFAULT '',
  description TEXT,
  status VARCHAR(20) DEFAULT 'active',
  sort_order INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 设置表
CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(100) NOT NULL,
  value TEXT,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- API密钥表
CREATE TABLE IF NOT EXISTS api_keys (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  api_key VARCHAR(64) NOT NULL,
  name VARCHAR(100) DEFAULT 'default',
  request_count INT UNSIGNED DEFAULT 0,
  last_used DATETIME DEFAULT NULL,
  last_used_at DATETIME DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY api_key (api_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 初始数据
-- ============================================

-- 管理员账号 admin / admin123
INSERT INTO users (username, password, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- 默认分类
INSERT INTO categories (name, slug, description) VALUES
('技术分享', 'tech', '技术教程、代码、工具'),
('日常', 'life', '生活日常'),
('随笔', 'notes', '随笔感想');

-- 示例文章
INSERT INTO posts (title, slug, content, excerpt, category_id, status, published_at) VALUES
('欢迎来到Your Blog', 'welcome', '<h2>🐱 欢迎访问！</h2><p>这是一个基于纯PHP开发的轻量级博客系统。</p><p>特点：</p><ul><li>⚡ 轻量快速，无需框架</li><li>🎨 赛博朋克风格</li><li>📝 Markdown支持</li><li>💬 评论系统</li></ul><p>开始写作吧！</p>', '欢迎来到Your Blog，这是一个轻量级PHP博客系统', 2, 'published', NOW());

-- 示例API密钥
INSERT INTO api_keys (api_key, name) VALUES
('YOUR_API_KEY', 'default');

-- 示例链接
INSERT INTO links (name, url, description, status) VALUES
('GitHub', 'https://github.com', '代码仓库', 'active');
