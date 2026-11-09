<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tareas programadas de GestionaHub
Schedule::command('citas:recordatorios')->everyMinute();
Schedule::command('campanas:enviar-programadas')->everyMinute();
Schedule::command('citas:lista-espera')->everyTenMinutes();
Schedule::command('campanas:boletin-inactivos')->monthlyOn(1, '09:00');
Schedule::command('insumos:alerta-stock')->dailyAt('08:00');
