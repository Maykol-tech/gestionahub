<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GestionaHub - Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --bg: #FFFFFF; --nav-bg: #2B6EF6; --cta: #F47B20; --surface: #F8FAFC;
            --border: #E2E8F0; --text: #1E293B; --text2: #475569; --text3: #94A3B8;
        }
        body { margin: 0; font-family: 'Inter', sans-serif; display: flex; background: var(--surface); color: var(--text); height: 100vh; overflow: hidden; }
        
        .sidebar { width: 250px; background: var(--nav-bg); color: white; display: flex; flex-direction: column; overflow-y: auto; }
        .sidebar-header { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h2 { margin: 0; font-size: 18px; font-weight: 700; }
        .sidebar-header p { margin: 4px 0 0 0; font-size: 11px; opacity: 0.8; }
        
        .menu-group { padding: 15px 0 5px 0; }
        .menu-label { padding: 0 20px; font-size: 10px; text-transform: uppercase; opacity: 0.6; font-weight: 600; margin-bottom: 5px; }
        .menu-item { display: flex; align-items: center; gap: 10px; padding: 10px 20px; font-size: 13px; font-weight: 500; cursor: pointer; color: rgba(255,255,255,0.8); text-decoration: none; }
        .menu-item:hover, .menu-item.active { background: rgba(255,255,255,0.15); color: white; border-left: 3px solid white; padding-left: 17px; }
        .menu-item svg { width: 16px; height: 16px; stroke-width: 2; }
        
        .sidebar-footer { margin-top: auto; padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); font-size: 12px; }
        .sidebar-footer strong { display: block; font-size: 14px; }
        
        .content { flex: 1; padding: 24px 30px; overflow-y: auto; display: flex; flex-direction: column; gap: 24px; }
        .header { display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 22px; font-weight: 700; margin: 0; }
        .btn-cta { background: var(--cta); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px; display: flex; align-items: center; gap: 8px; }
        
        .cards-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
        .card { background: var(--bg); padding: 20px; border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 1px 4px rgba(0,0,0,0.02); }
        .card h3 { margin: 0 0 10px 0; font-size: 11px; color: var(--text2); font-weight: 600; text-transform: uppercase; }
        .card .value { font-size: 28px; font-weight: 700; color: var(--text); }
        .card .subtext { font-size: 12px; margin-top: 8px; }
        .text-green { color: #16A34A; }
        .text-gray { color: var(--text3); }
        
        .ai-summary { background: #F0F7FF; border: 1px solid #D0E2FF; border-radius: 12px; padding: 20px; }
        .ai-summary h3 { margin: 0 0 10px 0; font-size: 14px; color: #1E40AF; display: flex; align-items: center; gap: 8px; }
        .ai-summary ul { margin: 0; padding-left: 20px; font-size: 13px; color: var(--text2); line-height: 1.6; }
        
        .tables-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .table-container { background: var(--bg); border-radius: 12px; border: 1px solid var(--border); overflow: hidden; display: flex; flex-direction: column; min-height: 200px; }
        .table-header { padding: 16px 20px; border-bottom: 1px solid var(--border); font-size: 14px; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; }
        th { padding: 12px 20px; background: var(--surface); color: var(--text2); font-weight: 500; font-size: 11px; text-transform: uppercase; }
        td { padding: 14px 20px; border-bottom: 1px solid var(--border); color: var(--text); }
        
        .empty-state { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--text3); font-size: 13px; padding: 40px; gap: 10px; }
        .empty-state svg { width: 32px; height: 32px; opacity: 0.5; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>GestionaHub</h2>
            <p>Panel de control</p>
        </div>
        <div class="menu-group">
            <div class="menu-label">Principal</div>
            <a class="menu-item active"><i data-lucide="layout-dashboard"></i> Inicio</a>
            <a class="menu-item"><i data-lucide="shopping-cart"></i> Pedidos</a>
            <a class="menu-item"><i data-lucide="package"></i> Productos</a>
            <a class="menu-item"><i data-lucide="users"></i> Clientes</a>
            <a class="menu-item"><i data-lucide="heart"></i> Fidelización</a>
            <a class="menu-item"><i data-lucide="scissors"></i> Servicios</a>
        </div>
        <div class="menu-group">
            <div class="menu-label">Control</div>
            <a class="menu-item"><i data-lucide="briefcase"></i> Caja del día</a>
            <a class="menu-item"><i data-lucide="bar-chart-2"></i> Reportes</a>
            <a class="menu-item"><i data-lucide="credit-card"></i> Cobros y Pagos</a>
            <a class="menu-item"><i data-lucide="truck"></i> Repartos</a>
            <a class="menu-item"><i data-lucide="activity"></i> Producción</a>
            <a class="menu-item"><i data-lucide="megaphone"></i> Publicidad</a>
            <a class="menu-item"><i data-lucide="home"></i> Proveedores</a>
        </div>
        <div class="menu-group">
            <div class="menu-label">Presencia</div>
            <a class="menu-item"><i data-lucide="store"></i> Mi Vitrina</a>
            <a class="menu-item"><i data-lucide="globe"></i> Tienda pública</a>
            <a class="menu-item"><i data-lucide="message-circle"></i> Bot WhatsApp</a>
            <a class="menu-item"><i data-lucide="search"></i> Prospectar</a>
        </div>
        <div class="menu-group">
            <div class="menu-label">Cuenta</div>
            <a class="menu-item"><i data-lucide="map-pin"></i> Sucursales</a>
            <a class="menu-item"><i data-lucide="file-text"></i> Planes y facturación</a>
            <a class="menu-item"><i data-lucide="settings"></i> Personalizar</a>
        </div>
        <div class="sidebar-footer">
            <strong>RC Maykol</strong>
            Plan Completo · S/149/mes
        </div>
    </div>

    <div class="content">
        <div class="header">
            <h1>Buenos días, Maykol</h1>
            <button class="btn-cta"><i data-lucide="plus"></i> Nuevo producto</button>
        </div>
        
        <div class="cards-grid">
            <div class="card">
                <h3>Ventas hoy</h3>
                <div class="value">S/ {{ $datos['ventas_hoy'] }}</div>
                <div class="subtext text-green">↑ 18% vs ayer</div>
            </div>
            <div class="card">
                <h3>Pedidos</h3>
                <div class="value">{{ $datos['pedidos'] }}</div>
                <div class="subtext text-gray">3 pendientes</div>
            </div>
            <div class="card">
                <h3>Clientes nuevos</h3>
                <div class="value">{{ $datos['clientes_nuevos'] }}</div>
                <div class="subtext text-green">↑ esta semana</div>
            </div>
            <div class="card">
                <h3>Gasto publicidad</h3>
                <div class="value">S/ {{ $datos['gasto_publicidad'] }}</div>
                <div class="subtext text-gray">ROI {{ $datos['roi'] }}x</div>
            </div>
        </div>

        <div class="ai-summary">
            <h3><i data-lucide="sparkles"></i> Resumen IA semanal</h3>
            <p style="margin: 0 0 10px 0; font-size: 14px; font-weight: 600; color: var(--text);">Conecta tus ventas para generar el resumen automático.</p>
        </div>

        <div class="tables-grid">
            <div class="table-container">
                <div class="table-header">Últimos pedidos</div>
                <div class="empty-state">
                    <i data-lucide="inbox"></i>
                    <span>Aún no hay pedidos registrados</span>
                </div>
            </div>

            <div class="table-container">
                <div class="table-header">Stock bajo — alerta</div>
                <div class="empty-state">
                    <i data-lucide="check-circle"></i>
                    <span>Todo el stock está en orden</span>
                </div>
            </div>
        </div>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>