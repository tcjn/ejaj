<?php
/**
 * DomainWatch API v3.5 (check_dns, check_ports, check_http)
 * Domain & SSL expiry monitoring — ITOps Team
 */

// ── Error handling: always return JSON, never a blank 500 ──────
ini_set('display_errors', 0);
error_reporting(E_ALL);
set_error_handler(function($severity, $msg, $file, $line) {
    $err = "$msg in $file:$line";
    if (!(error_reporting() & $severity)) return false;
    http_response_code(500);
    // Headers may already be sent; try anyway
    @header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'PHP error', 'detail' => $err]);
    exit(1);
});
set_exception_handler(function($e) {
    http_response_code(500);
    @header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'PHP exception', 'detail' => $e->getMessage()]);
    exit(1);
});

// check_all loops over many domains — needs extra time
set_time_limit(120);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

define('DOMAINS_FILE',    __DIR__ . '/domains.json');
define('CACHE_DIR',       __DIR__ . '/cache');
define('CACHE_FILE',      CACHE_DIR . '/domains_cache.json');
define('CONFIG_FILE',     CACHE_DIR . '/teams_config.json');
define('RDAP_BOOT_FILE',  CACHE_DIR . '/rdap_bootstrap.json');
define('RDAP_BOOT_TTL',   86400);
define('RDAP_BOOT_URL',   'https://data.iana.org/rdap/dns.json');
// Registries that publish no expiry date by design (auto-renewal model)
define('NO_EXPIRY_TLDS',  ['de', 'at', 'ch', 'li']);

if (!is_dir(CACHE_DIR)) {
    if (!mkdir(CACHE_DIR, 0755, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'Cannot create cache directory: ' . CACHE_DIR . ' — check write permissions']);
        exit(1);
    }
}


/* ============================================================
   ROUTING
   ============================================================ */
$action = $_GET['action'] ?? 'list';
switch ($action) {
    case 'list':             actionList();            break;
    case 'check':            actionCheck();           break;
    case 'check_all':        actionCheckAll();        break;
    case 'check_ssl':        actionCheckSSL();        break;
    case 'toggle_ssl':       actionToggleSSL();       break;
    case 'save_config':      actionSaveConfig();      break;
    case 'get_config':       actionGetConfig();       break;
    case 'update_domain':    actionUpdateDomain();    break;
    case 'check_http':       actionCheckHttp();       break;
    case 'check_dns':        actionCheckDns();        break;
    case 'check_ports':      actionCheckPorts();      break;
    case 'debug':            actionDebug();           break;
    default: jsonOut(['error' => 'Unknown action']);
}

/* ============================================================
   ACTIONS
   ============================================================ */

function actionList() {
    $domains = loadDomains();
    $cache   = loadCache();
    $result  = [];
    foreach ($domains as $d) {
        $cached   = $cache[$d['domain']] ?? [];
        $result[] = array_merge([
            'expiry_date'       => null,
            'registrar'         => null,
            'days_left'         => null,
            'status'            => 'unknown',
            'last_checked'      => null,
            'whois_source'      => null,
            'ssl_expiry'        => null,
            'ssl_days_left'     => null,
            'ssl_status'        => 'unknown',
            'ssl_issuer'        => null,
            'ssl_last_checked'  => null,
            'http_status'       => null,
            'final_url'         => null,
            'redirects'         => [],
            'response_ms'       => null,
            'http_check_status' => null,
            'last_http_checked' => null,
            'dns_records'       => null,
            'dns_ns'            => [],
            'dns_ns_prev'       => [],
            'ns_changed'        => false,
            'ns_changed_at'     => null,
            'last_dns_checked'  => null,
            'dns_healthy'       => null,
            'port_results'      => null,
            'resolved_ip'       => null,
            'last_ports_checked'=> null,
        ], $d, $cached);
    }
    usort($result, fn($a,$b) => sortNulls($a['days_left'], $b['days_left']));
    jsonOut(['domains' => $result, 'total' => count($result)]);
}

function actionCheck() {
    $domain = strtolower(trim($_GET['domain'] ?? ''));
    if (!$domain) { jsonOut(['error' => 'No domain provided']); return; }
    $result = whoisCheck($domain);
    mergeCache($domain, $result);
    jsonOut($result);
}

function actionCheckAll() {
    // Deprecated in favour of batched per-domain calls from the frontend.
    // Kept for backwards compatibility — checks up to 10 domains per call
    // to stay within PHP time limits.
    $domains = loadDomains();
    $offset  = max(0, (int)($_GET['offset'] ?? 0));
    $limit   = min(10, (int)($_GET['limit']  ?? 10));
    $slice   = array_slice($domains, $offset, $limit);
    $results = [];
    foreach ($slice as $d) {
        $whois = whoisCheck($d['domain']);
        mergeCache($d['domain'], $whois);
        $results[] = array_merge($d, loadCache()[$d['domain']] ?? []);
    }
    jsonOut([
        'domains'    => $results,
        'offset'     => $offset,
        'limit'      => $limit,
        'total'      => count($domains),
        'has_more'   => ($offset + $limit) < count($domains),
        'checked_at' => date('Y-m-d H:i:s'),
    ]);
}

function actionCheckSSL() {
    $domain = strtolower(trim($_GET['domain'] ?? ''));
    if (!$domain) { jsonOut(['error' => 'No domain provided']); return; }
    if (!validateDomain($domain)) { jsonOut(['error' => 'Domain not in monitored list']); return; }
    $result = sslCheck($domain);
    mergeCache($domain, $result);
    jsonOut($result);
}

