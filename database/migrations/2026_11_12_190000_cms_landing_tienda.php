<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GEST-88: contenido editable de la landing SaaS (ambito "saas", tenant_id nulo) y de la tienda
 * de cada negocio (ambito "tienda", con tenant_id): secciones, bloques, preguntas, testimonios y logos.
 * También agrega a los leads lo que necesita la bandeja (GEST-90). Solo agrega; no borra nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        $comunes = function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id')->nullable()->index();
            $t->string('ambito', 10)->default('saas')->index(); // saas | tienda
            $t->unsignedInteger('orden')->default(0);
            $t->boolean('visible')->default(true);
        };

        if (! Schema::hasTable('cms_secciones')) {
            Schema::create('cms_secciones', function (Blueprint $t) use ($comunes) {
                $comunes($t);
                $t->string('clave', 40);              // hero, funciones, precios, testimonios, faq, logos, blog...
                $t->string('titulo', 160)->nullable();
                $t->string('subtitulo', 255)->nullable();
                $t->json('ajustes')->nullable();
                $t->timestamps();
                $t->unique(['ambito', 'tenant_id', 'clave']);
            });
        }

        if (! Schema::hasTable('cms_bloques')) {
            Schema::create('cms_bloques', function (Blueprint $t) use ($comunes) {
                $comunes($t);
                $t->string('seccion', 40)->index();   // a qué sección pertenece (clave)
                $t->string('icono', 40)->nullable();
                $t->string('titulo', 160);
                $t->text('texto')->nullable();
                $t->string('imagen_url', 255)->nullable();
                $t->string('enlace', 255)->nullable();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('cms_faqs')) {
            Schema::create('cms_faqs', function (Blueprint $t) use ($comunes) {
                $comunes($t);
                $t->string('pregunta', 255);
                $t->text('respuesta');
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('cms_testimonios')) {
            Schema::create('cms_testimonios', function (Blueprint $t) use ($comunes) {
                $comunes($t);
                $t->string('nombre', 120);
                $t->string('negocio', 160)->nullable();
                $t->text('texto');
                $t->string('foto_url', 255)->nullable();
                $t->unsignedTinyInteger('estrellas')->default(5);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('cms_logos')) {
            Schema::create('cms_logos', function (Blueprint $t) use ($comunes) {
                $comunes($t);
                $t->string('nombre', 120);
                $t->string('imagen_url', 255)->nullable();
                $t->string('enlace', 255)->nullable();
                $t->timestamps();
            });
        }

        if (Schema::hasTable('landing_leads')) {
            Schema::table('landing_leads', function (Blueprint $t) {
                if (! Schema::hasColumn('landing_leads', 'utm_source')) $t->string('utm_source', 100)->nullable();
                if (! Schema::hasColumn('landing_leads', 'utm_medium')) $t->string('utm_medium', 100)->nullable();
                if (! Schema::hasColumn('landing_leads', 'utm_campaign')) $t->string('utm_campaign', 100)->nullable();
                if (! Schema::hasColumn('landing_leads', 'notas')) $t->text('notas')->nullable();
                if (! Schema::hasColumn('landing_leads', 'tenant_id')) $t->unsignedBigInteger('tenant_id')->nullable(); // si ya se registró
                if (! Schema::hasColumn('landing_leads', 'contactado_at')) $t->timestamp('contactado_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['cms_logos', 'cms_testimonios', 'cms_faqs', 'cms_bloques', 'cms_secciones'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
