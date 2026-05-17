<?php

namespace App\Services\Security;

use App\Models\BaseModel;
use App\Models\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SecurityAuditRestoreService
{
    public function __construct(
        protected AuditPrivacyService $privacyService
    ) {}

    public function preview(Log $log, string $mode = 'auto'): array
    {
        $modelClass = (string) $log->modelo;
        $this->assertRestorableModel($modelClass);

        $before = $this->privacyService->normalizePayload($log->dados_anteriores);
        $after = $this->privacyService->normalizePayload($log->dados_depois);
        $target = $this->payloadForMode($log, $mode, $before, $after);
        $instance = new $modelClass();
        $table = $instance->getTable();
        $columns = Schema::getColumnListing($table);
        $filteredTarget = array_intersect_key($target, array_flip($columns));
        $current = $this->findCurrentRecord($modelClass, $log->registro_id);

        return [
            'mode' => $mode,
            'model_class' => $modelClass,
            'table' => $table,
            'record_id' => $log->registro_id,
            'current_exists' => (bool) $current,
            'current' => $current ? $current->toArray() : null,
            'target' => $filteredTarget,
            'ignored_fields' => array_values(array_diff(array_keys($target), array_keys($filteredTarget))),
        ];
    }

    public function restore(Log $log, string $mode = 'auto'): BaseModel
    {
        $preview = $this->preview($log, $mode);
        $modelClass = $preview['model_class'];
        $target = $preview['target'];

        if (empty($target)) {
            throw ValidationException::withMessages([
                'audit_restore' => ['Não existem dados válidos para restaurar neste log.'],
            ]);
        }

        $recordId = $log->registro_id;
        $current = $this->findCurrentRecord($modelClass, $recordId);

        if ($mode === 'clone') {
            unset($target['id']);
            $model = new $modelClass();
            $model->fill($target);
            $model->save();
            return $model;
        }

        if ($current) {
            $current->fill($target);

            if (array_key_exists('deleted_at', $target)) {
                $current->setAttribute('deleted_at', $target['deleted_at']);
            }

            if ($log->acao === 'delete' && method_exists($current, 'restore') && array_key_exists('deleted_at', $current->getAttributes())) {
                $current->setAttribute('deleted_at', null);
            }

            $current->save();
            return $current;
        }

        $model = new $modelClass();
        $model->fill($target);

        if ($recordId && in_array('id', Schema::getColumnListing($model->getTable()), true)) {
            $model->setAttribute('id', $recordId);
            $model->exists = false;
        }

        $model->save();
        return $model;
    }

    protected function payloadForMode(Log $log, string $mode, array $before, array $after): array
    {
        if ($mode === 'clone') {
            return !empty($before) ? $before : $after;
        }

        if ($log->acao === 'delete') {
            return $before;
        }

        if ($log->acao === 'update') {
            return $before;
        }

        if ($log->acao === 'create') {
            return $after;
        }

        return !empty($before) ? $before : $after;
    }

    protected function assertRestorableModel(string $modelClass): void
    {
        if ($modelClass === '' || !class_exists($modelClass) || !is_subclass_of($modelClass, BaseModel::class)) {
            throw ValidationException::withMessages([
                'audit_restore' => ['A restauração automática só é suportada para Models que herdam de BaseModel.'],
            ]);
        }
    }

    protected function findCurrentRecord(string $modelClass, $recordId): ?BaseModel
    {
        if (!$recordId) {
            return null;
        }

        $query = $modelClass::query();

        $instance = new $modelClass();

        if (method_exists($instance, 'scopeWithDeleted')) {
            $query->withDeleted();
        } elseif (method_exists($instance, 'scopeWithTrashed')) {
            $query->withTrashed();
        }

        return $query->whereKey($recordId)->first();
    }
}
