<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DomainWatch — Domain & SSL Monitor</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root {
  --hdr:          #1e3a7a;
  --hdr2:         #162f63;
  --hdr-border:   rgba(255,255,255,0.10);
  --bg:           #eef1f6;
  --bg2:          #e4e8f0;
  --surface:      #ffffff;
  --surface2:     #f5f7fb;
  --border:       #d8dfe9;
  --border2:      #bcc6d7;
  --text:         #1a2336;
  --text-dim:     #4a5568;
  --text-muted:   #8a97ae;
  --accent:       #1a6fc4;
  --accent-h:     #1558a8;
  --accent-light: #e8f0fb;
  --ok:           #16a34a;
  --ok-bg:        #dcfce7;
  --soon:         #b45309;
  --soon-bg:      #fef3c7;
  --warning:      #c2410c;
  --warning-bg:   #ffedd5;
  --critical:     #dc2626;
  --critical-bg:  #fee2e2;
  --no-expiry:    #1d4ed8;
  --no-expiry-bg: #dbeafe;
  --ssl-ok:       #0891b2;
  --ssl-bg:       #e0f2fe;
  --unknown:      #6b7280;
  --unknown-bg:   #f3f4f6;
  --font:         'DM Sans', system-ui, sans-serif;
  --mono:         'JetBrains Mono', monospace;
  --r:            8px;
  --hdr-h:        58px;
  --shadow:       0 1px 3px rgba(0,0,0,0.07), 0 2px 8px rgba(0,0,0,0.04);
  --shadow-card:  0 1px 4px rgba(0,0,0,0.08), 0 0 0 1px var(--border);
}

*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{background:var(--bg);color:var(--text);font-family:var(--font);font-size:14px;line-height:1.5;min-height:100vh}

