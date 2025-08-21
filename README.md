# 🚀 Telegram Live Streaming Platform

Professional Telegram Live Streaming platformasi - YouTube videolarini Telegram kanallarida jonli efir qilish uchun.

## ✨ Asosiy Xususiyatlar

- 🔐 **Telegram API integratsiyasi** - MadelineProto orqali
- 📺 **YouTube Live Streaming** - YouTube videolarini jonli efir qilish
- 💬 **Telegram kanallar bilan integratsiya** - Avtomatik xabar yuborish
- 🗄️ **MySQL database** - Ma'lumotlarni saqlash
- 🔒 **Xavfsizlik** - Environment variables va .htaccess protection
- 📱 **Responsive Web Interface** - Barcha qurilmalarda ishlaydi

## 🛠️ Texnik Talablar

- **PHP**: 8.0 yoki undan yuqori
- **MySQL**: 5.7 yoki undan yuqori
- **Composer**: Dependency management uchun
- **Web Server**: Apache/Nginx
- **SSL Certificate**: Production uchun majburiy

## 📦 O'rnatish

### 1. Repository ni klonlash
```bash
git clone https://github.com/yourusername/telegram-live-streaming.git
cd telegram-live-streaming
```

### 2. Dependencies ni o'rnatish
```bash
composer install --no-dev --optimize-autoloader
```

### 3. Environment sozlamalari
```bash
cp env.example .env
# .env faylini o'z hosting sozlamalaringiz bilan to'ldiring
```

### 4. Database yaratish
```sql
CREATE DATABASE telegram_live CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Papka huquqlarini o'rnatish
```bash
chmod 755 public/sessions
chmod 755 uploads
chmod 755 logs
```

## ⚙️ Konfiguratsiya

### Telegram API
1. [my.telegram.org/apps](https://my.telegram.org/apps) ga kiring
2. Yangi app yarating
3. API_ID va API_HASH ni oling
4. .env faylga qo'shing

### YouTube API
1. [Google Cloud Console](https://console.developers.google.com/) ga kiring
2. YouTube Data API v3 ni yoqing
3. API key yarating
4. .env faylga qo'shing

### Database
```env
DB_HOST=localhost
DB_NAME=telegram_live
DB_USER=your_username
DB_PASS=your_password
```

## 🚀 Beget Hosting ga Joylash

### 1. Fayllarni yuklash
- Barcha fayllarni `public_html` papkasiga yuklang
- `public` papkasidagi fayllarni asosiy papkaga ko'chiring

### 2. Database sozlash
- Beget panel orqali MySQL database yarating
- Database ma'lumotlarini .env faylga yozing

### 3. SSL sertifikat
- Beget panel orqali SSL sertifikatni yoqing
- HTTPS orqali ishlashni ta'minlang

### 4. Cron job (ixtiyoriy)
```bash
# Har 5 daqiqada session fayllarini tozalash
*/5 * * * * php /path/to/your/app/cleanup_sessions.php
```

## 📁 Fayl Tuzilishi

```
telegram-live-streaming/
├── config/
│   └── database.php          # Database konfiguratsiyasi
├── public/
│   ├── index.php             # Asosiy web interfeys
│   ├── api.php               # API endpoint
│   ├── .htaccess             # Apache sozlamalari
│   └── sessions/             # Telegram session fayllari
├── src/
│   ├── TelegramManager.php   # Telegram API boshqaruvi
│   ├── YouTubeManager.php    # YouTube API boshqaruvi
│   └── LiveStreamManager.php # Live streaming boshqaruvi
├── uploads/                  # Yuklangan fayllar
├── logs/                     # Log fayllar
├── vendor/                   # Composer dependencies
├── .env.example              # Environment sozlamalari namunas
├── .gitignore               # Git ignore fayli
├── composer.json            # Composer konfiguratsiyasi
└── README.md                # Bu fayl
```

## 🔧 Foydalanish

### 1. Web interfeys orqali
- `yourdomain.com` ga kiring
- Telegram raqamingizni kiriting
- QR kodni skan qiling yoki kodni kiriting

### 2. Live streaming boshlash
- YouTube video URL sini kiriting
- Telegram kanalini tanlang
- "Start Stream" tugmasini bosing

### 3. Stream boshqaruvi
- Stream holatini kuzatish
- Pause/Resume/Stop qilish
- Log fayllarini ko'rish

## 🛡️ Xavfsizlik

- `.env` fayl hech qachon GitHub ga yuklanmasin
- `public/sessions` papkasi web orqali ochiq emas
- `.htaccess` orqali xavfsizlik ta'minlangan
- SQL injection va XSS hujumlaridan himoyalangan

## 📊 Monitoring va Log

- **Log fayllar**: `logs/app.log`
- **Telegram log**: `public/MadelineProto.log`
- **Database log**: MySQL error log
- **Web server log**: Apache/Nginx access/error log

## 🚨 Xatoliklarni Tuzatish

### Umumiy muammolar

1. **Session papkasi yaratilmayapti**
   ```bash
   chmod 755 public/sessions
   ```

2. **Database bog'lanish xatosi**
   - .env fayldagi database ma'lumotlarini tekshiring
   - Database mavjudligini tekshiring

3. **Telegram API xatosi**
   - API_ID va API_HASH to'g'ri ekanligini tekshiring
   - Phone raqam formatini tekshiring (+998...)

4. **YouTube API xatosi**
   - API key to'g'ri ekanligini tekshiring
   - YouTube Data API v3 yoqilganligini tekshiring

## 🔄 Yangilash

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
# Database migration kerak bo'lsa
```

## 📝 License

Bu loyiha MIT License ostida tarqatiladi.

## 🤝 Hissa qo'shish

1. Fork qiling
2. Feature branch yarating (`git checkout -b feature/amazing-feature`)
3. O'zgarishlarni commit qiling (`git commit -m 'Add amazing feature'`)
4. Branch ga push qiling (`git push origin feature/amazing-feature`)
5. Pull Request yarating

## 📞 Qo'llab-quvvatlash

- **Email**: support@yourdomain.com
- **Telegram**: @your_support_bot
- **Issues**: GitHub Issues orqali

## 🙏 Minnatdorchilik

- [MadelineProto](https://github.com/danog/MadelineProto) - Telegram API
- [Google API Client](https://github.com/googleapis/google-api-php-client) - YouTube API
- [Composer](https://getcomposer.org/) - Dependency management

---

⭐ **Agar loyiha foydali bo'lsa, star bering!**
