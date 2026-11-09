{{-- Estilos compartidos por los modulos de Control, Presencia y Cuenta --}}
<style>
.vacio{display:flex;flex-direction:column;align-items:center;text-align:center;padding:44px 20px;gap:4px;}
.vacio-ico{width:52px;height:52px;border-radius:14px;background:var(--surface2);color:var(--fg3);display:flex;align-items:center;justify-content:center;margin-bottom:8px;border:1px solid var(--border);}
.vacio-ttl{font-size:14px;font-weight:600;color:var(--fg);}
.vacio-txt{font-size:13px;color:var(--fg3);max-width:440px;}
.vacio-acc{margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;justify-content:center;}
.tabs{display:flex;gap:4px;padding:0 12px;border-bottom:1px solid var(--border);overflow-x:auto;}
.tab{padding:12px 10px;font-size:13px;font-weight:600;color:var(--fg3);background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-1px;cursor:pointer;font-family:inherit;white-space:nowrap;transition:color .15s,border-color .2s var(--ease);}
.tab:hover{color:var(--fg);}
.tab.on{color:var(--accent);border-bottom-color:var(--accent);}
.tab .n{font-size:11px;font-weight:600;color:var(--fg3);background:var(--surface2);border-radius:99px;padding:1px 7px;margin-left:4px;}
.tab.on .n{background:var(--acl);color:var(--accent);}
.seg{display:inline-flex;border:1px solid var(--border);border-radius:8px;overflow:hidden;background:#fff;}
.seg a{padding:7px 12px;font-size:12px;font-weight:600;color:var(--fg2);border-right:1px solid var(--border);transition:background .15s,color .15s;white-space:nowrap;}
.seg a:last-child{border-right:none;}
.seg a:hover{background:var(--surface2);}
.seg a.on{background:var(--acl);color:var(--accent);}
.g-21{display:grid;grid-template-columns:minmax(0,2fr) minmax(0,1fr);gap:14px;align-items:start;}
.g-11{display:grid;grid-template-columns:1fr 1fr;gap:14px;align-items:start;}
.g-3{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;}
.kv{display:flex;align-items:center;gap:10px;padding:11px 16px;border-bottom:1px solid var(--border);font-size:13px;}
.kv:last-child{border-bottom:none;}
.kv-v{margin-left:auto;font-weight:700;font-variant-numeric:tabular-nums;}
.hbar{height:5px;border-radius:99px;background:var(--surface2);overflow:hidden;margin-top:6px;}
.hbar span{display:block;height:100%;background:var(--accent);border-radius:99px;transform-origin:left;animation:hbarIn .6s var(--ease) backwards;}
@keyframes hbarIn{from{transform:scaleX(0)}}
.pronto{display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--fg3);background:var(--surface2);border:1px solid var(--border);padding:2px 8px;border-radius:99px;white-space:nowrap;}
.nota{display:flex;gap:10px;align-items:flex-start;padding:12px 14px;border:1px solid #C7D9F8;background:#F5F8FF;border-radius:10px;font-size:12px;color:var(--fg2);margin-bottom:16px;}
.nota .ic{color:var(--accent);margin-top:1px;}
.field-row{display:grid;grid-template-columns:1fr 1fr;gap:0 12px;}
.inp-date{width:auto;padding:6px 10px;}
@media (max-width:1100px){.g-21,.g-3{grid-template-columns:1fr;}}
@media (max-width:860px){.g-11,.field-row{grid-template-columns:1fr;}.seg{width:100%;}.seg a{flex:1;text-align:center;}}
</style>