/* ════════════════════════════════════════
   HEADER — deep blue, Proxmox-style
════════════════════════════════════════ */
header{
  background: linear-gradient(180deg, var(--hdr) 0%, var(--hdr2) 100%);
  height: var(--hdr-h);
  position: sticky; top: 0; z-index: 200;
  box-shadow: 0 2px 10px rgba(0,0,0,0.28);
  border-bottom: 1px solid var(--hdr-border);
}
.header-inner{
  max-width:1640px; margin:0 auto; height:100%;
  display:flex; align-items:center; gap:14px; padding:0 22px;
}
.logo{display:flex;align-items:center;gap:9px;flex-shrink:0;text-decoration:none}
.logo-icon{
  width:34px;height:34px;border-radius:8px;
  background:rgba(255,255,255,0.13);border:1px solid rgba(255,255,255,0.22);
  display:flex;align-items:center;justify-content:center;font-size:17px;
}
.logo-text{font-size:18px;font-weight:700;color:#fff;letter-spacing:-0.3px;line-height:1}
.logo-text em{font-style:normal;color:rgba(255,255,255,0.5);font-weight:400}
.hdr-sep{width:1px;height:26px;background:var(--hdr-border);flex-shrink:0}
.hdr-ts{
  font-family:var(--mono);font-size:11.5px;
  color:rgba(255,255,255,0.5);white-space:nowrap;
}
.hdr-ts b{color:rgba(255,255,255,0.82);font-weight:500}
.hdr-space{flex:1}
.hdr-right{display:flex;align-items:center;gap:7px}

.hbtn{
  height:30px;padding:0 13px;border-radius:6px;
  border:1px solid rgba(255,255,255,0.18);
  background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.88);
  font-size:12.5px;font-weight:500;font-family:var(--font);cursor:pointer;
  display:flex;align-items:center;gap:5px;white-space:nowrap;
  transition:background .12s,border-color .12s;
}
.hbtn:hover{background:rgba(255,255,255,0.16);border-color:rgba(255,255,255,0.32)}
.hbtn.prim{background:#2563eb;border-color:#3b7df8;color:#fff}
.hbtn.prim:hover{background:#1d4ed8}
.hbtn:disabled{opacity:.45;cursor:not-allowed}
.hbtn svg,.hbtn span{pointer-events:none}

.lang-sw{display:flex;border:1px solid rgba(255,255,255,0.18);border-radius:6px;overflow:hidden}
.lang-btn{
  height:30px;padding:0 10px;background:transparent;
  color:rgba(255,255,255,0.5);border:none;font-size:11.5px;font-weight:700;
  font-family:var(--font);cursor:pointer;transition:.12s;letter-spacing:.3px;
}
.lang-btn.active{background:rgba(255,255,255,0.18);color:#fff}
.lang-btn:hover:not(.active){color:rgba(255,255,255,0.8)}

/* ════════════════════════════════════════
   PAGE BODY
════════════════════════════════════════ */
main{max-width:1640px;margin:0 auto;padding:22px 22px 52px}

/* ── Search + controls bar ── */
.toolbar{
  display:flex;align-items:center;gap:10px;margin-bottom:18px;flex-wrap:wrap;
}
.search-wrap{position:relative;flex:1;min-width:220px;max-width:560px}
.search-ico{
  position:absolute;left:11px;top:50%;transform:translateY(-50%);
  font-size:14px;color:var(--text-muted);pointer-events:none;
}
.search-input{
  width:100%;height:38px;border:1px solid var(--border);border-radius:8px;
  background:var(--surface);color:var(--text);font-size:13.5px;
  font-family:var(--font);padding:0 12px 0 34px;outline:none;
  box-shadow:var(--shadow);transition:border-color .15s,box-shadow .15s;
}
.search-input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(26,111,196,0.13)}
.search-input::placeholder{color:var(--text-muted)}

.sel{
  height:38px;border:1px solid var(--border);border-radius:8px;
  background:var(--surface);color:var(--text);font-size:13px;
  font-family:var(--font);padding:0 30px 0 11px;outline:none;cursor:pointer;
  box-shadow:var(--shadow);appearance:none;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='7' viewBox='0 0 11 7'%3E%3Cpath fill='%238a97ae' d='M5.5 7L0 0h11z'/%3E%3C/svg%3E");
  background-repeat:no-repeat;background-position:right 10px center;
}
.sel:focus{border-color:var(--accent)}

/* filter tab strip — Proxmox style */
.filter-strip{
  display:flex;align-items:center;gap:4px;
  background:var(--surface);border:1px solid var(--border);
  border-radius:8px;padding:3px;box-shadow:var(--shadow);
}
.ftab{
  height:30px;padding:0 13px;border-radius:6px;border:none;
  background:transparent;color:var(--text-dim);font-size:13px;
  font-weight:500;font-family:var(--font);cursor:pointer;
  display:flex;align-items:center;gap:5px;transition:.12s;white-space:nowrap;
}
.ftab:hover:not(.active){background:var(--bg)}
.ftab.active{background:var(--accent);color:#fff;font-weight:600}
.ftab.active.f-critical{background:var(--critical)}
.ftab.active.f-warning{background:var(--warning)}
.ftab.active.f-soon{background:var(--soon)}
.ftab.active.f-ok{background:var(--ok)}
.ftab-n{
  font-size:10.5px;font-weight:700;padding:1px 6px;border-radius:10px;
  background:rgba(255,255,255,0.22);min-width:18px;text-align:center;
}
.ftab:not(.active) .ftab-n{background:var(--bg2);color:var(--text-muted)}

/* sort controls */
.sort-wrap{display:flex;align-items:center;gap:6px;margin-left:auto}
.sort-lbl{font-size:12px;color:var(--text-muted);white-space:nowrap}

/* ── Stats row ── */
.stats-row{
  display:grid;grid-template-columns:repeat(6,1fr);gap:10px;margin-bottom:20px;
}
.stat-card{
  background:var(--surface);border:1px solid var(--border);border-radius:var(--r);
  padding:13px 15px;box-shadow:var(--shadow);display:flex;align-items:center;gap:11px;
  transition:box-shadow .15s,transform .15s;
}
.stat-card:hover{box-shadow:0 4px 14px rgba(0,0,0,0.10);transform:translateY(-1px)}
.stat-dot{
  width:9px;height:9px;border-radius:50%;background:var(--c,#8a97ae);flex-shrink:0;
  box-shadow:0 0 0 3px color-mix(in srgb, var(--c,#8a97ae) 18%, transparent);
}
.stat-info{flex:1;min-width:0}
.stat-lbl{font-size:10.5px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.stat-num{font-size:23px;font-weight:700;color:var(--c,var(--text));font-family:var(--mono);line-height:1.1}

/* ── Section header (like "CME  4/4 online") ── */
.section-hd{
  display:flex;align-items:center;gap:10px;
  margin-bottom:16px;
  border-bottom:2px solid var(--accent);
  padding-bottom:10px;
}
.section-globe{font-size:16px}
.section-title{font-size:15px;font-weight:700;color:var(--text)}
.section-pill{
  font-size:12px;font-family:var(--mono);font-weight:600;color:var(--text-dim);
  background:var(--bg2);border:1px solid var(--border);
  border-radius:10px;padding:2px 9px;
}
.section-space{flex:1}
.section-action{font-size:12px;color:var(--accent);cursor:pointer;font-weight:500}
.section-action:hover{text-decoration:underline}

/* ════════════════════════════════════════
   DOMAIN CARDS — Proxmox node card style
════════════════════════════════════════ */
.cards-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(290px,1fr));
  gap:12px;
}

.dcard{
  background:var(--surface);border:1px solid var(--border);
  border-radius:var(--r);box-shadow:var(--shadow-card);
  transition:box-shadow .15s,transform .15s;
  animation:fadeUp .22s ease both;
  border-left:3px solid var(--strip, var(--border2));
  position:relative;
}
.dcard:hover{box-shadow:0 6px 22px rgba(0,0,0,0.12);transform:translateY(-2px)}
.dcard.checking{opacity:.65;animation:pulse 1.1s ease infinite}
@keyframes pulse{0%,100%{opacity:.65}50%{opacity:.9}}
@keyframes fadeUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}

/* Card header */
.dcard-head{
  display:flex;align-items:center;gap:10px;
  padding:12px 14px 10px;
  border-bottom:1px solid var(--border);
  background:var(--surface2);
}
.dcard-avatar{
  width:32px;height:32px;border-radius:7px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
  font-weight:700;font-size:13px;color:#fff;font-family:var(--mono);
}
.dcard-name-wrap{flex:1;min-width:0}
.dcard-name{
  font-size:13.5px;font-weight:600;font-family:var(--mono);
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
  color:var(--text);
}
.dcard-team{font-size:11px;color:var(--text-muted);margin-top:1px}
.dcard-online{
  font-size:10.5px;font-weight:700;letter-spacing:.3px;
  padding:2px 8px;border-radius:10px;white-space:nowrap;flex-shrink:0;
}
.st-ok{color:var(--ok);background:var(--ok-bg)}
.st-soon{color:var(--soon);background:var(--soon-bg)}
.st-warning{color:var(--warning);background:var(--warning-bg)}
.st-critical,.st-expired{color:var(--critical);background:var(--critical-bg)}
.st-no-expiry{color:var(--no-expiry);background:var(--no-expiry-bg)}
.st-unknown,.st-error{color:var(--unknown);background:var(--unknown-bg)}

/* Card body */
.dcard-body{padding:11px 14px}

/* Resource bars */
.res-bars{display:flex;flex-direction:column;gap:8px;margin-bottom:11px}
.res-row{display:flex;align-items:center;gap:7px}
.res-lbl{font-size:10.5px;color:var(--text-muted);font-weight:600;width:42px;flex-shrink:0;text-transform:uppercase;letter-spacing:.3px}
.res-track{
  flex:1;height:5px;background:var(--bg2);border-radius:3px;
  overflow:hidden;border:1px solid var(--border);
}
.res-fill{height:100%;border-radius:3px;transition:width .4s ease}
.res-val{
  font-size:11.5px;font-weight:600;font-family:var(--mono);
  width:52px;text-align:right;flex-shrink:0;
}

/* Card footer / meta */
.dcard-foot{
  display:flex;align-items:center;justify-content:space-between;gap:6px;
  padding:9px 14px 11px;border-top:1px solid var(--border);
}
.dcard-info{
  display:flex;flex-direction:column;gap:2px;flex:1;min-width:0;
}
.dcard-meta-row{
  display:flex;align-items:center;gap:5px;font-size:11px;color:var(--text-muted);
}
.dcard-meta-row b{color:var(--text-dim);font-weight:600}
.dcard-tags{display:flex;gap:3px;flex-wrap:wrap;margin-top:3px}
.tag{
  font-size:9.5px;font-weight:700;padding:2px 6px;border-radius:3px;
  background:var(--accent-light);color:var(--accent);text-transform:uppercase;letter-spacing:.3px;
}
.dcard-actions{display:flex;gap:3px;flex-shrink:0}
.ico-btn{
  width:26px;height:26px;border-radius:5px;border:1px solid var(--border);
  background:var(--bg);color:var(--text-dim);font-size:12px;
  cursor:pointer;display:flex;align-items:center;justify-content:center;
  transition:.12s;text-decoration:none;
}
.ico-btn:hover{background:var(--accent-light);border-color:var(--accent);color:var(--accent)}
.ico-btn:disabled{opacity:.35;cursor:not-allowed}

/* SSL mini-badge in card */
.ssl-row{display:flex;align-items:center;gap:5px}
.ssl-tog{
  width:26px;height:14px;border-radius:7px;background:var(--border2);
  position:relative;cursor:pointer;border:none;padding:0;flex-shrink:0;
  transition:background .12s;
}
.ssl-tog::after{
  content:'';position:absolute;left:2px;top:2px;width:10px;height:10px;
  border-radius:50%;background:#fff;transition:transform .12s;
  box-shadow:0 1px 2px rgba(0,0,0,.2);
}
.ssl-tog.on{background:var(--ssl-ok)}
.ssl-tog.on::after{transform:translateX(12px)}
.ssl-lbl-txt{font-size:11px;color:var(--text-muted)}
.ssl-lbl-txt.on{color:var(--ssl-ok);font-weight:600}
.ssl-badge{font-size:10px;font-weight:600;padding:2px 6px;border-radius:10px}

/* Empty / loading */
.cards-empty{
  grid-column:1/-1;text-align:center;padding:72px 20px;color:var(--text-muted);
}
.empty-ico{font-size:42px;margin-bottom:14px}
.empty-title{font-size:16px;font-weight:700;color:var(--text-dim)}
.empty-sub{font-size:13px;margin-top:5px}
.spinner{
  width:28px;height:28px;border:3px solid var(--border);border-top-color:var(--accent);
  border-radius:50%;animation:spin .7s linear infinite;margin:0 auto 14px;
}
@keyframes spin{to{transform:rotate(360deg)}}

/* ════════════════════════════════════════
   MODALS
════════════════════════════════════════ */
.overlay{
  position:fixed;inset:0;z-index:1000;
  background:rgba(10,18,40,0.55);backdrop-filter:blur(4px);
  display:none;align-items:center;justify-content:center;padding:20px;
}
.overlay.open{display:flex}
.modal{
  background:var(--surface);border-radius:12px;border:1px solid var(--border);
  box-shadow:0 24px 64px rgba(0,0,0,0.22);width:100%;max-width:480px;
  max-height:90vh;overflow-y:auto;padding:24px;
}
.modal-lg{max-width:600px}
.modal-head{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;gap:12px}
.modal-title{font-size:17px;font-weight:700}
.modal-sub{font-size:13px;color:var(--text-muted);margin-top:2px}
.modal-close{
  width:28px;height:28px;border-radius:6px;border:1px solid var(--border);
  background:var(--bg);color:var(--text-dim);font-size:14px;
  cursor:pointer;flex-shrink:0;display:flex;align-items:center;justify-content:center;
}
.modal-close:hover{background:var(--critical-bg);color:var(--critical)}

/* Forms */
.fg{margin-bottom:16px}
.fl{display:block;font-size:13px;font-weight:600;color:var(--text-dim);margin-bottom:6px}
.fi{
  width:100%;height:38px;border:1px solid var(--border);border-radius:7px;
  background:var(--surface2);color:var(--text);font-size:13.5px;
  font-family:var(--font);padding:0 12px;outline:none;
  transition:border-color .15s,box-shadow .15s;
}
.fi:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(26,111,196,.10)}
.fh{font-size:11.5px;color:var(--text-muted);margin-top:4px}
.cb-group{display:flex;gap:6px;flex-wrap:wrap}
.cb-item{
  display:flex;align-items:center;gap:5px;padding:5px 10px;
  border:1px solid var(--border);border-radius:6px;cursor:pointer;
  font-size:13px;font-weight:500;color:var(--text-muted);background:var(--bg);transition:.12s;
}
.cb-item input{display:none}
.cb-item.checked{border-color:var(--accent);background:var(--accent-light);color:var(--accent)}
.toggle-row{display:flex;align-items:center;gap:10px}
.toggle{
  width:36px;height:20px;border-radius:10px;background:var(--border2);
  position:relative;cursor:pointer;transition:background .15s;flex-shrink:0;
}
.toggle::after{
  content:'';position:absolute;left:2px;top:2px;width:16px;height:16px;
  border-radius:50%;background:#fff;transition:transform .15s;
  box-shadow:0 1px 3px rgba(0,0,0,.2);
}
.toggle.on{background:var(--ok)}
.toggle.on::after{transform:translateX(16px)}
.toggle-lbl{font-size:13px;color:var(--text-dim)}

/* Buttons */
.btn{
  height:38px;padding:0 18px;border-radius:7px;font-size:13.5px;font-weight:600;
  font-family:var(--font);cursor:pointer;display:inline-flex;align-items:center;
  gap:7px;border:1px solid transparent;transition:.15s;white-space:nowrap;text-decoration:none;
}
.btn-primary{background:var(--accent);color:#fff;border-color:var(--accent)}
.btn-primary:hover{background:var(--accent-h)}
.btn-ghost{background:var(--bg);color:var(--text-dim);border-color:var(--border)}
.btn-ghost:hover{background:var(--accent-light);color:var(--accent);border-color:var(--accent)}

/* Detail grid */
.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px}
.detail-item{background:var(--surface2);border-radius:7px;border:1px solid var(--border);padding:10px 12px}
.di-full{grid-column:1/-1}
.di-lbl{font-size:10.5px;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:700;margin-bottom:3px}
.di-val{font-size:14px;font-weight:600;color:var(--text)}
.ssl-section{background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:16px}
.ssl-section-title{font-size:13px;font-weight:700;margin-bottom:12px}
.ssl-monitor-toggle{display:flex;align-items:center;gap:8px;margin-bottom:12px}

/* Toasts */
.toasts{position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px}
.toast{
  padding:10px 16px;border-radius:8px;font-size:13.5px;font-weight:500;
  color:#fff;box-shadow:0 4px 16px rgba(0,0,0,.18);max-width:320px;
  border-left:4px solid rgba(255,255,255,.3);
  animation:tIn .2s ease, tOut .3s ease 2.9s forwards;
}
.toast.ok{background:#14532d;border-left-color:var(--ok)}
.toast.err{background:#7f1d1d;border-left-color:var(--critical)}
.toast.inf{background:#1e3a5f;border-left-color:#60a5fa}
@keyframes tIn{from{opacity:0;transform:translateX(18px)}to{opacity:1;transform:translateX(0)}}
@keyframes tOut{to{opacity:0;transform:translateX(18px)}}

/* Footer */
footer{background:var(--surface);border-top:1px solid var(--border);padding:11px 22px}
.footer{
  max-width:1640px;margin:0 auto;display:flex;align-items:center;
  justify-content:space-between;gap:12px;flex-wrap:wrap;
}
.footer-text{font-size:12px;color:var(--text-muted)}
.cron-code{font-family:var(--mono);font-size:11px;background:var(--bg);padding:2px 7px;border-radius:4px;border:1px solid var(--border)}

/* Responsive */
@media(max-width:1100px){.stats-row{grid-template-columns:repeat(3,1fr)}}
@media(max-width:700px){
  .stats-row{grid-template-columns:repeat(2,1fr)}
  .cards-grid{grid-template-columns:1fr}
  .header-inner{padding:0 13px}
  main{padding:14px 12px 36px}
  .filter-strip{display:none}
}

.blink{animation:blk 1s step-end infinite}
@keyframes blk{50%{opacity:0}}
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:var(--bg)}
::-webkit-scrollbar-thumb{background:var(--border2);border-radius:3px}

/* ════════════════════════════════════════
   DARK MODE
════════════════════════════════════════ */
body.dark {
  --hdr:          #0f1f3d;
  --hdr2:         #0a1628;
  --hdr-border:   rgba(255,255,255,0.08);
  --bg:           #0d1117;
  --bg2:          #161b24;
  --surface:      #161b24;
  --surface2:     #1c2333;
  --border:       rgba(255,255,255,0.08);
  --border2:      rgba(255,255,255,0.14);
  --text:         #e6edf3;
  --text-dim:     #8b949e;
  --text-muted:   #484f58;
  --accent:       #388bfd;
  --accent-h:     #58a6ff;
  --accent-light: rgba(56,139,253,0.15);
  --ok:           #3fb950;
  --ok-bg:        rgba(63,185,80,0.15);
  --soon:         #d29922;
  --soon-bg:      rgba(210,153,34,0.15);
  --warning:      #f0883e;
  --warning-bg:   rgba(240,136,62,0.15);
  --critical:     #f85149;
  --critical-bg:  rgba(248,81,73,0.15);
  --no-expiry:    #79c0ff;
  --no-expiry-bg: rgba(121,192,255,0.15);
  --ssl-ok:       #39d3f5;
  --ssl-bg:       rgba(57,211,245,0.12);
  --unknown:      #6e7681;
  --unknown-bg:   rgba(110,118,129,0.15);
  --shadow:       0 1px 3px rgba(0,0,0,0.3), 0 2px 8px rgba(0,0,0,0.2);
  --shadow-card:  0 2px 8px rgba(0,0,0,0.4), 0 0 0 1px var(--border);
}
body.dark header { background: linear-gradient(180deg, #0f1f3d 0%, #0a1628 100%); }
body.dark .sel {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='7' viewBox='0 0 11 7'%3E%3Cpath fill='%238b949e' d='M5.5 7L0 0h11z'/%3E%3C/svg%3E");
}

/* Dark mode toggle button */
.dark-toggle {
  width:30px; height:30px; border-radius:6px;
  border:1px solid rgba(255,255,255,0.18);
  background:rgba(255,255,255,0.08); color:rgba(255,255,255,0.88);
  font-size:15px; cursor:pointer;
  display:flex; align-items:center; justify-content:center;
  transition:background .12s,border-color .12s; flex-shrink:0;
}
.dark-toggle:hover { background:rgba(255,255,255,0.18); border-color:rgba(255,255,255,0.32); }

/* View toggle buttons */
.view-toggle {
  display:flex; border:1px solid rgba(255,255,255,0.18);
  border-radius:6px; overflow:hidden; flex-shrink:0;
}
.vtbtn {
  width:30px; height:30px; border:none; background:transparent;
  color:rgba(255,255,255,0.6); font-size:14px; cursor:pointer;
  display:flex; align-items:center; justify-content:center; transition:.12s;
}
.vtbtn:hover { background:rgba(255,255,255,0.12); color:rgba(255,255,255,0.9); }
.vtbtn.active { background:rgba(255,255,255,0.2); color:#fff; }

/* ════════════════════════════════════════
   LIST VIEW
════════════════════════════════════════ */
.cards-grid.list-view { display:flex; flex-direction:column; gap:4px; }
.cards-grid.list-view .dcard {
  display:flex; flex-direction:column;
  border-left-width:0; border-left:3px solid var(--strip, var(--border2));
  border-radius:var(--r); overflow:visible;
  animation:fadeUp .18s ease both;
}
/* Hide the ::before strip (using border-left instead) */
.cards-grid.list-view .dcard::before { display:none; }
/* Row 1: head + body + foot all in one horizontal flex row */
.cards-grid.list-view .dcard-info-row {
  display:flex; flex-direction:row; align-items:stretch;
  border-bottom:1px solid var(--border); min-height:0;
}
.cards-grid.list-view .dcard-head {
  flex:0 0 220px; border-bottom:none; border-right:1px solid var(--border);
  background:transparent; padding:8px 14px; align-items:center;
}
.cards-grid.list-view .dcard-body {
  flex:1; padding:8px 16px; display:flex; align-items:center; gap:12px;
  border-right:1px solid var(--border); border-bottom:none;
}
.cards-grid.list-view .dcard-body .res-bars { flex:1; margin-bottom:0; gap:4px; }
.cards-grid.list-view .dcard-body .ssl-row  { margin-left:0; flex-shrink:0; }
.cards-grid.list-view .dcard-body .intel-badges { flex-shrink:0; }
.cards-grid.list-view .dcard-foot {
  flex:0 0 200px; padding:8px 14px; border-top:none; border-bottom:none;
  flex-direction:column; align-items:flex-start; justify-content:center; gap:2px;
}
.cards-grid.list-view .dcard-foot .dcard-info .dcard-meta-row { font-size:11px; white-space:nowrap; }
.cards-grid.list-view .dcard-foot .dcard-tags { display:none; }
.cards-grid.list-view .dcard-foot .dcard-actions { display:none; }
/* Row 2: action buttons */
.cards-grid.list-view .dcard-actions-row {
  display:flex; align-items:center; gap:4px;
  padding:6px 14px;
}
/* Hide actions-row in grid/compact — only shown in list */
.dcard-actions-row { display:none; }
.cards-grid.list-view .dcard-actions-row { display:flex; }

/* ════════════════════════════════════════
   COMPACT VIEW
════════════════════════════════════════ */
.cards-grid.compact-view {
  display:flex; flex-direction:column; gap:0;
  background:var(--surface); border:1px solid var(--border);
  border-radius:var(--r); overflow:hidden; box-shadow:var(--shadow-card);
}
.compact-header {
  display:grid; grid-template-columns:4px 1fr 80px 110px 100px 36px;
  align-items:center; background:var(--surface2);
  border-bottom:2px solid var(--border);
  font-size:10.5px; font-weight:700; text-transform:uppercase;
  letter-spacing:.5px; color:var(--text-muted); user-select:none;
}
.compact-header span { padding:8px 12px; }
.compact-header span:first-child { padding:0; }
.cards-grid.compact-view .dcard {
  display:grid; grid-template-columns:4px 1fr 80px 110px 100px 36px;
  align-items:center; border:none; border-bottom:1px solid var(--border);
  border-radius:0; border-left:none; box-shadow:none;
  animation:fadeUp .15s ease both; transition:background .1s;
}
.cards-grid.compact-view .dcard:last-child { border-bottom:none; }
.cards-grid.compact-view .dcard:hover { background:var(--bg); transform:none; box-shadow:none; }
.cards-grid.compact-view .dcard-head,
.cards-grid.compact-view .dcard-body,
.cards-grid.compact-view .dcard-foot,
.compact-strip { align-self:stretch; width:4px; border-radius:0; }
.compact-name  { display:flex; align-items:center; gap:8px; padding:7px 12px; min-width:0; }
.compact-avatar {
  width:22px; height:22px; border-radius:4px; flex-shrink:0;
  display:flex; align-items:center; justify-content:center;
  font-size:10px; font-weight:700; color:#fff; font-family:var(--mono);
}
.compact-domain {
  font-size:13px; font-weight:600; font-family:var(--mono);
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:var(--text);
}
.compact-days  { padding:7px 12px; font-size:13px; font-weight:700; font-family:var(--mono); white-space:nowrap; }
.compact-date  { padding:7px 12px; font-size:11.5px; color:var(--text-muted); font-family:var(--mono); white-space:nowrap; }
.compact-ssl   { padding:7px 12px; display:flex; align-items:center; gap:4px; font-size:11px; }
.compact-act   { padding:4px 6px; display:flex; align-items:center; justify-content:center; }
.cdot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
.cdot-ok       { background:var(--ok); }
.cdot-soon     { background:var(--soon); }
.cdot-warning  { background:var(--warning); }
.cdot-critical,.cdot-expired { background:var(--critical); }
.cdot-unknown  { background:var(--border2); }

/* ── DNS / Port / Intel CSS ─────────────────────────────────── */
.code-2xx { background:var(--ok-bg);       color:var(--ok);       padding:2px 7px; border-radius:10px; font-size:10px; font-weight:700; font-family:var(--mono); }
.code-3xx { background:var(--soon-bg);     color:var(--soon);     padding:2px 7px; border-radius:10px; font-size:10px; font-weight:700; font-family:var(--mono); }
.code-4xx { background:var(--warning-bg);  color:var(--warning);  padding:2px 7px; border-radius:10px; font-size:10px; font-weight:700; font-family:var(--mono); }
.code-5xx { background:var(--critical-bg); color:var(--critical); padding:2px 7px; border-radius:10px; font-size:10px; font-weight:700; font-family:var(--mono); }
body.dark .code-3xx { background:rgba(251,191,36,0.15); color:#fbbf24; }

.port-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); gap:8px; margin-top:8px; }
.port-item { display:flex; flex-direction:column; align-items:flex-start; border:1px solid var(--border); border-radius:8px; padding:9px 11px; background:var(--surface); transition:.15s; }
.port-item.open   { border-color:var(--ok);     background:var(--ok-bg); }
.port-item.closed { border-color:var(--border2); background:var(--bg2); opacity:.65; }
body.dark .port-item.open { background:rgba(34,197,94,0.07); }
.port-num  { font-family:var(--mono); font-size:13px; font-weight:700; color:var(--text); }
.port-name { font-size:10px; color:var(--text-muted); margin-top:1px; }
.port-ms   { font-size:10px; font-family:var(--mono); margin-top:4px; }
.port-open-ms   { color:var(--ok); }
.port-closed-lbl{ font-size:10px; color:var(--text-muted); margin-top:4px; }
.port-dot  { width:7px; height:7px; border-radius:50%; margin-bottom:4px; }
.port-dot.open   { background:var(--ok); }
.port-dot.closed { background:var(--border2); }
.resolved-ip { font-family:var(--mono); font-size:11px; color:var(--text-muted); margin-bottom:8px; }

.intel-tabs { display:flex; gap:2px; background:var(--bg2); border-radius:7px; padding:3px; margin-bottom:14px; }
.itab { flex:1; height:28px; border:none; border-radius:5px; background:transparent; color:var(--text-muted); font-size:12px; font-weight:500; font-family:var(--font); cursor:pointer; transition:.12s; }
.itab:hover  { background:var(--surface); color:var(--text); }
.itab.active { background:var(--surface); color:var(--accent); font-weight:700; box-shadow:var(--shadow); }
.intel-badges { display:flex; gap:4px; flex-wrap:wrap; margin-top:4px; }

/* ════════════════════════════════════════
   INTEL HOVER TOOLTIPS
════════════════════════════════════════ */
.intel-badges { position: relative; }

.ibadge-wrap {
  position: relative;
  display: inline-flex;
}

/* The popup panel */
.ibadge-popup {
  display: none;
  position: fixed;
  z-index: 99999;
  min-width: 240px;
  max-width: 340px;
  background: var(--surface);
  border: 1px solid var(--border2);
  border-radius: 10px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.22), 0 2px 8px rgba(0,0,0,0.15);
  padding: 10px 12px;
  pointer-events: none;
  white-space: normal;
  word-break: break-all;
  /* position set by JS on mouseover */
}

/* Arrow */
.ibadge-popup::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  border: 6px solid transparent;
  border-top-color: var(--border2);
}
.ibadge-popup::before {
  content: '';
  position: absolute;
  top: calc(100% - 1px);
  left: 50%;
  transform: translateX(-50%);
  border: 6px solid transparent;
  border-top-color: var(--surface);
  z-index: 1;
}

/* popup shown/hidden via JS mouseover for correct fixed positioning */
.ibadge-popup.visible {
  display: block;
  animation: popIn .12s ease both;
}

@keyframes popIn {
  from { opacity:0; transform:translateX(-50%) translateY(4px); }
  to   { opacity:1; transform:translateX(-50%) translateY(0); }
}

/* Popup title row */
.ibp-title {
  font-size: 10px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .6px;
  color: var(--text-muted);
  margin-bottom: 8px;
  padding-bottom: 5px;
  border-bottom: 1px solid var(--border);
}

/* DNS rows inside popup */
.ibp-dns-type {
  font-size: 9.5px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .5px;
  color: var(--accent);
  margin: 6px 0 2px;
}
.ibp-dns-row {
  display: flex;
  gap: 6px;
  font-family: var(--mono);
  font-size: 10.5px;
  color: var(--text-dim);
  padding: 2px 0;
  border-bottom: 1px solid var(--border);
  align-items: baseline;
}
.ibp-dns-row:last-child { border-bottom: none; }
.ibp-dns-val { flex: 1; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ibp-dns-ttl { color: var(--text-muted); font-size: 9.5px; flex-shrink: 0; }

/* Port rows inside popup */
.ibp-port-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 4px;
  margin-top: 4px;
}
.ibp-port-item {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 10.5px;
  font-family: var(--mono);
  padding: 3px 5px;
  border-radius: 5px;
}
.ibp-port-item.open   { background: var(--ok-bg);  color: var(--ok); }
.ibp-port-item.closed { background: var(--bg2);    color: var(--text-muted); opacity: .7; }
.ibp-dot { width: 5px; height: 5px; border-radius: 50%; flex-shrink: 0; }
.ibp-dot.open   { background: var(--ok); }
.ibp-dot.closed { background: var(--border2); }

/* NS change alert in popup */
.ibp-ns-alert {
  background: var(--critical-bg);
  color: var(--critical);
  border-radius: 5px;
  padding: 4px 7px;
  font-size: 10.5px;
  margin-bottom: 6px;
}

/* Popup tail — flip if near top of viewport: handled via .flip class added by JS */


/* ── Intel icon badges (grid view) ─────────────────────────── */
.ibi {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  border-radius: 5px;
  font-size: 12px;
  cursor: default;
  transition: transform .1s, box-shadow .1s;
}
.ibadge-popup.visible ~ * .ibi,
.ibadge-wrap:hover .ibi {
  transform: scale(1.15);
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.ibi-ok    { background: var(--ok-bg);       filter: none; }
.ibi-warn  { background: var(--soon-bg);     }
.ibi-error { background: var(--critical-bg); filter: grayscale(.3); }
.ibi-none  { background: var(--bg2);         opacity: .45; }

/* ── HTTP status icon ───────────────────────────────────────── */
.ibi-http-redirect {
  background: var(--soon-bg);
}

/* HTTP chain inside popup */
.ibp-http-summary {
  font-size: 11.5px;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 8px;
  font-family: var(--mono);
}
.ibp-http-chain {
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.ibp-http-row {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 10.5px;
  font-family: var(--mono);
  padding: 3px 0;
  border-bottom: 1px solid var(--border);
}
.ibp-http-row:last-child { border-bottom: none; }
.ibp-http-arrow {
  color: var(--text-muted);
  flex-shrink: 0;
  font-size: 10px;
}
.ibp-http-url {
  color: var(--text-dim);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
  min-width: 0;
}
</style>
</head>
<body>
<div id="intel-popup" style="display:none;position:fixed;z-index:2147483647;min-width:240px;max-width:340px;background:var(--surface);border:1px solid var(--border2);border-radius:10px;box-shadow:0 8px 32px rgba(0,0,0,0.22),0 2px 8px rgba(0,0,0,0.15);padding:10px 12px;pointer-events:none;white-space:normal;word-break:break-all;font-size:13px;color:var(--text);font-family:var(--font)"></div>

<!-- ═══════════════════════════════════════
     HEADER
═══════════════════════════════════════ -->
<header>
  <div class="header-inner">
    <a class="logo" href="#">
      <div class="logo-icon">🛡️</div>
      <div class="logo-text">Domain<em>Watch</em></div>
    </a>
    <div class="hdr-sep"></div>
    <div class="hdr-ts" id="lastChecked"><b data-i="notChecked">Not checked yet</b></div>
    <div class="hdr-ts" id="domainSourceInfo" style="font-size:11px;opacity:.6"></div>
    <div class="hdr-space"></div>
    <div class="hdr-right">
      <div class="lang-sw">
        <button class="lang-btn active" data-lang="en" onclick="setLang('en')">EN</button>
        <button class="lang-btn"        data-lang="pl" onclick="setLang('pl')">PL</button>
      </div>
      <button class="dark-toggle" id="darkToggleBtn" onclick="toggleDark()" title="Toggle dark mode">🌙</button>
      <button class="hbtn" onclick="openTeamsModal()">🔔 <span data-i="teamsBtn">Teams</span></button>
      <button class="hbtn prim" id="checkAllBtn" onclick="checkAllDomains()">⚡ <span data-i="checkAllBtn">Check All</span></button>
    </div>
  </div>
</header>

<!-- ═══════════════════════════════════════
     MAIN
═══════════════════════════════════════ -->
<main>

  <!-- Stats -->
  <div class="stats-row" id="statsRow">
    <div class="stat-card" style="--c:var(--text)">
      <div class="stat-dot"></div>
      <div class="stat-info">
        <div class="stat-lbl" data-i="statTotal">All Domains</div>
        <div class="stat-num" id="s-total">—</div>
      </div>
    </div>
    <div class="stat-card" style="--c:var(--critical)">
      <div class="stat-dot"></div>
      <div class="stat-info">
        <div class="stat-lbl" data-i="statCrit">Critical</div>
        <div class="stat-num" id="s-critical">—</div>
      </div>
    </div>
    <div class="stat-card" style="--c:var(--warning)">
      <div class="stat-dot"></div>
      <div class="stat-info">
        <div class="stat-lbl" data-i="statWarn">Warning</div>
        <div class="stat-num" id="s-warning">—</div>
      </div>
    </div>
    <div class="stat-card" style="--c:var(--soon)">
      <div class="stat-dot"></div>
      <div class="stat-info">
        <div class="stat-lbl" data-i="statSoon">Expiring Soon</div>
        <div class="stat-num" id="s-soon">—</div>
      </div>
    </div>
    <div class="stat-card" style="--c:var(--ok)">
      <div class="stat-dot"></div>
      <div class="stat-info">
        <div class="stat-lbl" data-i="statOk">OK</div>
        <div class="stat-num" id="s-ok">—</div>
      </div>
    </div>
    <div class="stat-card" style="--c:var(--ssl-ok)">
      <div class="stat-dot"></div>
      <div class="stat-info">
        <div class="stat-lbl" data-i="statSsl">SSL Monitored</div>
        <div class="stat-num" id="s-ssl">—</div>
      </div>
    </div>
  </div>

  <!-- Toolbar -->
  <div class="toolbar">
    <div class="search-wrap">
      <span class="search-ico">🔍</span>
      <input type="text" class="search-input" id="searchInput"
             placeholder="Search domain, owner, team…"
             data-placeholder-i="searchPlaceholder"
             oninput="renderCards()">
    </div>
    <select class="sel" id="teamSel" onchange="renderCards()">
      <option value="" data-i="allTeams">All teams</option>
    </select>
    <div class="filter-strip" id="filterPills">
      <button class="ftab active" data-status="all"      onclick="setFilter(this,'all')"      data-i="filterAll">All <span class="ftab-n" id="fn-all">—</span></button>
      <button class="ftab f-critical" data-status="critical" onclick="setFilter(this,'critical')" >🔴 <span data-i="filterCrit">Critical</span> <span class="ftab-n" id="fn-critical">—</span></button>
      <button class="ftab f-warning"  data-status="warning"  onclick="setFilter(this,'warning')"  >🟠 <span data-i="filterWarn">Warning</span>  <span class="ftab-n" id="fn-warning">—</span></button>
      <button class="ftab f-soon"     data-status="soon"     onclick="setFilter(this,'soon')"     >🟡 <span data-i="filterSoon">Soon</span>     <span class="ftab-n" id="fn-soon">—</span></button>
      <button class="ftab f-ok"       data-status="ok"       onclick="setFilter(this,'ok')"       >✅ <span data-i="filterOk">OK</span>         <span class="ftab-n" id="fn-ok">—</span></button>
      <button class="ftab"            data-status="unknown"  onclick="setFilter(this,'unknown')"  >❓ <span data-i="filterUnknown">Unknown</span> <span class="ftab-n" id="fn-unknown">—</span></button>
    </div>
    <div class="sort-wrap">
      <span class="sort-lbl">Sort:</span>
      <select class="sel" id="sortSel" onchange="setSortFromSel()" style="min-width:130px">
        <option value="days_left-asc">Days Left ↑</option>
        <option value="days_left-desc">Days Left ↓</option>
        <option value="domain-asc">Name A→Z</option>
        <option value="domain-desc">Name Z→A</option>
        <option value="expiry_date-asc">Expiry Date ↑</option>
      </select>
    </div>
    <button class="hbtn" onclick="checkAllSSL()" style="margin-left:4px">🔒 <span data-i="checkSslBtn">Check SSL</span></button>
    <div class="view-toggle" style="margin-left:4px">
      <button class="vtbtn active" id="vbGrid"    onclick="setView('grid')"    title="Grid view">⊞</button>
      <button class="vtbtn"        id="vbList"    onclick="setView('list')"    title="List view">☰</button>
      <button class="vtbtn"        id="vbCompact" onclick="setView('compact')" title="Compact view">≡</button>
    </div>
  </div>

  <!-- Section heading -->
  <div class="section-hd">
    <span class="section-globe">🌐</span>
    <span class="section-title">DomainWatch</span>
    <span class="section-pill" id="onlinePill">— domains</span>
    <div class="section-space"></div>
  </div>

  <!-- Cards -->
  <div class="cards-grid" id="cardsGrid">
    <div class="cards-empty">
      <div class="spinner"></div>
      <div data-i="loading">Loading domains…</div>
    </div>
  </div>

</main>

<!-- FOOTER -->
<footer>
  <div class="footer">
    <span class="footer-text">DomainWatch v3.3 — ITOps Team</span>
    <span class="footer-text"><span data-i="cronHint">Cron:</span> <code class="cron-code">0 7 * * * php refresh_domains.php</code></span>
  </div>
</footer>

<!-- ═══════════════════════════════════════
     TEAMS MODAL
═══════════════════════════════════════ -->
<div class="overlay" id="teamsOverlay">
  <div class="modal">
    <div class="modal-head">
      <div>
        <div class="modal-title" data-i="teamsCfgTitle">🔔 Teams Configuration</div>
        <div class="modal-sub"   data-i="teamsCfgSub">Microsoft Teams webhook integration</div>
      </div>
      <button class="modal-close" onclick="closeModal('teamsOverlay')">✕</button>
    </div>
    <div class="fg">
      <label class="fl" data-i="webhookLabel">Webhook URL</label>
      <input class="fi" type="url" id="teamsWebhook" placeholder="https://outlook.office.com/webhook/...">
      <div class="fh" data-i="webhookHint">Incoming Webhook URL from Microsoft Teams</div>
    </div>
    <div class="fg">
      <label class="fl" data-i="dashUrlLabel">Dashboard URL</label>
      <input class="fi" type="url" id="dashUrl" placeholder="https://your-server.com/domainwatch/">
      <div class="fh" data-i="dashUrlHint">Link included in Teams notifications</div>
    </div>
    <div class="fg">
      <label class="fl" data-i="notifyWhenLabel">Notify when fewer than</label>
      <div class="cb-group" id="cbDays">
        <label class="cb-item checked"><input type="checkbox" value="7"  checked><span>7d</span></label>
        <label class="cb-item checked"><input type="checkbox" value="14" checked><span>14d</span></label>
        <label class="cb-item checked"><input type="checkbox" value="30" checked><span>30d</span></label>
        <label class="cb-item">        <input type="checkbox" value="60">        <span>60d</span></label>
        <label class="cb-item">        <input type="checkbox" value="90">        <span>90d</span></label>
      </div>
    </div>
    <div class="fg">
      <label class="fl" data-i="notifLabel">Notifications</label>
      <div class="toggle-row">
        <div class="toggle" id="teamsToggle" onclick="this.classList.toggle('on')"></div>
        <span class="toggle-lbl" data-i="notifToggle">Enable Teams notifications</span>
      </div>
    </div>
    <div style="display:flex;gap:8px;margin-top:6px">
      <button class="btn btn-primary" onclick="saveTeamsCfg()" style="flex:1" data-i="saveCfgBtn">💾 Save</button>
      <button class="btn btn-ghost"   onclick="testTeams()"                    data-i="testCfgBtn">🧪 Test</button>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════
     DETAIL MODAL
═══════════════════════════════════════ -->
<div class="overlay" id="detailOverlay">
  <div class="modal modal-lg">
    <div class="modal-head">
      <div>
        <div class="modal-title" id="detailTitle">Domain Details</div>
        <div class="modal-sub"   id="detailSub"></div>
      </div>
      <button class="modal-close" onclick="closeModal('detailOverlay')">✕</button>
    </div>
    <div id="detailBody"></div>
  </div>
</div>

<div class="toasts" id="toasts"></div>

<script>


let lang = localStorage.getItem('dw_lang') || 'en';
function t(k) { return T[lang][k] || T.en[k] || k; }

function setLang(l) {
  lang = l;
  localStorage.setItem('dw_lang', l);
  document.querySelectorAll('.lang-btn').forEach(b => b.classList.toggle('active', b.dataset.lang === l));
  applyLang();
  renderCards();
}

function applyLang() {
  document.querySelectorAll('[data-i]').forEach(el => {
    const k = el.dataset.i;
    if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
      el.placeholder = t(k);
    } else {
      el.textContent = t(k);
    }
  });
  document.getElementById('searchInput').placeholder = t('searchPlaceholder');
  // Update team filter "All" option
  const firstOpt = document.querySelector('#teamSel option[value=""]');
  if (firstOpt) firstOpt.textContent = t('allTeams');
}

// ── State ─────────────────────────────────────────────────────
const API = 'api.php';
let allDomains  = [];
let sortField   = 'days_left';
let sortDir     = 'asc';
let statusFilter= 'all';
let checking    = new Set();
let isDemoMode  = false;
let viewMode    = localStorage.getItem('dw_view') || 'grid'; // 'grid' | 'list'
let darkMode    = localStorage.getItem('dw_dark') === '1';

// ── Dark mode ─────────────────────────────────────────────────
function applyDark() {
  document.body.classList.toggle('dark', darkMode);
  const btn = document.getElementById('darkToggleBtn');
  if (btn) btn.textContent = darkMode ? '☀️' : '🌙';
}

function toggleDark() {
  darkMode = !darkMode;
  localStorage.setItem('dw_dark', darkMode ? '1' : '0');
  applyDark();
}

// ── View toggle ───────────────────────────────────────────────
function setView(v) {
  viewMode = v;
  localStorage.setItem('dw_view', v);
  document.getElementById('vbGrid')   ?.classList.toggle('active', v === 'grid');
  document.getElementById('vbList')   ?.classList.toggle('active', v === 'list');
  document.getElementById('vbCompact')?.classList.toggle('active', v === 'compact');
  const grid = document.getElementById('cardsGrid');
  grid.classList.toggle('list-view',    v === 'list');
  grid.classList.toggle('compact-view', v === 'compact');
  renderCards();
}

// ── Init ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  applyDark();
  setLang(lang);
  initCbCheckboxes();
  // Apply saved view mode
  setView(viewMode);
  loadDomains();
  loadTeamsCfg();
});

function initCbCheckboxes() {
  document.querySelectorAll('.cb-item input').forEach(cb => {
    cb.addEventListener('change', () => cb.parentElement.classList.toggle('checked', cb.checked));
  });
}

// ── Load ──────────────────────────────────────────────────────
async function loadDomains() {
  try {
    const r = await fetch(`${API}?action=list&_=${Date.now()}`, {
      cache: 'no-store',
      headers: { 'Cache-Control': 'no-cache', 'Pragma': 'no-cache' }
    });
    if (!r.ok) throw new Error(`HTTP ${r.status}`);
    const d = await r.json();
    if (!Array.isArray(d.domains)) throw new Error('Invalid response');
    allDomains = d.domains;
    isDemoMode = false;
    postLoad();
  } catch(e) {
    console.error('loadDomains failed:', e);
    // Show error banner instead of silently loading demo
    const grid = document.getElementById('cardsGrid');
    if (grid) grid.innerHTML = `
      <div style="grid-column:1/-1;padding:32px;text-align:center;color:var(--critical)">
        <div style="font-size:24px;margin-bottom:8px">⚠️ Could not load domains</div>
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:16px">${e.message || 'API unreachable'} — check that api.php is deployed</div>
        <button class="btn btn-primary" onclick="loadDomains()">🔄 Retry</button>
        <button class="btn btn-ghost" onclick="loadDemo()" style="margin-left:8px">👁 Load Demo</button>
      </div>`;
  }
}

function postLoad() {
  updateStats();
  buildTeamFilter();
  renderCards();
  const checked = allDomains.find(d => d.last_checked);
  if (checked) {
    document.getElementById('lastChecked').textContent =
      t('checkedAt') + ' ' + fmtDateTime(checked.last_checked);
  }
  // Show domain count in header so mismatches are immediately visible
  const countEl = document.getElementById('domainSourceInfo');
  if (countEl) countEl.textContent = `${allDomains.length} domains${isDemoMode ? ' (demo)' : ''}`;
}

function loadDemo() {
  isDemoMode = true;
  const now = new Date();
  const ad  = (n) => { const x = new Date(now); x.setDate(x.getDate()+n); return x.toISOString().split('T')[0]; };
  allDomains = [
    { domain:'example.com',          owner:'John Smith',     team:'Marketing', notes:'Main website',    tags:['production','critical'], monitor_ssl:true,
      expiry_date:ad(5),   days_left:5,   status:'critical', registrar:'GoDaddy',      last_checked:now.toISOString(),
      ssl_expiry:ad(12),   ssl_days_left:12, ssl_status:'critical', ssl_issuer:"Let's Encrypt",ssl_last_checked:now.toISOString(),
      http_status:301, final_url:'https://www.example.com/', redirects:[{url:'https://example.com',status:301},{url:'https://www.example.com/',status:200}], response_ms:142, http_check_status:'redirect', last_http_checked:now.toISOString(),
      dns_records:{A:[{value:'93.184.216.34',ttl:3600}],AAAA:[],CNAME:[],MX:[{value:'mail.example.com',ttl:3600,priority:10}],NS:[{value:'ns1.example.com',ttl:86400},{value:'ns2.example.com',ttl:86400}],TXT:[{value:'v=spf1 include:_spf.google.com ~all',ttl:3600}],SOA:[{value:'ns1.example.com',rname:'admin.example.com',serial:2024010101,ttl:3600}],dnssec:true,healthy:true},
      dns_ns:['ns1.example.com','ns2.example.com'], dns_ns_prev:[], ns_changed:false, ns_changed_at:null, last_dns_checked:now.toISOString(), dns_healthy:true,
      port_results:[{port:80,name:'HTTP',open:true,ms:45},{port:443,name:'HTTPS',open:true,ms:52},{port:25,name:'SMTP',open:false,ms:null},{port:587,name:'SMTP/TLS',open:false,ms:null},{port:22,name:'SSH',open:false,ms:null},{port:993,name:'IMAPS',open:false,ms:null}],
      resolved_ip:'93.184.216.34', last_ports_checked:now.toISOString() },
    { domain:'myapp.pl',             owner:'Anna Nowak',     team:'Dev',       notes:'B2B App',         tags:['production'],           monitor_ssl:true,
      expiry_date:ad(18),  days_left:18,  status:'warning',  registrar:'OVH',           last_checked:now.toISOString(),
      ssl_expiry:ad(25),   ssl_days_left:25, ssl_status:'warning',  ssl_issuer:"DigiCert",     ssl_last_checked:now.toISOString(),
      http_status:200, final_url:'https://myapp.pl/', redirects:[{url:'https://myapp.pl/',status:200}], response_ms:88, http_check_status:'ok', last_http_checked:now.toISOString(),
      dns_records:{A:[{value:'51.38.0.1',ttl:300}],AAAA:[],CNAME:[],MX:[{value:'mail.ovh.net',ttl:3600,priority:1}],NS:[{value:'dns1.ovh.net',ttl:86400},{value:'dns2.ovh.net',ttl:86400}],TXT:[{value:'v=spf1 include:mx.ovh.com ~all',ttl:3600}],SOA:[{value:'dns1.ovh.net',rname:'tech.ovh.net',serial:2024020101,ttl:3600}],dnssec:false,healthy:true},
      dns_ns:['dns1.ovh.net','dns2.ovh.net'], dns_ns_prev:['ns1.oldprovider.com','ns2.oldprovider.com'], ns_changed:true, ns_changed_at:now.toISOString(), last_dns_checked:now.toISOString(), dns_healthy:true,
      port_results:[{port:80,name:'HTTP',open:true,ms:32},{port:443,name:'HTTPS',open:true,ms:38},{port:25,name:'SMTP',open:true,ms:120},{port:587,name:'SMTP/TLS',open:true,ms:115},{port:22,name:'SSH',open:false,ms:null},{port:993,name:'IMAPS',open:false,ms:null}],
      resolved_ip:'51.38.0.1', last_ports_checked:now.toISOString() },
    { domain:'dashboard.company.eu', owner:'Maria Green',    team:'Dev',       notes:'Internal panel',  tags:['internal','critical'],  monitor_ssl:true,
      expiry_date:ad(27),  days_left:27,  status:'warning',  registrar:'Name.com',      last_checked:now.toISOString(),
      ssl_expiry:ad(60),   ssl_days_left:60, ssl_status:'soon',    ssl_issuer:"Comodo",       ssl_last_checked:now.toISOString(),
      http_status:200, final_url:'https://dashboard.company.eu/', redirects:[{url:'https://dashboard.company.eu/',status:200}], response_ms:210, http_check_status:'ok', last_http_checked:now.toISOString() },
    { domain:'testportal.io',        owner:'Peter Wilson',   team:'QA',        notes:'Test env',        tags:['staging'],              monitor_ssl:false,
      expiry_date:ad(45),  days_left:45,  status:'soon',     registrar:'Cloudflare',    last_checked:now.toISOString(),
      ssl_expiry:null, ssl_days_left:null, ssl_status:'unknown', ssl_issuer:null,       ssl_last_checked:null,
      http_status:503, final_url:'https://testportal.io/', redirects:[{url:'https://testportal.io/',status:503}], response_ms:320, http_check_status:'server-error', last_http_checked:now.toISOString() },
    { domain:'blog.companysite.com', owner:'Caroline Davis', team:'Marketing', notes:'Company blog',    tags:['production'],           monitor_ssl:false,
      expiry_date:ad(90),  days_left:90,  status:'ok',       registrar:'Namecheap',     last_checked:now.toISOString(),
      ssl_expiry:null, ssl_days_left:null, ssl_status:'unknown', ssl_issuer:null,       ssl_last_checked:null,
      http_status:null, final_url:null, redirects:[], response_ms:null, http_check_status:null, last_http_checked:null },
    { domain:'api.service.net',      owner:'Thomas Brown',   team:'Backend',   notes:'REST API v2',     tags:['production','critical'], monitor_ssl:true,
      expiry_date:ad(180), days_left:180, status:'ok',       registrar:'Google Domains',last_checked:now.toISOString(),
      ssl_expiry:ad(200),  ssl_days_left:200,ssl_status:'ok',     ssl_issuer:"Google Trust", ssl_last_checked:now.toISOString(),
      http_status:200, final_url:'https://api.service.net/', redirects:[{url:'https://api.service.net/',status:200}], response_ms:55, http_check_status:'ok', last_http_checked:now.toISOString() },
    { domain:'cdn.assets.tech',      owner:'Mark Taylor',    team:'Infra',     notes:'Static CDN',      tags:['production','critical'], monitor_ssl:true,
      expiry_date:ad(300), days_left:300, status:'ok',       registrar:'Dynadot',       last_checked:now.toISOString(),
      ssl_expiry:ad(280),  ssl_days_left:280,ssl_status:'ok',     ssl_issuer:"Sectigo",      ssl_last_checked:now.toISOString(),
      http_status:301, final_url:'https://cdn.assets.tech/static/', redirects:[{url:'https://cdn.assets.tech',status:301},{url:'https://cdn.assets.tech/static/',status:200}], response_ms:76, http_check_status:'redirect', last_http_checked:now.toISOString() },
    { domain:'staging.myapp.pl',     owner:'Anna Nowak',     team:'Dev',       notes:'Staging',         tags:['staging'],              monitor_ssl:false,
      expiry_date:null, days_left:null, status:'unknown', registrar:null,           last_checked:null,
      ssl_expiry:null, ssl_days_left:null, ssl_status:'unknown', ssl_issuer:null,   ssl_last_checked:null,
      http_status:null, final_url:null, redirects:[], response_ms:null, http_check_status:null, last_http_checked:null },
  ];
  document.getElementById('lastChecked').textContent = t('demoMode');
  postLoad();
  toast('inf', t('demoMode'));
}

// ── Check all domains — batched parallel ──────────────────────
// Fires BATCH_SIZE requests concurrently, waits for all to finish,
// then fires the next batch. Each request is one domain = one PHP
// execution = no timeout. 65 domains ÷ 5 concurrent = 13 rounds.
async function checkAllDomains() {
  const BATCH_SIZE = 5;   // concurrent requests per round
  const btn = document.getElementById('checkAllBtn');
  btn.disabled = true;

  if (isDemoMode) {
    btn.innerHTML = `<span class="blink">⚡</span> <span>0/${allDomains.length}</span>`;
    toast('inf', '🎭 Demo mode — simulating WHOIS check…');
    for (let i = 0; i < allDomains.length; i++) {
      checking.add(allDomains[i].domain); renderCards();
      await sleep(120);
      checking.delete(allDomains[i].domain);
      allDomains[i] = { ...allDomains[i], last_checked: new Date().toISOString() };
      btn.innerHTML = `<span class="blink">⚡</span> <span>${i+1}/${allDomains.length}</span>`;
      updateStats(); renderCards();
    }
    document.getElementById('lastChecked').textContent = t('demoMode');
    toast('ok', `🎭 Demo: ${allDomains.length} domains "checked"`);
    btn.disabled = false;
    btn.innerHTML = `<span>⚡</span> <span>${t('checkAllBtn')}</span>`;
    return;
  }

  const domains = [...allDomains]; // snapshot
  let done = 0;
  const total = domains.length;
  btn.innerHTML = `<span class="blink">⚡</span> <span>0/${total}</span>`;
  toast('inf', `🔍 Checking ${total} domains in batches of ${BATCH_SIZE}…`);

  // Mark all as checking
  domains.forEach(d => checking.add(d.domain));
  renderCards();

  for (let i = 0; i < total; i += BATCH_SIZE) {
    const batch = domains.slice(i, i + BATCH_SIZE);

    // Fire all in batch concurrently
    await Promise.allSettled(batch.map(async (d) => {
      try {
        const r = await fetch(`${API}?action=check&domain=${enc(d.domain)}`);
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        const result = await r.json();
        // Always merge — even error results carry status/last_checked
        // so the row shows "Error" instead of stale "Unknown"
        const idx = allDomains.findIndex(x => x.domain === d.domain);
        if (idx !== -1) allDomains[idx] = { ...allDomains[idx], ...result };
      } catch(e) {
        // Domain failed — leave existing data, don't crash the whole run
        console.warn(`${d.domain}: ${e.message}`);
      } finally {
        checking.delete(d.domain);
        done++;
        btn.innerHTML = `<span class="blink">⚡</span> <span>${done}/${total}</span>`;
        updateStats(); renderCards();
      }
    }));

    // Small pause between batches to be polite to WHOIS servers
    if (i + BATCH_SIZE < total) await sleep(400);
  }

  const now = new Date().toLocaleString('en-GB', { day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit' });
  document.getElementById('lastChecked').textContent = t('checkedAt') + ' ' + now;
  toast('ok', `✅ ${total} domains checked`);
  checking.clear();
  btn.disabled = false;
  btn.innerHTML = `<span>⚡</span> <span>${t('checkAllBtn')}</span>`;
  renderCards();
}

async function checkAllSSL() {
  toast('inf', '🔒 Checking SSL for all monitored domains…');
  const sslDomains = allDomains.filter(d => d.monitor_ssl);
  for (const d of sslDomains) {
    await checkSSL(d.domain);
    await sleep(300);
  }
  toast('ok', `🔒 SSL checked for ${sslDomains.length} domains`);
}

async function checkDomain(domain) {
  checking.add(domain); renderCards();
  if (isDemoMode) {
    await sleep(600);
    const idx = allDomains.findIndex(x => x.domain === domain);
    if (idx !== -1) allDomains[idx] = { ...allDomains[idx], last_checked: new Date().toISOString() };
    updateStats(); renderCards();
    toast('ok', `🎭 Demo: ${domain} — ${t('toastUpdated')}`);
    checking.delete(domain); renderCards();
    return;
  }
  try {
    const r = await fetch(`${API}?action=check&domain=${enc(domain)}`);
    const d = await r.json();
    const idx = allDomains.findIndex(x => x.domain === domain);
    if (idx !== -1) allDomains[idx] = { ...allDomains[idx], ...d };
    updateStats(); renderCards();
    toast('ok', `✅ ${domain} — ${t('toastUpdated')}`);
  } catch { toast('err', `❌ ${domain}`); }
  checking.delete(domain); renderCards();
}

async function checkSSL(domain) {
  checking.add('ssl:' + domain); renderCards();
  if (isDemoMode) {
    await sleep(700);
    const idx = allDomains.findIndex(x => x.domain === domain);
    if (idx !== -1) {
      const now = new Date();
      const exp = new Date(now); exp.setDate(exp.getDate() + 87);
      allDomains[idx] = { ...allDomains[idx],
        ssl_expiry: exp.toISOString().split('T')[0], ssl_days_left: 87,
        ssl_status: 'ok', ssl_issuer: "Let's Encrypt",
        ssl_last_checked: now.toISOString() };
    }
    updateStats(); renderCards();
    toast('ok', `🎭 Demo: 🔒 ${domain}`);
    checking.delete('ssl:' + domain); renderCards();
    return;
  }
  try {
    const r = await fetch(`${API}?action=check_ssl&domain=${enc(domain)}`);
    const d = await r.json();
    const idx = allDomains.findIndex(x => x.domain === domain);
    if (idx !== -1) allDomains[idx] = { ...allDomains[idx], ...d };
    updateStats(); renderCards();
    toast('ok', `${t('toastSslChecked')}: ${domain}`);
  } catch { toast('err', `❌ SSL ${domain}`); }
  checking.delete('ssl:' + domain); renderCards();
}

async function toggleSSL(domain, enable) {
  try {
    const r = await fetch(`${API}?action=toggle_ssl`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ domain, monitor_ssl: enable }),
    });
    const d = await r.json();
    if (d.success) {
      const idx = allDomains.findIndex(x => x.domain === domain);
      if (idx !== -1) allDomains[idx].monitor_ssl = enable;
      updateStats(); renderCards();
      toast('ok', enable ? `🔒 ${t('sslMonitorOn')}: ${domain}` : `🔓 ${t('sslMonitorOff')}: ${domain}`);
    }
  } catch {
    // Demo: update locally
    const idx = allDomains.findIndex(x => x.domain === domain);
    if (idx !== -1) allDomains[idx].monitor_ssl = enable;
    updateStats(); renderCards();
    toast('ok', enable ? `🔒 ${t('sslMonitorOn')}: ${domain}` : `🔓 ${t('sslMonitorOff')}: ${domain}`);
  }
}

// ── Filter / Sort ─────────────────────────────────────────────
function setFilter(btn, status) {
  statusFilter = status;
  document.querySelectorAll('.ftab').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  renderCards();
}

function sortBy(field) {
  sortDir = (sortField === field && sortDir === 'asc') ? 'desc' : 'asc';
  sortField = field;
  renderCards();
}

function getFiltered() {
  const search = (document.getElementById('searchInput')?.value || '').toLowerCase();
  const team   = document.getElementById('teamSel')?.value || '';

  // Statuses that count as "unknown" for filtering purposes
  const unknownStatuses = new Set(['unknown', 'error', null, undefined, '']);

  return [...allDomains]
    .filter(d => {
      if (statusFilter === 'abandoned') return !!d.abandoned;
      if (statusFilter !== 'all') {
        if (d.abandoned) return false; // hide abandoned from other filters
        const s = d.status || 'unknown';
        if (statusFilter === 'unknown') {
          if (!unknownStatuses.has(s)) return false;
        } else {
          if (s !== statusFilter) return false;
        }
      }
      if (team && d.team !== team) return false;
      if (search) {
        const hay = [d.domain, d.owner, d.team, d.notes, d.abandoned_note, ...(d.tags||[])].join(' ').toLowerCase();
        if (!hay.includes(search)) return false;
      }
      return true;
    })
    .sort((a, b) => {
      let va = a[sortField], vb = b[sortField];
      if (va === null || va === undefined) return 1;
      if (vb === null || vb === undefined) return -1;
      if (typeof va === 'number') return sortDir === 'asc' ? va - vb : vb - va;
      va = String(va).toLowerCase(); vb = String(vb).toLowerCase();
      return sortDir === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va);
    });
}

// ── Render ────────────────────────────────────────────────────
function setSortFromSel() {
  const v = document.getElementById('sortSel').value.split('-');
  sortField = v[0]; sortDir = v[1];
  renderCards();
}

function renderCards() {
  const rows = getFiltered();
  const grid = document.getElementById('cardsGrid');

  // Apply view mode classes
  grid.classList.toggle('list-view',    viewMode === 'list');
  grid.classList.toggle('compact-view', viewMode === 'compact');

  // Update filter tab counts
  const counts = { all: allDomains.length, critical:0, warning:0, soon:0, ok:0, unknown:0, abandoned:0 };
  allDomains.forEach(d => {
    if (d.abandoned) { counts.abandoned++; return; }
    const s = d.status || 'unknown';
    if      (s === 'expired')  counts.critical++;
    else if (s === 'critical') counts.critical++;
    else if (s === 'warning')  counts.warning++;
    else if (s === 'soon')     counts.soon++;
    else if (s === 'ok')       counts.ok++;
    else                       counts.unknown++;
  });
  Object.keys(counts).forEach(k => {
    const el = document.getElementById('fn-' + k);
    if (el) el.textContent = counts[k];
  });

  // Update section pill
  const onlinePill = document.getElementById('onlinePill');
  if (onlinePill) onlinePill.textContent = rows.length + ' / ' + allDomains.length + ' domains';

  if (!rows.length) {
    grid.innerHTML = (viewMode === 'compact' ? compactHeader() : '') + `<div class="cards-empty">
      <div class="empty-ico">🔭</div>
      <div class="empty-title">${t('emptyTitle')}</div>
      <div class="empty-sub">${t('emptySub')}</div>
    </div>`;
    return;
  }

  // Compact view — ultra-minimal rows
  if (viewMode === 'compact') {
    grid.innerHTML = compactHeader() + rows.map((d, i) => {
      const isChecking = checking.has(d.domain);
      const sc = statusColor(d.status);
      const stripColors = { ok:'var(--ok)',soon:'var(--soon)',warning:'var(--warning)',critical:'var(--critical)',expired:'var(--critical)','no-expiry':'var(--no-expiry)',unknown:'var(--border2)',error:'var(--border2)' };
      const stripColor = stripColors[d.status] || 'var(--border2)';
      const statusLabel = getStatusLabel(d.status);
      const isOn = !!d.monitor_ssl;
      let daysHtml = '<span style="color:var(--text-muted)">—</span>';
      if (isChecking) daysHtml = '<span class="blink" style="color:var(--accent)">…</span>';
      else if (d.days_left !== null) {
        const txt = d.days_left < 0 ? t('expiredLabel') : d.days_left + 'd';
        daysHtml = `<span style="color:${sc}">${txt}</span>`;
      }
      let sslHtml = '<span style="color:var(--text-muted);font-size:10px">off</span>';
      if (isOn && d.ssl_days_left !== null) {
        const ssc = sslStatusColor(d.ssl_status);
        sslHtml = `<span class="cdot cdot-${d.ssl_status||'unknown'}"></span><span style="color:${ssc};font-family:var(--mono)">${d.ssl_days_left}d</span>`;
      } else if (isOn) {
        sslHtml = `<span style="color:var(--ssl-ok);font-size:10px">on</span>`;
      }
      return `
      <div class="dcard ${d.abandoned ? 'abandoned' : ''}" style="animation-delay:${i*0.015}s" onclick="openDetail('${d.domain}')" title="${d.domain}">
        <div class="compact-strip" style="background:${stripColor}"></div>
        <div class="compact-name">
          <div class="compact-avatar" style="background:${domColor(d.domain)}">${d.domain.charAt(0).toUpperCase()}</div>
          <span class="compact-domain">${d.domain}</span>
        </div>
        <div class="compact-days">${daysHtml}</div>
        <div class="compact-date">${d.expiry_date ? fmtDate(d.expiry_date) : '—'}</div>
        <div class="compact-ssl">${sslHtml}</div>
        <div class="compact-act">
          <button class="ico-btn" onclick="event.stopPropagation();checkDomain('${d.domain}')" title="Refresh" ${isChecking?'disabled':''}style="width:24px;height:24px;font-size:11px">🔄</button>
        </div>
      </div>`;
    }).join('');
    return;
  }


  // Compact view — ultra-minimal rows
  if (viewMode === 'compact') {
    grid.innerHTML = compactHeader() + rows.map((d, i) => {
      const isChecking = checking.has(d.domain);
      const sc = statusColor(d.status);
      const stripColors = { ok:'var(--ok)',soon:'var(--soon)',warning:'var(--warning)',critical:'var(--critical)',expired:'var(--critical)','no-expiry':'var(--no-expiry)',unknown:'var(--border2)',error:'var(--border2)' };
      const stripColor = stripColors[d.status] || 'var(--border2)';
      const isOn = !!d.monitor_ssl;
      let daysHtml = '<span style="color:var(--text-muted)">—</span>';
      if (isChecking) daysHtml = '<span class="blink" style="color:var(--accent)">…</span>';
      else if (d.days_left !== null) {
        const txt = d.days_left < 0 ? t('expiredLabel') : d.days_left + 'd';
        daysHtml = `<span style="color:${sc}">${txt}</span>`;
      }
      let sslHtml = '<span style="color:var(--text-muted);font-size:10px">off</span>';
      if (isOn && d.ssl_days_left !== null) {
        const ssc = sslStatusColor(d.ssl_status);
        sslHtml = `<span class="cdot cdot-${d.ssl_status||'unknown'}"></span><span style="color:${ssc};font-family:var(--mono)">${d.ssl_days_left}d</span>`;
      } else if (isOn) {
        sslHtml = `<span style="color:var(--ssl-ok);font-size:10px">on</span>`;
      }
      return `
      <div class="dcard" style="animation-delay:${i*0.015}s" onclick="openDetail('${d.domain}')" title="${d.domain}">
        <div class="compact-strip" style="background:${stripColor}"></div>
        <div class="compact-name">
          <div class="compact-avatar" style="background:${domColor(d.domain)}">${d.domain.charAt(0).toUpperCase()}</div>
          <span class="compact-domain">${d.domain}</span>
        </div>
        <div class="compact-days">${daysHtml}</div>
        <div class="compact-date">${d.expiry_date ? fmtDate(d.expiry_date) : '—'}</div>
        <div class="compact-ssl">${sslHtml}</div>
        <div class="compact-act">
          <button class="ico-btn" onclick="event.stopPropagation();checkDomain('${d.domain}')" title="Refresh" ${isChecking?'disabled':''}style="width:24px;height:24px;font-size:11px">🔄</button>
        </div>
      </div>`;
    }).join('');
    return;
  }

  grid.innerHTML = rows.map((d, i) => {
    const isChecking    = checking.has(d.domain);
    const isSslChecking = checking.has('ssl:' + d.domain);
    const sc  = statusColor(d.status);
    const bw  = barWidth(d.days_left);
    const statusLabel = getStatusLabel(d.status);
    const tags = (d.tags || []).map(tg => `<span class="tag">${tg}</span>`).join('');
    const avatarColor = domColor(d.domain);
    const letter = d.domain.charAt(0).toUpperCase();

    // Strip color per status
    const stripColors = {
      ok: 'var(--ok)', soon: 'var(--soon)', warning: 'var(--warning)',
      critical: 'var(--critical)', expired: 'var(--critical)',
      'no-expiry': 'var(--no-expiry)', unknown: 'var(--border2)', error: 'var(--border2)'
    };
    const stripColor = stripColors[d.status] || 'var(--border2)';

    // Days bar value text
    let daysText = '—';
    if (isChecking) daysText = '…';
    else if (d.days_left !== null) {
      daysText = d.days_left < 0 ? t('expiredLabel') : d.days_left + 'd';
    }

    // SSL cell
    const isOn = !!d.monitor_ssl;
    let sslHtml = '';
    if (!isOn) {
      sslHtml = `<button class="ssl-tog" onclick="toggleSSL('${d.domain}',true)" title="${t('sslOff')}"></button>
                 <span class="ssl-lbl-txt">${t('sslOff')}</span>`;
    } else if (isSslChecking) {
      sslHtml = `<button class="ssl-tog on" onclick="toggleSSL('${d.domain}',false)"></button>
                 <span class="ssl-lbl-txt on blink">${t('sslChecking')}</span>`;
    } else if (d.ssl_days_left !== null) {
      const ssc = sslStatusColor(d.ssl_status);
      const sLabel = getStatusLabel(d.ssl_status);
      sslHtml = `<button class="ssl-tog on" onclick="toggleSSL('${d.domain}',false)"></button>
                 <span class="ssl-badge st-${d.ssl_status||'unknown'}" style="font-size:10px;padding:2px 6px;border-radius:10px">${sLabel}</span>
                 <span style="font-family:var(--mono);font-size:10.5px;color:${ssc};font-weight:600">${d.ssl_days_left}d</span>`;
    } else {
      sslHtml = `<button class="ssl-tog on" onclick="toggleSSL('${d.domain}',false)"></button>
                 <span class="ssl-lbl-txt on">${t('sslOn')}</span>`;
    }

    // Registrar short
    const reg = d.registrar ? d.registrar.replace(/,.*$/, '').substring(0, 22) : '—';

    // HTTP status badge
    const httpBadge = httpStatusBadge(d);
    const isHttpChecking  = checking.has('http:'  + d.domain);
    const isIntelChecking = checking.has('intel:' + d.domain);

    const actionsHtml = `
          <button class="ico-btn" onclick="openDetail('${d.domain}')" title="Details">🔎</button>
          <button class="ico-btn" onclick="checkDomain('${d.domain}')" title="Refresh WHOIS" ${isChecking?'disabled':''}>🔄</button>
          ${isOn ? `<button class="ico-btn" onclick="checkSSL('${d.domain}')" title="Check SSL" ${isSslChecking?'disabled':''}>🔒</button>` : ''}
          <button class="ico-btn" onclick="checkHttp('${d.domain}')" title="Check HTTP/redirects" ${isHttpChecking?'disabled':''}>🌐</button>
          <button class="ico-btn" onclick="runIntelCard('${d.domain}')" title="Refresh DNS &amp; Ports" ${isIntelChecking?'disabled':''}>🔍</button>
          <a class="ico-btn" href="https://${d.domain}" target="_blank" rel="noopener" title="Open">🔗</a>`;

    return `
    <div class="dcard ${isChecking ? 'checking' : ''} ${d.abandoned ? 'abandoned' : ''}" style="--strip:${stripColor};animation-delay:${i*0.025}s;border-left-color:${stripColor}">
      <div class="dcard-info-row">
      <div class="dcard-head">
        <div class="dcard-avatar" style="background:${avatarColor}">${letter}</div>
        <div class="dcard-name-wrap">
          <div class="dcard-name" title="${d.domain}">${d.domain}</div>
          <div class="dcard-team">
            ${d.abandoned ? `<span class="abandoned-badge">⚠️ Abandoned</span>` : `${d.owner || ''}${d.owner && d.team ? ' · ' : ''}${d.team || ''}`}
          </div>
          ${d.abandoned && d.abandoned_note ? `<div class="abandoned-note" title="${d.abandoned_note}">📝 ${d.abandoned_note}</div>` : ''}
        </div>
        <div class="dcard-online st-${d.status || 'unknown'}">${isChecking ? '⌛' : statusLabel}</div>
      </div>
      <div class="dcard-body">
        <div class="res-bars">
          <div class="res-row">
            <span class="res-lbl">Expiry</span>
            <div class="res-track"><div class="res-fill" style="width:${bw}%;background:${sc}"></div></div>
            <span class="res-val" style="color:${sc}">${daysText}</span>
          </div>
          <div class="res-row">
            <span class="res-lbl">Date</span>
            <div class="res-track"><div class="res-fill" style="width:${bw}%;background:${sc};opacity:.35"></div></div>
            <span class="res-val" style="font-size:10px;color:var(--text-muted)">${d.expiry_date ? fmtDate(d.expiry_date) : '—'}</span>
          </div>
        </div>
        <div class="ssl-row">${sslHtml}</div>
        <div class="intel-badges">${isHttpChecking ? '<span class="ibi ibi-none" style="animation:blink .8s step-end infinite">🌐</span>' : httpBadge}${isIntelChecking ? '<span class="ibi ibi-none" style="animation:blink .8s step-end infinite">🔍</span>' : dnsBadge(d)+nsBadge(d)+portBadge(d)}</div>
      </div>
      <div class="dcard-foot">
        <div class="dcard-info">
          <div class="dcard-meta-row">📋 <b>${reg}</b></div>
          ${d.last_checked ? `<div class="dcard-meta-row">🕐 ${fmtDateTime(d.last_checked)}</div>` : ''}
          ${tags ? `<div class="dcard-tags">${tags}</div>` : ''}
        </div>
        <div class="dcard-actions">${actionsHtml}</div>
      </div>
      </div><!-- /dcard-info-row -->
      <div class="dcard-actions-row">${actionsHtml}</div>
    </div>`;
  }).join('');
}


function compactHeader() {
  return `<div class="compact-header">
    <span></span>
    <span>Domain</span>
    <span>Days</span>
    <span>Expires</span>
    <span>SSL</span>
    <span></span>
  </div>`;
}

// ── Stats ─────────────────────────────────────────────────────
function updateStats() {
  const c = { total:allDomains.length, critical:0, warning:0, soon:0, ok:0, expired:0, ssl:0 };
  allDomains.forEach(d => {
    if (d.abandoned) return; // don't count abandoned in active stats
    if (d.status === 'critical') c.critical++;
    else if (d.status === 'warning') c.warning++;
    else if (d.status === 'soon')    c.soon++;
    else if (d.status === 'ok')      c.ok++;
    else if (d.status === 'expired') c.critical++; // count with critical
    if (d.monitor_ssl) c.ssl++;
  });
  document.getElementById('s-total').textContent    = c.total;
  document.getElementById('s-critical').textContent = c.critical;
  document.getElementById('s-warning').textContent  = c.warning;
  document.getElementById('s-soon').textContent     = c.soon;
  document.getElementById('s-ok').textContent       = c.ok;
  document.getElementById('s-ssl').textContent      = c.ssl;
}

function buildTeamFilter() {
  const teams = [...new Set(allDomains.map(d => d.team).filter(Boolean))].sort();
  const sel   = document.getElementById('teamSel');
  const cur   = sel.value;
  sel.innerHTML = `<option value="">${t('allTeams')}</option>` +
    teams.map(t2 => `<option value="${t2}" ${t2===cur?'selected':''}>${t2}</option>`).join('');
}

// ── Detail Modal ──────────────────────────────────────────────
function openDetail(domain) {
  const d = allDomains.find(x => x.domain === domain);
  if (!d) return;

  const tld = d.domain.split('.').pop().substring(0,3).toUpperCase();
  const tags = (d.tags||[]).map(t2 => `<span class="tag ${t2}">${t2}</span>`).join(' ');
  const statusLabel = getStatusLabel(d.status);
  const isOn = !!d.monitor_ssl;

  document.getElementById('detailTitle').textContent = d.domain;
  document.getElementById('detailSub').innerHTML =
    `<span class="badge b-${d.status}">${statusLabel}</span>`;

  document.getElementById('detailBody').innerHTML = `
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px">
      <div style="width:40px;height:40px;border-radius:8px;border:1px solid var(--border);background:${domColor(d.domain)};display:flex;align-items:center;justify-content:center;font-weight:700;font-size:18px;color:#fff;font-family:var(--mono);flex-shrink:0">
        ${d.domain.charAt(0).toUpperCase()}
      </div>
      <div style="flex:1">
        <div style="font-family:var(--mono);font-size:16px;font-weight:600">${d.domain}</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:3px">${d.notes||''}</div>
      </div>
    </div>

    <div class="detail-grid">
      <div class="detail-item">
        <div class="di-lbl">${t('detailExpiry')}</div>
        <div class="di-val">${d.expiry_date ? fmtDate(d.expiry_date) : '—'}</div>
      </div>
      <div class="detail-item">
        <div class="di-lbl">${t('detailDaysLeft')}</div>
        <div class="di-val" style="color:${statusColor(d.status)}">${d.days_left !== null ? d.days_left + ' ' + t('days') : '—'}</div>
      </div>
      <div class="detail-item">
        <div class="di-lbl">${t('detailOwner')}</div>
        <div class="di-val">${d.owner||'—'}</div>
      </div>
      <div class="detail-item">
        <div class="di-lbl">${t('detailTeam')}</div>
        <div class="di-val">${d.team||'—'}</div>
      </div>
      <div class="detail-item">
        <div class="di-lbl">${t('detailRegistrar')}</div>
        <div class="di-val">${d.registrar||'—'}</div>
      </div>
      <div class="detail-item">
        <div class="di-lbl">${t('detailLastChecked')}</div>
        <div class="di-val">${d.last_checked ? fmtDateTime(d.last_checked) : '—'}</div>
      </div>
      ${tags ? `<div class="detail-item di-full">
        <div class="di-lbl">${t('detailTags')}</div>
        <div class="di-val">${tags}</div>
      </div>` : ''}
    </div>

    <!-- SSL Section -->
    <div class="ssl-section">
      <div class="ssl-section-title">
        🔒 ${t('detailSslSection')}
      </div>
      <div class="ssl-monitor-toggle">
        <div class="toggle ${isOn ? 'on' : ''}" id="detailSslToggle" onclick="detailToggleSSL('${d.domain}')"></div>
        <span class="toggle-lbl">${t('detailSslMonitor')}</span>
      </div>
      ${isOn ? `
      <div class="detail-grid">
        <div class="detail-item">
          <div class="di-lbl">${t('detailSslExpiry')}</div>
          <div class="di-val">${d.ssl_expiry ? fmtDate(d.ssl_expiry) : '—'}</div>
        </div>
        <div class="detail-item">
          <div class="di-lbl">${t('detailSslDays')}</div>
          <div class="di-val" style="color:${sslStatusColor(d.ssl_status)}">${d.ssl_days_left !== null ? d.ssl_days_left + ' ' + t('days') : '—'}</div>
        </div>
        <div class="detail-item">
          <div class="di-lbl">${t('detailSslIssuer')}</div>
          <div class="di-val">${d.ssl_issuer||'—'}</div>
        </div>
        <div class="detail-item">
          <div class="di-lbl">${t('detailSslLastChecked')}</div>
          <div class="di-val">${d.ssl_last_checked ? fmtDateTime(d.ssl_last_checked) : '—'}</div>
        </div>
      </div>` : `<div style="font-size:13px;color:var(--text-muted);padding:8px 0">${t('sslOff')}</div>`}
    </div>

    <div style="display:flex;gap:8px;margin-top:20px">
      <button class="btn btn-primary" onclick="checkDomainAndRefresh('${d.domain}')" style="flex:1">🔄 ${t('btnRefresh')}</button>
      ${isOn ? `<button class="btn btn-ghost" onclick="checkSSLAndRefresh('${d.domain}')">🔒 ${t('btnCheckSsl')}</button>` : ''}
      <button class="btn btn-ghost" onclick="checkHttpAndRefresh('${d.domain}')">🌐 Check HTTP</button>
      <a class="btn btn-ghost" href="https://${d.domain}" target="_blank" rel="noopener">🔗 ${t('btnOpen')}</a>
    </div>

    <!-- HTTP Status Section -->
    <div class="ssl-section" style="margin-top:16px">
      <div class="ssl-section-title">🌐 HTTP Status & Redirects</div>
      ${d.http_check_status ? `
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;flex-wrap:wrap">
        ${httpStatusBadge(d)}
        ${d.response_ms !== null ? `<span style="font-size:11px;color:var(--text-muted);font-family:var(--mono)">${d.response_ms}ms</span>` : ''}
        ${d.last_http_checked ? `<span style="font-size:11px;color:var(--text-muted)">checked ${fmtDateTime(d.last_http_checked)}</span>` : ''}
      </div>
      ${d.redirects && d.redirects.length > 0 ? `
      <div class="redirect-chain">
        ${d.redirects.map((r, idx) => {
          const cat = r.status >= 500 ? '5xx' : r.status >= 400 ? '4xx' : r.status >= 300 ? '3xx' : '2xx';
          const arrow = idx < d.redirects.length - 1 ? '↓' : '✓';
          return `<div class="redirect-step">
            <div class="redirect-step-code code-${cat}">${r.status}</div>
            <div class="redirect-step-url">${arrow} ${r.url}</div>
          </div>`;
        }).join('')}
      </div>` : ''}
      ` : `<div style="font-size:13px;color:var(--text-muted);padding:8px 0">Not checked yet — click 🌐 Check HTTP above</div>`}
    </div>

    <!-- Domain Intelligence Section -->
    <div class="ssl-section" style="margin-top:16px">
      <div class="ssl-section-title" style="display:flex;align-items:center;justify-content:space-between">
        <span>🔍 Domain Intelligence</span>
        <div style="display:flex;gap:6px">
          <button id="detailDnsBtn"   class="btn btn-ghost" style="height:26px;font-size:11px;padding:0 10px" onclick="checkDnsAndRefresh('${d.domain}')">🌐 DNS</button>
          <button id="detailPortsBtn" class="btn btn-ghost" style="height:26px;font-size:11px;padding:0 10px" onclick="checkPortsAndRefresh('${d.domain}')">🔌 Ports</button>
          <button id="detailBothBtn"  class="btn btn-ghost" style="height:26px;font-size:11px;padding:0 10px" onclick="runIntelAndRefresh('${d.domain}')">⚡ Both</button>
          <span id="detailIntelSpinner" style="display:none;font-size:12px;color:var(--accent);animation:spin 1s linear infinite">⟳</span>
        </div>
      </div>

      <!-- Intel tabs -->
      ${(d.dns_records || d.port_results) ? `
      <div class="intel-tabs">
        <button class="itab active" id="itab-dns-${d.domain.replace(/\./g,'_')}"  onclick="showIntelTab('dns','${d.domain}')">🌐 DNS Records</button>
        <button class="itab"        id="itab-port-${d.domain.replace(/\./g,'_')}" onclick="showIntelTab('port','${d.domain}')">🔌 Ports</button>
      </div>

      <!-- DNS panel -->
      <div id="ipanel-dns-${d.domain.replace(/\./g,'_')}">
        ${d.ns_changed ? `
        <div class="ns-change-alert">
          <div class="ns-change-alert-icon">⚠️</div>
          <div>
            <strong>Nameserver change detected</strong> (${d.ns_changed_at ? fmtDateTime(d.ns_changed_at) : 'recently'})<br>
            <span style="font-size:11px">Previous: <code style="font-family:var(--mono)">${(d.dns_ns_prev||[]).join(', ') || '—'}</code></span>
          </div>
        </div>` : ''}
        ${d.dns_records ? buildDnsHtml(d.dns_records) : '<div style="color:var(--text-muted);font-size:13px;padding:8px 0">No DNS data yet</div>'}
        ${d.last_dns_checked ? `<div style="font-size:11px;color:var(--text-muted);margin-top:8px">Checked ${fmtDateTime(d.last_dns_checked)}</div>` : ''}
      </div>

      <!-- Ports panel -->
      <div id="ipanel-port-${d.domain.replace(/\./g,'_')}" style="display:none">
        ${d.port_results ? buildPortsHtml(d) : '<div style="color:var(--text-muted);font-size:13px;padding:8px 0">No port data yet</div>'}
        ${d.last_ports_checked ? `<div style="font-size:11px;color:var(--text-muted);margin-top:8px">Checked ${fmtDateTime(d.last_ports_checked)}</div>` : ''}
      </div>
      ` : `<div style="font-size:13px;color:var(--text-muted);padding:8px 0">Not checked yet — use DNS / Ports / Both buttons above</div>`}
    </div>
    <div class="abandon-section" style="margin-top:16px">
      <div class="abandon-section-title">⚠️ Mark as Abandoned</div>
      <div class="toggle-row">
        <div class="toggle ${d.abandoned ? 'on' : ''}" id="abandonToggle" onclick="toggleAbandonUI()"></div>
        <span class="toggle-lbl">This domain is abandoned / decommissioned</span>
      </div>
      <div id="abandonNoteWrap" style="display:${d.abandoned ? 'block' : 'none'}">
        <textarea class="abandon-note-input" id="abandonNoteInput" placeholder="Reason for abandonment, redirect plan, contact person…">${d.abandoned_note || ''}</textarea>
      </div>
      <div style="display:flex;gap:8px;margin-top:10px">
        <button class="btn btn-ghost" style="flex:1;border-color:#fcd34d;color:#92400e" onclick="saveAbandoned('${d.domain}')">💾 Save</button>
      </div>
    </div>`;

  document.getElementById('detailOverlay').classList.add('open');
}

async function detailToggleSSL(domain) {
  const d = allDomains.find(x => x.domain === domain);
  if (!d) return;
  const newVal = !d.monitor_ssl;
  await toggleSSL(domain, newVal);
  closeModal('detailOverlay');
  setTimeout(() => openDetail(domain), 200);
}

function toggleAbandonUI() {
  const toggle = document.getElementById('abandonToggle');
  const wrap   = document.getElementById('abandonNoteWrap');
  toggle.classList.toggle('on');
  wrap.style.display = toggle.classList.contains('on') ? 'block' : 'none';
  if (toggle.classList.contains('on')) {
    setTimeout(() => document.getElementById('abandonNoteInput')?.focus(), 50);
  }
}

async function saveAbandoned(domain) {
  const isAbandoned = document.getElementById('abandonToggle').classList.contains('on');
  const note        = document.getElementById('abandonNoteInput')?.value.trim() || '';
  try {
    await fetch(`${API}?action=update_domain`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ domain, abandoned: isAbandoned, abandoned_note: note }),
    });
  } catch {}
  // Update local state immediately
  const idx = allDomains.findIndex(x => x.domain === domain);
  if (idx !== -1) {
    allDomains[idx].abandoned      = isAbandoned;
    allDomains[idx].abandoned_note = note;
  }
  updateStats(); renderCards();
  closeModal('detailOverlay');
  toast('ok', isAbandoned ? `⚠️ ${domain} marked as abandoned` : `✅ ${domain} restored`);
}