function actionToggleSSL() {
    $input  = json_decode(file_get_contents('php://input'), true);
    $domain = $input['domain'] ?? '';
    $enable = (bool)($input['monitor_ssl'] ?? false);
    if (!$domain) { jsonOut(['error' => 'No domain provided']); return; }
    $domains = loadDomains();
    $found   = false;
    foreach ($domains as &$d) {
        if ($d['domain'] === $domain) { $d['monitor_ssl'] = $enable; $found = true; break; }
    }
    unset($d);
    if (!$found) { jsonOut(['error' => 'Domain not found']); return; }
    file_put_contents(DOMAINS_FILE, json_encode($domains, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    jsonOut(['success' => true, 'domain' => $domain, 'monitor_ssl' => $enable]);
}

function actionSaveConfig() {
    $input = json_decode(file_get_contents('php://input'), true);
    file_put_contents(CONFIG_FILE, json_encode($input, JSON_PRETTY_PRINT));
    jsonOut(['success' => true]);
}

function actionGetConfig() {
    echo file_exists(CONFIG_FILE)
        ? file_get_contents(CONFIG_FILE)
        : json_encode(['webhook_url'=>'','notify_days'=>[7,14,30],'enabled'=>false,'dashboard_url'=>'']);
}
function actionUpdateDomain() {
    $input  = json_decode(file_get_contents('php://input'), true);
    $domain = $input['domain'] ?? '';
    if (!$domain) { jsonOut(['error' => 'No domain']); return; }
    $domains = loadDomains();
    foreach ($domains as &$d) {
        if ($d['domain'] === $domain) {
            foreach (['owner','team','notes','tags','abandoned','abandoned_note'] as $f) {
                if (isset($input[$f])) $d[$f] = $input[$f];
            }
            if (array_key_exists('monitor_ssl', $input)) $d['monitor_ssl'] = (bool)$input['monitor_ssl'];
            break;
        }
    }
    unset($d);
    file_put_contents(DOMAINS_FILE, json_encode($domains, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    jsonOut(['success' => true]);
}


/* ============================================================
   HTTP STATUS & REDIRECT CHECK
   ============================================================ */

/**
 * Checks the HTTP status of a domain:
 *   - Follows up to 10 redirects, recording the full chain
 *   - Detects final HTTP status code
 *   - Measures total response time
 *   - Classifies result: ok | redirect | error | timeout
 *
 * Request:  GET api.php?action=check_http&domain=example.com
 * Response: { domain, http_status, final_url, redirects:[...], response_ms, http_check_status, last_http_checked }
 */
function actionCheckHttp() {
    $domain = strtolower(trim($_GET['domain'] ?? ''));
    if (!$domain) { jsonOut(['error' => 'No domain']); return; }
    if (!validateDomain($domain)) { jsonOut(['error' => 'Domain not in monitored list']); return; }

    $result = checkHttpStatus($domain);

    // Persist into cache
    $cache = file_exists(CACHE_FILE) ? json_decode(file_get_contents(CACHE_FILE), true) : [];
    if (!isset($cache[$domain])) $cache[$domain] = [];
    $cache[$domain]['http_status']       = $result['http_status']       ?? null;
    $cache[$domain]['final_url']         = $result['final_url']         ?? null;
    $cache[$domain]['redirects']         = $result['redirects']         ?? [];
    $cache[$domain]['response_ms']       = $result['response_ms']       ?? null;
    $cache[$domain]['http_check_status'] = $result['http_check_status'] ?? 'error';
    $cache[$domain]['last_http_checked'] = date('c');
    file_put_contents(CACHE_FILE, json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    jsonOut(array_merge(['domain' => $domain], $result));
}

function checkHttpStatus(string $domain): array {
    $startUrl  = 'https://' . $domain;
    $redirects = [];
    $current   = $startUrl;
    $maxHops   = 10;
    $startTime = microtime(true);

    for ($hop = 0; $hop <= $maxHops; $hop++) {
        $ch = curl_init($current);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_NOBODY         => true,       // HEAD request — faster
            CURLOPT_FOLLOWLOCATION => false,       // We follow manually to record chain
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => false,       // We already check SSL separately
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT      => 'DomainWatch/3.3 (+https://github.com/domainwatch)',
            CURLOPT_HTTPHEADER     => ['Accept: text/html,application/xhtml+xml,*/*'],
        ]);

        $raw      = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            // Try plain HTTP fallback if HTTPS fails on first hop
            if ($hop === 0 && str_starts_with($current, 'https://')) {
                $fallback = 'http://' . $domain;
                $ch2 = curl_init($fallback);
                curl_setopt_array($ch2, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HEADER         => true,
                    CURLOPT_NOBODY         => true,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_TIMEOUT        => 8,
                    CURLOPT_CONNECTTIMEOUT => 6,
                    CURLOPT_USERAGENT      => 'DomainWatch/3.3',
                ]);
                $raw2     = curl_exec($ch2);
                $code2    = (int)curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                $err2     = curl_error($ch2);
                curl_close($ch2);
                if (!$err2 && $code2 > 0) {
                    $redirects[] = ['url' => $fallback, 'status' => $code2];
                    $current     = $fallback;
                    $httpCode    = $code2;
                    $raw         = $raw2;
                    $curlErr     = '';
                }
            }
            if ($curlErr) {
                $ms = (int)round((microtime(true) - $startTime) * 1000);
                return [
                    'http_status'       => null,
                    'final_url'         => $current,
                    'redirects'         => $redirects,
                    'response_ms'       => $ms,
                    'http_check_status' => str_contains($curlErr, 'timed out') ? 'timeout' : 'error',
                    'error_detail'      => $curlErr,
                ];
            }
        }

        // Record this hop
        $redirects[] = ['url' => $current, 'status' => $httpCode];

        // 3xx — extract Location and follow
        if ($httpCode >= 300 && $httpCode < 400 && $hop < $maxHops) {
            if (preg_match('/^Location:\s*(.+)$/mi', $raw, $m)) {
                $location = trim($m[1]);
                // Resolve relative redirects
                if (str_starts_with($location, '/')) {
                    $parsed  = parse_url($current);
                    $location = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? $domain) . $location;
                } elseif (!preg_match('#^https?://#i', $location)) {
                    $location = 'https://' . $location;
                }
                $current = $location;
                continue;
            }
        }

        // Final destination reached
        $ms       = (int)round((microtime(true) - $startTime) * 1000);
        $isOk     = $httpCode >= 200 && $httpCode < 400;
        $hasRedir = count($redirects) > 1;

        // Determine overall status
        if ($httpCode === 0)                         $checkStatus = 'error';
        elseif ($httpCode >= 200 && $httpCode < 300) $checkStatus = $hasRedir ? 'redirect' : 'ok';
        elseif ($httpCode >= 300 && $httpCode < 400) $checkStatus = 'redirect';
        elseif ($httpCode >= 400 && $httpCode < 500) $checkStatus = 'client-error';
        else                                         $checkStatus = 'server-error';

        return [
            'http_status'       => $httpCode,
            'final_url'         => $current,
            'redirects'         => $redirects,
            'response_ms'       => $ms,
            'http_check_status' => $checkStatus,
        ];
    }

    // Too many hops
    $ms = (int)round((microtime(true) - $startTime) * 1000);
    return [
        'http_status'       => null,
        'final_url'         => $current,
        'redirects'         => $redirects,
        'response_ms'       => $ms,
        'http_check_status' => 'loop',
        'error_detail'      => 'Too many redirects (>' . $maxHops . ')',
    ];
}

/* ============================================================
   DOMAIN INTELLIGENCE — DNS CHECKER
   ============================================================ */

/**
 * Fetches A, AAAA, CNAME, MX, NS, TXT, SOA records for a domain.
 * Also detects nameserver changes vs. last stored state and flags drift.
 *
 * GET api.php?action=check_dns&domain=example.com
 */
