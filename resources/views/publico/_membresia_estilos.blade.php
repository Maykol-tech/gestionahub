<style>
    :root { --bg:#F3F5F9; --card:#fff; --fg:#111827; --fg2:#4B5563; --fg3:#6B7280; --border:#E5E7EB; --brand:{{ $color }}; --ok:#16A34A; --okl:#DCFCE7; --red:#DC2626; --redl:#FEE2E2; --amb:#B45309; --ambl:#FEF3C7; }
    @media (prefers-color-scheme: dark) { :root { --bg:#0B1220; --card:#121B2C; --fg:#F3F4F6; --fg2:#CBD5E1; --fg3:#94A3B8; --border:#1F2A3D; --okl:#14532D; --redl:#450A0A; --ambl:#451A03; --ok:#4ADE80; --red:#F87171; --amb:#FBBF24; } }
    * { box-sizing:border-box; }
    body { margin:0; font-family:system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; background:var(--bg); color:var(--fg); -webkit-font-smoothing:antialiased; }
    .wrap { max-width:520px; margin:0 auto; padding:18px 16px 40px; padding-top:max(18px, env(safe-area-inset-top)); }
    .neg { display:flex; align-items:center; gap:10px; margin-bottom:16px; }
    .neg img, .neg .ini { width:40px; height:40px; border-radius:10px; object-fit:cover; background:var(--brand); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:14px; }
    .neg b { font-size:15px; }
    .neg span { display:block; font-size:12px; color:var(--fg3); }
    h1 { font-size:22px; margin:4px 0 4px; }
    .sub { color:var(--fg2); font-size:14px; margin:0 0 14px; line-height:1.45; }
    .card { background:var(--card); border:1px solid var(--border); border-radius:18px; padding:16px; margin-top:14px; }
    .card.elegido { border-color:var(--brand); box-shadow:0 0 0 2px color-mix(in srgb, var(--brand) 30%, transparent); }
    .ttl { font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.07em; color:var(--fg3); margin-bottom:10px; }
    .precio { font-size:28px; font-weight:900; font-variant-numeric:tabular-nums; }
    .precio small { font-size:14px; font-weight:600; color:var(--fg3); }
    ul.ben { list-style:none; padding:0; margin:10px 0 0; }
    ul.ben li { font-size:14px; padding:4px 0 4px 24px; position:relative; color:var(--fg2); }
    ul.ben li::before { content:'✓'; position:absolute; left:2px; color:var(--ok); font-weight:800; }
    .btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; width:100%; padding:13px 16px; border-radius:12px; border:0; font-size:15px; font-weight:700; cursor:pointer; text-decoration:none; background:var(--brand); color:#fff; font-family:inherit; margin-top:12px; }
    .btn.sec { background:transparent; color:var(--fg); border:1px solid var(--border); }
    label { display:block; font-size:13px; font-weight:600; margin:12px 0 5px; color:var(--fg2); }
    .inp { width:100%; padding:11px 12px; border-radius:10px; border:1px solid var(--border); background:var(--card); color:var(--fg); font-size:15px; font-family:inherit; }
    .inp:focus { outline:2px solid color-mix(in srgb, var(--brand) 45%, transparent); border-color:var(--brand); }
    .err { color:var(--red); font-size:12px; margin-top:4px; }
    .alerta { padding:12px 14px; border-radius:12px; font-size:14px; margin-top:12px; line-height:1.4; }
    .alerta.ok { background:var(--okl); color:var(--ok); }
    .alerta.mal { background:var(--redl); color:var(--red); }
    .alerta.info { background:var(--ambl); color:var(--amb); }
    .chip { display:inline-block; font-size:12px; font-weight:800; padding:4px 10px; border-radius:99px; white-space:nowrap; }
    .chip.activa, .chip.pagado { background:var(--okl); color:var(--ok); }
    .chip.pendiente, .chip.por_revisar { background:var(--ambl); color:var(--amb); }
    .chip.vencida, .chip.vencido { background:var(--redl); color:var(--red); }
    .chip.cancelada, .chip.anulado { background:var(--bg); color:var(--fg3); }
    .pie { text-align:center; font-size:12px; color:var(--fg3); margin-top:18px; line-height:1.5; }
</style>