// ── Teams Config ──────────────────────────────────────────────
function openTeamsModal() {
  document.getElementById('teamsOverlay').classList.add('open');
}

async function loadTeamsCfg() {
  try {
    const r = await fetch(`${API}?action=get_config`);
    const c = await r.json();
    if (c.webhook_url) document.getElementById('teamsWebhook').value = c.webhook_url;
    if (c.dashboard_url) document.getElementById('dashUrl').value = c.dashboard_url;
    if (c.enabled) document.getElementById('teamsToggle').classList.add('on');
    if (c.notify_days) {
      document.querySelectorAll('#cbDays input').forEach(cb => {
        cb.checked = c.notify_days.includes(+cb.value);
        cb.parentElement.classList.toggle('checked', cb.checked);
      });
    }
  } catch {}
}

async function saveTeamsCfg() {
  const cfg = {
    webhook_url:  document.getElementById('teamsWebhook').value,
    dashboard_url:document.getElementById('dashUrl').value,
    enabled:      document.getElementById('teamsToggle').classList.contains('on'),
    notify_days:  [...document.querySelectorAll('#cbDays input:checked')].map(cb => +cb.value),
  };
  try {
    await fetch(`${API}?action=save_config`, {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify(cfg),
    });
  } catch {}
  toast('ok', t('toastSaved'));
  closeModal('teamsOverlay');
}

