<?php

namespace Platform\Change\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Platform\Change\Enums\ChangePhaseNumber;
use Platform\Change\Enums\ChangePhaseStatus;

class ChangePhase extends Model
{
    protected $table = 'change_phases';

    protected $fillable = [
        'uuid', 'change_project_id', 'phase_number', 'status',
        'notes', 'responsible', 'evidence',
        'started_at', 'completed_at', 'metadata',
    ];

    protected $casts = [
        'phase_number' => ChangePhaseNumber::class,
        'status' => ChangePhaseStatus::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function project(): BelongsTo { return $this->belongsTo(ChangeProject::class, 'change_project_id'); }
    public function actions(): HasMany { return $this->hasMany(ChangeAction::class, 'change_phase_id'); }
    public function logs(): HasMany { return $this->hasMany(ChangeLog::class, 'change_phase_id'); }
}
