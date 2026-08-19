<?php

namespace App\Services;

use App\Models\Despesa;
use App\Models\FonteRenda;

/**
 * RF-008 — Overview financeiro, TELA-003.
 * Contrato: arquitetura.documentacao_tecnica.servicos (App\Services\OverviewService::calcular).
 *
 * Overview nao possui tabela/model proprio; agrega DOM-002 (fontes_renda) e DOM-003 (despesas)
 * filtrados por (usuario_id, mes_referencia) — mesmo indice composto ja criado em ambas as
 * migrations (RNF-003).
 */
class OverviewService
{
    /**
     * @param  string  $usuarioId  uuid do usuario autenticado (RN-005). Nunca aceitar valor vindo
     *                             de input externo sem revalidar contra auth()->id() no chamador —
     *                             este metodo confia no valor recebido.
     * @param  string  $mesReferencia  formato YYYY-MM (PARAM-OVW-001).
     */
    public function calcular(string $usuarioId, string $mesReferencia): OverviewData
    {
        $rendaTotal = (float) FonteRenda::query()
            ->where('usuario_id', $usuarioId)
            ->where('mes_referencia', $mesReferencia)
            ->sum('valor_liquido');

        $despesasTotal = (float) Despesa::query()
            ->where('usuario_id', $usuarioId)
            ->where('mes_referencia', $mesReferencia)
            ->sum('valor');

        $saldoDisponivel = $rendaTotal - $despesasTotal;

        // RN-001: renda_total == 0 -> percentual nao calculado (evita divisao por zero).
        $percentualComprometido = $rendaTotal > 0.0
            ? round(($despesasTotal / $rendaTotal) * 100, 1)
            : null;

        // RN-007: despesas > renda -> alerta, percentual exibido sem teto em 100% (ja garantido
        // pelo calculo acima, que nao limita o resultado).
        $alertaComprometimento = $despesasTotal > $rendaTotal;

        return new OverviewData(
            mesReferencia: $mesReferencia,
            rendaTotal: $rendaTotal,
            despesasTotal: $despesasTotal,
            saldoDisponivel: $saldoDisponivel,
            percentualComprometido: $percentualComprometido,
            alertaComprometimento: $alertaComprometimento,
        );
    }
}