async function testTeams() {
  const url = document.getElementById('teamsWebhook').value;
  if (!url) { toast('err', t('toastNoWebhook')); return; }
  toast('inf', '🧪 Sending test…');
  const payload = {
    type:'message',
    attachments:[{
      contentType:'application/vnd.microsoft.card.adaptive',
      content:{
        '$schema':'http://adaptivecards.io/schemas/adaptive-card.json',
        type:'AdaptiveCard', version:'1.4',
        body:[
          { type:'TextBlock', text:'✅ DomainWatch — Test notification', weight:'Bolder', size:'Medium' },
          { type:'TextBlock', text:'Teams integration is working correctly!', wrap:true },
        ],
      },
    }],
  };
  try {
    await fetch(url, { method:'POST', mode:'no-cors', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
    toast('ok', t('toastTestSent'));
  } catch { toast('err', '❌ Failed to reach Teams webhook'); }
}

// ── Helpers ───────────────────────────────────────────────────
function statusColor(s) {
  return { ok:'var(--ok)', soon:'var(--soon)', warning:'var(--warning)',
           critical:'var(--critical)', expired:'#f87171',
           'no-expiry':'#60a5fa',
           unknown:'var(--text-muted)', error:'var(--text-muted)' }[s] || 'var(--text-muted)';
}
function sslStatusColor(s) {
  return { ok:'var(--ssl-ok)', soon:'var(--soon)', warning:'var(--warning)',
           critical:'var(--critical)', expired:'#f87171', unknown:'var(--text-muted)', error:'var(--text-muted)' }[s] || 'var(--text-muted)';
}
function barWidth(days) {
  if (days === null) return 0;
  if (days <= 0) return 100;
  if (days >= 365) return 3;
  return Math.max(3, 100 - (days/365)*97);
}
function fmtDate(s) {
  if (!s) return '—';
  const d = new Date(s);
  return d.toLocaleDateString(lang === 'pl' ? 'pl-PL' : 'en-GB', { day:'2-digit', month:'short', year:'numeric' });
}
function fmtDateTime(s) {
  if (!s) return '—';
  const d = new Date(s);
  return d.toLocaleDateString(lang === 'pl' ? 'pl-PL' : 'en-GB', { day:'2-digit', month:'short' }) + ' ' +
         d.toLocaleTimeString('en-GB', { hour:'2-digit', minute:'2-digit' });
}
function cap(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

// Deterministic color per domain name for letter avatars
function domColor(domain) {
  const colors = ['#6366f1','#8b5cf6','#ec4899','#f59e0b','#10b981','#3b82f6','#ef4444','#14b8a6','#f97316','#84cc16'];
  let h = 0;
  for (let i = 0; i < domain.length; i++) h = (h * 31 + domain.charCodeAt(i)) & 0xffffffff;
  return colors[Math.abs(h) % colors.length];
}

// Safe status label — handles 'no-expiry', unknown values, etc.
function getStatusLabel(status) {
  return T[lang]['status' + cap(status||'unknown')]
      || T.en['status'  + cap(status||'unknown')]
      || T[lang][status] || cap(status) || '?';
}
function enc(s) { return encodeURIComponent(s); }
function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }
function copy(text) {
  navigator.clipboard?.writeText(text);
  toast('ok', `${t('toastCopied')}: ${text}`);
}


// ── HTTP Status Check ─────────────────────────────────────────
function httpStatusBadge(d) {
  const s = d.http_check_status;
  if (!s) {
    return '<span class="ibadge-wrap"><span class="ibi ibi-none">🌐</span></span>';
  }

  const cls = { 'ok':'ibi-ok', 'redirect':'ibi-http-redirect',
    'client-error':'ibi-error','server-error':'ibi-error',
    'timeout':'ibi-error','loop':'ibi-error','error':'ibi-error' }[s] || 'ibi-none';

  const hops = (d.redirects || []).length;
  const ms   = d.response_ms !== null && d.response_ms !== undefined ? d.response_ms + 'ms' : '';

  let chainHtml = '';
  if (d.redirects && d.redirects.length > 0) {
    d.redirects.forEach(function(r, idx) {
      const cat    = r.status >= 500 ? '5xx' : r.status >= 400 ? '4xx' : r.status >= 300 ? '3xx' : '2xx';
      const isLast = idx === d.redirects.length - 1;
      const urlShort = r.url.length > 42 ? r.url.substring(0,40)+'…' : r.url;
      chainHtml += '<div class="ibp-http-row">' +
        '<span class="code-' + cat + '" style="font-size:10px;padding:1px 5px;border-radius:4px;flex-shrink:0">' + r.status + '</span>' +
        '<span class="ibp-http-arrow">' + (isLast ? '✓' : '→') + '</span>' +
        '<span class="ibp-http-url" title="' + r.url + '">' + urlShort + '</span>' +
        '</div>';
    });
  }

  const summaryMap = { 'ok':'✓ Live','redirect':'↪ Redirect','client-error':'✕ Client Error',
    'server-error':'✕ Server Error','timeout':'⏱ Timeout','loop':'∞ Loop','error':'✕ Unreachable' };
  const code     = d.http_status ? ' ' + d.http_status : '';
  const hopsTxt  = hops > 1 ? ' · ' + (hops-1) + ' hop' + (hops>2?'s':'') : '';
  const summary  = (summaryMap[s] || s) + code + hopsTxt + (ms ? ' · '+ms : '');
  const ts       = d.last_http_checked ? '<div style="font-size:9px;color:var(--text-muted);margin-top:6px;border-top:1px solid var(--border);padding-top:5px">checked ' + fmtDateTime(d.last_http_checked) + '</div>' : '';

  const html = '<div class="ibp-title">🌐 HTTP Status</div><div class="ibp-http-summary">' + summary + '</div>' +
    (chainHtml ? '<div class="ibp-http-chain">' + chainHtml + '</div>' : '') + ts;
  const pid  = _regPopup(html);
  return '<span class="ibadge-wrap"><span class="ibi ' + cls + '" data-pid="' + pid + '">🌐</span></span>';
}


async function checkHttp(domain) {
  checking.add('http:' + domain);
  renderCards();
  try {
    const r = await fetch(`${API}?action=check_http&domain=${encodeURIComponent(domain)}`);
    const data = await r.json();
    if (data.error) { toast('err', `🌐 ${domain}: ${data.error}`); return; }

    const idx = allDomains.findIndex(x => x.domain === domain);
    if (idx !== -1) {
      Object.assign(allDomains[idx], {
        http_status:       data.http_status,
        final_url:         data.final_url,
        redirects:         data.redirects || [],
        response_ms:       data.response_ms,
        http_check_status: data.http_check_status,
        last_http_checked: new Date().toISOString(),
      });
    }

    const s = data.http_check_status;
    if (s === 'ok')           toast('ok',  `🌐 ${domain} → Live (${data.http_status}) in ${data.response_ms}ms`);
    else if (s === 'redirect'){
      const hops = (data.redirects || []).length - 1;
      toast('inf', `↪ ${domain} → ${hops} redirect(s) → ${data.final_url}`);
    }
    else                      toast('err', `🌐 ${domain} → ${s} (${data.http_status || 'no response'})`);
  } catch (e) {
    toast('err', `🌐 ${domain}: ${e.message}`);
  } finally {
    checking.delete('http:' + domain);
    renderCards();
  }
}

async function checkAllHttp() {
  const domains = allDomains.filter(d => !d.abandoned);
  const BATCH = 4;
  let done = 0;
  toast('inf', `🌐 Checking HTTP for ${domains.length} domains…`);
  for (let i = 0; i < domains.length; i += BATCH) {
    const batch = domains.slice(i, i + BATCH);
    await Promise.allSettled(batch.map(d => checkHttp(d.domain)));
    done += batch.length;
    if (done < domains.length) await new Promise(r => setTimeout(r, 300));
  }
  toast('ok', `✅ HTTP check complete for ${domains.length} domains`);
}

// ── Domain Intelligence ───────────────────────────────────────

function dnsBadge(d) {
  if (!d.last_dns_checked) {
    return '<span class="ibadge-wrap"><span class="ibi ibi-none">🌐</span></span>';
  }

  const cls    = d.dns_healthy === false ? 'ibi-error' : d.ns_changed ? 'ibi-warn' : 'ibi-ok';
  const aCount = (d.dns_records?.A || []).length;

  let body = '';
  if (d.dns_healthy === false) {
    body = '<div style="color:var(--critical);font-size:11px;padding:4px 0">No DNS records found</div>';
  } else {
    if (d.ns_changed) {
      body += '<div class="ibp-ns-alert">⚠️ Nameserver change!<br><span style="font-size:9.5px">Was: ' + ((d.dns_ns_prev||[]).join(', ')||'—') + '</span></div>';
    }
    if (d.dns_records) {
      const order = ['A','AAAA','CNAME','MX','NS','TXT'];
      for (const type of order) {
        const rows = d.dns_records[type];
        if (!rows || !rows.length) continue;
        body += '<div class="ibp-dns-type">' + type + '</div>';
        rows.slice(0,4).forEach(function(r) {
          const val   = type === 'MX' ? r.value + ' (pri '+r.priority+')' : r.value;
          const short = val.length > 38 ? val.substring(0,36)+'…' : val;
          const ttl   = r.ttl !== undefined ? r.ttl + 's' : '';
          body += '<div class="ibp-dns-row"><span class="ibp-dns-val">' + short + '</span><span class="ibp-dns-ttl">' + ttl + '</span></div>';
        });
        if (rows.length > 4) body += '<div style="font-size:9.5px;color:var(--text-muted);padding:2px 0">+' + (rows.length-4) + ' more</div>';
      }
    }
  }
  if (d.last_dns_checked) {
    body += '<div style="font-size:9px;color:var(--text-muted);margin-top:6px;border-top:1px solid var(--border);padding-top:5px">checked ' + fmtDateTime(d.last_dns_checked) + '</div>';
  }

  const html = '<div class="ibp-title">🌐 DNS Records' + (aCount ? ' — '+aCount+' A' : '') + '</div>' + body;
  const pid  = _regPopup(html);
  return '<span class="ibadge-wrap"><span class="ibi ' + cls + '" data-pid="' + pid + '">🌐</span></span>';
}


function nsBadge(d) {
  if (!d.dns_ns || !d.dns_ns.length) return '';
  const rows = d.dns_ns.map(function(ns) {
    return '<div class="ibp-dns-row"><span class="ibp-dns-val">' + ns + '</span></div>';
  }).join('');
  const ts = d.last_dns_checked
    ? '<div style="font-size:9px;color:var(--text-muted);margin-top:6px;border-top:1px solid var(--border);padding-top:5px">checked ' + fmtDateTime(d.last_dns_checked) + '</div>'
    : '';
  const html = '<div class="ibp-title">🔤 Nameservers</div>' + rows + ts;
  const pid  = _regPopup(html);
  return '<span class="ibadge-wrap"><span class="ibi ibi-ok" style="font-size:9px;font-family:var(--mono);width:auto;padding:0 4px" data-pid="' + pid + '">NS</span></span>';
}


function portBadge(d) {
  if (!d.port_results) {
    return '<span class="ibadge-wrap"><span class="ibi ibi-none">🔌</span></span>';
  }
  const open  = d.port_results.filter(function(p){ return p.open; }).length;
  const total = d.port_results.length;
  const cls   = open === 0 ? 'ibi-error' : 'ibi-ok';

  const portRows = d.port_results.map(function(p) {
    const ms = p.open && p.ms !== null ? '<span style="margin-left:auto;font-size:9.5px;opacity:.7">' + p.ms + 'ms</span>' : '';
    return '<div class="ibp-port-item ' + (p.open ? 'open' : 'closed') + '">' +
      '<span class="ibp-dot ' + (p.open ? 'open' : 'closed') + '"></span>' +
      '<span>' + p.port + '</span>' +
      '<span style="font-size:9.5px;margin-left:2px;opacity:.8">' + p.name + '</span>' +
      ms + '</div>';
  }).join('');

  const ipLine = d.resolved_ip ? '<div style="font-family:var(--mono);font-size:10px;color:var(--text-muted);margin-bottom:6px">' + d.resolved_ip + '</div>' : '';
  const ts     = d.last_ports_checked ? '<div style="font-size:9px;color:var(--text-muted);margin-top:6px;border-top:1px solid var(--border);padding-top:5px">checked ' + fmtDateTime(d.last_ports_checked) + '</div>' : '';

  const html = '<div class="ibp-title">🔌 Ports — ' + open + '/' + total + ' open</div>' + ipLine + '<div class="ibp-port-grid">' + portRows + '</div>' + ts;
  const pid  = _regPopup(html);
  return '<span class="ibadge-wrap"><span class="ibi ' + cls + '" data-pid="' + pid + '">🔌</span></span>';
}


function showIntelTab(tab, domain) {
  const key = domain.replace(/\./g,'_');
  ['dns','port'].forEach(t => {
    const panel = document.getElementById(`ipanel-${t}-${key}`);
    const btn   = document.getElementById(`itab-${t}-${key}`);
    if (panel) panel.style.display = t === tab ? '' : 'none';
    if (btn)   btn.classList.toggle('active', t === tab);
  });
}

function buildDnsHtml(recs) {
  if (!recs) return '';
  const order = ['A','AAAA','CNAME','MX','NS','TXT','SOA'];
  let html = '';

  if (recs.dnssec) {
    html += `<div style="margin-bottom:8px"><span class="dnssec-badge">🔐 DNSSEC enabled</span></div>`;
  }

  for (const type of order) {
    const rows = recs[type];
    if (!rows || !rows.length) continue;
    html += `<div class="dns-type-block">
      <div class="dns-type-label">${type}</div>`;
    for (const r of rows) {
      if (type === 'SOA') {
        html += `<div class="dns-record-row">
          <div class="dns-record-val">${r.value} &nbsp;<span style="color:var(--text-muted);font-size:10px">${r.rname || ''}</span></div>
          <div class="dns-record-ttl">serial ${r.serial || '—'}</div>
        </div>`;
      } else if (type === 'TXT') {
        const v = r.value || '';
        const short = v.length > 80 ? v.substring(0,80) + '…' : v;
        html += `<div class="dns-record-row">
          <div class="dns-record-val dns-txt-val" title="${v.replace(/"/g,'&quot;')}">"${short}"</div>
          <div class="dns-record-ttl">${r.ttl}s</div>
        </div>`;
      } else {
        const pri = type === 'MX' ? `<div class="dns-record-pri">pri ${r.priority}</div>` : '';
        html += `<div class="dns-record-row">
          ${pri}
          <div class="dns-record-val">${r.value}</div>
          <div class="dns-record-ttl">${r.ttl}s</div>
        </div>`;
      }
    }
    html += `</div>`;
  }
  return html || '<div style="color:var(--text-muted);font-size:13px">No records found</div>';
}

function buildPortsHtml(d) {
  if (!d.port_results || !d.port_results.length) return '';
  let html = '';
  if (d.resolved_ip) {
    html += `<div class="resolved-ip">Resolved IP: ${d.resolved_ip}</div>`;
  }
  html += `<div class="port-grid">`;
  for (const p of d.port_results) {
    const cls = p.open ? 'open' : 'closed';
    html += `<div class="port-item ${cls}">
      <div class="port-dot ${cls}"></div>
      <div class="port-num">${p.port}</div>
      <div class="port-name">${p.name}</div>
      ${p.open
        ? `<div class="port-ms port-open-ms">✓ ${p.ms}ms</div>`
        : `<div class="port-closed-lbl">closed</div>`}
    </div>`;
  }
  html += `</div>`;
  return html;
}

async function checkDns(domain) {
  checking.add('dns:' + domain);
  renderCards();
  try {
    const r    = await fetch(`${API}?action=check_dns&domain=${encodeURIComponent(domain)}`);
    const data = await r.json();
    if (data.error) { toast('err', `🌐 DNS ${domain}: ${data.error}`); return; }

    const idx = allDomains.findIndex(x => x.domain === domain);
    if (idx !== -1) {
      Object.assign(allDomains[idx], {
        dns_records:      data,
        dns_ns:           data.NS ? data.NS.map(n => n.value) : [],
        dns_ns_prev:      data.prev_ns || [],
        ns_changed:       data.ns_changed || false,
        ns_changed_at:    data.ns_changed_at || null,
        last_dns_checked: new Date().toISOString(),
        dns_healthy:      data.healthy,
      });
    }

    if (data.ns_changed) {
      toast('err', `⚠️ ${domain}: Nameserver change detected!`);
    } else if (!data.healthy) {
      toast('err', `🌐 ${domain}: No DNS records found`);
    } else {
      const aCount = (data.A || []).length;
      const nsCount = (data.NS || []).length;
      toast('ok', `✓ DNS ${domain} — ${aCount} A record(s), ${nsCount} NS`);
    }
  } catch (e) {
    toast('err', `DNS check failed: ${e.message}`);
  } finally {
    checking.delete('dns:' + domain);
    renderCards();
  }
}

async function checkPorts(domain) {
  checking.add('port:' + domain);
  renderCards();
  try {
    const r    = await fetch(`${API}?action=check_ports&domain=${encodeURIComponent(domain)}`);
    const data = await r.json();
    if (data.error) { toast('err', `🔌 ${domain}: ${data.error}`); return; }

    const idx = allDomains.findIndex(x => x.domain === domain);
    if (idx !== -1) {
      Object.assign(allDomains[idx], {
        port_results:       data.ports || [],
        resolved_ip:        data.resolved_ip || null,
        last_ports_checked: new Date().toISOString(),
      });
    }

    const open = (data.ports || []).filter(p => p.open).length;
    toast('ok', `🔌 ${domain} — ${open}/${(data.ports||[]).length} ports open (${data.total_ms}ms)`);
  } catch (e) {
    toast('err', `Port check failed: ${e.message}`);
  } finally {
    checking.delete('port:' + domain);
    renderCards();
  }
}

async function runIntelDomain(domain) {
  await Promise.allSettled([checkDns(domain), checkPorts(domain)]);
}

async function runIntelCard(domain) {
  checking.add('intel:' + domain);
  renderCards();
  await runIntelDomain(domain);
  checking.delete('intel:' + domain);
  renderCards();
}

// ── In-place modal refresh helpers ───────────────────────────
function setIntelBtnsLoading(loading) {
  ['detailDnsBtn','detailPortsBtn','detailBothBtn'].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    el.disabled = loading;
    el.style.opacity = loading ? '.5' : '';
    el.style.cursor  = loading ? 'wait' : '';
  });
  const spinner = document.getElementById('detailIntelSpinner');
  if (spinner) spinner.style.display = loading ? 'inline-block' : 'none';
}

