<!-- MODAL QR -->
<div id="modalQR" style="display: none !important; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); z-index: 9999; justify-content: center; align-items: center; backdrop-filter: blur(4px);">
    <div style="background: #fff; width: 100%; max-width: 380px; border-radius: 20px; padding: 32px 24px; text-align: center; position: relative; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <button onclick="document.getElementById('modalQR').style.display='none'" style="position: absolute; top: 16px; right: 16px; background: #F1F5F9; border: none; width: 32px; height: 32px; border-radius: 50%; font-size: 16px; cursor: pointer;">&times;</button>
        <h3 id="qrTitulo" style="font-size: 24px; font-weight: 800; margin-bottom: 4px;">Mesa</h3>
        <p style="font-size: 13px; color: #64748B; margin-bottom: 24px;">Escanea para ver el menú</p>
        <div id="qrContenedor" style="display: flex; justify-content: center; align-items: center; min-height: 250px; background: #F8FAFC; border-radius: 16px; padding: 16px; margin-bottom: 24px; border: 1px solid #E2E8F0;"></div>
        <button onclick="window.print()" class="btn btn-p" style="width: 100%; padding: 14px; font-weight: 700; border-radius: 12px;">Imprimir QR</button>
    </div>
</div>

<!-- MODAL NUEVA MESA -->
<div id="modalNuevaMesa" style="display: none !important; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); z-index: 9999; justify-content: center; align-items: center; backdrop-filter: blur(4px);">
    <div style="background: #fff; width: 100%; max-width: 450px; border-radius: 16px; padding: 32px; position: relative;">
        <button onclick="document.getElementById('modalNuevaMesa').style.display='none'" style="position: absolute; top: 16px; right: 16px; background: #F1F5F9; border: none; width: 32px; height: 32px; border-radius: 50%; font-size: 16px; cursor: pointer;">&times;</button>
        <h3 style="font-size: 20px; font-weight: 800; margin-bottom: 24px;">Agregar Nueva Mesa</h3>
        <form action="{{ route('admin.mesas.store') }}" method="POST" x-data="{ nombre: '', zona: 'Salón', capacidad: 4 }">
            @csrf
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Número de Mesa</label>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-weight: 700; color: #94A3B8;">Mesa</span>
                    <input type="text" name="nombre" x-model="nombre" required placeholder="Ej: 1" style="width: 100%; padding: 12px; border: 1px solid #E2E8F0; border-radius: 8px; font-weight: 700;">
                </div>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Zona</label>
                <select name="zona" x-model="zona" style="width: 100%; padding: 12px; border: 1px solid #E2E8F0; border-radius: 8px;">
                    <option value="Salón">Salón</option>
                    <option value="Terraza">Terraza</option>
                    <option value="Barra">Barra</option>
                </select>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="document.getElementById('modalNuevaMesa').style.display='none'" class="btn btn-g">Cancelar</button>
                <button type="submit" class="btn btn-p">Guardar Mesa</button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModalQR(nombre, url) {
        document.getElementById('qrTitulo').innerText = nombre;
        document.getElementById('qrContenedor').innerHTML = 'Cargando...';
        document.getElementById('modalQR').style.setProperty('display', 'flex', 'important');
        fetch(url).then(r => r.json()).then(d => document.getElementById('qrContenedor').innerHTML = d.svg);
    }
</script>