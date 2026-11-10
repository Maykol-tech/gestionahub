{{-- GEST-69: CRUD de proveedores (listar, nuevo, editar, eliminar).
     Uso: @include('admin.partials.proveedores_crud', ['lista' => $proveedoresLista]) --}}
<div class="panel" id="crud-proveedores" style="margin-bottom:16px;">
    <div class="panel-hd">
        <span style="font-weight:700;">Proveedores</span>
        <span class="sub" style="margin-left:8px;" id="prov-total"></span>
        <button type="button" class="btn btn-p btn-sm" style="margin-left:auto;" id="prov-nuevo">+ Nuevo proveedor</button>
    </div>

    <div style="overflow-x:auto;">
        <table class="tbl" style="width:100%;">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>RUC</th>
                    <th>Contacto</th>
                    <th>Teléfono</th>
                    <th>Estado</th>
                    <th style="text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody id="prov-filas"></tbody>
        </table>
    </div>

    <div id="prov-vacio" class="empty" style="padding:18px;" hidden>
        <div style="color:var(--fg3);font-size:13px;">Aún no tienes proveedores. Crea el primero con “+ Nuevo proveedor”.</div>
    </div>
</div>

{{-- Ventana: proveedor (nuevo o editar) --}}
<div class="modal-bg" id="prov-modal" hidden style="position:fixed;inset:0;z-index:60;align-items:center;justify-content:center;padding:16px;">
    <form class="modal" id="prov-form" style="max-width:560px;width:100%;" novalidate>
        <div class="modal-hd">
            <div>
                <div class="modal-ttl" id="prov-modal-ttl">Nuevo proveedor</div>
                <div class="modal-sub">Los datos se usan en las órdenes de compra.</div>
            </div>
            <button type="button" class="icon-btn sm" style="margin-left:auto;" data-prov-cerrar aria-label="Cerrar">✕</button>
        </div>
        <div class="modal-bd">
            <div class="campos-prov">
                <div class="field">
                    <label class="lbl" for="prov-nombre">Nombre *</label>
                    <input class="inp" id="prov-nombre" name="nombre" maxlength="120" required>
                    <div class="ferr" data-err="nombre"></div>
                </div>
                <div class="field">
                    <label class="lbl" for="prov-ruc">RUC</label>
                    <input class="inp" id="prov-ruc" name="ruc" maxlength="11" inputmode="numeric" placeholder="11 dígitos">
                    <div class="ferr" data-err="ruc"></div>
                </div>
                <div class="field">
                    <label class="lbl" for="prov-contacto">Persona de contacto</label>
                    <input class="inp" id="prov-contacto" name="contacto" maxlength="120">
                    <div class="ferr" data-err="contacto"></div>
                </div>
                <div class="field">
                    <label class="lbl" for="prov-telefono">Teléfono</label>
                    <input class="inp" id="prov-telefono" name="telefono" maxlength="30">
                    <div class="ferr" data-err="telefono"></div>
                </div>
                <div class="field">
                    <label class="lbl" for="prov-email">Correo</label>
                    <input class="inp" id="prov-email" name="email" type="email" maxlength="120">
                    <div class="ferr" data-err="email"></div>
                </div>
                <div class="field">
                    <label class="lbl" for="prov-activo">Estado</label>
                    <label style="display:flex;gap:8px;align-items:center;font-size:13px;margin-top:8px;">
                        <input type="checkbox" id="prov-activo" name="activo" checked> Activo
                    </label>
                </div>
            </div>
            <div class="field" style="margin-top:12px;">
                <label class="lbl" for="prov-notas">Notas</label>
                <textarea class="inp" id="prov-notas" name="notas" rows="2" maxlength="500"></textarea>
                <div class="ferr" data-err="notas"></div>
            </div>
            <div class="ferr" id="prov-error-general" style="margin-top:8px;"></div>
        </div>
        <div class="modal-ft">
            <button type="button" class="btn btn-g" data-prov-cerrar>Cancelar</button>
            <button type="submit" class="btn btn-p" id="prov-guardar">Guardar</button>
        </div>
    </form>
</div>