function refreshDetailBody(domain) {
  const d = allDomains.find(x => x.domain === domain);
  if (!d) return;
  // Re-render only the intelligence section without closing the modal
  openDetail(domain);
}

async function checkDnsAndRefresh(domain) {
  setIntelBtnsLoading(true);
  await checkDns(domain);
  refreshDetailBody(domain);
  setIntelBtnsLoading(false);
}

async function checkPortsAndRefresh(domain) {
  setIntelBtnsLoading(true);
  await checkPorts(domain);
  refreshDetailBody(domain);
  setIntelBtnsLoading(false);
}

async function runIntelAndRefresh(domain) {
  setIntelBtnsLoading(true);
  await runIntelDomain(domain);
  refreshDetailBody(domain);
  setIntelBtnsLoading(false);
}

async function checkHttpAndRefresh(domain) {
  const btn = document.querySelector(`[onclick="checkHttpAndRefresh('${domain}')"]`);
  if (btn) { btn.disabled = true; btn.textContent = '⟳ Checking…'; }
  await checkHttp(domain);
  openDetail(domain);
}

async function checkDomainAndRefresh(domain) {
  const btn = document.querySelector(`[onclick="checkDomainAndRefresh('${domain}')"]`);
  if (btn) { btn.disabled = true; btn.innerHTML = '⟳ Refreshing…'; }
  await checkDomain(domain);
  openDetail(domain);
}

