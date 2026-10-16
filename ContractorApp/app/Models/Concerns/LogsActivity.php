<?php

namespace App\Models\Concerns;

use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * Records create/update/delete, status changes and completion in the activity log.
 *
 * Models using this trait implement activityLabel() to name themselves in descriptions.
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(fn (Model $model) => app(ActivityLogger::class)->modelEvent($model, 'created'));

        static::updated(function (Model $model): void {
            $logger = app(ActivityLogger::class);

            if ($model->wasChanged('status')) {
                $logger->statusChanged($model, $model->getOriginal('status'), $model->getAttribute('status'));

                return;
            }

            if (array_diff(array_keys($model->getChanges()), ['updated_at']) !== []) {
                $logger->modelEvent($model, 'updated');
            }
        });

        static::deleted(fn (Model $model) => app(ActivityLogger::class)->modelEvent($model, 'deleted'));
    }

    abstract public function activityLabel(): string;
}
