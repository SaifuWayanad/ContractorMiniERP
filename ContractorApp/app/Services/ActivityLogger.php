<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Contractor;
use App\Models\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Central place that writes activity_logs rows.
 */
class ActivityLogger
{
    public function __construct(private Request $request) {}

    /**
     * Log a created/updated/deleted event for a model that uses the LogsActivity trait.
     */
    public function modelEvent(Model $model, string $action): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $this->record(
            action: $action,
            description: sprintf('%s %s %s "%s"', $user->name, $action, $this->entityLabel($model), $model->activityLabel()),
            subject: $model,
        );
    }

    /**
     * Log a status change; a change to "completed" is recorded as a completion.
     */
    public function statusChanged(Model $model, BackedEnum|string|null $from, BackedEnum|string|null $to): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $toValue = $to instanceof BackedEnum ? $to->value : $to;

        if ($toValue === 'completed') {
            $this->record('completed', sprintf('%s marked "%s" as completed', $user->name, $model->activityLabel()), $model);

            return;
        }

        $this->record('status_changed', sprintf(
            '%s changed %s "%s" from %s to %s',
            $user->name,
            $this->entityLabel($model),
            $model->activityLabel(),
            $this->statusLabel($from),
            $this->statusLabel($to),
        ), $model);
    }

    /**
     * Write one activity row. The contractor is taken from the subject, falling back to the signed-in user.
     */
    public function record(string $action, string $description, ?Model $subject = null, ?User $user = null, ?int $contractorId = null): ActivityLog
    {
        $user ??= Auth::user();

        $contractorId ??= match (true) {
            $subject instanceof Contractor => $subject->getKey(),
            $subject !== null && $subject->getAttribute('contractor_id') !== null => $subject->getAttribute('contractor_id'),
            default => $user?->contractor_id,
        };

        $log = new ActivityLog([
            'action' => $action,
            'entity_type' => $subject !== null ? Str::snake(class_basename($subject)) : null,
            'entity_id' => $subject?->getKey(),
            'description' => Str::limit($description, 497),
            'ip_address' => $this->request->ip(),
        ]);
        $log->user_id = $user?->id;
        $log->contractor_id = $contractorId;
        $log->save();

        return $log;
    }

    private function entityLabel(Model $model): string
    {
        return Str::of(class_basename($model))->snake(' ')->replace('project location', 'location')->value();
    }

    private function statusLabel(BackedEnum|string|null $status): string
    {
        if ($status instanceof BackedEnum && method_exists($status, 'label')) {
            return $status->label();
        }

        return $status instanceof BackedEnum ? (string) $status->value : (string) $status;
    }
}