async function checkSSLAndRefresh(domain) {
  const btn = document.querySelector(`[onclick="checkSSLAndRefresh('${domain}')"]`);
  if (btn) { btn.disabled = true; btn.textContent = '⟳ Checking…'; }
  await checkSSL(domain);
  openDetail(domain);
}

async function runIntelAll() {
  const domains = allDomains.filter(d => !d.abandoned);
  toast('inf', `🔍 Running Domain Intelligence on ${domains.length} domains…`);
  const BATCH = 3;
  for (let i = 0; i < domains.length; i += BATCH) {
    const batch = domains.slice(i, i + BATCH);
    await Promise.allSettled(batch.flatMap(d => [checkDns(d.domain), checkPorts(d.domain)]));
    if (i + BATCH < domains.length) await new Promise(r => setTimeout(r, 500));
  }
  // Warn about any NS changes
  const changed = allDomains.filter(d => d.ns_changed);
  if (changed.length) {
    toast('err', `⚠️ Nameserver changes detected: ${changed.map(d => d.domain).join(', ')}`);
  }
  toast('ok', `✅ Domain Intelligence complete for ${domains.length} domains`);
}

function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function toast(type, msg) {
  const c = document.getElementById('toasts');
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.textContent = msg;
  c.appendChild(el);
  setTimeout(() => el.remove(), 3200);
}