<style>
    #prov-modal[hidden] { display: none !important; }
    .campos-prov { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .campos-prov .ferr { color: #B91C1C; font-size: 12px; margin-top: 4px; }
    .campos-prov .inp.err, #prov-form .inp.err { border-color: #B91C1C !important; }
    .prov-acc { display: flex; gap: 6px; justify-content: flex-end; flex-wrap: wrap; }
    @media (max-width: 720px) { .campos-prov { grid-template-columns: 1fr; } }
</style>

@push('scripts')
<script>
(function () {
    const base = @js(url('admin/proveedores/lista'));
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    let proveedores = @js($lista->values());
    let editandoId = null;

    const $ = (sel) => document.querySelector(sel);
    const esc = (t) => String(t ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const modal = $('#prov-modal');
    const form = $('#prov-form');

    function pintar() {
        const tbody = $('#prov-filas');
        const vacio = $('#prov-vacio');
        $('#prov-total').textContent = proveedores.length + (proveedores.length === 1 ? ' registrado' : ' registrados');
        vacio.hidden = proveedores.length > 0;
        tbody.innerHTML = proveedores.map((p) => `
            <tr data-id="${p.id}">
                <td><b>${esc(p.nombre)}</b>${p.notas ? `<div style="color:var(--fg3);font-size:12px;">${esc(p.notas)}</div>` : ''}</td>
                <td>${esc(p.ruc || '—')}</td>
                <td>${esc(p.contacto || '—')}${p.email ? `<div style="color:var(--fg3);font-size:12px;">${esc(p.email)}</div>` : ''}</td>
                <td>${esc(p.telefono || '—')}</td>
                <td><span class="badge">${p.activo ? 'Activo' : 'Inactivo'}</span></td>
                <td><div class="prov-acc">
                    <button type="button" class="btn btn-g btn-sm" data-editar="${p.id}">Editar</button>
                    <button type="button" class="btn btn-g btn-sm" data-eliminar="${p.id}">Eliminar</button>
                </div></td>
            </tr>`).join('');
    }

    function limpiarErrores() {
        form.querySelectorAll('[data-err]').forEach((el) => (el.textContent = ''));
        form.querySelectorAll('.inp').forEach((el) => el.classList.remove('err'));
        $('#prov-error-general').textContent = '';
    }

    function abrir(p) {
        editandoId = p ? p.id : null;
        limpiarErrores();
        form.reset();
        $('#prov-modal-ttl').textContent = p ? 'Editar proveedor' : 'Nuevo proveedor';
        form.nombre.value = p?.nombre ?? '';
        form.ruc.value = p?.ruc ?? '';
        form.contacto.value = p?.contacto ?? '';
        form.telefono.value = p?.telefono ?? '';
        form.email.value = p?.email ?? '';
        form.notas.value = p?.notas ?? '';
        form.activo.checked = p ? !!p.activo : true;
        modal.hidden = false;
        modal.style.display = 'flex';
        form.nombre.focus();
    }

    function cerrar() {
        modal.hidden = true;
        modal.style.display = 'none';
    }

    async function peticion(url, metodo, cuerpo) {
        const res = await fetch(url, {
            method: metodo,
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: cuerpo ? JSON.stringify(cuerpo) : undefined,
            credentials: 'same-origin',
        });
        const json = await res.json().catch(() => ({}));
        return { ok: res.ok && json.success !== false, status: res.status, json };
    }

    $('#prov-nuevo').addEventListener('click', () => abrir(null));

    document.querySelectorAll('[data-prov-cerrar]').forEach((b) => b.addEventListener('click', cerrar));
    modal.addEventListener('mousedown', (e) => { if (e.target === modal) cerrar(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !modal.hidden) cerrar(); });

    $('#prov-filas').addEventListener('click', async (e) => {
        const editar = e.target.closest('[data-editar]');
        const eliminar = e.target.closest('[data-eliminar]');

        if (editar) {
            abrir(proveedores.find((p) => p.id == editar.dataset.editar));
            return;
        }

        if (eliminar) {
            const p = proveedores.find((x) => x.id == eliminar.dataset.eliminar);
            if (!p || !confirm(`¿Eliminar a ${p.nombre}?`)) return;
            const r = await peticion(`${base}/${p.id}`, 'DELETE');
            if (!r.ok) {
                alert(r.json.message || 'No se pudo eliminar el proveedor.');
                return;
            }
            proveedores = proveedores.filter((x) => x.id !== p.id);
            pintar();
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        limpiarErrores();

        const boton = $('#prov-guardar');
        boton.disabled = true;

        const cuerpo = {
            nombre: form.nombre.value,
            ruc: form.ruc.value,
            contacto: form.contacto.value,
            telefono: form.telefono.value,
            email: form.email.value,
            notas: form.notas.value,
            activo: form.activo.checked,
        };

        const url = editandoId ? `${base}/${editandoId}` : base;
        const r = await peticion(url, editandoId ? 'PUT' : 'POST', cuerpo);
        boton.disabled = false;

        if (!r.ok) {
            const errores = r.json.errors || {};
            if (Object.keys(errores).length) {
                Object.entries(errores).forEach(([campo, msgs]) => {
                    const el = form.querySelector(`[data-err="${campo}"]`);
                    if (el) el.textContent = msgs[0];
                    const input = form.querySelector(`[name="${campo}"]`);
                    if (input) input.classList.add('err');
                });
            } else {
                $('#prov-error-general').textContent = r.json.message || 'No se pudo guardar. Intenta de nuevo.';
            }
            return;
        }

        const guardado = r.json.proveedor;
        if (editandoId) {
            proveedores = proveedores.map((p) => (p.id === guardado.id ? guardado : p));
        } else {
            proveedores = [...proveedores, guardado].sort((a, b) => a.nombre.localeCompare(b.nombre));
        }
        pintar();
        cerrar();
    });

    pintar();
})();
</script>
@endpush
