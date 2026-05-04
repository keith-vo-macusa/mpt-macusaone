<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Vps extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'ip',
        'port',
        'username',
        'password',
        'note',
        'is_active',
        'is_online',
        'last_checked_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_online' => 'boolean',
        'last_checked_at' => 'datetime',
        'port' => 'integer',
        'password' => 'encrypted',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
