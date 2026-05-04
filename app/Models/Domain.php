<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Domain extends Model
{
    use LogsActivity;

    protected $fillable = [
        'domain',
        'max_subdomains',
        'subdomains_count',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_subdomains' => 'integer',
        'subdomains_count' => 'integer',
        'priority' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getRemainingSubdomainsAttribute(): int
    {
        return max(0, $this->max_subdomains - $this->subdomains_count);
    }
}
