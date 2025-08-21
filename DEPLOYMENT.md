# 🚀 Telegram Live Streaming - Hosting ga Joylash

## 📋 Oldindan Tayyorgarlik

### 1. API Kalitlarni Olish
- **Telegram API**: [my.telegram.org/apps](https://my.telegram.org/apps)
- **YouTube API**: [Google Cloud Console](https://console.developers.google.com/)

### 2. Database Tayyorlash
- MySQL database yarating
- Database nomi: `telegram_live`
- Username va password ni eslab qoling

## 🌐 Hosting ga Joylash

### 1. Fayllarni Yuklash
```bash
# Barcha fayllarni public_html papkasiga yuklang
# Yoki hosting panel orqali upload qiling
```

### 2. Papka Tuzilishi
```
public_html/
├── src/                    # Asosiy kod
├── public/                 # Web interfeys
├── uploads/                # Yuklangan fayllar
├── logs/                   # Log fayllar
├── vendor/                 # Composer dependencies
├── .env                    # Konfiguratsiya
├── composer.json           # Dependencies
└── index.php              # Asosiy fayl
```

### 3. Environment Sozlash
```bash
# .env faylini yarating
cp .env.example .env

# .env faylini tahrirlang
nano .env
```

**.env faylga quyidagilarni kiriting:**
```env
# Telegram API
TELEGRAM_API_ID=your_api_id
TELEGRAM_API_HASH=your_api_hash
TELEGRAM_PHONE=+998935493969

# YouTube API
YOUTUBE_API_KEY=your_youtube_api_key

# Database
DB_HOST=localhost
DB_NAME=telegram_live
DB_USER=your_db_username
DB_PASS=your_db_password

# Paths
SESSION_PATH=public/sessions/
UPLOAD_PATH=uploads/

# Production
APP_ENV=production
LOG_LEVEL=INFO
LOG_FILE=logs/app.log
```

### 4. Composer Dependencies
```bash
# Hosting da composer o'rnatilgan bo'lsa
composer install --no-dev --optimize-autoloader

# Yoki vendor papkasini local da yarating va yuklang
composer install --no-dev --optimize-autoloader
# vendor papkasini hosting ga yuklang
```

### 5. Papka Huquqlari
```bash
chmod 755 public/sessions
chmod 755 uploads
chmod 755 logs
chmod 644 .env
```

### 6. Database Jadvallar
```sql
-- Database da quyidagi jadvallar avtomatik yaratiladi
-- TelegramManager yaratilganda
```

## 🔧 Xatoliklarni Tuzatish

### 1. Class Not Found Xatosi
```bash
# Composer autoload ni qayta yarating
composer dump-autoload --optimize

# Yoki vendor papkasini qayta yuklang
```

### 2. Database Xatosi
```bash
# .env fayldagi database ma'lumotlarini tekshiring
# Database mavjudligini tekshiring
```

### 3. Session Papka Xatosi
```bash
# Papka huquqlarini tekshiring
ls -la public/sessions
chmod 755 public/sessions
```

### 4. Log Xatosi
```bash
# Log papkasiga yozish huquqini tekshiring
chmod 755 logs
touch logs/app.log
chmod 666 logs/app.log
```

## 🚀 Test Qilish

### 1. Web Interfeys
```
https://yourdomain.com/public/
```

### 2. API Endpoint
```
https://yourdomain.com/public/api.php
```

### 3. Log Fayllar
```bash
tail -f logs/app.log
tail -f public/MadelineProto.log
```

## 📱 Telegram Bot

### 1. Bot Token
- [@BotFather](https://t.me/BotFather) ga yozing
- Yangi bot yarating
- Token ni oling

### 2. Webhook Sozlash
```php
// api.php ga qo'shing
$bot = new TelegramBot($token);
$bot->setWebhook('https://yourdomain.com/public/api.php');
```

## 🔒 Xavfsizlik

### 1. .htaccess
```apache
# public/.htaccess faylida
<Files ".env">
    Order allow,deny
    Deny from all
</Files>
```

### 2. SSL Sertifikat
- HTTPS ni majburiy qiling
- SSL sertifikatni yoqing

### 3. Firewall
- Keraksiz portlarni yoping
- IP cheklovlarni qo'ying

## 📊 Monitoring

### 1. Log Monitoring
```bash
# Log fayllarni kuzatish
tail -f logs/app.log
tail -f public/MadelineProto.log
```

### 2. Database Monitoring
```sql
-- Faol sessionlarni ko'rish
SELECT * FROM telegram_sessions WHERE updated_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);

-- Faol streamlarni ko'rish
SELECT * FROM live_streams WHERE status = 'active';
```

### 3. Performance Monitoring
```bash
# PHP performance
php -m | grep -E "(opcache|apcu)"

# Memory usage
ps aux | grep php
```

## 🔄 Yangilash

### 1. Code Yangilash
```bash
git pull origin main
composer install --no-dev --optimize-autoloader
```

### 2. Database Migration
```sql
-- Yangi jadvallar kerak bo'lsa
-- Database.php da createTables() metodini yangilang
```

### 3. Cache Tozalash
```bash
# Session fayllarni tozalash
rm -rf public/sessions/*
rm -rf logs/*.log
```

## 📞 Qo'llab-quvvatlash

- **Email**: support@yourdomain.com
- **Telegram**: @your_support_bot
- **Documentation**: [README.md](README.md)

---

✅ **Hosting ga joylash muvaffaqiyatli bo'lsin!**
