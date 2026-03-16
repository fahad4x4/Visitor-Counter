# 📊 Visitor Counter Pro

> A professional visitor tracking plugin for **GetSimple CMS** — real-time statistics, pure SVG charts, AJAX live refresh, and a full admin dashboard, all in a single file with zero external dependencies.

![Version](https://img.shields.io/badge/version-2.0-blue)
![License](https://img.shields.io/badge/license-MIT-green)
![GetSimple](https://img.shields.io/badge/GetSimple-3.3.x-orange)
![Dependencies](https://img.shields.io/badge/dependencies-none-brightgreen)

---

## ✨ Features

- **Unique visitor tracking** — cookie-based deduplication, one count per browser per 24 hours
- **4 stat periods** — Today, This Week, This Month, All Time
- **Trend indicator** — compares today vs yesterday (▲ ▼ →) with percentage
- **Pure SVG charts** — Bar chart (last 30 days) + Line chart (hourly today), rendered server-side with zero JS libraries
- **AJAX live refresh** — stat cards update every 60 seconds without page reload
- **Frontend widget** — small counter badge injected into the theme footer
- **Bot filter** — automatically skips common crawlers and spiders
- **Reset button** — clear all statistics from the admin panel (CSRF protected)
- **Show/hide widget** — toggle the frontend badge from settings
- **Security** — CSRF nonce protection, atomic file writes with exclusive locks, HttpOnly cookie
- **Lightweight** — single PHP file, no external dependencies, no database required

---

## 📦 Installation

1. Download `visitor_counter_pro.php`
2. Upload it to your GetSimple `/plugins/` directory
3. Go to **Settings → Plugins** in the admin panel and activate it
4. Click **📊 Visitor Counter** in the settings sidebar

---

## 📊 Admin Dashboard

The admin panel shows:

| Section | Description |
|---------|-------------|
| **Stat Cards** | Today / This Week / This Month / All Time with color-coded cards |
| **Trend** | Percentage change vs yesterday |
| **Bar Chart** | Daily visitor count for the last 30 days (SVG, server-rendered) |
| **Line Chart** | Hourly visitor distribution for today (SVG, server-rendered) |
| **Settings** | Toggle frontend widget visibility |
| **Danger Zone** | Reset all statistics with confirmation prompt |

---

## 🔢 How Tracking Works

1. When a visitor loads any frontend page, the plugin checks for a `vcp_visit` cookie
2. If the cookie is absent, the visitor is counted as unique and the cookie is set for 24 hours
3. The daily count is stored in a per-day JSON file (`YYYY-MM-DD.json`)
4. The all-time total is stored in `total.json`
5. Hourly data is stored in `YYYY-MM-DD_hours.json` for the line chart
6. All file writes use `flock(LOCK_EX)` to prevent race conditions under concurrent traffic

---

## 🤖 Bot Filtering

The plugin automatically skips counting the following user agent patterns:

`bot` · `crawl` · `spider` · `slurp` · `mediapartners` · `facebookexternalhit`

---

## 🔒 Security

- All admin form submissions are protected with a **CSRF nonce** via PHP sessions
- The visitor cookie is set with the **HttpOnly** flag to prevent JavaScript access
- File writes use **exclusive locks** (`LOCK_EX`) to prevent data corruption under concurrent traffic
- All output values are cast to integers via `vcp_int()` to prevent XSS
- No user-controlled input is ever written directly to disk without sanitization

---

## ⚙️ Requirements

- GetSimple CMS **3.3.x** (including Community Edition)
- PHP **7.4+**
- No additional dependencies
- No database required

---

## 📁 File Structure

```
plugins/
└── visitor_counter_pro.php     ← single-file plugin

data/other/visitor_counter/
├── total.json                  ← all-time visitor total
├── meta.json                   ← plugin settings
├── YYYY-MM-DD.json             ← daily visitor count (one file per day)
└── YYYY-MM-DD_hours.json       ← hourly breakdown per day
```

---

## 📝 Changelog

### v2.0
- Complete rewrite from scratch
- Added pure SVG bar chart (last 30 days) and line chart (hourly today)
- Added CSRF nonce protection on all admin forms
- Added atomic file writes with `flock(LOCK_EX)` to fix race conditions
- Added bot/crawler filtering
- Added trend indicator (today vs yesterday)
- Added AJAX live refresh for stat cards
- Added hourly visitor tracking
- Removed all `error_log()` calls from production code
- Removed unused JS/CSS file dependencies
- HttpOnly flag added to visitor cookie
- All numeric output sanitized with `vcp_int()`

### v1.0
- Initial release
- Basic daily/weekly/monthly/total tracking
- Simple admin stats page

---

## 🤝 Contributing

Bug reports and pull requests are welcome.  
Please open an issue first to discuss what you would like to change.

---

## 📄 License

[MIT](LICENSE) — free to use, modify, and distribute.

---

Made with ❤️ by [Fahad4x4](https://github.com/fahad4x4)