function actionCheckDns() {
    $domain = strtolower(trim($_GET['domain'] ?? ''));
    if (!$domain) { jsonOut(['error' => 'No domain']); return; }
    if (!validateDomain($domain)) { jsonOut(['error' => 'Domain not in monitored list']); return; }

    $result = fetchDnsRecords($domain);

    // Load cache, preserve previous NS for change detection
    $cache = file_exists(CACHE_FILE) ? json_decode(file_get_contents(CACHE_FILE), true) : [];
    if (!isset($cache[$domain])) $cache[$domain] = [];

    $prevNs      = $cache[$domain]['dns_ns'] ?? [];
    $currentNs   = array_column($result['NS'] ?? [], 'value');
    sort($prevNs); sort($currentNs);
    $nsChanged   = !empty($prevNs) && $prevNs !== $currentNs;
    $nsChangedAt = $nsChanged ? date('c') : ($cache[$domain]['ns_changed_at'] ?? null);
    $prevNsSaved = $nsChanged ? $prevNs : ($cache[$domain]['dns_ns_prev'] ?? []);

    $cache[$domain]['dns_records']      = $result;
    $cache[$domain]['dns_ns']           = $currentNs;
    $cache[$domain]['dns_ns_prev']      = $prevNsSaved;
    $cache[$domain]['ns_changed']       = $nsChanged;
    $cache[$domain]['ns_changed_at']    = $nsChangedAt;
    $cache[$domain]['last_dns_checked'] = date('c');
    $cache[$domain]['dns_healthy']      = $result['healthy'];

    file_put_contents(CACHE_FILE, json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    jsonOut(array_merge(['domain' => $domain, 'ns_changed' => $nsChanged, 'ns_changed_at' => $nsChangedAt, 'prev_ns' => $prevNsSaved], $result));
}

function fetchDnsRecords(string $domain): array {
    $types  = [DNS_A, DNS_AAAA, DNS_CNAME, DNS_MX, DNS_NS, DNS_TXT, DNS_SOA];
    $labels = ['A', 'AAAA', 'CNAME', 'MX', 'NS', 'TXT', 'SOA'];
    $out    = [];
    $hasAny = false;

    foreach ($types as $i => $type) {
        $label   = $labels[$i];
        $records = @dns_get_record($domain, $type);
        if (!$records) { $out[$label] = []; continue; }
        $hasAny = true;

        $out[$label] = array_map(function($r) use ($label) {
            return match($label) {
                'A'     => ['value' => $r['ip'],              'ttl' => $r['ttl']],
                'AAAA'  => ['value' => $r['ipv6'],            'ttl' => $r['ttl']],
                'CNAME' => ['value' => $r['target'],          'ttl' => $r['ttl']],
                'MX'    => ['value' => $r['target'],          'ttl' => $r['ttl'], 'priority' => $r['pri']],
                'NS'    => ['value' => rtrim($r['target'],'.'), 'ttl' => $r['ttl']],
                'TXT'   => ['value' => implode('', $r['entries'] ?? [$r['txt'] ?? '']), 'ttl' => $r['ttl']],
                'SOA'   => ['value' => $r['mname'],           'ttl' => $r['ttl'],
                            'rname' => $r['rname'], 'serial' => $r['serial'],
                            'refresh' => $r['refresh'], 'retry' => $r['retry'], 'expire' => $r['expire']],
                default => ['value' => json_encode($r),       'ttl' => $r['ttl'] ?? 0],
            };
        }, $records);
    }

    // DNSSEC indicator via SOA check
    $dnssec = false;
    if (!empty($out['NS'])) {
        $nsHost = $out['NS'][0]['value'] ?? '';
        if ($nsHost) {
            // Try to detect DNSKEY record presence (basic check)
            $dnskey = @dns_get_record($domain, DNS_ANY);
            foreach (($dnskey ?: []) as $r) {
                if (($r['type'] ?? '') === 'DNSKEY') { $dnssec = true; break; }
            }
        }
    }
    $out['dnssec'] = $dnssec;
    $out['healthy'] = $hasAny;

    return $out;
}


/* ============================================================
   DOMAIN INTELLIGENCE — PORT CHECKER
   ============================================================ */

/**
 * Checks connectivity on common + custom ports.
 * GET api.php?action=check_ports&domain=example.com[&ports=80,443,25]
 */
function actionCheckPorts() {
    $domain      = strtolower(trim($_GET['domain'] ?? ''));
    $customPorts = $_GET['ports'] ?? '';
    if (!$domain) { jsonOut(['error' => 'No domain']); return; }
    if (!validateDomain($domain)) { jsonOut(['error' => 'Domain not in monitored list']); return; }

    // Resolve domain to IP first
    $ip = gethostbyname($domain);
    if ($ip === $domain) {
        jsonOut(['error' => "Cannot resolve $domain to an IP address"]);
        return;
    }
    // Block SSRF via DNS rebinding to private ranges
    if (isPrivateIP($ip)) {
        jsonOut(['error' => 'Domain resolves to a private/internal IP — blocked for security']);
        return;
    }

    // Default ports to check with friendly names
    $defaultPorts = [
        80   => 'HTTP',
        443  => 'HTTPS',
        25   => 'SMTP',
        587  => 'SMTP/TLS',
        465  => 'SMTPS',
        993  => 'IMAPS',
        995  => 'POP3S',
        22   => 'SSH',
        21   => 'FTP',
        3306 => 'MySQL',
        5432 => 'PostgreSQL',
        6379 => 'Redis',
    ];

    // Merge with any custom ports from query string
    $portsToCheck = $defaultPorts;
    if ($customPorts) {
        foreach (array_map('intval', explode(',', $customPorts)) as $p) {
            if ($p > 0 && $p <= 65535 && !isset($portsToCheck[$p])) {
                $portsToCheck[$p] = "Port $p";
            }
        }
    }

    $results   = [];
    $startTime = microtime(true);

    foreach ($portsToCheck as $port => $name) {
        $t0      = microtime(true);
        $sock    = @fsockopen($ip, $port, $errno, $errstr, 3);
        $ms      = (int)round((microtime(true) - $t0) * 1000);
        $open    = $sock !== false;
        if ($sock) fclose($sock);
        $results[] = [
            'port'   => $port,
            'name'   => $name,
            'open'   => $open,
            'ms'     => $open ? $ms : null,
            'error'  => $open ? null : $errstr,
        ];
    }

    $totalMs = (int)round((microtime(true) - $startTime) * 1000);
    $openCount = count(array_filter($results, fn($r) => $r['open']));

    // Persist
    $cache = file_exists(CACHE_FILE) ? json_decode(file_get_contents(CACHE_FILE), true) : [];
    if (!isset($cache[$domain])) $cache[$domain] = [];
    $cache[$domain]['port_results']       = $results;
    $cache[$domain]['resolved_ip']        = $ip;
    $cache[$domain]['last_ports_checked'] = date('c');
    file_put_contents(CACHE_FILE, json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    jsonOut([
        'domain'      => $domain,
        'resolved_ip' => $ip,
        'ports'       => $results,
        'open_count'  => $openCount,
        'total_ms'    => $totalMs,
        'checked_at'  => date('c'),
    ]);
}

/**
 * Debug endpoint: api.php?action=debug[&domain=example.com]
 * Returns environment info and optionally traces each lookup strategy.
 */
function actionDebug() {
    $domain = strtolower(trim($_GET['domain'] ?? ''));

    $info = [
        'api_version'       => 'v3.5',
        'actions_available' => ['list','check','check_all','check_ssl','toggle_ssl','save_config','get_config','update_domain','check_http','check_dns','check_ports','debug'],
        'php_version'       => PHP_VERSION,
        'curl_available'    => function_exists('curl_init'),
        'allow_url_fopen'   => ini_get('allow_url_fopen') ? 'on' : 'off',
        'curl_version'      => function_exists('curl_version') ? curl_version()['version'] : null,
        'rdap_bootstrap'    => [
            'file'   => RDAP_BOOT_FILE,
            'exists' => file_exists(RDAP_BOOT_FILE),
            'age_s'  => file_exists(RDAP_BOOT_FILE) ? time() - filemtime(RDAP_BOOT_FILE) : null,
            'ttl_s'  => RDAP_BOOT_TTL,
            'tlds'   => file_exists(RDAP_BOOT_FILE)
                        ? count(json_decode(file_get_contents(RDAP_BOOT_FILE), true) ?? [])
                        : 0,
        ],
        'cache_dir_writable' => is_writable(CACHE_DIR),
        'server_time'        => date('Y-m-d H:i:s T'),
        'domains_file'       => DOMAINS_FILE,
        'domains_count'      => count(loadDomains()),
        'domains_list'       => array_column(loadDomains(), 'domain'),
        'cache_count'        => count(loadCache()),
        'network_tests'      => [
            'port43_blocked'         => testSocket('whois.verisign-grs.com', 43) !== 'ok' ? 'yes' : 'no',
            'rdap_verisign_com'      => testHttp('https://rdap.verisign.com/com/v1/domain/example.com'),
            'rdap_iana_bootstrap'    => testHttp('https://data.iana.org/rdap/dns.json'),
            'rdap_dns_be'            => testHttp('https://rdap.dns.be/domain/example.be'),
            'rdap_sidn_nl'           => testHttp('https://rdap.sidn.nl/domain/example.nl'),
            'rdap_nic_io'            => testHttp('https://rdap.nic.io/domain/example.io'),
            'rdap_eu'                => testHttp('https://rdap.eu/domain/example.eu'),
            'rdap_tcinet_ru'         => testHttp('https://rdap.tcinet.ru/domain/example.ru'),
            'rdap_donuts_services'   => testHttp('https://rdap.donuts.co/domain/xdn.services'),
            // Universal RDAP proxy candidates
            'proxy_rdap_org'         => testHttp('https://rdap.org/domain/example.com'),
            'proxy_api_rdap_org'     => testHttp('https://api.rdap.org/domain/example.com'),
            'proxy_rdap_net'         => testHttp('https://www.rdap.net/domain/example.com'),
            'proxy_who_dat'          => testHttp('https://who-dat.as93.net/example.com'),
            'proxy_whoisjsonapi'     => testHttp('https://www.whoisjsonapi.com/v1/example.com'),
            'proxy_whoisfreaks'      => testHttp('https://api.whoisfreaks.com/v1.0/whois?whois=live&domainName=example.com'),
        ],
    ];

    if ($domain) {
        // Trace each strategy individually
        $tld  = domTLD($domain);
        $sld  = domSLD($domain);
        $map  = ianaBootstrapMap();

        $trace = [
            'domain'       => $domain,
            'tld'          => $tld,
            'sld'          => $sld,
            'no_expiry_tld'=> isNoExpiryTld($domain),
            'iana_bootstrap_tld_entry' => $map[$tld] ?? null,
            'iana_bootstrap_sld_entry' => $sld ? ($map[$sld] ?? null) : null,
        ];

        // Mirror exact production cascade order
        $t = microtime(true);
        $r1 = tryIanaRdap($domain);
        $trace['strategy_1_iana_rdap'] = ['result' => $r1 ? 'OK' : 'null', 'ms' => round((microtime(true)-$t)*1000)];

        if (!$r1) {
            $t = microtime(true);
            $r2 = tryRdapOrg($domain);
            $trace['strategy_2_rdap_org_proxy'] = ['result' => $r2 ? 'OK' : 'null', 'ms' => round((microtime(true)-$t)*1000)];
        }

        if (!$r1 && !($r2??null)) {
            $t = microtime(true);
            $r3 = tryStaticRdap($domain);
            $trace['strategy_3_static_rdap'] = ['result' => $r3 ? 'OK' : 'null', 'ms' => round((microtime(true)-$t)*1000)];
        }

        if (!$r1 && !($r2??null) && !($r3??null)) {
            $t = microtime(true);
            $r4 = tryIanaWhoisDiscovery($domain);
            $trace['strategy_4_iana_whois_port43'] = ['result' => $r4 ? 'OK' : 'null', 'ms' => round((microtime(true)-$t)*1000)];
        }

        if (!$r1 && !($r2??null) && !($r3??null) && !($r4??null)) {
            $t = microtime(true);
            $r5 = tryStaticWhois($domain);
            $trace['strategy_5_static_whois_port43'] = ['result' => $r5 ? 'OK' : 'null', 'ms' => round((microtime(true)-$t)*1000)];
        }

        if (!$r1 && !($r2??null) && !($r3??null) && !($r4??null) && !($r5??null)) {
            $t = microtime(true);
            $r6 = tryRdapNicGuess($domain);
            $trace['strategy_6_rdap_nic_guess'] = ['result' => $r6 ? 'OK' : 'null', 'ms' => round((microtime(true)-$t)*1000)];
        }

        if (!$r1 && !($r2??null) && !($r3??null) && !($r4??null) && !($r5??null) && !($r6??null)) {
            $t = microtime(true);
            $r7 = tryWhoisJsonApi($domain);
            $trace['strategy_7_whoisjsonapi'] = ['result' => $r7 ? 'OK' : 'null', 'ms' => round((microtime(true)-$t)*1000)];
        }

        $final = $r1 ?? ($r2??null) ?? ($r3??null) ?? ($r4??null) ?? ($r5??null) ?? ($r6??null) ?? ($r7??null);
        $trace['final_result'] = $final ? array_intersect_key($final, array_flip(['expiry_date','registrar','days_left','status','whois_source'])) : null;
        $info['trace'] = $trace;
    }

    jsonOut($info);
}

/* ============================================================
   IANA RDAP BOOTSTRAP  (Strategy 1)
   ============================================================
   Loads https://data.iana.org/rdap/dns.json once per 24 h,
   builds a flat  tld => rdap_base_url  map and caches it.
   Covers every ICANN-registered TLD — no manual list needed.
   ============================================================ */

/**
 * Return the IANA bootstrap map: ['com' => 'https://...', ...]
 * Uses local cache; re-fetches when stale.
 */
function ianaBootstrapMap(): array {
    static $map = null;
    if ($map !== null) return $map;

    // Load from local cache if fresh
    if (file_exists(RDAP_BOOT_FILE) &&
        (time() - filemtime(RDAP_BOOT_FILE)) < RDAP_BOOT_TTL) {
        $cached = json_decode(file_get_contents(RDAP_BOOT_FILE), true);
        if ($cached) { $map = $cached; return $map; }
    }

    // Fetch fresh copy from IANA
    $data = httpGet(RDAP_BOOT_URL, 15);
    if (!$data) {
        // Return whatever we have on disk even if stale
        if (file_exists(RDAP_BOOT_FILE)) {
            $cached = json_decode(file_get_contents(RDAP_BOOT_FILE), true);
            if ($cached) { $map = $cached; return $map; }
        }
        $map = [];
        return $map;
    }

    $json = json_decode($data, true);
    if (!isset($json['services'])) { $map = []; return $map; }

    // Flatten:  [[tlds], [urls]] → tld => first_https_url
    $flat = [];
    foreach ($json['services'] as $svc) {
        $tlds = $svc[0] ?? [];
        $urls = $svc[1] ?? [];
        if (!$tlds || !$urls) continue;

        // Prefer HTTPS URL
        $url = null;
        foreach ($urls as $u) { if (str_starts_with($u, 'https://')) { $url = $u; break; } }
        if (!$url) $url = $urls[0];

        // Ensure trailing slash
        if (substr($url, -1) !== '/') $url .= '/';

        foreach ($tlds as $tld) {
            $flat[strtolower($tld)] = $url;
        }
    }

    file_put_contents(RDAP_BOOT_FILE, json_encode($flat, JSON_PRETTY_PRINT));
    $map = $flat;
    return $map;
}

/**
 * Strategy 1: look up TLD in IANA bootstrap, query RDAP endpoint.
 */
function tryIanaRdap(string $domain): ?array {
    $map = ianaBootstrapMap();
    if (!$map) return null;

    $tld = domTLD($domain);

    // Exact TLD match
    $base = $map[$tld] ?? null;

    // Fallback: some ccTLDs have SLD-style entries (rare but safe to try)
    if (!$base) {
        $sld = domSLD($domain);
        if ($sld) $base = $map[$sld] ?? null;
    }

    if (!$base) return null;

    // Construct the RDAP domain query URL
    // Some registries want the full domain; IANA base URLs end with '/'
    $url = $base . 'domain/' . urlencode($domain);

    $data = httpGet($url, 10);
    if (!$data) return null;

    $json = json_decode($data, true);
    if (!$json || isset($json['errorCode'])) return null;

    return parseRdapJson($domain, $json, 'iana-rdap-bootstrap');
}

/* ============================================================
   STATIC RDAP FALLBACK  (Strategy 2)
   ============================================================
   Fast direct map for popular TLDs — used when bootstrap
   cache is cold or the IANA fetch fails.
   ============================================================ */

function tryStaticRdap(string $domain): ?array {
    $t = domTLD($domain);
    $s = domSLD($domain);

    static $map = [
        // ── Generic ───────────────────────────────────────
        'com'         => 'https://rdap.verisign.com/com/v1/',
        'net'         => 'https://rdap.verisign.com/net/v1/',
        'org'         => 'https://rdap.publicinterestregistry.org/rdap/',
        'info'        => 'https://rdap.identitydigital.services/rdap/',
        'biz'         => 'https://rdap.identitydigital.services/rdap/',
        'pro'         => 'https://rdap.identitydigital.services/rdap/',
        'mobi'        => 'https://rdap.identitydigital.services/rdap/',
        // ── New gTLDs ─────────────────────────────────────
        'world'       => 'https://rdap.nic.world/',
        'services'    => 'https://rdap.donuts.co/domain/',         // Identity Digital (Donuts)
        'online'      => 'https://rdap.centralnic.com/online/',
        'site'        => 'https://rdap.centralnic.com/site/',
        'tech'        => 'https://rdap.centralnic.com/tech/',
        'store'       => 'https://rdap.centralnic.com/store/',
        'shop'        => 'https://rdap.gmoregistry.net/rdap/',
        'digital'     => 'https://rdap.donuts.co/domain/',
        'media'       => 'https://rdap.donuts.co/domain/',
        'agency'      => 'https://rdap.donuts.co/domain/',
        'solutions'   => 'https://rdap.donuts.co/domain/',
        'app'         => 'https://pubapi.registry.google/rdap/',
        'dev'         => 'https://pubapi.registry.google/rdap/',
        'page'        => 'https://pubapi.registry.google/rdap/',
        'new'         => 'https://pubapi.registry.google/rdap/',
        'cloud'       => 'https://rdap.nic.cloud/',
        'ai'          => 'https://rdap.nic.ai/',
        'io'          => 'https://rdap.nic.io/',
        'co'          => 'https://rdap.nic.co/',
        'xyz'         => 'https://rdap.centralnic.com/xyz/',
        // ── ccTLDs ────────────────────────────────────────
        'pl'          => 'https://rdap.dns.pl/',
        'eu'          => 'https://rdap.eu/',
        'de'          => 'https://rdap.denic.de/',        // NOTE: no expiry date published
        'uk'          => 'https://rdap.nominet.uk/',
        'fr'          => 'https://rdap.nic.fr/',
        'nl'          => 'https://rdap.sidn.nl/',
        'be'          => 'https://rdap.dns.be/',
        'ch'          => 'https://rdap.nic.ch/',
        'at'          => 'https://rdap.nic.at/',
        'se'          => 'https://rdap.iis.se/',
        'no'          => 'https://rdap.norid.no/',
        'dk'          => 'https://rdap.dk-hostmaster.dk/',
        'fi'          => 'https://rdap.fi/rdap/rdap/',    // SIDR .fi double-path
        'it'          => 'https://rdap.nic.it/',
        'es'          => 'https://rdap.nic.es/',
        'pt'          => 'https://rdap.dns.pt/',
        'cz'          => 'https://rdap.nic.cz/',
        'sk'          => 'https://rdap.sk-nic.sk/',
        'hu'          => 'https://rdap.nic.hu/',
        'ro'          => 'https://rdap.rotld.ro/',
        'bg'          => 'https://rdap.register.bg/',
        'ru'          => 'https://rdap.tcinet.ru/',
        'su'          => 'https://rdap.tcinet.ru/',
        'ua'          => 'https://rdap.hostmaster.ua/',
        'us'          => 'https://rdap.identitydigital.services/rdap/',  // Identity Digital (fka Afilias)
        'ca'          => 'https://rdap.cira.ca/rdap/',
        'au'          => 'https://rdap.cctld.au/rdap/',
        'nz'          => 'https://rdap.srs.net.nz/',
        'jp'          => 'https://rdap.jprs.jp/',
        'cn'          => 'https://rdap.cnnic.cn/',
        'hk'          => 'https://rdap.hkirc.hk/',
        'tw'          => 'https://ccrdap.twnic.tw/tw/',
        'kr'          => 'https://rdap.kr/',
        'sg'          => 'https://rdap.sgnic.sg/',
        'in'          => 'https://rdap.registry.in/',
        'br'          => 'https://rdap.registro.br/',
        'mx'          => 'https://rdap.mx/',
        'ar'          => 'https://rdap.nic.ar/',
        'za'          => 'https://rdap.registry.net.za/',
        'ph'          => 'https://rdap.dot.ph/',
        'ae'          => 'https://rdap.aeda.net.ae/',
        'nu'          => 'https://rdap.iis.nu/',
        'is'          => 'https://rdap.isnic.is/',
        'ie'          => 'https://rdap.iedr.ie/',
        'lt'          => 'https://rdap.domreg.lt/',
        'lv'          => 'https://rdap.nic.lv/',
        'ee'          => 'https://rdap.internet.ee/',
        'hr'          => 'https://rdap.dns.hr/',
        'si'          => 'https://rdap.register.si/',
        'rs'          => 'https://rdap.rnids.rs/',
        'mx'          => 'https://rdap.mx/',
        // ── SLDs ─────────────────────────────────────────
        'co.uk'       => 'https://rdap.nominet.uk/',
        'org.uk'      => 'https://rdap.nominet.uk/',
        'net.uk'      => 'https://rdap.nominet.uk/',
        'me.uk'       => 'https://rdap.nominet.uk/',
        'com.au'      => 'https://rdap.cctld.au/rdap/',
        'net.au'      => 'https://rdap.cctld.au/rdap/',
        'org.au'      => 'https://rdap.cctld.au/rdap/',
        'com.br'      => 'https://rdap.registro.br/',
        'net.br'      => 'https://rdap.registro.br/',
        'org.br'      => 'https://rdap.registro.br/',
        'com.mx'      => 'https://rdap.mx/',
        'net.mx'      => 'https://rdap.mx/',
        'com.hk'      => 'https://rdap.hkirc.hk/',
        'net.hk'      => 'https://rdap.hkirc.hk/',
        'org.hk'      => 'https://rdap.hkirc.hk/',
        'com.ar'      => 'https://rdap.nic.ar/',
        'com.sg'      => 'https://rdap.sgnic.sg/',
        'co.jp'       => 'https://rdap.jprs.jp/',
        'ne.jp'       => 'https://rdap.jprs.jp/',
        'co.nz'       => 'https://rdap.srs.net.nz/',
        'co.za'       => 'https://rdap.registry.net.za/',
        'com.cn'      => 'https://rdap.cnnic.cn/',
        // CentralNic SLDs — URL format: https://rdap.centralnic.com/{sld}/domain/{domain}
        'br.com'      => 'https://rdap.centralnic.com/br.com/',
        'cn.com'      => 'https://rdap.centralnic.com/cn.com/',
        'de.com'      => 'https://rdap.centralnic.com/de.com/',
        'eu.com'      => 'https://rdap.centralnic.com/eu.com/',
        'gb.com'      => 'https://rdap.centralnic.com/gb.com/',
        'gb.net'      => 'https://rdap.centralnic.com/gb.net/',
        'uk.com'      => 'https://rdap.centralnic.com/uk.com/',
        'uk.net'      => 'https://rdap.centralnic.com/uk.net/',
        'us.com'      => 'https://rdap.centralnic.com/us.com/',
        'ru.com'      => 'https://rdap.centralnic.com/ru.com/',
        'hu.com'      => 'https://rdap.centralnic.com/hu.com/',
        'no.com'      => 'https://rdap.centralnic.com/no.com/',
        'se.com'      => 'https://rdap.centralnic.com/se.com/',
        'se.net'      => 'https://rdap.centralnic.com/se.net/',
        'za.com'      => 'https://rdap.centralnic.com/za.com/',
        'kr.com'      => 'https://rdap.centralnic.com/kr.com/',
        'sa.com'      => 'https://rdap.centralnic.com/sa.com/',
        'jpn.com'     => 'https://rdap.centralnic.com/jpn.com/',
        'qc.com'      => 'https://rdap.centralnic.com/qc.com/',
    ];

    // Try SLD first (e.g. com.au before au)
    if ($s && isset($map[$s])) {
        $r = rdapFetch($map[$s] . 'domain/' . urlencode($domain), $domain, 'rdap-static-sld');
        if ($r) return $r;
    }

    if (!isset($map[$t])) return null;
    return rdapFetch($map[$t] . 'domain/' . urlencode($domain), $domain, 'rdap-static');
}

function rdapFetch(string $url, string $domain, string $source): ?array {
    $data = httpGet($url, 9);
    if (!$data) return null;
    $json = json_decode($data, true);
    if (!$json || isset($json['errorCode'])) return null;
    return parseRdapJson($domain, $json, $source);
}

/* ============================================================
   IANA WHOIS DISCOVERY  (Strategy 3)
   ============================================================
   Queries whois.iana.org for the TLD's port-43 WHOIS server,
   then queries it directly.
   ============================================================ */

function tryIanaWhoisDiscovery(string $domain): ?array {
    $tld = domTLD($domain);
    $fp  = @fsockopen('whois.iana.org', 43, $errno, $errstr, 5);
    if (!$fp) return null;
    stream_set_timeout($fp, 6);
    fputs($fp, $tld . "\r\n");
    $raw = '';
    while (!feof($fp)) $raw .= fgets($fp, 4096);
    fclose($fp);
    if (!preg_match('/whois:\s*(\S+)/i', $raw, $m)) return null;
    $server = trim($m[1]);
    if (!$server || $server === 'whois.iana.org') return null;
    return socketWhois($server, $domain, 'whois-iana-discovery');
}

/* ============================================================
   STATIC WHOIS MAP  (Strategy 4)
   ============================================================ */

function tryStaticWhois(string $domain): ?array {
    $t = domTLD($domain);
    $s = domSLD($domain);
    static $map = [
        'com'=>'whois.verisign-grs.com','net'=>'whois.verisign-grs.com','org'=>'whois.pir.org',
        'info'=>'whois.afilias.net','biz'=>'whois.biz','co'=>'whois.nic.co',
        'io'=>'whois.nic.io','ai'=>'whois.nic.ai','app'=>'whois.nic.google',
        'dev'=>'whois.nic.google','xyz'=>'whois.centralnic.com',
        'ae'=>'whois.aeda.net.ae','au'=>'whois.auda.org.au','be'=>'whois.dns.be',
        'bg'=>'whois.register.bg','br'=>'whois.registro.br','ca'=>'whois.cira.ca',
        'ch'=>'whois.nic.ch','cn'=>'whois.cnnic.net.cn','cz'=>'whois.nic.cz',
        'de'=>'whois.denic.de','dk'=>'whois.dk-hostmaster.dk','ee'=>'whois.tld.ee',
        'es'=>'whois.nic.es','eu'=>'whois.eu','fi'=>'whois.fi','fr'=>'whois.nic.fr',
        'hk'=>'whois.hkirc.hk','hr'=>'whois.dns.hr','hu'=>'whois.nic.hu',
        'ie'=>'whois.iedr.ie','il'=>'whois.isoc.org.il','in'=>'whois.registry.in',
        'is'=>'whois.isnic.is','it'=>'whois.nic.it','jp'=>'whois.jprs.jp',
        'kr'=>'whois.kr','lt'=>'whois.domreg.lt','lv'=>'whois.nic.lv',
        'mx'=>'whois.mx','my'=>'whois.mynic.my','nl'=>'whois.domain-registry.nl',
        'no'=>'whois.norid.no','nu'=>'whois.iis.nu','nz'=>'whois.srs.net.nz',
        'ph'=>'whois.dot.ph','pl'=>'whois.dns.pl','pt'=>'whois.dns.pt',
        'ro'=>'whois.rotld.ro','rs'=>'whois.rnids.rs','ru'=>'whois.tcinet.ru',
        'se'=>'whois.iis.se','sg'=>'whois.sgnic.sg','si'=>'whois.register.si',
        'sk'=>'whois.sk-nic.sk','su'=>'whois.tcinet.ru','tr'=>'whois.nic.tr',
        'tw'=>'whois.twnic.net.tw','ua'=>'whois.ua','uk'=>'whois.nic.uk',
        'us'=>'whois.nic.us','za'=>'whois.registry.net.za',
        // gTLDs
        'services'=>'whois.nic.services','digital'=>'whois.nic.digital',
        'media'=>'whois.nic.media','agency'=>'whois.nic.agency','cloud'=>'whois.nic.cloud',
        'online'=>'whois.nic.online','site'=>'whois.nic.site','tech'=>'whois.centralnic.com',
        'store'=>'whois.centralnic.com','me'=>'whois.nic.me',
        // SLDs
        'co.uk'=>'whois.nic.uk','org.uk'=>'whois.nic.uk','me.uk'=>'whois.nic.uk',
        'com.au'=>'whois.auda.org.au','net.au'=>'whois.auda.org.au','org.au'=>'whois.auda.org.au',
        'com.br'=>'whois.registro.br','net.br'=>'whois.registro.br',
        'com.hk'=>'whois.hkirc.hk','net.hk'=>'whois.hkirc.hk',
        'com.cn'=>'whois.cnnic.net.cn','net.cn'=>'whois.cnnic.net.cn',
        'com.mx'=>'whois.mx','com.sg'=>'whois.sgnic.sg',
        'co.jp'=>'whois.jprs.jp','ne.jp'=>'whois.jprs.jp',
        'co.nz'=>'whois.srs.net.nz','co.za'=>'whois.registry.net.za',
        // CentralNic
        'eu.com'=>'whois.centralnic.com','uk.com'=>'whois.centralnic.com',
        'us.com'=>'whois.centralnic.com','de.com'=>'whois.centralnic.com',
        'br.com'=>'whois.centralnic.com','ru.com'=>'whois.centralnic.com',
    ];
    if ($s && isset($map[$s])) {
        $r = socketWhois($map[$s], $domain, 'whois-sld');
        if ($r) return $r;
    }
    if (isset($map[$t])) return socketWhois($map[$t], $domain, 'whois-static');
    return socketWhois('whois.nic.' . $t, $domain, 'whois-nic-guess');
}

/* Strategy 5 — REST fallback */
function tryWhoisJsonApi(string $domain): ?array {
    $data = httpGet('https://www.whoisjsonapi.com/v1/' . urlencode($domain), 10);
    if (!$data) return null;
    $json = json_decode($data, true);
    $exp  = $json['domain']['expiration_date'] ?? null;
    if (!$exp) return null;
    return buildResult($domain, $exp, $json['registrar']['name'] ?? null, 'whoisjsonapi');
}

/* ============================================================
   REGISTRIES THAT PUBLISH NO EXPIRY DATE
   ============================================================
   Some registries (notably DENIC .de) do not include an
   expiration date in RDAP or WHOIS responses by design.
   Domains auto-renew while paid; there is no fixed expiry.
   We still query them so we can confirm the domain is active,
   but show "N/A" instead of "Error".
   ============================================================ */

function isNoExpiryTld(string $domain): bool {
    $tld = domTLD($domain);
    return in_array($tld, NO_EXPIRY_TLDS);
}

/**
 * Try RDAP and return domain info even when no expiry is available.
 * Used as a post-cascade step for no-expiry TLDs.
 */
function tryNoExpiryRdap(string $domain, string $source): ?array {
    // Attempt to get at least registrar info from RDAP
    $map = [
        'de' => 'https://rdap.denic.de/',
        'at' => 'https://rdap.nic.at/',
        'ch' => 'https://rdap.nic.ch/',
        'li' => 'https://rdap.nic.ch/',   // SWITCH manages .li same as .ch
    ];
    $tld  = domTLD($domain);
    $base = $map[$tld] ?? null;
    if (!$base) return null;

    $data = httpGet($base . 'domain/' . urlencode($domain), 10);
    if (!$data) return null;
    $json = json_decode($data, true);
    if (!$json || isset($json['errorCode'])) return null;

    // Extract registrar even though there's no expiry
    $registrar = null;
    foreach ($json['entities'] ?? [] as $e) {
        if (in_array('registrar', $e['roles'] ?? [])) {
            foreach ($e['vcardArray'][1] ?? [] as $v) {
                if (($v[0] ?? '') === 'fn' && !empty($v[3])) { $registrar = $v[3]; break; }
            }
        }
    }

    return [
        'domain'       => $domain,
        'expiry_date'  => null,
        'registrar'    => $registrar,
        'days_left'    => null,
        'status'       => 'no-expiry',
        'last_checked' => date('Y-m-d H:i:s'),
        'whois_source' => $source . '-no-expiry',
        'note'         => 'Registry does not publish expiry dates (auto-renewal model)',
    ];
}



function whoisCheck(string $domain): array {
    $domain = strtolower(trim($domain));

    // Full cascade — works whether port 43 is open or not:
    //   1. IANA RDAP bootstrap (HTTPS, 1197 TLDs)
    //   2. rdap.org proxy (HTTPS, universal — covers registries blocked by firewall)
    //   3. Static RDAP map (HTTPS, direct)
    //   4. IANA WHOIS discovery (port 43 — works once firewall opens)
    //   5. Static WHOIS map (port 43 — works once firewall opens)
    //   6. rdap.nic.{tld} guess (HTTPS)
    //   7. whoisjsonapi.com REST (HTTPS)
    $result = tryIanaRdap($domain)
           ?? tryRdapOrg($domain)
           ?? tryStaticRdap($domain)
           ?? tryIanaWhoisDiscovery($domain)
           ?? tryStaticWhois($domain)
           ?? tryRdapNicGuess($domain)
           ?? tryWhoisJsonApi($domain);

    if (!$result && isNoExpiryTld($domain)) {
        $result = tryNoExpiryRdap($domain, 'rdap');
    }

    return $result ?? [
        'domain'       => $domain,
        'expiry_date'  => null,
        'registrar'    => null,
        'days_left'    => null,
        'status'       => 'error',
        'last_checked' => date('Y-m-d H:i:s'),
        'whois_source' => 'none',
        'error'        => 'All lookup strategies failed. Visit api.php?action=debug&domain=' . $domain,
    ];
}

/**
 * rdap.org universal proxy — single HTTPS endpoint for any TLD.
 * Acts as a proxy to the correct registry RDAP server.
 * https://rdap.org/domain/{domain}
 */
function tryRdapOrg(string $domain): ?array {
    $data = httpGet('https://rdap.org/domain/' . urlencode($domain), 10);
    if (!$data) return null;
    $json = json_decode($data, true);
    if (!$json || isset($json['errorCode'])) return null;
    return parseRdapJson($domain, $json, 'rdap.org');
}


/**
 * Strategy 6: Guess rdap.nic.{tld} — works for many ccTLD and new gTLD operators
 * that follow the standard NIC naming convention.
 */
function tryRdapNicGuess(string $domain): ?array {
    $tld = domTLD($domain);
    // Skip TLDs we already know don't use this pattern
    $skip = ['com','net','org','info','biz','eu','uk','au','de','nl','fr','pl','ru','cn','br','jp','ca','us'];
    if (in_array($tld, $skip)) return null;

    $url  = "https://rdap.nic.{$tld}/domain/" . urlencode($domain);
    $data = httpGet($url, 6);
    if (!$data) return null;
    $json = json_decode($data, true);
    if (!$json || isset($json['errorCode'])) return null;
    return parseRdapJson($domain, $json, 'rdap-nic-guess');
}

/* ============================================================
   RDAP RESPONSE PARSER
   ============================================================ */

function parseRdapJson(string $domain, array $json, string $source): ?array {
    $expiryRaw = null;
    $registrar = null;

    // Scan all events — match any expiry-related action name
    foreach ($json['events'] ?? [] as $e) {
        $act = strtolower(trim($e['eventAction'] ?? ''));
        $isExpiry = (
            strpos($act, 'expir') !== false ||  // expiration, expiry, expires
            $act === 'expiry' ||
            $act === 'expires' ||
            $act === 'deletion'                 // some registries use deletion date
        );
        if ($isExpiry && !empty($e['eventDate'])) {
            $expiryRaw = $e['eventDate'];
            // Prefer 'expiration' over 'deletion' if both present — keep scanning
            if (strpos($act, 'expir') !== false) break;
        }
    }

    // Also check top-level fields some registries include
    if (!$expiryRaw) {
        foreach (['expirationDate','expiration_date','expires','expiry'] as $k) {
            if (!empty($json[$k])) { $expiryRaw = $json[$k]; break; }
        }
    }

    // Extract registrar name
    foreach ($json['entities'] ?? [] as $e) {
        $roles = $e['roles'] ?? [];
        if (in_array('registrar', $roles) || in_array('sponsor', $roles)) {
            // vcardArray[1] is array of vCard properties
            foreach ($e['vcardArray'][1] ?? [] as $v) {
                if (($v[0] ?? '') === 'fn' && !empty($v[3])) {
                    $registrar = $v[3]; break;
                }
            }
            if (!$registrar) {
                $registrar = $e['publicIds'][0]['identifier']
                          ?? ($e['handle'] ?? null);
            }
            if ($registrar) break;
        }
    }
    // Some registries put registrar at top level
    if (!$registrar && !empty($json['registrar'])) {
        $registrar = is_string($json['registrar'])
            ? $json['registrar']
            : ($json['registrar']['name'] ?? null);
    }

    if (!$expiryRaw) return null;
    return buildResult($domain, $expiryRaw, $registrar, $source);
}

/* ============================================================
   WHOIS SOCKET QUERY  — with thin-registry referral following
   ============================================================ */

function socketWhois(string $server, string $domain, string $source): ?array {
    $server = strtolower(trim($server));
    if (!$server) return null;
    if (strpos($server, 'denic.de') !== false)        $query = "-T dn,ace $domain\r\n";
    elseif (strpos($server, 'jprs.jp') !== false)     $query = "$domain/e\r\n";
    elseif (strpos($server, 'dk-hostmaster') !== false) $query = "--show-handles $domain\r\n";
    else                                               $query = "$domain\r\n";

    $fp = @fsockopen($server, 43, $errno, $errstr, 4);
    if (!$fp) return null;
    stream_set_timeout($fp, 6);
    fputs($fp, $query);
    $raw = '';
    while (!feof($fp)) {
        $chunk = fgets($fp, 4096);
        if ($chunk === false) break;
        $raw .= $chunk;
        if (strlen($raw) > 131072) break;
    }
    fclose($fp);
    if (!trim($raw)) return null;
    // Follow thin-registry referral
    if (preg_match('/Registrar WHOIS Server:\s*([\w.\-]+)/i', $raw, $m)) {
        $ref = trim($m[1]);
        if ($ref && $ref !== $server) {
            $r = socketWhois($ref, $domain, $source . '+ref');
            if ($r) return $r;
        }
    }
    return parseWhoisText($domain, $raw, $source);
}

function parseWhoisText(string $domain, string $raw, string $source): ?array {
    $expiryPatterns = [
        '/Registry Expiry Date:\s*(.+)/i',
        '/Registrar Registration Expiration Date:\s*(.+)/i',
        '/Registration Expiry Date:\s*(.+)/i',
        '/Expir[ay] Date:\s*(.+)/i',
        '/Expiration Date:\s*(.+)/i',
        '/Expiry:\s*(.+)/i',
        '/Expire Date:\s*(.+)/i',
        '/expiration-date:\s*(.+)/i',
        '/expire:\s*(.+)/i',
        '/expires:\s*(.+)/i',
        '/Exp-Date:\s*(.+)/i',           // DENIC .de
        '/\[Expires on\]\s*(.+)/i',      // JPRS .jp
        '/paid-till:\s*(.+)/i',          // TCINET .ru
        '/domain_datebilleduntil:\s*(.+)/i', // AUDA .au
        '/Expiry Date \([^)]+\):\s*(.+)/i',  // HKIRC .hk
        '/expires:\s*(\d{8})/i',         // registro.br
        '/Renewal Date:\s*(.+)/i',
        '/Valid Until:\s*(.+)/i',
        '/Record Expires:\s*(.+)/i',
        '/expires on:\s*(.+)/i',
    ];
    $registrarPatterns = [
        '/Registrar:\s*(.+)/i',
        '/Registered By:\s*(.+)/i',
        '/Registrar Name:\s*(.+)/i',
    ];
    $expiryRaw = null; $registrar = null;
    foreach ($expiryPatterns as $p) {
        if (preg_match($p, $raw, $m)) {
            $v = trim(preg_replace('/\s*\(.*?\)/', '', $m[1]));
            if ($v && strlen($v) > 3) { $expiryRaw = $v; break; }
        }
    }
    foreach ($registrarPatterns as $p) {
        if (preg_match($p, $raw, $m)) { $registrar = trim($m[1]); break; }
    }
    if (!$expiryRaw) return null;
    return buildResult($domain, $expiryRaw, $registrar, $source);
}



/* ============================================================
   DATE NORMALISATION
   ============================================================ */


function normaliseDate(string $s): ?string {
    $s = trim($s);
    // Strip trailing timezone abbreviation
    $s = preg_replace('/\s+(?:UTC|GMT|CET|CEST|EEST|EET|BST|EST|PST|MSK|[A-Z]{2,5})\s*$/i', '', $s);
    // Strip parenthetical notes
    $s = preg_replace('/\s*\(.*?\)\s*/', '', $s);
    // Strip "before " / "not after " prefixes
    $s = preg_replace('/^(?:before|not after)\s+/i', '', $s);
    $s = trim($s);
    if (!$s) return null;

    // YYYY/MM/DD  →  YYYY-MM-DD  (JPRS .jp)
    if (preg_match('/^(\d{4})\/(\d{2})\/(\d{2})/', $s, $m))
        return "$m[1]-$m[2]-$m[3]";

    // YYYY.MM.DD  →  YYYY-MM-DD  (TCINET .ru)
    if (preg_match('/^(\d{4})\.(\d{2})\.(\d{2})/', $s, $m))
        return "$m[1]-$m[2]-$m[3]";

    // YYYYMMDD  →  YYYY-MM-DD  (registro.br)
    if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $s, $m))
        return "$m[1]-$m[2]-$m[3]";

    // DD-MMM-YYYY  →  YYYY-MMM-DD  (.hk HKIRC)
    if (preg_match('/^(\d{2})-([A-Za-z]{3})-(\d{4})/', $s, $m))
        return "$m[3]-$m[2]-$m[1]";

    // DD.MM.YYYY  →  YYYY-MM-DD
    if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})/', $s, $m))
        return "$m[3]-$m[2]-$m[1]";

    // DD/MM/YYYY  →  YYYY-MM-DD
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $s, $m))
        return "$m[3]-$m[2]-$m[1]";

    // ISO date with space-separated time: YYYY-MM-DD HH:MM:SS  → strip time
    $s = preg_replace('/^(\d{4}-\d{2}-\d{2})\s+\d{2}:\d{2}.*$/', '$1', $s);
    // ISO 8601 with T: YYYY-MM-DDTHH:MM:SS...  → strip time
    $s = preg_replace('/^(\d{4}-\d{2}-\d{2})T\d{2}:\d{2}.*$/', '$1', $s);

    return $s ?: null;
}

