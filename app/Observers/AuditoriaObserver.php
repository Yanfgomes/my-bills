<?php

namespace App\Observers;

use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Observer generico de auditoria (arquitetura.padroes_tecnologias.log_auditoria), registrado
 * no AppServiceProvider para todo model de dado de negocio (User, e os models de dominio
 * adicionados em marcos futuros — FonteRenda, Despesa). Escuta created/updated/deleted e
 * grava o registro correspondente em logs_auditoria. Qualquer escrita que contorne o Eloquent
 * (ex: bulk insert via DB::table()) nao passa por aqui — nao ha uso planejado disso.
 */
class AuditoriaObserver
{
    public function created(Model $model): void
    {
        $this->registrar('criacao', $model, null, $this->atributosVisiveis($model));
    }

    public function updated(Model $model): void
    {
        $anterior = $this->ocultarCampos($model, array_intersect_key($model->getOriginal(), $model->getChanges()));
        $novo = $this->ocultarCampos($model, $model->getChanges());
        $this->registrar('alteracao', $model, $anterior, $novo);
    }

    public function deleted(Model $model): void
    {
        $this->registrar('exclusao', $model, $this->atributosVisiveis($model), null);
    }

    private function atributosVisiveis(Model $model): array
    {
        return $this->ocultarCampos($model, $model->getAttributes());
    }

    /**
     * Nunca grava campo sensivel (ex: senha) em texto no log — mesmo hash, o log de
     * auditoria nao precisa reter a credencial (RNF-001).
     */
    private function ocultarCampos(Model $model, array $valores): array
    {
        foreach ($model->getHidden() as $campo) {
            unset($valores[$campo]);
        }

        return $valores;
    }

    private function registrar(string $acao, Model $model, ?array $valorAnterior, ?array $valorNovo): void
    {
        // RF-001 (registro aberto): a criacao do proprio usuario acontece sem sessao
        // autenticada. Convencao (padroes_tecnologias.log_auditoria.observacao_rf001):
        // usuario_id do registro = id do proprio usuario recem-criado, unico caso do
        // sistema em que o "usuario autenticado que executou" e o proprio registro afetado.
        $usuarioId = Auth::id() ?? ($model instanceof User ? $model->getKey() : null);

        if ($usuarioId === null) {
            // Nao deveria ocorrer em fluxo autorizado (toda escrita de dominio exige
            // usuario autenticado, exceto o auto-registro acima); nada a gravar sem ele.
            return;
        }

        LogAuditoria::create([
            'acao' => $acao,
            'usuario_id' => $usuarioId,
            'tabela_afetada' => $model->getTable(),
            'registro_id' => (string) $model->getKey(),
            'valor_anterior' => $valorAnterior,
            'valor_novo' => $valorNovo,
            'criado_em' => now(),
        ]);
    }
}
