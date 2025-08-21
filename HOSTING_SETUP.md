# 🌐 Hosting ga Joylash - GitHub dan

## 📋 GitHub dan Yuklash

### 1. GitHub Repository
```
https://github.com/azizbek-web-dev/YouTube-Live-to-Telegram-Stream-PHP-
```

### 2. Hosting da Yuklash
```bash
# SSH orqali (agar SSH yoqilgan bo'lsa)
git clone https://github.com/azizbek-web-dev/YouTube-Live-to-Telegram-Stream-PHP-.git

# Yoki ZIP download
# GitHub da "Code" tugmasini bosing -> "Download ZIP"
```

## 🚀 Tezkor Joylash

### 1. Fayllarni Yuklash
```bash
# GitHub dan ZIP yuklab oling
# Extract qiling public_html papkasiga
```

### 2. Environment Sozlash
```bash
# .env faylini yarating
cp env.example .env

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

# Production
APP_ENV=production
LOG_LEVEL=INFO
LOG_FILE=logs/app.log
```

### 3. Composer Dependencies
```bash
# Hosting da composer o'rnatilgan bo'lsa
composer install --no-dev --optimize-autoloader

# Yoki vendor papkasini local da yarating va yuklang
composer install --no-dev --optimize-autoloader
# vendor papkasini hosting ga yuklang
```

### 4. Papka Huquqlari
```bash
chmod 755 public/sessions
chmod 755 uploads
chmod 755 logs
chmod 644 .env
```

### 5. Database Yaratish
```sql
CREATE DATABASE telegram_live CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 🔄 Yangilash

### 1. Git orqali (SSH bo'lsa)
```bash
cd /path/to/your/app
git pull origin main
composer install --no-dev --optimize-autoloader
```

### 2. Manual yangilash
- GitHub dan yangi ZIP yuklab oling
- Eski fayllarni o'chiring
- Yangi fayllarni yuklang
- .env faylini qayta sozlang

## 🌐 Web Interfeys

### 1. Asosiy sahifa
```
https://yourdomain.com/public/
```

### 2. API Endpoint
```
https://yourdomain.com/public/api.php
```

## 🔧 Xatoliklarni Tuzatish

### 1. Class Not Found
```bash
composer dump-autoload --optimize
```

### 2. Database Xatosi
- .env faylini tekshiring
- Database mavjudligini tekshiring

### 3. Session Papka
```bash
chmod 755 public/sessions
```

### 4. Log Xatosi
```bash
chmod 755 logs
touch logs/app.log
chmod 666 logs/app.log
```

## 📱 Telegram Bot

### 1. Bot Token
- [@BotFather](https://t.me/BotFather) ga yozing
- Yangi bot yarating
- Token ni oling

### 2. Webhook
```php
// api.php ga qo'shing
$bot = new TelegramBot($token);
$bot->setWebhook('https://yourdomain.com/public/api.php');
```

## 🔒 Xavfsizlik

### 1. SSL Sertifikat
- HTTPS ni majburiy qiling
- SSL sertifikatni yoqing

### 2. .htaccess
- public/.htaccess faylida xavfsizlik sozlamalari mavjud

### 3. Environment
- .env fayl hech qachon web orqali ochiq emas

## 📊 Monitoring

### 1. Log Fayllar
```bash
tail -f logs/app.log
tail -f public/MadelineProto.log
```

### 2. Database
```sql
SELECT * FROM telegram_sessions;
SELECT * FROM live_streams;
```

## 📞 Qo'llab-quvvatlash

- **GitHub**: [Repository](https://github.com/azizbek-web-dev/YouTube-Live-to-Telegram-Stream-PHP-)
- **Issues**: GitHub Issues orqali
- **Documentation**: [README.md](README.md)

---

✅ **GitHub dan hosting ga joylash muvaffaqiyatli bo'lsin!**
