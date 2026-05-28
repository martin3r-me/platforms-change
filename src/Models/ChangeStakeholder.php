<?php

namespace Platform\Change\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Platform\Change\Enums\StakeholderInfluence;
use Platform\Change\Enums\StakeholderSupport;
use Platform\Organization\Models\OrganizationEntity;
use Symfony\Component\Uid\UuidV7;

class ChangeStakeholder extends Model
{
    use SoftDeletes;

    protected $table = 'change_stakeholders';

    protected $fillable = [
        'uuid', 'team_id', 'user_id', 'change_project_id',
        'name', 'role', 'influence_level', 'support_level',
        'notes', 'entity_id', 'metadata',
    ];

    protected $casts = [
        'influence_level' => StakeholderInfluence::class,
        'support_level' => StakeholderSupport::class,
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
    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function entity(): BelongsTo { return $this->belongsTo(OrganizationEntity::class, 'entity_id'); }
}
