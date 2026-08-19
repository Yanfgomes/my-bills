<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * RF-006 (Despesas — cadastro de despesa), DOM-003.
     * Colunas/tipos conforme arquitetura.modelagem_dados.entidades[tabela=despesas]
     * (aprovado): id uuid, usuario_id (FK -> users, cascade), descricao, valor
     * (CHECK > 0, RN-002 reforcado em banco), categoria (opcional), mes_referencia
     * (formato YYYY-MM validado na camada de app, RN-003/RN-008 — sem CHECK de
     * formato/imutabilidade no SQLite), criado_em/atualizado_em nomeados conforme o
     * contrato aprovado (nao created_at/updated_at padrao do Eloquent). Indice
     * composto (usuario_id, mes_referencia) para RNF-003.
     *
     * Mesmo motivo tecnico ja registrado na migration de fontes_renda: a constraint
     * CHECK precisa nascer dentro do proprio CREATE TABLE porque SQLite nao suporta
     * "ALTER TABLE ... ADD CONSTRAINT" — por isso a tabela e criada via SQL bruto em
     * vez do Schema Builder, mesma excecao pontual a convencoes_migracao.padrao_laravel.
     */
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE despesas (
                id CHAR(36) NOT NULL PRIMARY KEY,
                usuario_id CHAR(36) NOT NULL,
                descricao VARCHAR(120) NOT NULL,
                valor NUMERIC(12, 2) NOT NULL CHECK (valor > 0),
                categoria VARCHAR(60) NULL,
                mes_referencia VARCHAR(7) NOT NULL,
                criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (usuario_id) REFERENCES users (id) ON DELETE CASCADE
            )
        SQL);

        Schema::table('despesas', function ($table) {
            $table->index(['usuario_id', 'mes_referencia']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('despesas');
    }
};
