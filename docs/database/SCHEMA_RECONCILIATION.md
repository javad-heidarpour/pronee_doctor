# ممیزی تطبیق دیتابیس Proneex با Migrationها

## منبع مرجع بررسی‌شده

- فایل: `Dump20261119.sql`
- زمان ثبت در Library: 2026-11-19 19:15 UTC
- موتور اعلام‌شده در dump: MySQL 8.0.26
- تعداد جدول‌های دارای `CREATE TABLE`: 91
- این فایل snapshot قدیمی ساختار است و وضعیت فعلی دیتابیس در زمان اجرای این ممیزی را اثبات نمی‌کند.
- جدول `migrations` در dump فقط DDL دارد و هیچ `INSERT INTO migrations` در فایل موجود نیست؛ بنابراین از روی این فایل نمی‌توان فهرست Migrationهای اجراشده یا batch آن‌ها را تعیین کرد.

## یافته مهم: مهاجرت تسویه حرفه‌ای

در snapshot، جدول `clinic_professional_sessions` این موارد را دارد:

- ستون‌های `payer_confirmed_at`، `payer_confirmed_by_user_id`، `receiver_confirmed_at` و `receiver_confirmed_by_user_id` موجودند.
- ستون `receiver_reference` در snapshot وجود ندارد.
- کلید خارجی payer به users وجود دارد، اما کلید خارجی receiver در snapshot وجود ندارد.
- جدول `clinic_professional_dues` در snapshot وجود ندارد.

این وضعیت با شکست نیمه‌کاره Migration `2026_11_19_020000_add_professional_dues_and_dual_settlement.php` که قبلاً گزارش شده بود سازگار است. چون snapshot پیش از اصلاح دستی گزارش‌شده در همان روز تهیه شده، نباید آن را وضعیت فعلی دیتابیس فرض کرد و نباید SQL اصلاحی را بدون مشاهده وضعیت فعلی دوباره اجرا کرد.

## اصلاح کد Migration

در Migration `2026_11_19_120000_create_staff_compensation_and_replacement_tables.php` تعریف کلید خارجی `replacement_staff_user_id` اصلاح شد تا ستون مرجع و نام constraint جداگانه و صریح باشند:

```php
$table->foreignId('replacement_staff_user_id')
    ->constrained('users', 'id', 'cssr_replacement_staff_user_fk')
    ->restrictOnDelete();
```

این اصلاح از ارجاع اشتباه به ستونی به نام `cssr_replacement_staff_user_fk` در جدول users جلوگیری می‌کند.

## قواعد ادامه تطبیق

1. قبل از تهیه SQL نهایی، وضعیت زنده هر جدول/ستون/index/foreign key با `information_schema` یا `SHOW CREATE TABLE` بررسی شود.
2. برای Migration نیمه‌اجراشده، فقط اجزای واقعاً مفقود اضافه شوند؛ ستون/کلید/جدول موجود دوباره ساخته نشود.
3. هیچ جدول یا داده‌ای حذف یا بازسازی نشود.
4. برای کلیدهای خارجی نام کوتاه و صریح (کمتر از 64 نویسه) تعیین شود.
5. SQL مستقیم Workbench و Migration متناظر دو روش جایگزین‌اند؛ برای یک تغییر، هر دو را هم‌زمان اجرا نکنید.
6. تا زمان تأیید تطبیق با وضعیت زنده دیتابیس، بسته SQL صرفاً پیش‌نویس است و نباید روی دیتابیس عملیاتی اجرا شود.

## موارد باقی‌مانده

- دریافت وضعیت زنده جدول‌های درگیر با Migrationهای 2026-11-19 تا 2026-11-21.
- تطبیق همه Migrationهای موجود با snapshot و وضعیت زنده، شامل تغییر ستون‌ها، indexها، foreign keyها و داده‌های منو.
- تولید یک بسته نهایی Workbench با ترتیب وابستگی صحیح و کنترل اجرای مجدد.
- بازبینی خطاهای FK با محدودیت 64 نویسه در کل Migrationها.
- عدم حذف پوشه Migrationها تا وقتی نسخه نهایی SQL، تطبیق‌شده و تأییدشده تحویل نشده است.