// Close overlay on backdrop click
document.querySelectorAll('.overlay').forEach(o => {
  o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.overlay.open').forEach(o => o.classList.remove('open'));
});



// ── i18n ─────────────────────────────────────────────────────
const T = {
  en: {
    tagline:'Domain & SSL Monitor', notChecked:'Not checked yet',
    teamsBtn:'Teams', checkAllBtn:'Check All', checkSslBtn:'Check SSL All',
    statTotal:'All Domains', statTotalSub:'monitored',
    statCrit:'Critical',     statCritSub:'≤ 14 days',
    statWarn:'Warning',      statWarnSub:'15 – 30 days',
    statSoon:'Expiring Soon',statSoonSub:'31 – 60 days',
    statOk:'OK',             statOkSub:'> 60 days',
    statSsl:'SSL Monitored', statSslSub:'certs tracked',
    searchPlaceholder:'Search domain, owner, team…',
    allTeams:'All teams',
    filterAll:'All', filterCrit:'🔴 Critical', filterWarn:'🟠 Warning',
    filterSoon:'🟡 Soon', filterOk:'✅ OK', filterUnknown:'❓ Unknown',
    colDomain:'Domain', colExpiry:'Days Left', colDate:'Exp. Date',
    colSsl:'SSL', colStatus:'Status', colOwner:'Owner / Team',
    colRegistrar:'Registrar', colActions:'Actions',
    loading:'Loading domains…',
    cronHint:'Cron:',
    teamsCfgTitle:'🔔 Teams Configuration', teamsCfgSub:'Microsoft Teams webhook integration',
    webhookLabel:'Webhook URL', webhookHint:'Incoming Webhook URL from Microsoft Teams',
    dashUrlLabel:'Dashboard URL', dashUrlHint:'Link included in Teams notifications',
    notifyWhenLabel:'Notify when fewer than',
    notifLabel:'Notifications', notifToggle:'Enable Teams notifications',
    saveCfgBtn:'💾 Save', testCfgBtn:'🧪 Test',
    days:'days', daysLeft:'days left', expiredLabel:'EXPIRED',
    statusOk:'OK', statusSoon:'Soon', statusWarning:'Warning',
    statusCritical:'Critical', statusExpired:'Expired', statusUnknown:'Unknown', statusError:'Error', 'statusNo-expiry':'No expiry',
    sslOn:'SSL monitored', sslOff:'SSL off', sslChecking:'Checking…',
    detailTitle:'Domain Details', detailOwner:'Owner', detailTeam:'Team',
    detailNotes:'Notes', detailTags:'Tags', detailRegistrar:'Registrar',
    detailExpiry:'Expiry Date', detailDaysLeft:'Days Left',
    detailLastChecked:'Last Checked', detailSslExpiry:'SSL Expiry',
    detailSslDays:'SSL Days Left', detailSslIssuer:'SSL Issuer',
    detailSslLastChecked:'SSL Last Checked', detailSslSection:'SSL Certificate',
    detailSslMonitor:'Monitor SSL for this domain',
    btnRefresh:'Refresh WHOIS', btnOpen:'Open', btnCheckSsl:'Check SSL',
    checkingLabel:'Checking…', emptyTitle:'No results',
    emptySub:'Change filters or add domains to domains.json',
    toastSaved:'✅ Configuration saved', toastTestSent:'✅ Test sent to Teams',
    toastNoWebhook:'⚠️ Please enter a Webhook URL',
    toastUpdated:'✅ Updated', toastError:'❌ Error — running in demo mode',
    toastSslChecked:'🔒 SSL checked', toastCopied:'📋 Copied',
    sslMonitorOn:'SSL monitoring enabled', sslMonitorOff:'SSL monitoring disabled',
    checkedAt:'Checked:',
    demoMode:'🎭 Demo mode — connect api.php for live data',
    noSslData:'No data',
  },
  pl: {
    tagline:'Monitor Domen i SSL', notChecked:'Nie sprawdzono',
    teamsBtn:'Teams', checkAllBtn:'Sprawdź wszystkie', checkSslBtn:'Sprawdź SSL',
    statTotal:'Wszystkie', statTotalSub:'w monitoringu',
    statCrit:'Krytyczne',  statCritSub:'≤ 14 dni',
    statWarn:'Ostrzeżenie',statWarnSub:'15 – 30 dni',
    statSoon:'Wkrótce',    statSoonSub:'31 – 60 dni',
    statOk:'OK',           statOkSub:'> 60 dni',
    statSsl:'SSL',         statSslSub:'śledzonych cert.',
    searchPlaceholder:'Szukaj domeny, właściciela, zespołu…',
    allTeams:'Wszystkie zespoły',
    filterAll:'Wszystkie', filterCrit:'🔴 Krytyczne', filterWarn:'🟠 Ostrzeżenie',
    filterSoon:'🟡 Wkrótce', filterOk:'✅ OK', filterUnknown:'❓ Nieznane',
    colDomain:'Domena', colExpiry:'Pozostało', colDate:'Data wygaśnięcia',
    colSsl:'SSL', colStatus:'Status', colOwner:'Właściciel / Zespół',
    colRegistrar:'Rejestrator', colActions:'Akcje',
    loading:'Ładowanie domen…',
    cronHint:'Cron:',
    teamsCfgTitle:'🔔 Konfiguracja Teams', teamsCfgSub:'Integracja webhook Microsoft Teams',
    webhookLabel:'Webhook URL', webhookHint:'URL Incoming Webhook z Microsoft Teams',
    dashUrlLabel:'URL Dashboardu', dashUrlHint:'Link w powiadomieniu Teams',
    notifyWhenLabel:'Powiadamiaj gdy zostało mniej niż',
    notifLabel:'Powiadomienia', notifToggle:'Włącz powiadomienia Teams',
    saveCfgBtn:'💾 Zapisz', testCfgBtn:'🧪 Test',
    days:'dni', daysLeft:'dni', expiredLabel:'WYGASŁA',
    statusOk:'OK', statusSoon:'Wkrótce', statusWarning:'Ostrzeżenie',
    statusCritical:'Krytyczne', statusExpired:'Wygasła', statusUnknown:'Nieznane', statusError:'Błąd', 'statusNo-expiry':'Bez wygaśnięcia',
    sslOn:'SSL monitorowany', sslOff:'SSL wyłączony', sslChecking:'Sprawdzam…',
    detailTitle:'Szczegóły domeny', detailOwner:'Właściciel', detailTeam:'Zespół',
    detailNotes:'Notatki', detailTags:'Tagi', detailRegistrar:'Rejestrator',
    detailExpiry:'Data wygaśnięcia', detailDaysLeft:'Pozostało dni',
    detailLastChecked:'Ostatnie sprawdzenie', detailSslExpiry:'Wygaśnięcie SSL',
    detailSslDays:'Pozostało (SSL)', detailSslIssuer:'Wystawca SSL',
    detailSslLastChecked:'SSL sprawdzono', detailSslSection:'Certyfikat SSL',
    detailSslMonitor:'Monitoruj SSL dla tej domeny',
    btnRefresh:'Odśwież WHOIS', btnOpen:'Otwórz', btnCheckSsl:'Sprawdź SSL',
    checkingLabel:'Sprawdzam…', emptyTitle:'Brak wyników',
    emptySub:'Zmień filtry lub dodaj domeny do domains.json',
    toastSaved:'✅ Konfiguracja zapisana', toastTestSent:'✅ Test wysłany do Teams',
    toastNoWebhook:'⚠️ Podaj Webhook URL',
    toastUpdated:'✅ Zaktualizowano', toastError:'❌ Błąd — tryb demo',
    toastSslChecked:'🔒 SSL sprawdzony', toastCopied:'📋 Skopiowano',
    sslMonitorOn:'Monitoring SSL włączony', sslMonitorOff:'Monitoring SSL wyłączony',
    checkedAt:'Sprawdzono:',
    demoMode:'🎭 Tryb demo — podłącz api.php dla danych live',
    noSslData:'Brak danych',
  }
};

