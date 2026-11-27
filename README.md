# 📊 Visitor Counter Pro

> A single-file visitor statistics plugin for **GetSimple CMS**, with server-rendered SVG charts, live refresh, and an admin dashboard. No external dependencies, no database.

![Version](https://img.shields.io/badge/version-2.0-blue)
![License](https://img.shields.io/badge/license-GPL--3.0-blue)
![GetSimple](https://img.shields.io/badge/GetSimple-3.3.x-orange)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php&logoColor=white)
![Dependencies](https://img.shields.io/badge/dependencies-none-brightgreen)

**[English](#english) | [العربية](#arabic)**

---

<a id="english"></a>

## English

### Overview

Visitor Counter Pro counts unique visitors on a GetSimple CMS site and shows the numbers in a dashboard inside the admin panel. Everything lives in one PHP file, `visitor_counter.php`. Statistics are stored as small JSON files in GetSimple's data folder, so no database is needed.

> **Recent change:** the plugin is now a true single-file plugin. The old v1 `visitor_counter/` folder (`ajax.js`, `css/style.css`, `lang/`, `stats.php`) was unused and has been removed from the repository. The frontend widget and the live-refresh endpoint now read only two small files (today's count and the all-time total) instead of a full month of data.

### Features

- **Unique visitor tracking**: one count per browser every 24 hours, using an HttpOnly `vcp_visit` cookie.
- **Four stat cards**: Today, This Week (last 7 days), This Month (last 30 days), and All Time.
- **Trend indicator**: today compared with yesterday, shown as ▲ / ▼ / → with a percentage.
- **Pure SVG charts**, rendered on the server with no JavaScript libraries:
  - Bar chart of daily visitors for the last 30 days.
  - Line chart of today's visitors by hour.
- **Live refresh**: the Today and All Time numbers refresh every 60 seconds, in the dashboard and in the frontend widget, without reloading the page.
- **Frontend widget**: a small "Today / Total" badge added to the theme footer, which can be shown or hidden from the settings.
- **Bot filter**: common crawlers and spiders are not counted.
- **Reset**: clear all statistics from the admin panel, with a confirmation prompt.
- **Security**: CSRF nonce on every admin form, file writes under an exclusive lock, and integer-only output for counts.

### Requirements

- GetSimple CMS **3.3.x** (including Community Edition)
- PHP **7.4+**
- Write access to GetSimple's `data/other/` folder
- No additional dependencies, no database

### Installation

1. Download `visitor_counter.php` from this repository.
2. Upload it to your GetSimple `plugins/` directory.
3. In the admin panel, go to **Plugins** and activate **Visitor Counter Pro**.
4. Open **Settings → 📊 Visitor Counter** in the sidebar.

The data folder `data/other/visitor_counter/` is created automatically on the first counted visit.

### Usage

#### Admin dashboard

| Section | What it shows |
|---------|---------------|
| **Stat cards** | Today / This Week / This Month / All Time |
| **Trend** | Percentage change of today vs. yesterday |
| **Bar chart** | Daily visitors for the last 30 days |
| **Line chart** | Hourly visitor distribution for today |
| **Settings** | "Show Widget on Frontend" toggle |
| **Danger Zone** | Reset all statistics (asks for confirmation) |

The dashboard is available at `admin/load.php?id=visitor_counter`.

#### How tracking works

1. When a visitor loads a frontend page, the plugin checks for the `vcp_visit` cookie.
2. If the cookie is missing, the visit is counted and the cookie is set for 24 hours.
3. The daily count is saved in `YYYY-MM-DD.json`.
4. The all-time total is saved in `total.json`.
5. The hourly breakdown is saved in `YYYY-MM-DD_hours.json` for the line chart.
6. Every count update uses `flock(LOCK_EX)` to avoid race conditions under concurrent traffic.

#### Bot filtering

Visits are skipped when the user agent contains any of:

`bot` · `crawl` · `spider` · `slurp` · `mediapartners` · `facebookexternalhit`

#### Live refresh endpoint

Any frontend URL with `?vcp_ajax=1` returns the current numbers as JSON, for example `{"today": 12, "total": 3456}`. The widget and the dashboard call it every 60 seconds.

#### Security notes

- Admin forms are protected by a CSRF nonce stored in the PHP session.
- The visitor cookie is set with the **HttpOnly** flag (and **Secure** on HTTPS).
- File writes use exclusive locks (`LOCK_EX`) to prevent data corruption.
- Counts are cast to integers with `vcp_int()` before output, to prevent XSS.

### Project structure

Repository:

```
Visitor-Counter/
├── visitor_counter.php   ← the whole plugin (single file)
├── LICENSE               ← GNU GPL v3
└── README.md
```

Data created on your site at runtime:

```
data/other/visitor_counter/
├── total.json              ← all-time visitor total
├── meta.json               ← plugin settings (widget visibility)
├── YYYY-MM-DD.json         ← daily visitor count (one file per day)
├── YYYY-MM-DD_hours.json   ← hourly breakdown per day
└── last_prune.txt          ← date of the last cleanup of old day files
```

### Changelog

#### Unreleased
- **Reset All Statistics** no longer deletes `meta.json`, so the widget visibility setting survives a reset.
- Day files (`YYYY-MM-DD.json`, `YYYY-MM-DD_hours.json`) older than 90 days (`VCP_KEEP_DAYS`) are now pruned automatically, at most once per day.

#### v2.0
- Complete rewrite from scratch.
- Pure SVG bar chart (last 30 days) and line chart (hourly, today).
- CSRF nonce protection on all admin forms.
- Atomic file writes with `flock(LOCK_EX)` to fix race conditions.
- Bot and crawler filtering.
- Trend indicator (today vs. yesterday).
- AJAX live refresh for the stat cards.
- Hourly visitor tracking.
- Removed all `error_log()` calls from production code.
- Removed unused JS/CSS file dependencies; the unused v1 `visitor_counter/` folder was later deleted from the repository.
- HttpOnly flag on the visitor cookie.
- All numeric output sanitized with `vcp_int()`.
- Frontend widget and live refresh now read only today's count and the total.

#### v1.0
- Initial release.
- Basic daily / weekly / monthly / total tracking.
- Simple admin stats page.

### Contributing

Bug reports and pull requests are welcome. Please open an issue first to discuss what you would like to change.

### License

Released under the **GNU General Public License v3.0**. See [LICENSE](LICENSE) for the full text.

### Author

**Fahad ALGhathbar**: [@fahad4x4](https://github.com/fahad4x4)

---

<a id="arabic"></a>

<div dir="rtl">

## العربية

### نظرة عامة

إضافة **Visitor Counter Pro** تحسب الزوار الفريدين لموقع يعمل بنظام **GetSimple CMS**، وتعرض الإحصاءات في لوحة داخل صفحة الإدارة. الإضافة كلها في ملف PHP واحد هو `visitor_counter.php`، وتُحفظ الإحصاءات في ملفات JSON صغيرة داخل مجلد البيانات في GetSimple، فلا حاجة إلى قاعدة بيانات.

> **تحديث أخير:** أصبحت الإضافة ملفًا واحدًا فعلًا. حُذف من المستودع مجلد الإصدار الأول القديم `visitor_counter/` (ويضم `ajax.js` و`css/style.css` و`lang/` و`stats.php`) لأنه لم يعد مستخدمًا. كما أصبحت أداة الواجهة ونقطة التحديث المباشر تقرآن ملفين صغيرين فقط (عدد اليوم والإجمالي الكلي) بدلًا من بيانات شهر كامل.

### المزايا

- **تتبّع الزوار الفريدين**: زيارة واحدة لكل متصفح كل 24 ساعة، عبر ملف تعريف ارتباط `vcp_visit` من نوع HttpOnly.
- **أربع بطاقات إحصائية**: اليوم، وهذا الأسبوع (آخر 7 أيام)، وهذا الشهر (آخر 30 يومًا)، والإجمالي الكلي.
- **مؤشر الاتجاه**: مقارنة اليوم بالأمس بالرموز ▲ / ▼ / → مع النسبة المئوية.
- **رسوم بيانية بصيغة SVG** تُرسم على الخادم دون أي مكتبة JavaScript:
  - رسم أعمدة للزوار اليوميين خلال آخر 30 يومًا.
  - رسم خطي لزوار اليوم موزّعين على الساعات.
- **تحديث مباشر**: يتجدد رقما «اليوم» و«الإجمالي» كل 60 ثانية في لوحة الإدارة وفي أداة الواجهة، دون إعادة تحميل الصفحة.
- **أداة في الواجهة**: شارة صغيرة تعرض «اليوم / الإجمالي» في تذييل القالب، ويمكن إظهارها أو إخفاؤها من الإعدادات.
- **تصفية الروبوتات**: لا تُحتسب زيارات محركات الزحف والعناكب الشائعة.
- **إعادة التعيين**: مسح جميع الإحصاءات من لوحة الإدارة بعد رسالة تأكيد.
- **الأمان**: رمز تحقق (CSRF nonce) في كل نماذج الإدارة، وكتابة الملفات بقفل حصري، وإخراج الأعداد كأرقام صحيحة فقط.

### المتطلبات

- نظام GetSimple CMS الإصدار **3.3.x** (بما فيه إصدار المجتمع Community Edition)
- PHP الإصدار **7.4** أو أحدث
- صلاحية الكتابة على مجلد `data/other/` في GetSimple
- لا توجد اعتماديات إضافية، ولا حاجة إلى قاعدة بيانات

### التثبيت

1. نزّل الملف `visitor_counter.php` من هذا المستودع.
2. ارفعه إلى مجلد `plugins/` في موقعك.
3. من لوحة الإدارة، افتح **Plugins** وفعّل **Visitor Counter Pro**.
4. افتح **Settings ← 📊 Visitor Counter** من القائمة الجانبية.

يُنشأ مجلد البيانات `data/other/visitor_counter/` تلقائيًا عند احتساب أول زيارة.

### الاستخدام

#### لوحة الإدارة

| القسم | ما يعرضه |
|-------|----------|
| **البطاقات الإحصائية** | اليوم / هذا الأسبوع / هذا الشهر / الإجمالي |
| **الاتجاه** | نسبة التغيّر بين اليوم والأمس |
| **رسم الأعمدة** | الزوار اليوميون خلال آخر 30 يومًا |
| **الرسم الخطي** | توزيع زوار اليوم على الساعات |
| **الإعدادات** | مفتاح «Show Widget on Frontend» لإظهار الأداة في الواجهة |
| **منطقة الخطر** | إعادة تعيين جميع الإحصاءات (مع طلب تأكيد) |

رابط اللوحة: `admin/load.php?id=visitor_counter`.

#### آلية التتبّع

1. عند فتح الزائر أي صفحة في الواجهة، تتحقق الإضافة من وجود ملف الارتباط `vcp_visit`.
2. إن لم يكن موجودًا، تُحتسب الزيارة ويُضبط الملف لمدة 24 ساعة.
3. يُحفظ عدد اليوم في الملف `YYYY-MM-DD.json`.
4. يُحفظ الإجمالي الكلي في الملف `total.json`.
5. يُحفظ التوزيع على الساعات في الملف `YYYY-MM-DD_hours.json` لاستخدامه في الرسم الخطي.
6. كل تحديث للعدد يستخدم `flock(LOCK_EX)` لتجنّب تعارض الكتابة عند تزامن الزيارات.

#### تصفية الروبوتات

لا تُحتسب الزيارة إذا احتوى معرّف المتصفح (User Agent) على أيٍّ من:

`bot` · `crawl` · `spider` · `slurp` · `mediapartners` · `facebookexternalhit`

#### نقطة التحديث المباشر

أي رابط في الواجهة مضافًا إليه `?vcp_ajax=1` يعيد الأرقام الحالية بصيغة JSON، مثل `{"today": 12, "total": 3456}`. تستدعيه الأداة ولوحة الإدارة كل 60 ثانية.

#### ملاحظات أمنية

- نماذج الإدارة محمية برمز تحقق CSRF محفوظ في جلسة PHP.
- ملف ارتباط الزائر مضبوط بخاصية **HttpOnly** (وبخاصية **Secure** عند استخدام HTTPS).
- كتابة الملفات تتم بقفل حصري (`LOCK_EX`) لمنع تلف البيانات.
- تُحوَّل الأعداد إلى أرقام صحيحة عبر `vcp_int()` قبل عرضها، للحماية من XSS.

### هيكل المشروع

المستودع:

```
Visitor-Counter/
├── visitor_counter.php   ← الإضافة كاملة (ملف واحد)
├── LICENSE               ← رخصة GNU GPL v3
└── README.md
```

البيانات التي تُنشأ في موقعك أثناء التشغيل:

```
data/other/visitor_counter/
├── total.json              ← الإجمالي الكلي للزوار
├── meta.json               ← إعدادات الإضافة (إظهار الأداة)
├── YYYY-MM-DD.json         ← عدد زوار اليوم (ملف لكل يوم)
├── YYYY-MM-DD_hours.json   ← التوزيع على الساعات لكل يوم
└── last_prune.txt          ← تاريخ آخر حذف لملفات الأيام القديمة
```

### سجل التغييرات

#### غير منشور بعد
- زر **Reset All Statistics** لم يعد يحذف `meta.json`، فيبقى إعداد إظهار الأداة بعد إعادة التعيين.
- تُحذف تلقائيًا ملفات الأيام (`YYYY-MM-DD.json` و`YYYY-MM-DD_hours.json`) الأقدم من 90 يومًا (`VCP_KEEP_DAYS`)، مرة واحدة في اليوم على الأكثر.

#### الإصدار 2.0
- إعادة كتابة كاملة من الصفر.
- رسم أعمدة بصيغة SVG (آخر 30 يومًا) ورسم خطي (حسب الساعة لليوم الحالي).
- حماية CSRF لجميع نماذج الإدارة.
- كتابة ذرّية للملفات باستخدام `flock(LOCK_EX)` لمعالجة تعارض الكتابة.
- تصفية الروبوتات ومحركات الزحف.
- مؤشر الاتجاه (اليوم مقارنة بالأمس).
- تحديث مباشر للبطاقات الإحصائية عبر AJAX.
- تتبّع الزوار حسب الساعة.
- إزالة جميع استدعاءات `error_log()` من شيفرة الإنتاج.
- إزالة الاعتماد على ملفات JS/CSS غير المستخدمة، ثم حذف مجلد الإصدار الأول `visitor_counter/` من المستودع.
- خاصية HttpOnly لملف ارتباط الزائر.
- تنقية جميع المخرجات الرقمية عبر `vcp_int()`.
- أداة الواجهة والتحديث المباشر يقرآن الآن عدد اليوم والإجمالي فقط.

#### الإصدار 1.0
- الإصدار الأول.
- تتبّع أساسي يومي وأسبوعي وشهري وإجمالي.
- صفحة إحصاءات بسيطة في لوحة الإدارة.

### المساهمة

نرحّب بالإبلاغ عن الأخطاء وبطلبات الدمج (Pull Requests). يُفضَّل فتح بلاغ (Issue) أولًا لمناقشة التغيير المقترح.

### الرخصة

المشروع منشور بموجب **رخصة جنو العمومية العامة، الإصدار 3.0 (GPL-3.0)**. راجع ملف [LICENSE](LICENSE) للنص الكامل.

### المؤلف

**فهد الغثبر (Fahad ALGhathbar)**: [@fahad4x4](https://github.com/fahad4x4)

</div>