/* ============================================================
   BUILD RESULT
   ============================================================ */

function buildResult(string $domain, string $raw, ?string $registrar, string $source): ?array {
    $s = normaliseDate(trim($raw));
    if (!$s) return null;
    try {
        $expiry   = new DateTime($s);
        $now      = new DateTime();
        $daysLeft = (int)(($expiry->getTimestamp() - $now->getTimestamp()) / 86400);
        return [
            'domain'       => $domain,
            'expiry_date'  => $expiry->format('Y-m-d'),
            'registrar'    => $registrar ? trim($registrar) : null,
            'days_left'    => $daysLeft,
            'status'       => domainStatus($daysLeft),
            'last_checked' => date('Y-m-d H:i:s'),
            'whois_source' => $source,
        ];
    } catch (Exception $e) { return null; }
}

/* ============================================================
   SSL CHECK
   ============================================================ */

function sslCheck(string $domain): array {
    $domain  = strtolower(trim($domain));
    $context = stream_context_create(['ssl' => [
        'capture_peer_cert' => true,
        'verify_peer'       => false,
        'verify_peer_name'  => false,
        'SNI_enabled'       => true,
        'peer_name'         => $domain,
    ]]);
    $client = @stream_socket_client(
        "ssl://{$domain}:443", $errno, $errstr, 8, STREAM_CLIENT_CONNECT, $context
    );
    if (!$client) {
        return ['ssl_expiry'=>null,'ssl_days_left'=>null,'ssl_status'=>'error',
                'ssl_issuer'=>null,'ssl_subject'=>null,
                'ssl_last_checked'=>date('Y-m-d H:i:s'),
                'ssl_error'=>"Connection failed: $errstr ($errno)"];
    }
    $params = stream_context_get_params($client);
    fclose($client);
    $cert = $params['options']['ssl']['peer_certificate'] ?? null;
    if (!$cert) {
        return ['ssl_expiry'=>null,'ssl_days_left'=>null,'ssl_status'=>'error',
                'ssl_issuer'=>null,'ssl_subject'=>null,
                'ssl_last_checked'=>date('Y-m-d H:i:s'),'ssl_error'=>'No certificate returned'];
    }
    $info     = openssl_x509_parse($cert);
    $validTo  = $info['validTo_time_t'] ?? 0;
    $expiry   = new DateTime('@' . $validTo);
    $daysLeft = (int)(($validTo - time()) / 86400);
    return [
        'ssl_expiry'       => $expiry->format('Y-m-d'),
        'ssl_days_left'    => $daysLeft,
        'ssl_status'       => sslStatus($daysLeft),
        'ssl_issuer'       => $info['issuer']['O'] ?? ($info['issuer']['CN'] ?? 'Unknown'),
        'ssl_subject'      => $info['subject']['CN'] ?? $domain,
        'ssl_last_checked' => date('Y-m-d H:i:s'),
    ];
}