// ── Intel badge hover popups ─────────────────────────────────
// Single body-level popup div, content stored in JS Map (no encoding issues)
const _popupStore = new Map();
let   _popupIdSeq = 0;

function _regPopup(html) {
  const id = 'pp' + (_popupIdSeq++);
  _popupStore.set(id, html);
  return id;
}

(function() {
  const popup = document.getElementById('intel-popup');
  if (!popup) return;

  document.addEventListener('mouseover', function(e) {
    const icon = e.target.closest('[data-pid]');
    if (!icon) return;
    const html = _popupStore.get(icon.getAttribute('data-pid'));
    if (!html) return;

    popup.innerHTML = html;
    popup.style.display = 'block';

    const rect   = icon.getBoundingClientRect();
    const margin = 10;
    const pw     = Math.max(popup.offsetWidth,  240);
    const ph     = Math.max(popup.offsetHeight, 80);

    let top  = rect.top - ph - margin;
    let left = rect.left + rect.width / 2 - pw / 2;

    if (top < margin) top = rect.bottom + margin;
    if (left < margin) left = margin;
    if (left + pw > window.innerWidth - margin) left = window.innerWidth - pw - margin;

    popup.style.top  = top  + 'px';
    popup.style.left = left + 'px';
  });

  document.addEventListener('mouseout', function(e) {
    const icon = e.target.closest('[data-pid]');
    if (!icon) return;
    if (icon.contains(e.relatedTarget)) return;
    popup.style.display = 'none';
  });
})();

</script>
</body>
</html>