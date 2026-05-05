# Your Blog - 轻量级PHP博客系统

简洁好看的纯PHP博客系统，无需框架，开箱即用。

## 功能特性

- RESTful API - 完整的文章管理API接口
- 赛博朋克主题 - 暗色系+渐变+粒子动画
- 响应式设计 - 适配手机/平板/电脑
- 评论系统 - 访客评论功能
- 分类和标签 - 文章分类管理
- 后台管理 - 完整的文章管理后台
- API密钥认证 - 安全的API访问控制

## 环境要求

- PHP 7.4+ (建议8.0+)
- MySQL 5.7+ 或 MariaDB 10.3+
- Nginx 或 Apache

## 快速安装

1. 上传所有文件到Web服务器目录
2. 导入数据库: mysql -u root -p < init.sql
3. 修改config.php中的数据库连接信息
4. 设置目录权限: chown -R www-data:www-data /path/to/blog

## 默认账号

- 后台管理: admin / admin123
- API密钥: YOUR_API_KEY

## API接口

所有API请求需要携带X-API-Key请求头。

基础URL: https://your-domain.com/api.php

| 方法 | 端点 | 说明 |
|------|------|------|
| GET | /api.php?action=posts | 获取文章列表 |
| POST | /api.php?action=posts | 创建文章 |
| GET | /api.php?action=post&id=1 | 获取单篇文章 |
| PUT | /api.php?action=posts&id=1 | 更新文章 |
| DELETE | /api.php?action=posts&id=1 | 删除文章 |

## 目录结构

blog/
├── index.php          # 首页
├── post.php           # 文章详情
├── api.php            # REST API
├── admin/             # 后台管理
├── includes/          # 公共组件
├── assets/           # 静态资源
├── config.php        # 配置文件
├── init.sql          # 数据库初始化
└── README.md         # 说明文档

## 安全建议

1. 修改默认密码
2. 修改API密钥
3. 生产环境启用HTTPS
4. 配置防火墙

## 许可证

MIT License