/* ============================================================
   TLD / SLD EXTRACTORS
   ============================================================ */

function domTLD(string $domain): string {
    $parts = explode('.', strtolower($domain));
    return end($parts);
}

function domSLD(string $domain): ?string {
    static $known = [
        // UK
        'co.uk','org.uk','net.uk','me.uk','ltd.uk','plc.uk','sch.uk','gov.uk','mod.uk','nhs.uk','ac.uk',
        // Australia
        'com.au','net.au','org.au','edu.au','gov.au','asn.au','id.au',
        // New Zealand
        'co.nz','org.nz','net.nz','govt.nz','ac.nz','school.nz',
        // Brazil
        'com.br','net.br','org.br','edu.br','gov.br','mil.br','adv.br','eng.br',
        // Mexico
        'com.mx','net.mx','org.mx','edu.mx','gob.mx',
        // Japan
        'co.jp','ne.jp','or.jp','ac.jp','go.jp','ad.jp','gr.jp','ed.jp','lg.jp',
        // Hong Kong
        'com.hk','net.hk','org.hk','edu.hk','gov.hk','idv.hk',
        // China
        'com.cn','net.cn','org.cn','edu.cn','gov.cn','mil.cn',
        // Singapore
        'com.sg','net.sg','org.sg','edu.sg','gov.sg',
        // South Africa
        'co.za','org.za','net.za','edu.za','gov.za',
        // Argentina
        'com.ar','net.ar','org.ar','edu.ar','gov.ar',
        // India
        'co.in','net.in','org.in','edu.in','gov.in','firm.in','gen.in',
        // South Korea
        'co.kr','ne.kr','or.kr','re.kr','pe.kr','go.kr','mil.kr','ac.kr','hs.kr',
        // Taiwan
        'com.tw','net.tw','org.tw','edu.tw','gov.tw','idv.tw',
        // Turkey
        'com.tr','net.tr','org.tr','edu.tr','gov.tr','mil.tr',
        // Israel
        'co.il','org.il','net.il','ac.il','gov.il',
        // Kenya
        'co.ke','or.ke','ne.ke','ac.ke','go.ke',
        // Nigeria
        'com.ng','net.ng','org.ng','edu.ng','gov.ng',
        // Other
        'com.ph','net.ph','org.ph',
        'com.ua','net.ua','org.ua',
        'com.pk','net.pk','org.pk',
        'com.my','net.my','org.my',
        'com.id','net.id','or.id','ac.id',
        'com.vn','net.vn','org.vn',
        'com.ec','net.ec','org.ec',
        'com.pe','net.pe','org.pe',
        'com.ve','net.ve','org.ve',
        'com.co','net.co','org.co',
        'com.pt','net.pt','org.pt',
        'com.gr','net.gr','org.gr',
        'com.uy','net.uy','org.uy',
        'com.bo','net.bo','org.bo',
        'com.py','net.py','org.py',
        'com.gt','net.gt','org.gt',
        'com.cr','net.cr','org.cr',
        'com.ni','net.ni','org.ni',
        'com.hn','net.hn','org.hn',
        'com.sv','net.sv','org.sv',
        'com.do','net.do','org.do',
        'com.pa','net.pa','org.pa',
        'com.cu','net.cu','org.cu',
        // CentralNic
        'br.com','cn.com','de.com','eu.com','gb.com','gb.net','hu.com',
        'jpn.com','kr.com','no.com','qc.com','ru.com','sa.com',
        'se.com','se.net','uk.com','uk.net','us.com','za.com',
        'fm.com','fo.com',
    ];
    $parts = explode('.', strtolower($domain));
    $n = count($parts);
    if ($n >= 3) {
        $cand = $parts[$n-2] . '.' . $parts[$n-1];
        if (in_array($cand, $known)) return $cand;
    }
    return null;
}

