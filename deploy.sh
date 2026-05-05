#!/bin/bash
# ============================================
# 🍅 YourBlog博客 - 一键部署脚本
# ============================================
# 用法: bash deploy.sh
# ============================================

set -e

echo "🍅 YourBlog博客 - 一键部署"
echo "========================"

# 颜色
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

info() { echo -e "${CYAN}[INFO]${NC} $1"; }
success() { echo -e "${GREEN}[OK]${NC} $1"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }

# 1. 安装依赖
info "安装 PHP, MySQL, Nginx..."
apt-get update -qq
apt-get install -y -qq php php-mysql php-cgi mysql-server nginx > /dev/null 2>&1
success "依赖安装完成"

# 2. 启动 MySQL
info "启动 MySQL..."
service mysql start
success "MySQL 已启动"

# 3. 初始化数据库
info "初始化数据库..."
read -p "请输入 MySQL root 密码 (回车=无密码): " MYSQL_ROOT_PASS
MYSQL_PASS_ARG=""
if [ -n "$MYSQL_ROOT_PASS" ]; then
    MYSQL_PASS_ARG="-p$MYSQL_ROOT_PASS"
fi

mysql $MYSQL_PASS_ARG -e "CREATE DATABASE IF NOT EXISTS tomato_blog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || true

# 导入SQL
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
if [ -n "$MYSQL_ROOT_PASS" ]; then
    mysql $MYSQL_PASS_ARG tomato_blog < "$SCRIPT_DIR/init.sql"
else
    mysql tomato_blog < "$SCRIPT_DIR/init.sql"
fi
success "数据库初始化完成"

# 4. 配置 PHP
info "配置 PHP..."
# 启用 PHP-FPM
sed -i 's/^;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /etc/php/*/cgi/php.ini 2>/dev/null || true
# 设置时区
sed -i "s/^;date.timezone =/date.timezone = Asia\/Shanghai/" /etc/php/*/cgi/php.ini 2>/dev/null || true
sed -i "s/^;date.timezone =/date.timezone = Asia\/Shanghai/" /etc/php/*/fpm/php.ini 2>/dev/null || true

# 5. 部署文件
info "部署网站文件..."
DEPLOY_DIR="/var/www/blog"
mkdir -p "$DEPLOY_DIR"
cp -r "$SCRIPT_DIR/." "$DEPLOY_DIR/"
chown -R www-data:www-data "$DEPLOY_DIR"
success "网站文件已部署到 $DEPLOY_DIR"

# 6. 配置 Nginx
info "配置 Nginx..."
cat > /etc/nginx/sites-available/blog <<'NGINX'
server {
    listen 80;
    server_name _;
    root /var/www/blog;
    index index.php index.html;
    
    client_max_body_size 50M;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php-fpm.sock;
    }
    
    location ~ /\.ht {
        deny all;
    }
    
    # API路由
    location /api/ {
        try_files $uri /api.php?$query_string;
    }
    
    # 静态资源缓存
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
NGINX

# 启用站点
ln -sf /etc/nginx/sites-available/blog /etc/nginx/sites-enabled/blog
rm -f /etc/nginx/sites-enabled/default

# 测试配置
nginx -t
service nginx restart
success "Nginx 配置完成"

# 7. 启动 PHP-FPM
service php-fpm start 2>/dev/null || service php8.2-fpm start 2>/dev/null || true
success "PHP-FPM 已启动"

# 8. 完成
echo ""
echo "============================================"
echo "🎉 YourBlog博客部署完成!"
echo "============================================"
echo ""
echo "📍 网站目录: /var/www/blog"
echo "🔑 数据库: tomato_blog"
echo "👤 管理员账号: admin / YOUR_ADMIN_PASSWORD"
echo "🔑 默认API密钥: tomato_api_2026_secret"
echo ""

# 获取服务器IP
SERVER_IP=$(hostname -I 2>/dev/null | awk '{print $1}' || echo "你的服务器IP")
echo "🌐 访问地址: http://$SERVER_IP"
echo ""
echo "💡 提示:"
echo "   1. 修改 config.php 中的数据库配置"
echo "   2. 修改 init.sql 中的默认密码"
echo "   3. 配置域名 DNS 指向此服务器"
echo "   4. 可通过 API 远程管理文章"
echo ""
