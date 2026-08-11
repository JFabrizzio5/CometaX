<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Amarra las horas a un hito/incidencia y le da cuerpo a los hitos.
 *
 * Antes: `milestones` era solo etiqueta + estado, `time_entries` no colgaba de
 * nada y nadie la escribía, y `projects.hours_used` se tecleaba a mano. Con esto
 * el reporte por horas se puede agrupar por fase → hito → actividad.
 *
 * Las FK se agregan solo fuera de SQLite: ALTER TABLE de SQLite no soporta
 * ADD CONSTRAINT y forzarlo obliga a reconstruir la tabla. Producción es MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        $soportaFk = DB::connection()->getDriverName() !== 'sqlite';

        Schema::table('milestones', function (Blueprint $table) {
            // Agrupador libre ("Fase 1 — Descubrimiento"). Texto y no tabla aparte:
            // las fases cambian de nombre por proyecto y no se reutilizan entre clientes.
            $table->string('phase')->nullable()->after('project_id');
            $table->text('description')->nullable()->after('label');
            $table->date('starts_on')->nullable()->after('status');
            $table->date('due_on')->nullable()->after('starts_on');
            $table->decimal('hours_budgeted', 8, 2)->default(0)->after('due_on');

            $table->index(['project_id', 'phase']);
        });

        Schema::table('time_entries', function (Blueprint $table) use ($soportaFk) {
            $table->unsignedBigInteger('milestone_id')->nullable()->after('consultant_id');
            $table->unsignedBigInteger('incident_id')->nullable()->after('milestone_id');

            // analisis|backend|frontend|qa_manual|qa_automatizado|despliegue|reunion|soporte|otro
            $table->string('category')->default('otro')->after('activity');

            // medido   → cronometrado / capturado en el momento
            // reconstruido → desglose retroactivo de trabajo real no registrado
            // Se guarda para trazabilidad interna; no se muestra al cliente.
            $table->string('source')->default('medido')->after('category');

            $table->boolean('billable')->default(true)->after('hours');

            // Lote de generación: permite deshacer un desglose completo de un golpe.
            $table->uuid('batch_id')->nullable()->after('billable');

            $table->index(['project_id', 'entry_date']);
            $table->index('milestone_id');
            $table->index('batch_id');

            if ($soportaFk) {
                $table->foreign('milestone_id')->references('id')->on('milestones')->nullOnDelete();
                $table->foreign('incident_id')->references('id')->on('incidents')->nullOnDelete();
            }
        });

        Schema::table('project_activities', function (Blueprint $table) use ($soportaFk) {
            $table->unsignedBigInteger('milestone_id')->nullable()->after('project_id');
            $table->index('milestone_id');

            if ($soportaFk) {
                $table->foreign('milestone_id')->references('id')->on('milestones')->nullOnDelete();
            }
        });

        Schema::table('incidents', function (Blueprint $table) use ($soportaFk) {
            $table->unsignedBigInteger('milestone_id')->nullable()->after('project_id');
            $table->index('milestone_id');

            if ($soportaFk) {
                $table->foreign('milestone_id')->references('id')->on('milestones')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        $soportaFk = DB::connection()->getDriverName() !== 'sqlite';

        Schema::table('incidents', function (Blueprint $table) use ($soportaFk) {
            if ($soportaFk) {
                $table->dropForeign(['milestone_id']);
            }
            $table->dropIndex(['milestone_id']);
            $table->dropColumn('milestone_id');
        });

        Schema::table('project_activities', function (Blueprint $table) use ($soportaFk) {
            if ($soportaFk) {
                $table->dropForeign(['milestone_id']);
            }
            $table->dropIndex(['milestone_id']);
            $table->dropColumn('milestone_id');
        });

        Schema::table('time_entries', function (Blueprint $table) use ($soportaFk) {
            if ($soportaFk) {
                $table->dropForeign(['milestone_id']);
                $table->dropForeign(['incident_id']);
            }
            $table->dropIndex(['project_id', 'entry_date']);
            $table->dropIndex(['milestone_id']);
            $table->dropIndex(['batch_id']);
            $table->dropColumn(['milestone_id', 'incident_id', 'category', 'source', 'billable', 'batch_id']);
        });

        Schema::table('milestones', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'phase']);
            $table->dropColumn(['phase', 'description', 'starts_on', 'due_on', 'hours_budgeted']);
        });
    }
};
