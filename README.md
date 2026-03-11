# 🛡️ DomainWatch v2.0
**Domain & SSL Certificate Expiry Monitor with Microsoft Teams Alerts**

---

## 📁 File Structure

```
domainwatch/
├── index.html           ← Dashboard (bilingual EN/PL)
├── api.php              ← Backend API (WHOIS, RDAP, SSL check)
├── notify_teams.php     ← Teams notifier (run via cron)
├── domains.json         ← Domain list (edit this)
└── cache/               ← Auto-created cache directory
    ├── domains_cache.json    (WHOIS + SSL cache)
    └── teams_config.json     (Teams webhook config)
```

---

## 🚀 Installation

### Requirements
- PHP 7.4+ with extensions: `curl`, `json`, `openssl`
- Apache or Nginx with PHP-FPM
- Outbound internet access (for WHOIS/RDAP/SSL checks)
- Write permission on the project directory (for `cache/`)

### Steps
1. Upload all files to your web server directory
2. Ensure PHP can write to the directory:
   ```bash
   chmod 755 /var/www/html/domainwatch/
   ```
3. Open `index.html` (or your configured URL) in a browser
4. Edit `domains.json` with your domains
5. Click **⚡ Check All** to fetch WHOIS data
6. Click **🔒** on individual domains or **Check SSL** to fetch SSL data

---

## ⚙️ Domain Configuration (`domains.json`)

```json
[
  {
    "domain": "example.com",
    "owner": "John Smith",
    "team": "Dev",
    "notes": "Main production site",
    "tags": ["production", "critical"],
    "monitor_ssl": true
  }
]
```

| Field         | Type     | Description                                      |
|---------------|----------|--------------------------------------------------|
| `domain`      | string   | Bare domain (no https://)                        |
| `owner`       | string   | Responsible person                               |
| `team`        | string   | Team name (used for filtering)                   |
| `notes`       | string   | Free-text description                            |
| `tags`        | array    | `production`, `staging`, `internal`, `critical`  |
| `monitor_ssl` | boolean  | `true` = check SSL certificate expiry            |

---

## 🔒 SSL Monitoring

### Enable/Disable per domain
- **In the table**: click the small toggle (🔒/🔓) in the SSL column
- **In domain details**: click the larger toggle in the SSL section
- Changes are persisted immediately to `domains.json`

### Check SSL
- Per domain: click the 🔒 action button in each row
- All domains: click **Check SSL** in the toolbar
- SSL data is cached alongside WHOIS data

### SSL Status Thresholds
| Status   | Days Left |
|----------|-----------|
| OK       | > 30 days |
| Soon     | 15–30 days|
| Warning  | 8–14 days |
| Critical | ≤ 7 days  |
| Expired  | < 0 days  |

---

## 🔔 Microsoft Teams Integration

### Step 1: Create an Incoming Webhook
1. Open Teams → select a channel → click `···` → **Manage channel**
2. Add a connector → **Incoming Webhook**
3. Name it "DomainWatch", optionally upload an icon
4. Copy the generated **Webhook URL**

### Step 2: Configure in the Dashboard
1. Click **🔔 Teams** in the top-right
2. Paste the Webhook URL
3. Set your Dashboard URL (included as a button in notifications)
4. Select notification thresholds (7/14/30/60/90 days)
5. Enable notifications and click **💾 Save**
6. Click **🧪 Test** to verify the connection

### Step 3: Set Up a Cron Job

```bash
# Notify every day at 8:00 AM
0 8 * * * /usr/bin/php /var/www/html/domainwatch/notify_teams.php

# With logging
0 8 * * * /usr/bin/php /var/www/html/domainwatch/notify_teams.php >> /var/log/domainwatch.log 2>&1
```

The script sends an **Adaptive Card** to Teams listing:
- 🌐 Domains expiring within your configured thresholds
- 🔒 SSL certificates expiring within your configured thresholds

---

## 🌐 Supported TLDs

| Protocol | TLDs |
|----------|------|
| RDAP     | .com .net .org .pl .io .eu .de .tech .co .uk |
| WHOIS    | .com .net .org .pl .io .eu .de .uk .co |
| API fallback | All others (via whoisjsonapi.com) |

---

## 📊 Domain Status Thresholds

| Status   | Color  | Domain Days Left |
|----------|--------|-----------------|
| ✅ OK       | Green  | > 60 days       |
| 🟡 Soon    | Yellow | 31–60 days      |
| 🟠 Warning | Orange | 15–30 days      |
| 🔴 Critical| Red    | ≤ 14 days       |
| 💀 Expired | Dark   | < 0 days        |
| ❓ Unknown | Gray   | No data         |

---

## 🛠 Dashboard Features

| Feature | Description |
|---------|-------------|
| 🌍 Bilingual | Switch between English and Polish (EN/PL) |
| 📊 Stats cards | At-a-glance counts by status + SSL monitored |
| 🔍 Search | Filter by domain, owner, team, tags |
| 🏷 Tag filter | Filter by status (pills) or team (dropdown) |
| ↕️ Sort | Click any column header to sort |
| 🔄 Per-domain refresh | Refresh WHOIS for individual domains |
| 🔒 SSL toggle | Enable/disable SSL monitoring per domain |
| 🔎 Detail view | Modal with full WHOIS + SSL info |
| 📋 Copy | One-click copy domain to clipboard |
| 🔗 Open | Direct link to the domain |
| 🔔 Teams config | Full in-app webhook configuration |
| 🎭 Demo mode | Works standalone without a PHP backend |

---

## 🔧 API Endpoints (`api.php`)

| Action | Method | Description |
|--------|--------|-------------|
| `?action=list` | GET | List all domains with cached data |
| `?action=check&domain=x` | GET | WHOIS check single domain |
| `?action=check_all` | GET | WHOIS check all domains |
| `?action=check_ssl&domain=x` | GET | SSL check single domain |
| `?action=toggle_ssl` | POST | Enable/disable SSL for a domain |
| `?action=save_config` | POST | Save Teams configuration |
| `?action=get_config` | GET | Get Teams configuration |

---

## 🔐 Security Notes

- Place `cache/` outside the web root if possible, or add `.htaccess`:
  ```apache
  <Directory cache>
    Deny from all
  </Directory>
  ```
- The Teams webhook URL is stored in `cache/teams_config.json` — ensure the file is not publicly accessible
- SSL checks use `stream_socket_client` with `verify_peer: false` — this fetches the cert without validating its chain (intentional, for checking expiry only)

---

## 📝 License

Internal use — iTop Team
