{{-- Estilos base del panel (tokens del prototipo GestionaHub_Panel_v3) --}}
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
:root{
--bg:#FFFFFF;--surface:#FFFFFF;--surface2:#F4F6F9;--surface3:#F8FAFC;--border:#E2E7EF;
--fg:#1A2332;--fg2:#4A5568;--fg3:#94A3B8;
--accent:#2B6EF6;--acl:#EEF3FF;--acm:#93B4F9;
--cta:#F47B20;--ctl:#FFF1E6;--ctm:#F9A05C;
--green:#16A34A;--greenl:#DCFCE7;--amber:#D97706;--ambl:#FEF3C7;--red:#DC2626;--redl:#FEE2E2;
--nav:220px;--nav-bg:#2B6EF6;--nav-fg:rgba(255,255,255,.88);--nav-fg2:rgba(255,255,255,.45);
--nav-active:rgba(255,255,255,.15);--nav-hover:rgba(255,255,255,.08);
--shadow:0 1px 2px rgba(26,35,50,.04),0 1px 3px rgba(26,35,50,.06);
--ease:cubic-bezier(.22,1,.36,1);
}
*{box-sizing:border-box;margin:0;padding:0;}
html,body{height:100%;}
body{background:var(--bg);color:var(--fg);font-family:'Inter',system-ui,-apple-system,'Segoe UI',sans-serif;font-size:14px;line-height:1.5;-webkit-font-smoothing:antialiased;}
[x-cloak]{display:none!important;}
.ic{flex:none;}
a{color:inherit;text-decoration:none;}
.app{display:flex;height:100%;}
/* BARRA DE CARGA SUPERIOR */
#gh-progress{position:fixed;top:0;left:0;height:2px;width:0;background:var(--cta);z-index:100;opacity:0;box-shadow:0 0 8px rgba(244,123,32,.6);transition:width .25s var(--ease),opacity .3s;}
/* SIDEBAR */
.nav{width:var(--nav);background:var(--nav-bg);display:flex;flex-direction:column;flex-shrink:0;overflow-y:auto;z-index:40;transition:transform .3s var(--ease);}
.nav::-webkit-scrollbar{width:4px}.nav::-webkit-scrollbar-thumb{background:rgba(255,255,255,.2);border-radius:4px}
.nav-logo{padding:16px 16px 12px;border-bottom:1px solid rgba(255,255,255,.10);}
.nav-brand{font-size:17px;font-weight:700;color:#fff;letter-spacing:-.4px;display:block;}
.nav-brand small{display:block;font-size:10px;font-weight:400;color:var(--nav-fg2);margin-top:1px;letter-spacing:0;}
.nav-sec{padding:10px 0 2px;}
.nav-sec-lbl{font-size:10px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,.28);padding:0 14px 5px;}
.ni{display:flex;align-items:center;gap:9px;padding:7px 14px;cursor:pointer;color:var(--nav-fg);font-size:13px;font-weight:500;position:relative;transition:background .15s,color .15s;}
.ni:hover{background:var(--nav-hover);color:#fff;}
.ni.on{background:var(--nav-active);color:#fff;}
.ni.on::before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:3px;height:24px;background:#fff;border-radius:0 3px 3px 0;animation:barIn .3s var(--ease);}
@keyframes barIn{from{height:0}to{height:24px}}
.ni .ic{stroke:rgba(255,255,255,.55);transition:stroke .15s,transform .2s var(--ease);}
.ni:hover .ic,.ni.on .ic{stroke:#fff;}
.ni:hover .ic{transform:translateX(1px);}
.ni-badge{margin-left:auto;background:var(--cta);color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:99px;}
.ni-soon{margin-left:auto;width:6px;height:6px;border-radius:50%;background:rgba(255,255,255,.25);}
.nav-foot{margin-top:auto;padding:12px 14px;border-top:1px solid rgba(255,255,255,.10);}
.nav-usr{display:flex;align-items:center;gap:8px;}
.nav-out{width:30px;height:30px;border:none;border-radius:8px;background:transparent;color:rgba(255,255,255,.75);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background .15s,color .15s;}
.nav-out:hover{background:rgba(255,255,255,.14);color:#fff;}
.nav-av{width:30px;height:30px;border-radius:50%;background:rgba(255,255,255,.20);color:#fff;font-weight:700;font-size:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.nav-un{font-size:12px;font-weight:600;color:rgba(255,255,255,.88);}
.nav-up{font-size:10px;color:rgba(255,255,255,.45);}
.nav-overlay{display:none;}
/* MAIN + TOPBAR */
.main{flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0;transition:opacity .15s;}
.main.saliendo{opacity:.55;pointer-events:none;}
.topbar{background:var(--surface);border-bottom:1px solid var(--border);padding:0 20px;height:56px;display:flex;align-items:center;gap:10px;flex-shrink:0;position:relative;z-index:20;}
.topbar-ttl{font-size:15px;font-weight:700;letter-spacing:-.2px;}
.topbar-sub{font-size:12px;color:var(--fg3);}
.tba{margin-left:auto;display:flex;gap:8px;align-items:center;}
.icon-btn.menu-btn{display:none;}
.content{flex:1;overflow-y:auto;padding:20px;background:#fff;animation:pageIn .35s var(--ease);}
@keyframes pageIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
/* CAMPANITA */
.icon-btn{position:relative;width:36px;height:36px;border-radius:8px;border:1px solid var(--border);background:#fff;color:var(--fg2);display:inline-flex;align-items:center;justify-content:center;cursor:pointer;transition:background .15s,color .15s,transform .15s;}
.icon-btn:hover{background:var(--surface2);color:var(--fg);}
.icon-btn:active{transform:scale(.95);}
.icon-btn.sm{width:30px;height:30px;border-radius:7px;}
.bell-dot{position:absolute;top:-5px;right:-5px;min-width:18px;height:18px;padding:0 5px;border-radius:99px;background:var(--cta);color:#fff;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;border:2px solid #fff;}
.bell-ring{animation:ring 2.4s ease-in-out 1s 1;transform-origin:50% 0;}
@keyframes ring{0%,100%{transform:rotate(0)}10%{transform:rotate(14deg)}20%{transform:rotate(-12deg)}30%{transform:rotate(8deg)}40%{transform:rotate(-6deg)}50%{transform:rotate(0)}}
.dropdown{position:absolute;right:0;top:44px;width:330px;background:#fff;border:1px solid var(--border);border-radius:12px;box-shadow:0 12px 32px rgba(26,35,50,.14);overflow:hidden;transform-origin:top right;}
.dd-hd{padding:12px 14px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;font-weight:700;font-size:13px;}
.dd-item{display:flex;gap:10px;padding:11px 14px;border-bottom:1px solid var(--border);transition:background .12s;}
.dd-item:hover{background:var(--surface2);}
.dd-item:last-child{border-bottom:none;}
.dd-ico{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.dd-ttl{font-size:12px;font-weight:600;}
.dd-txt{font-size:11px;color:var(--fg3);}
.dd-empty{padding:28px 14px;text-align:center;color:var(--fg3);font-size:12px;}
.top-av{width:34px;height:34px;border-radius:50%;background:var(--acl);color:var(--accent);font-weight:700;font-size:12px;display:flex;align-items:center;justify-content:center;}
/* BOTONES */
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:background .15s,opacity .15s,transform .15s,box-shadow .15s;white-space:nowrap;font-family:inherit;line-height:1.4;}
.btn:active{transform:scale(.97);}
.btn:disabled{opacity:.6;cursor:not-allowed;}
.btn-p{background:var(--cta);color:#fff;box-shadow:0 1px 2px rgba(244,123,32,.25);}
.btn-p:hover{background:#E06A12;box-shadow:0 4px 12px rgba(244,123,32,.28);}
.btn-g{background:#fff;color:var(--fg2);border:1px solid var(--border);}
.btn-g:hover{background:var(--surface2);color:var(--fg);}
.btn-sm{padding:4px 10px;font-size:11px;border-radius:6px;}
.btn-wa{background:#25D366;color:#fff;}
.btn-wa:hover{opacity:.88;}
/* TARJETAS / KPI */
.card{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:16px;}
.stat-row{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px;}
.stat{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:14px 16px;transition:transform .25s var(--ease),box-shadow .25s var(--ease),border-color .25s;}
.stat:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(26,35,50,.08);border-color:var(--acm);}
.stat-lbl{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--fg3);margin-bottom:6px;display:flex;align-items:center;justify-content:space-between;}
.stat-val{font-size:22px;font-weight:700;font-variant-numeric:tabular-nums;letter-spacing:-.3px;}
.stat-ch{font-size:11px;margin-top:3px;}
.stat-ico{width:26px;height:26px;border-radius:7px;display:flex;align-items:center;justify-content:center;}
.up{color:var(--accent);} .dn{color:var(--red);} .neutral{color:var(--fg3);}
/* BADGES */
.badge{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:99px;font-size:11px;font-weight:600;white-space:nowrap;}
.badge .dot{width:6px;height:6px;border-radius:50%;background:currentColor;}
.b-g{background:var(--acl);color:var(--accent);}
.b-ok{background:var(--greenl);color:var(--green);}
.b-a{background:var(--ambl);color:var(--amber);}
.b-r{background:var(--redl);color:var(--red);}
.b-n{background:var(--surface2);color:var(--fg3);}
/* TABLA CORPORATIVA */
.panel{background:#fff;border:1px solid var(--border);border-radius:10px;box-shadow:var(--shadow);overflow:hidden;}
.panel-hd{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:12px 16px;border-bottom:1px solid var(--border);}
.panel-ttl{font-size:13px;font-weight:700;}
.panel-sub{font-size:12px;color:var(--fg3);}
.panel-ft{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 16px;border-top:1px solid var(--border);font-size:12px;color:var(--fg3);background:var(--surface3);}
.tbl{width:100%;border-collapse:collapse;}
.tbl th{text-align:left;font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--fg3);padding:10px 14px;border-bottom:1px solid var(--border);background:var(--surface3);white-space:nowrap;user-select:none;}
.tbl th.sortable{cursor:pointer;transition:color .15s;}
.tbl th.sortable:hover,.tbl th.sorted{color:var(--fg);}
.tbl th .ic{vertical-align:-2px;margin-left:2px;opacity:.6;}
.tbl td{padding:11px 14px;border-bottom:1px solid var(--border);font-size:13px;vertical-align:middle;transition:background .15s;}
.tbl tbody tr:last-child td{border-bottom:none;}
.tbl tbody tr:hover td{background:#FAFBFD;}
.tbl .num{text-align:right;font-variant-numeric:tabular-nums;}
.tbl-wrap{overflow-x:auto;}
.row-new td{animation:rowFlash 2.2s var(--ease);}
@keyframes rowFlash{0%{background:var(--ctl)}100%{background:transparent}}
.fade-in{animation:fadeUp .35s var(--ease) backwards;}
@keyframes fadeUp{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
.row-in td{animation:fadeUp .35s var(--ease) backwards;animation-delay:var(--d,0ms);}
.pager{display:flex;gap:4px;align-items:center;}
.pager button{min-width:28px;height:28px;border-radius:6px;border:1px solid var(--border);background:#fff;color:var(--fg2);font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;justify-content:center;transition:background .15s;}
.pager button:hover:not(:disabled){background:var(--surface2);}
.pager button.on{background:var(--accent);border-color:var(--accent);color:#fff;}
.pager button:disabled{opacity:.45;cursor:default;}
.skel{background:linear-gradient(90deg,var(--surface2) 25%,#EBEFF5 50%,var(--surface2) 75%);background-size:200% 100%;animation:skel 1.2s linear infinite;border-radius:6px;height:12px;}
@keyframes skel{from{background-position:200% 0}to{background-position:-200% 0}}
/* CHIPS / INPUTS */
.chips{display:flex;gap:6px;flex-wrap:wrap;}
.chip{padding:5px 12px;border-radius:99px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid var(--border);background:#fff;color:var(--fg2);transition:all .18s var(--ease);font-family:inherit;}
.chip.on{background:var(--accent);color:#fff;border-color:var(--accent);}
.chip:hover:not(.on){background:var(--surface2);}
.chip .n{opacity:.7;font-weight:500;margin-left:2px;}
.inp{width:100%;padding:8px 11px;border:1px solid var(--border);border-radius:7px;font-size:13px;background:#fff;color:var(--fg);font-family:inherit;transition:border-color .15s,box-shadow .15s;}
.inp:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px var(--acl);}
.inp.err{border-color:var(--red);box-shadow:0 0 0 3px var(--redl);}
select.inp{appearance:none;background-image:linear-gradient(45deg,transparent 50%,#94A3B8 50%),linear-gradient(135deg,#94A3B8 50%,transparent 50%);background-position:calc(100% - 15px) 50%,calc(100% - 10px) 50%;background-size:5px 5px;background-repeat:no-repeat;padding-right:28px;}
.search{position:relative;}
.search .ic{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--fg3);pointer-events:none;}
.search .inp{padding-left:32px;width:230px;}
.lbl{display:block;font-size:12px;font-weight:600;color:var(--fg2);margin-bottom:5px;}
.req{color:var(--red);}
.hint{font-size:11px;color:var(--fg3);margin-top:4px;}
.ferr{font-size:11px;color:var(--red);margin-top:4px;}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:0 12px;}
.field{margin-bottom:12px;}
.two{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.sec-ttl{font-size:13px;font-weight:700;margin-bottom:10px;}
/* MODAL (fade + escala suave) */
.modal-bg{position:fixed;inset:0;background:rgba(15,23,42,.45);backdrop-filter:blur(3px);z-index:60;display:flex;align-items:flex-start;justify-content:center;padding:6vh 16px;overflow-y:auto;}
.modal{background:#fff;border-radius:12px;width:100%;max-width:560px;box-shadow:0 24px 60px rgba(15,23,42,.25);overflow:hidden;}
.modal-hd{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;}
.modal-ico{width:36px;height:36px;border-radius:9px;background:var(--ctl);color:var(--cta);display:flex;align-items:center;justify-content:center;}
.modal-ttl{font-size:15px;font-weight:700;}
.modal-sub{font-size:12px;color:var(--fg3);}
.modal-bd{padding:18px 20px;}
.modal-ft{padding:14px 20px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:8px;background:var(--surface3);}
.form-sec{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--fg3);margin:4px 0 10px;padding-bottom:6px;border-bottom:1px solid var(--border);}
.m-enter{transition:opacity .2s var(--ease);} .m-enter .modal{transition:transform .2s var(--ease),opacity .2s var(--ease);}
.m-leave{transition:opacity .2s var(--ease);} .m-leave .modal{transition:transform .2s var(--ease),opacity .2s var(--ease);}
.m-from{opacity:0;} .m-from .modal{transform:scale(.97);opacity:0;}
.m-to{opacity:1;} .m-to .modal{transform:none;opacity:1;}
.spin{animation:spin .8s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}
/* TOASTS */
.toasts{position:fixed;right:20px;bottom:20px;z-index:80;display:flex;flex-direction:column;gap:8px;}
.toast{display:flex;align-items:center;gap:10px;min-width:260px;max-width:360px;padding:11px 14px;border-radius:10px;background:#1A2332;color:#fff;font-size:13px;font-weight:500;box-shadow:0 10px 30px rgba(15,23,42,.3);}
.toast .tdot{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
/* VACIOS / CARGA */
.empty{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;padding:48px 20px;color:var(--fg3);font-size:13px;text-align:center;}
.empty .ic{opacity:.6;}
.loadbar{height:2px;background:linear-gradient(90deg,transparent,var(--accent),transparent);background-size:200% 100%;animation:load 1s linear infinite;}
@keyframes load{from{background-position:200% 0}to{background-position:-200% 0}}
@media (prefers-reduced-motion:reduce){*,*::before,*::after{animation-duration:.01ms!important;animation-delay:0ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important;transition-delay:0ms!important;}.stat:hover,.kcard:hover,.mod-feat:hover{transform:none!important;}}
@media (max-width:1100px){.stat-row{grid-template-columns:repeat(2,1fr);}.two{grid-template-columns:1fr;}}
@media (max-width:860px){
.nav{position:fixed;inset:0 auto 0 0;transform:translateX(-100%);}
.nav.open{transform:none;box-shadow:12px 0 40px rgba(0,0,0,.2);}
.nav-overlay{display:block;position:fixed;inset:0;background:rgba(15,23,42,.4);z-index:30;}
.icon-btn.menu-btn{display:inline-flex;}
.topbar{height:auto;min-height:56px;flex-wrap:wrap;padding:10px 16px;}
.tba{width:100%;margin-left:0;}
.search{flex:1;min-width:0;}
.search .inp{width:100%;}
.top-av,.topbar-sub{display:none;}
.content{padding:16px;}
.tbl-wrap .tbl{min-width:680px;}
.tbl td{white-space:nowrap;}
.grid2{grid-template-columns:1fr;}
.panel-ft{flex-direction:column;align-items:flex-start;}
}
</style>
