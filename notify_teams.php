<?php
/**
 * DomainWatch — Microsoft Teams Notifier
 * Run via cron: 0 8 * * * php /path/to/notify_teams.php
 */

define('DOMAINS_FILE', __DIR__ . '/domains.json');
define('CACHE_DIR',    __DIR__ . '/cache');
define('CACHE_FILE',   CACHE_DIR . '/domains_cache.json');
define('CONFIG_FILE',  CACHE_DIR . '/teams_config.json');

if (!file_exists(CONFIG_FILE)) die("[DomainWatch] No Teams config found. Set webhook in dashboard.\n");

$config = json_decode(file_get_contents(CONFIG_FILE), true);
if (empty($config['enabled']) || empty($config['webhook_url'])) {
    die("[DomainWatch] Teams notifications are disabled.\n");
}

$webhookUrl   = $config['webhook_url'];
$notifyDays   = $config['notify_days']   ?? [7, 14, 30];
$dashboardUrl = $config['dashboard_url'] ?? '';

if (!file_exists(DOMAINS_FILE)) die("[DomainWatch] domains.json not found.\n");

$domains = json_decode(file_get_contents(DOMAINS_FILE), true);
$cache   = file_exists(CACHE_FILE) ? json_decode(file_get_contents(CACHE_FILE), true) : [];

$domainAlerts = [];
$sslAlerts    = [];

foreach ($domains as $d) {
    $key  = $d['domain'];
    $info = $cache[$key] ?? null;
    if (!$info) continue;

    // Domain expiry alerts
    if (isset($info['days_left']) && $info['days_left'] !== null) {
        foreach ($notifyDays as $threshold) {
            if ($info['days_left'] <= $threshold && $info['days_left'] >= 0) {
                $domainAlerts[] = [
                    'domain'    => $key,
                    'owner'     => $d['owner'] ?? '—',
                    'team'      => $d['team']  ?? '—',
                    'days_left' => $info['days_left'],
                    'expiry'    => $info['expiry_date'] ?? '—',
                    'type'      => 'domain',
                ];
                break;
            }
        }
    }

    // SSL expiry alerts
    if (!empty($d['monitor_ssl']) && isset($info['ssl_days_left']) && $info['ssl_days_left'] !== null) {
        foreach ($notifyDays as $threshold) {
            if ($info['ssl_days_left'] <= $threshold && $info['ssl_days_left'] >= 0) {
                $sslAlerts[] = [
                    'domain'    => $key,
                    'owner'     => $d['owner'] ?? '—',
                    'team'      => $d['team']  ?? '—',
                    'days_left' => $info['ssl_days_left'],
                    'expiry'    => $info['ssl_expiry'] ?? '—',
                    'issuer'    => $info['ssl_issuer'] ?? '—',
                    'type'      => 'ssl',
                ];
                break;
            }
        }
    }
}

$allAlerts = array_merge($domainAlerts, $sslAlerts);
if (empty($allAlerts)) {
    echo "[DomainWatch] No alerts to send. All domains OK.\n";
    exit(0);
}

// Build Adaptive Card body
$body = [
    [
        'type'   => 'TextBlock',
        'text'   => '🛡️ DomainWatch Alert',
        'weight' => 'Bolder',
        'size'   => 'ExtraLarge',
        'color'  => 'Warning',
    ],
    [
        'type' => 'TextBlock',
        'text' => 'The following items require your attention:',
        'wrap' => true,
        'spacing' => 'None',
    ],
];

if (!empty($domainAlerts)) {
    $body[] = ['type' => 'TextBlock', 'text' => '**🌐 Domain Expiry**', 'weight' => 'Bolder', 'spacing' => 'Medium'];
    $facts  = [];
    foreach ($domainAlerts as $a) {
        $emoji  = $a['days_left'] <= 7 ? '🔴' : ($a['days_left'] <= 14 ? '🟠' : '🟡');
        $facts[] = [
            'title' => "$emoji {$a['domain']}",
            'value' => "Expires: **{$a['expiry']}** ({$a['days_left']} days) · {$a['owner']} · {$a['team']}",
        ];
    }
    $body[] = ['type' => 'FactSet', 'facts' => $facts];
}

if (!empty($sslAlerts)) {
    $body[] = ['type' => 'TextBlock', 'text' => '**🔒 SSL Certificate**', 'weight' => 'Bolder', 'spacing' => 'Medium'];
    $facts  = [];
    foreach ($sslAlerts as $a) {
        $emoji  = $a['days_left'] <= 7 ? '🔴' : ($a['days_left'] <= 14 ? '🟠' : '🟡');
        $facts[] = [
            'title' => "$emoji {$a['domain']} SSL",
            'value' => "Expires: **{$a['expiry']}** ({$a['days_left']} days) · Issuer: {$a['issuer']}",
        ];
    }
    $body[] = ['type' => 'FactSet', 'facts' => $facts];
}

$body[] = [
    'type'     => 'TextBlock',
    'text'     => 'Checked: ' . date('d M Y H:i'),
    'isSubtle' => true,
    'size'     => 'Small',
    'spacing'  => 'Medium',
];

$actions = [];
if ($dashboardUrl) {
    $actions[] = ['type' => 'Action.OpenUrl', 'title' => '📊 Open Dashboard', 'url' => $dashboardUrl];
}

$payload = [
    'type'        => 'message',
    'attachments' => [[
        'contentType' => 'application/vnd.microsoft.card.adaptive',
        'content'     => [
            '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
            'type'    => 'AdaptiveCard',
            'version' => '1.4',
            'body'    => $body,
            'actions' => $actions,
        ],
    ]],
];

$ch = curl_init($webhookUrl);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response = curl_exec($ch);
$code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code === 200 || $response === '1') {
    echo "[DomainWatch] ✅ Alert sent to Teams. Domain: " . count($domainAlerts) . ", SSL: " . count($sslAlerts) . " alerts.\n";
} else {
    echo "[DomainWatch] ❌ Failed to send to Teams. HTTP $code — $response\n";
}