/* ============================================================
   HELPERS
   ============================================================ */

function domainStatus(int $d): string {
    if ($d < 0)   return 'expired';
    if ($d <= 14) return 'critical';
    if ($d <= 30) return 'warning';
    if ($d <= 60) return 'soon';
    return 'ok';
}

function sslStatus(int $d): string {
    if ($d < 0)   return 'expired';
    if ($d <= 7)  return 'critical';
    if ($d <= 14) return 'warning';
    if ($d <= 30) return 'soon';
    return 'ok';
}

function sortNulls($a, $b): int {
    if ($a === null && $b === null) return 0;
    if ($a === null) return 1;
    if ($b === null) return -1;
    return $a - $b;
}

/**
 * HTTP GET — tries cURL first (better redirect + TLS handling),
 * falls back to file_get_contents.
 *
 * Key fixes vs v3.0:
 *   • follow_location ALWAYS on  (RDAP servers redirect extensively)
 *   • cURL primary path          (bypasses allow_url_fopen restrictions,
 *                                 handles User-Agent blocks better)
 *   • proper 4xx/5xx rejection   (cascade continues on error)
 *   • 429 detection              (rate-limited → cascade continues)
 */
function httpGet(string $url, int $timeout = 8): ?string {
    // ── cURL path (preferred) ──────────────────────────────────────────
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 8,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT      => 'DomainWatch/3.1 (ITOps; +https://github.com)',
            CURLOPT_HTTPHEADER     => [
                'Accept: application/rdap+json, application/json, */*',
            ],
            CURLOPT_ENCODING       => '',          // accept gzip/deflate
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        ]);
        $data = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($data !== false && $data !== '' && $http > 0 && $http < 400) {
            return $data;
        }
        // 4xx/5xx/curl-error → fall through to stream fallback
    }

    // ── stream / file_get_contents fallback ───────────────────────────
    $ctx = stream_context_create([
        'http' => [
            'timeout'         => $timeout,
            'header'          => "User-Agent: DomainWatch/3.1 (ITOps)\r\n"
                               . "Accept: application/rdap+json, application/json, */*\r\n",
            'follow_location' => 1,    // ← was 0 in v3.0 — the main bug!
            'max_redirects'   => 8,
            'ignore_errors'   => true, // get body even on 4xx so we can inspect
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);

    $data = @file_get_contents($url, false, $ctx);
    if ($data === false || $data === '') return null;

    // Check the final HTTP status line (after redirects)
    // $http_response_header is set in THIS function scope by file_get_contents
    $statusCode = 200;
    foreach ($http_response_header ?? [] as $h) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) {
            $statusCode = (int)$m[1]; // last HTTP line wins (after redirects)
        }
    }
    if ($statusCode >= 400) return null;

    return $data;
}

