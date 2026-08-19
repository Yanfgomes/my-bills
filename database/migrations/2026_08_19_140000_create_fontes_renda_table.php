<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * RF-004 (Renda — cadastro do valor liquido recebido), DOM-002.
     * Colunas/tipos conforme arquitetura.modelagem_dados.entidades[tabela=fontes_renda]
     * (aprovado): id uuid, usuario_id (FK -> users, cascade), descricao, valor_liquido
     * (CHECK > 0, RN-002 reforcado em banco), mes_referencia (formato YYYY-MM validado na
     * camada de app, RN-003/RN-008 — sem CHECK de formato/imutabilidade no SQLite),
     * criado_em/atualizado_em nomeados conforme o contrato aprovado (nao created_at/updated_at
     * padrao do Eloquent). Indice composto (usuario_id, mes_referencia) para RNF-003.
     *
     * A constraint CHECK precisa nascer dentro do proprio CREATE TABLE: SQLite nao suporta
     * "ALTER TABLE ... ADD CONSTRAINT" (unica forma real de adicionar CHECK depois seria
     * recriar a tabela). Por isso a tabela e criada via SQL bruto em vez do Schema Builder
     * (que nao tem helper nativo para CHECK) — unica excecao no projeto a
     * convencoes_migracao.padrao_laravel, pelo motivo tecnico acima.
     */
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE fontes_renda (
                id CHAR(36) NOT NULL PRIMARY KEY,
                usuario_id CHAR(36) NOT NULL,
                descricao VARCHAR(120) NOT NULL,
                valor_liquido NUMERIC(12, 2) NOT NULL CHECK (valor_liquido > 0),
                mes_referencia VARCHAR(7) NOT NULL,
                criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (usuario_id) REFERENCES users (id) ON DELETE CASCADE
            )
        SQL);

        Schema::table('fontes_renda', function ($table) {
            $table->index(['usuario_id', 'mes_referencia']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fontes_renda');
    }
};
