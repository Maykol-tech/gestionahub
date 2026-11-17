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
Schedule::command('citas:lista-espera')->everyFiveMinutes();
Schedule::command('campanas:boletin-inactivos')->weeklyOn(1, '09:00');
Schedule::command('insumos:alerta-stock')->dailyAt('08:00');
Schedule::command('carritos:recordar')->everyFiveMinutes();
// GEST-91..96: avisos de prueba, renovación con tarjeta y checkouts sin pagar
Schedule::command('prueba:avisos')->dailyAt('09:00');
Schedule::command('suscripciones:cobrar')->hourly();
Schedule::command('checkouts:abandonados')->everyTenMinutes();
Schedule::command('membresias:procesar')->dailyAt('08:30');
