@extends('layouts.app')

@section('titulo', 'Repartidores')
@section('subtitulo', 'Tu equipo de reparto: altas, datos y pedidos asignados')

@section('contenido')
<div x-data="repartidoresCrud({{ Js::from($sinAsignar) }})" x-cloak>

    <div class="stat-row">
        <div class="stat fade-in">
            <div class="stat-lbl">Repartidores <span class="stat-ico" style="background:var(--acl);color:var(--accent)"><x-icon name="users" size="14" /></span></div>
            <div class="stat-val">{{ $resumen['total'] }}</div>
            <div class="stat-ch neutral">Registrados en tu negocio</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.05s">
            <div class="stat-lbl">Activos <span class="stat-ico" style="background:var(--greenl);color:var(--green)"><x-icon name="check" size="14" /></span></div>
            <div class="stat-val">{{ $resumen['activos'] }}</div>
            <div class="stat-ch neutral">Pueden recibir pedidos</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.1s">
            <div class="stat-lbl">Disponibles ahora <span class="stat-ico" style="background:var(--ambl);color:var(--amber)"><x-icon name="clock" size="14" /></span></div>
            <div class="stat-val">{{ $resumen['disponibles'] }}</div>
            <div class="stat-ch neutral">Activos y sin pausa</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.15s">
            <div class="stat-lbl">Pedidos en ruta <span class="stat-ico" style="background:var(--ctl);color:var(--cta)"><x-icon name="truck" size="14" /></span></div>
            <div class="stat-val">{{ $resumen['en_ruta'] }}</div>
            <div class="stat-ch neutral">Asignados y aún no entregados</div>
        </div>
    </div>

    <div class="panel fade-in" style="animation-delay:.18s">
        <div class="panel-hd" style="flex-wrap:wrap;gap:10px;">
            <div>
                <div class="panel-ttl">Equipo de reparto</div>
                <div class="panel-sub">{{ $repartidores->total() }} {{ $repartidores->total() === 1 ? 'resultado' : 'resultados' }}{{ $q !== '' ? ' para «' . $q . '»' : '' }}</div>
            </div>
            <div style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <form method="GET" action="{{ route('admin.repartidores.crud.index') }}" role="search" style="display:flex;gap:6px;">
                    <input class="inp" type="search" name="q" value="{{ $q }}" maxlength="80" placeholder="Nombre, teléfono o placa" aria-label="Buscar repartidor" style="min-width:210px;">
                    <button class="btn btn-g btn-sm" type="submit"><x-icon name="search" size="14" /> Buscar</button>
                </form>
                <button class="btn btn-p btn-sm" type="button" @click="abrir()"><x-icon name="plus" size="14" stroke="2.4" /> Nuevo repartidor</button>
            </div>
        </div>

        @if ($repartidores->count())
            <div class="tbl-wrap">
                <table class="tbl rep-crud-tbl">
                    <thead>
                        <tr>
                            <th>Repartidor</th>
                            <th>Teléfono</th>
                            <th>Vehículo</th>
                            <th>Placa</th>
                            <th>Estado</th>
                            <th class="num">En ruta</th>
                            <th style="text-align:right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($repartidores as $r)
                            <tr>
                                <td>
                                    <div style="font-weight:600;">{{ $r->nombre }}</div>
                                    @if ($r->dni)<div class="rep-sub">DNI {{ $r->dni }}</div>@endif
                                </td>
                                <td>{{ $r->telefono ?: '—' }}</td>
                                <td>{{ \App\Models\Repartidor::VEHICULOS[$r->vehiculo] ?? ucfirst((string) $r->vehiculo) }}</td>
                                <td style="font-variant-numeric:tabular-nums;">{{ $r->placa ?: '—' }}</td>
                                <td>
                                    @if ($r->activo)
                                        <span class="badge b-ok">Activo</span>
                                    @else
                                        <span class="badge b-n">Inactivo</span>
                                    @endif
                                </td>
                                <td class="num">{{ $r->en_ruta }}</td>
                                <td>
                                    <div class="rep-acc">
                                        <button class="btn btn-g btn-sm" type="button" @click="abrirAsignar({{ Js::from(['id' => $r->id, 'nombre' => $r->nombre, 'activo' => (bool) $r->activo]) }})" @if (! $r->activo) disabled @endif>Asignar pedido</button>
                                        <button class="btn btn-g btn-sm" type="button" @click="abrir({{ Js::from(['id' => $r->id, 'nombre' => $r->nombre, 'telefono' => $r->telefono, 'dni' => $r->dni, 'vehiculo' => $r->vehiculo, 'placa' => $r->placa]) }})">Editar</button>
                                        <button class="btn btn-g btn-sm" type="button" @click="estado({{ Js::from(['id' => $r->id, 'nombre' => $r->nombre, 'activo' => (bool) $r->activo]) }})">{{ $r->activo ? 'Desactivar' : 'Activar' }}</button>
                                        <button class="btn btn-g btn-sm rep-peligro" type="button" @click="eliminar({{ Js::from(['id' => $r->id, 'nombre' => $r->nombre]) }})">Eliminar</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="rep-pag">{{ $repartidores->links() }}</div>
        @else
            <x-vacio icono="users" titulo="{{ $q !== '' ? 'Ningún repartidor coincide con la búsqueda' : 'Aún no tienes repartidores' }}" texto="{{ $q !== '' ? 'Prueba con otro nombre, teléfono o placa.' : 'Agrega el primero con el botón «Nuevo repartidor».' }}" />
        @endif
    </div>

    {{-- ===== Ventana: alta y edicion ===== --}}
    <div class="rep-overlay" x-show="modal" @mousedown.self="cerrar()" @keydown.escape.window="cerrar()" role="dialog" aria-modal="true" aria-labelledby="rep-modal-ttl">
        <form class="rep-dialog" @submit.prevent="guardar()" novalidate>
            <div class="rep-dialog-hd">
                <div>
                    <div class="rep-dialog-ttl" id="rep-modal-ttl" x-text="editandoId ? 'Editar repartidor' : 'Nuevo repartidor'"></div>
                    <div class="rep-sub">Los datos aparecen en los reportes y en la asignación de pedidos.</div>
                </div>
                <button class="icon-btn sm" type="button" style="margin-left:auto;" @click="cerrar()" aria-label="Cerrar">✕</button>
            </div>
            <div class="rep-dialog-bd">
                <div class="rep-campos">
                    <label class="rep-campo rep-ancho">
                        <span>Nombre completo *</span>
                        <input class="inp" type="text" x-model="form.nombre" maxlength="120" autocomplete="off" :aria-invalid="!!errores.nombre">
                        <small class="rep-err" x-text="errores.nombre?.[0] ?? ''"></small>
                    </label>
                    <label class="rep-campo">
                        <span>Teléfono</span>
                        <input class="inp" type="tel" x-model="form.telefono" maxlength="20" autocomplete="off" placeholder="9XX XXX XXX" :aria-invalid="!!errores.telefono">
                        <small class="rep-err" x-text="errores.telefono?.[0] ?? ''"></small>
                    </label>
                    <label class="rep-campo">
                        <span>DNI</span>
                        <input class="inp" type="text" x-model="form.dni" maxlength="12" autocomplete="off" inputmode="numeric" :aria-invalid="!!errores.dni">
                        <small class="rep-err" x-text="errores.dni?.[0] ?? ''"></small>
                    </label>
                    <label class="rep-campo">
                        <span>Tipo de vehículo *</span>
                        <select class="inp" x-model="form.vehiculo">
                            @foreach (\App\Models\Repartidor::VEHICULOS as $clave => $texto)
                                <option value="{{ $clave }}">{{ $texto }}</option>
                            @endforeach
                        </select>
                        <small class="rep-err" x-text="errores.vehiculo?.[0] ?? ''"></small>
                    </label>
                    <label class="rep-campo">
                        <span>Placa</span>
                        <input class="inp" type="text" x-model="form.placa" maxlength="10" autocomplete="off" placeholder="ABC-123" :aria-invalid="!!errores.placa">
                        <small class="rep-err" x-text="errores.placa?.[0] ?? ''"></small>
                    </label>
                </div>
                <div class="rep-err rep-general" x-text="error"></div>
            </div>
            <div class="rep-dialog-ft">
                <button class="btn btn-g" type="button" @click="cerrar()">Cancelar</button>
                <button class="btn btn-p" type="submit" :disabled="cargando"><span x-text="cargando ? 'Guardando…' : (editandoId ? 'Guardar cambios' : 'Agregar repartidor')"></span></button>
            </div>
        </form>
    </div>

    {{-- ===== Ventana: asignar un pedido ===== --}}
    <div class="rep-overlay" x-show="asignando" @mousedown.self="cerrar()" @keydown.escape.window="cerrar()" role="dialog" aria-modal="true" aria-labelledby="rep-asig-ttl">
        <form class="rep-dialog" @submit.prevent="confirmarAsignar()" novalidate>
            <div class="rep-dialog-hd">
                <div>
                    <div class="rep-dialog-ttl" id="rep-asig-ttl">Asignar pedido a <span x-text="repartidor?.nombre"></span></div>
                    <div class="rep-sub">Solo aparecen pedidos de delivery pendientes, confirmados, en preparación o listos.</div>
                </div>
                <button class="icon-btn sm" type="button" style="margin-left:auto;" @click="cerrar()" aria-label="Cerrar">✕</button>
            </div>
            <div class="rep-dialog-bd">
                <template x-if="pedidos.length === 0">
                    <div class="rep-sub">No hay pedidos de delivery pendientes de asignar en este momento.</div>
                </template>
                <template x-if="pedidos.length > 0">
                    <label class="rep-campo">
                        <span>Pedido</span>
                        <select class="inp" x-model="pedidoId">
                            <option value="">Elige un pedido…</option>
                            <template x-for="p in pedidos" :key="p.id">
                                <option :value="p.id" x-text="p.numero + ' · S/ ' + Number(p.total).toFixed(2) + ' · ' + (p.direccion || 'sin dirección')"></option>
                            </template>
                        </select>
                    </label>
                </template>
                <div class="rep-err rep-general" x-text="error"></div>
            </div>
            <div class="rep-dialog-ft">
                <button class="btn btn-g" type="button" @click="cerrar()">Cancelar</button>
                <button class="btn btn-p" type="submit" :disabled="cargando || pedidos.length === 0"><span x-text="cargando ? 'Asignando…' : 'Asignar pedido'"></span></button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
    @include('admin.modulos._base')
    <style>
        .rep-sub{font-size:12px;color:var(--fg3);}
        .rep-acc{display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap;}
        .rep-peligro{color:var(--red);}
        .rep-crud-tbl .num{text-align:right;font-variant-numeric:tabular-nums;}
        .rep-pag{padding:12px 16px;display:flex;justify-content:flex-end;}
        .rep-overlay{position:fixed;inset:0;z-index:70;background:rgba(15,23,42,.45);display:flex;align-items:center;justify-content:center;padding:16px;}
        .rep-dialog{width:100%;max-width:560px;background:var(--surface);border-radius:14px;box-shadow:0 20px 50px rgba(15,23,42,.25);display:flex;flex-direction:column;max-height:calc(100vh - 32px);overflow:hidden;}
        .rep-dialog-hd{display:flex;gap:10px;align-items:flex-start;padding:16px 18px;border-bottom:1px solid var(--border);}
        .rep-dialog-ttl{font-size:16px;font-weight:700;}
        .rep-dialog-bd{padding:16px 18px;overflow-y:auto;}
        .rep-dialog-ft{display:flex;justify-content:flex-end;gap:8px;padding:12px 18px;border-top:1px solid var(--border);background:var(--surface3);}
        .rep-campos{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
        .rep-ancho{grid-column:1 / -1;}
        .rep-campo{display:flex;flex-direction:column;gap:4px;font-size:12px;font-weight:600;color:var(--fg2);}
        .rep-err{color:var(--red);font-size:11px;font-weight:500;}
        .rep-err:empty{display:none;}
        .rep-general{margin-top:10px;font-size:12px;}
        .rep-dialog .inp[aria-invalid="true"]{border-color:var(--red);}
        @media (max-width:600px){ .rep-campos{grid-template-columns:1fr;} .rep-dialog-ft .btn{flex:1;} }
    </style>
@endpush

@push('scripts')
<script>
    window.repartidoresCrud = function (pedidosSinAsignar) {
        const base = @js(url('admin/repartidores/lista'));
        const vacio = () => ({ nombre: '', telefono: '', dni: '', vehiculo: 'moto', placa: '' });

        return {
            modal: false,
            asignando: false,
            editandoId: null,
            repartidor: null,
            form: vacio(),
            pedidos: pedidosSinAsignar || [],
            pedidoId: '',
            errores: {},
            error: '',
            cargando: false,

            aviso(texto, tipo = 'ok') { Alpine.store('toasts').push(texto, tipo); },

            abrir(r = null) {
                this.editandoId = r ? r.id : null;
                this.form = r ? { nombre: r.nombre ?? '', telefono: r.telefono ?? '', dni: r.dni ?? '', vehiculo: r.vehiculo ?? 'moto', placa: r.placa ?? '' } : vacio();
                this.errores = {};
                this.error = '';
                this.asignando = false;
                this.modal = true;
            },

            cerrar() {
                this.modal = false;
                this.asignando = false;
            },

            async guardar() {
                this.cargando = true;
                this.errores = {};
                this.error = '';
                const url = this.editandoId ? `${base}/${this.editandoId}` : base;
                const r = await gh.api(url, { method: this.editandoId ? 'PUT' : 'POST', body: this.form });
                this.cargando = false;
                if (r.ok) {
                    this.aviso(r.data.message || 'Listo');
                    this.modal = false;
                    gh.ir(location.href, false);
                    return;
                }
                this.errores = r.data.errors || {};
                this.error = Object.keys(this.errores).length ? '' : (r.data.message || 'No se pudo guardar. Intenta de nuevo.');
            },

            async estado(r) {
                const res = await gh.api(`${base}/${r.id}/estado`, { method: 'PATCH' });
                if (!res.ok) { this.aviso(res.data.message || 'No se pudo cambiar el estado', 'error'); return; }
                this.aviso(res.data.message);
                gh.ir(location.href, false);
            },

            async eliminar(r) {
                if (!confirm(`¿Eliminar a ${r.nombre}? Esta acción no se puede deshacer.`)) return;
                const res = await gh.api(`${base}/${r.id}`, { method: 'DELETE' });
                if (!res.ok) { this.aviso(res.data.message || 'No se pudo eliminar', 'error'); return; }
                this.aviso(res.data.message);
                gh.ir(location.href, false);
            },

            abrirAsignar(r) {
                this.repartidor = r;
                this.pedidoId = '';
                this.error = '';
                this.modal = false;
                this.asignando = true;
            },

            async confirmarAsignar() {
                if (!this.pedidoId) { this.error = 'Elige un pedido.'; return; }
                this.cargando = true;
                this.error = '';
                const res = await gh.api(`${base}/${this.repartidor.id}/asignar`, { method: 'POST', body: { pedido_id: Number(this.pedidoId) } });
                this.cargando = false;
                if (!res.ok) {
                    this.error = (res.data.errors ? Object.values(res.data.errors)[0][0] : res.data.message) || 'No se pudo asignar.';
                    return;
                }
                this.aviso(res.data.message);
                this.asignando = false;
                gh.ir(location.href, false);
            },
        };
    };
    gh.ready(() => Alpine.data('repartidoresCrud', window.repartidoresCrud));
</script>
@endpush
