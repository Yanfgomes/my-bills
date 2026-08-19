<?php

namespace App\Services;

/**
 * RF-008 — Overview financeiro, DOM-004.
 * Contrato: arquitetura.documentacao_tecnica.contratos_dados (OverviewOutput) e
 * arquitetura.documentacao_tecnica.servicos (App\Services\OverviewService::calcular).
 *
 * DTO imutavel de retorno do OverviewService — Overview nao possui tabela/model proprio,
 * apenas agrega FonteRenda/Despesa filtradas por (usuario_id, mes_referencia).
 */
final readonly class OverviewData
{
    public function __construct(
        public string $mesReferencia,
        public float $rendaTotal,
        public float $despesasTotal,
        public float $saldoDisponivel,
        public ?float $percentualComprometido,
        public bool $alertaComprometimento,
    ) {}

    /**
     * Estado vazio (RF-008, criterio de aceite): nenhuma renda nem despesa cadastrada no mes
     * selecionado. Seguro comparar contra 0.0 porque tanto valor_liquido (fontes_renda) quanto
     * valor (despesas) tem CHECK (valor > 0) em banco — soma so pode ser 0 quando nao ha
     * nenhum registro no periodo.
     */
    public function estaVazio(): bool
    {
        return $this->rendaTotal <= 0.0 && $this->despesasTotal <= 0.0;
    }
}
