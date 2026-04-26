<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;

trait HasAuditFields
{
    use SoftDeletes;

    public static function bootHasAuditFields(): void
    {
        static::creating(function ($model) {
            $model->created_by = auth('api')->id();
            $model->updated_by = auth('api')->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth('api')->id();
        });

        static::softDeleted(function ($model) {
            $userId = auth('api')->id();
            $model->newQueryWithoutScopes()
                ->where($model->getKeyName(), $model->getKey())
                ->update(['deleted_by' => $userId]);
            $model->deleted_by = $userId;
        });

        static::restoring(function ($model) {
            $model->deleted_by = null;
        });
    }
}
