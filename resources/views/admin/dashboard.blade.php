<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GestionaHub - Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #FFFFFF;
            --nav-bg: #2B6EF6;
            --cta: #F47B20;
            --surface: #F8FAFC;
            --border: #E2E8F0;
            --text: #1E293B;
            --text2: #475569;
        }
        body { margin: 0; font-family: 'Inter', sans-serif; display: flex; background: var(--surface); color: var(--text); }
        
        .sidebar { width: 240px; background: var(--nav-bg); color: white; min-height: 100vh; padding: 20px 0; display: flex; flex-direction: column; }
        .sidebar h2 { text-align: center; margin: 0 0 30px 0; font-size: 20px; font-weight: 700; }
        .menu-label { padding: 10px 24px; font-size: 11px; text-transform: uppercase; opacity: 0.7; font-weight: 600; margin-top: 15px; letter-spacing: 0.5px; }
        .menu-item { padding: 10px 24px; cursor: pointer; font-size: 14px; font-weight: 500; transition: background 0.2s; }
        .menu-item:hover, .menu-item.active { background: rgba(255,255,255,0.15); border-left: 3px solid white; }
        
        .content { flex: 1; padding: 30px; display: flex; flex-direction: column; gap: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 24px; font-weight: 700; margin: 0; }
        .btn-cta { background: var(--cta); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px; }
        .btn-cta:hover { background: #d96a10; }
        
        .cards-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .card { background: var(--bg); padding: 24px; border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 1px 4px rgba(0,0,0,0.05); }
        .card h3 { margin: 0 0 10px 0; font-size: 14px; color: var(--text2); font-weight: 500; }
        .card .value { font-size: 28px; font-weight: 700; color: var(--text); }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>RC El Clásico</h2>
        
        <div class="menu-label">Principal</div>
        <div class="menu-item active">Inicio</div>
        <div class="menu-item">Pedidos</div>
        <div class="menu-item">Productos</div>
        <div class="menu-item">Clientes</div>
        <div class="menu-item">Fidelización</div>
        <div class="menu-item">Servicios</div>
        
        <div class="menu-label">Control</div>
        <div class="menu-item">Caja del día</div>
        <div class="menu-item">Reportes</div>
        <div class="menu-item">Cobros y Pagos</div>
        <div class="menu-item">Repartos</div>
        <div class="menu-item">Producción</div>
        <div class="menu-item">Publicidad</div>
        <div class="menu-item">Proveedores</div>

        <div class="menu-label">Presencia</div>
        <div class="menu-item">Mi Vitrina</div>
        <div class="menu-item">Tienda pública</div>
        <div class="menu-item">Bot WhatsApp</div>
        <div class="menu-item">Prospectar</div>

        <div class="menu-label">Cuenta</div>
        <div class="menu-item">Sucursales</div>
        <div class="menu-item">Planes y facturación</div>
        <div class="menu-item">Personalizar</div>
    </div>

    <div class="content">
        <div class="header">
            <h1>Buenos días, Maykol</h1>
            <button class="btn-cta">+ Nuevo producto</button>
        </div>
        
        <div class="cards-grid">
            <div class="card">
                <h3>Ventas hoy</h3>
                <div class="value">S/ 342</div>
            </div>
            <div class="card">
                <h3>Pedidos</h3>
                <div class="value">8</div>
            </div>
            <div class="card">
                <h3>Clientes nuevos</h3>
                <div class="value">3</div>
            </div>
            <div class="card">
                <h3>Gasto publicidad</h3>
                <div class="value">S/ 80 <span style="font-size:14px; color:#16A34A;">(ROI 4.3x)</span></div>
            </div>
        </div>
    </div>
</body>
</html>
