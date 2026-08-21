<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * RF-PADRAO-CONFIGURACOES (marco-3-padroes-pipeline), DOM-005. Colunas conforme
     * arquitetura.modelagem_dados.entidades[tabela=configuracoes_usuario] (aprovado):
     * id uuid, usuario_id (FK -> users, cascade, UNIQUE — reforca 1:1 de RN-009), idioma
     * (pt|en|es, default pt), tema (claro|escuro, default claro), tamanho_fonte
     * (pequeno|medio|grande, default medio), alto_contraste (boolean, default false),
     * reducao_movimento (boolean, default false), atualizado_em.
     *
     * Diferente de fontes_renda/despesas, nao ha CHECK de dominio no banco para os enums —
     * mesmo padrao ja usado para mes_referencia (validacao so na camada de app, via
     * ConfiguracaoManager::salvar()); SQLite nao tem tipo enum nativo e o valor permitido nao
     * tem semantica numerica que justifique CHECK. UNIQUE em usuario_id via Schema Builder
     * padrao (sem excecao tecnica como em fontes_renda), por isso criada com o Schema Builder
     * normal, nao SQL bruto.
     */
    public function up(): void
    {
        Schema::create('configuracoes_usuario', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('usuario_id');
            $table->string('idioma', 2)->default('pt');
            $table->string('tema', 7)->default('claro');
            $table->string('tamanho_fonte', 7)->default('medio');
            $table->boolean('alto_contraste')->default(false);
            $table->boolean('reducao_movimento')->default(false);
            $table->dateTime('atualizado_em')->useCurrent();

            $table->unique('usuario_id');
            $table->foreign('usuario_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracoes_usuario');
    }
};
