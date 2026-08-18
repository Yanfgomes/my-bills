<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabela unica de auditoria (RNF-PADRAO-LOG-AUDITORIA), capturada via Eloquent Model
     * Observers (arquitetura.padroes_tecnologias.log_auditoria) para todo model de dado de
     * negocio. Imutavel: sem updated_at, sem update/delete permitido por nenhum endpoint.
     */
    public function up(): void
    {
        Schema::create('logs_auditoria', function (Blueprint $table) {
            $table->id();
            $table->enum('acao', ['criacao', 'alteracao', 'exclusao']);
            $table->uuid('usuario_id');
            $table->string('tabela_afetada');
            $table->string('registro_id');
            $table->json('valor_anterior')->nullable();
            $table->json('valor_novo')->nullable();
            $table->timestamp('criado_em')->useCurrent();

            $table->foreign('usuario_id')->references('id')->on('users');
            $table->index(['tabela_afetada', 'registro_id']);
            $table->index('usuario_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs_auditoria');
    }
};
