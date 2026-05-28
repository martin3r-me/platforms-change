<?php

namespace Platform\Change\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Platform\Change\Enums\ChangeActionStatus;
use Symfony\Component\Uid\UuidV7;

class ChangeAction extends Model
{
    use SoftDeletes;

    protected $table = 'change_actions';

    protected $fillable = [
        'uuid', 'team_id', 'user_id', 'change_project_id', 'change_phase_id',
        'title', 'description', 'status', 'due_date', 'responsible',
        'completed_at', 'metadata',
    ];

    protected $casts = [
        'status' => ChangeActionStatus::class,
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = UuidV7::generate();
            }
            if (! $model->user_id) { $model->user_id = Auth::id(); }
        });
    }

    public function project(): BelongsTo { return $this->belongsTo(ChangeProject::class, 'change_project_id'); }
    public function phase(): BelongsTo { return $this->belongsTo(ChangePhase::class, 'change_phase_id'); }
    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
