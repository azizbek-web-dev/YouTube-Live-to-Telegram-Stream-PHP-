#!/bin/bash

# ===========================================
# Telegram Live Streaming - Beget Deployment
# ===========================================

echo "🚀 Telegram Live Streaming - Beget hosting ga joylash"
echo "=================================================="

# 1. Production environment sozlash
echo "📝 Production environment sozlanmoqda..."
cp env.example .env.production

# 2. Composer production dependencies
echo "📦 Production dependencies o'rnatilmoqda..."
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Kerakli papkalarni yaratish
echo "📁 Kerakli papkalar yaratilmoqda..."
mkdir -p public/sessions
mkdir -p uploads
mkdir -p logs

# 4. Papka huquqlarini o'rnatish
echo "🔐 Papka huquqlari o'rnatilmoqda..."
chmod 755 public/sessions
chmod 755 uploads
chmod 755 logs

# 5. Cache va temporary fayllarni tozalash
echo "🧹 Cache fayllar tozalanmoqda..."
find . -name "*.tmp" -delete
find . -name "*.log" -delete
find . -name "*.lock" -delete
find . -name "*.session" -delete

# 6. Production .htaccess yaratish
echo "🔒 Production .htaccess yaratilmoqda..."
cat > public/.htaccess << 'EOF'
# Production .htaccess
RewriteEngine On

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Protect sensitive files
<Files ".env">
    Order allow,deny
    Deny from all
</Files>

<Files "*.log">
    Order allow,deny
    Deny from all
</Files>

# Redirect all to index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
EOF

# 7. Deployment ma'lumotlari
echo ""
echo "✅ Deployment tayyor!"
echo ""
echo "📋 Keyingi qadamlar:"
echo "1. .env.production faylini .env ga o'zgartiring"
echo "2. Database ma'lumotlarini kiriting"
echo "3. Telegram va YouTube API kalitlarini kiriting"
echo "4. Beget panel orqali database yarating"
echo "5. SSL sertifikatni yoqing"
echo ""
echo "🌐 Web interfeys: yourdomain.com"
echo "📱 Telegram: @your_bot_username"
echo ""
echo "🔧 Xatoliklar bo'lsa: logs/ directory ni tekshiring"
