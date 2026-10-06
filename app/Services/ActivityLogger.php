<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

/**
 * Serviço central para registrar o histórico de ações (criação, edição,
 * ativação/desativação, exclusão) feitas pelos usuários sobre os modelos
 * do sistema, alimentando a tela de Log de Atividades.
 */
class ActivityLogger
{
    public static function log(string $action, string $description, ?object $subject = null, ?array $changes = null): ActivityLog
    {
        return ActivityLog::create([
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'user_id' => Auth::id(),
            'changes' => $changes,
        ]);
    }

    public static function created(object $subject, ?string $description = null): ActivityLog
    {
        return self::log('created', $description ?? self::defaultDescription($subject, 'criado(a)'), $subject);
    }

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    public static function updated(object $subject, array $changes, ?string $description = null): ActivityLog
    {
        return self::log('updated', $description ?? self::defaultDescription($subject, 'atualizado(a)'), $subject, $changes);
    }

    public static function activated(object $subject, ?string $description = null): ActivityLog
    {
        return self::log('activated', $description ?? self::defaultDescription($subject, 'ativado(a)'), $subject);
    }

    public static function deactivated(object $subject, ?string $description = null): ActivityLog
    {
        return self::log('deactivated', $description ?? self::defaultDescription($subject, 'desativado(a)'), $subject);
    }

    public static function deleted(object $subject, ?string $description = null): ActivityLog
    {
        return self::log('deleted', $description ?? self::defaultDescription($subject, 'excluído(a)'), $subject);
    }

    /**
     * Compara os dados novos com os valores atuais do model (comparação
     * fraca, `!=`, para não marcar como alterado algo como '1' vs 1) e
     * retorna só os campos que de fato mudaram, prontos para o `changes` do log.
     *
     * @param  array<string, mixed>  $newData
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public static function diff(object $subject, array $newData): array
    {
        $changes = [];

        foreach ($newData as $key => $newValue) {
            $oldValue = $subject->{$key} ?? null;

            if ($oldValue != $newValue) {
                $changes[$key] = ['old' => $oldValue, 'new' => $newValue];
            }
        }

        return $changes;
    }

    private static function defaultDescription(object $subject, string $verb): string
    {
        $label = ActivityLog::make(['subject_type' => get_class($subject)])->subject_label;
        $name = $subject->title ?? $subject->name ?? "#{$subject->id}";

        return "{$label} \"{$name}\" {$verb}";
    }
}