function testSocket(string $host, int $port): string {
    $fp = @fsockopen($host, $port, $errno, $errstr, 4);
    if ($fp) { fclose($fp); return 'ok'; }
    return "fail: $errstr ($errno)";
}
function testHttp(string $url): string {
    $data = httpGet($url, 5);
    return $data ? 'ok (' . strlen($data) . ' bytes)' : 'fail';
}

function loadDomains(): array {
    if (!file_exists(DOMAINS_FILE)) return [];
    return json_decode(file_get_contents(DOMAINS_FILE), true) ?? [];
}
/**
 * Security: ensure the requested domain is in domains.json.
 * Prevents SSRF — attackers cannot use this API to probe
 * internal IPs, localhost, or arbitrary third-party hosts.
 */
function validateDomain(string $domain): bool {
    if (!$domain) return false;
    $allowed = array_column(loadDomains(), 'domain');
    return in_array(strtolower($domain), $allowed, true);
}

/**
 * Security: block private/loopback IP ranges after DNS resolution.
 * Prevents SSRF even if someone adds a crafted domain to domains.json
 * that resolves to an internal address.
 */
function isPrivateIP(string $ip): bool {
    if (!filter_var($ip, FILTER_VALIDATE_IP)) return true; // invalid = block
    $privateRanges = [
        '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16',
        '127.0.0.0/8', '169.254.0.0/16', // loopback + link-local (AWS metadata)
        '::1/128', 'fc00::/7', 'fe80::/10', // IPv6 loopback + ULA + link-local
        '0.0.0.0/8', '100.64.0.0/10',       // shared address space
    ];
    foreach ($privateRanges as $range) {
        [$net, $bits] = explode('/', $range);
        if (strpos($net, ':') !== false) {
            // IPv6 — skip for simplicity, gethostbyname returns IPv4 anyway
            continue;
        }
        $netLong  = ip2long($net);
        $ipLong   = ip2long($ip);
        $mask     = ~((1 << (32 - (int)$bits)) - 1);
        if (($ipLong & $mask) === ($netLong & $mask)) return true;
    }
    return false;
}


function loadCache(): array {
    if (!file_exists(CACHE_FILE)) return [];
    return json_decode(file_get_contents(CACHE_FILE), true) ?? [];
}
function mergeCache(string $domain, array $data): void {
    $cache          = loadCache();
    $cache[$domain] = array_merge($cache[$domain] ?? [], $data);
    file_put_contents(CACHE_FILE, json_encode($cache, JSON_PRETTY_PRINT));
}
function jsonOut(array $data): void {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
}