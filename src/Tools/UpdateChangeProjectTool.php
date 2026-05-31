<?php

namespace Platform\Change\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Change\Models\ChangeProject;
use Platform\Change\Tools\Concerns\ResolvesChangeTeam;
use Platform\Organization\Models\OrganizationTimePeriod;
use Platform\Organization\Services\StorePlannedPeriod;

class UpdateChangeProjectTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesChangeTeam;

    public function getName(): string { return 'change.projects.PUT'; }

    public function getDescription(): string
    {
        return 'PUT /change/projects/{id} - Aktualisiert ein Change-Projekt.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id'           => ['type' => 'integer'],
                'project_id'        => ['type' => 'integer', 'description' => 'ERFORDERLICH.'],
                'name'              => ['type' => 'string'],
                'code'              => ['type' => 'string', 'description' => '"" zum Leeren.'],
                'description'       => ['type' => 'string', 'description' => '"" zum Leeren.'],
                'status'            => ['type' => 'string', 'description' => 'draft | active | paused | completed | cancelled.'],
                'target_date'       => ['type' => 'string', 'description' => 'YYYY-MM-DD oder "" zum Leeren.'],
                'owner_entity_id'   => ['type' => 'integer', 'description' => '0 oder null zum Leeren.'],
                'urgency_statement' => ['type' => 'string', 'description' => '"" zum Leeren.'],
                'vision_statement'  => ['type' => 'string', 'description' => '"" zum Leeren.'],
                'metadata'          => ['type' => 'object'],
            ],
            'required' => ['project_id'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeamAndRoot($arguments, $context);
            if ($resolved['error']) return $resolved['error'];
            $rootTeamId = (int) $resolved['root_team_id'];

            $found = $this->validateAndFindModel($arguments, $context, 'project_id', ChangeProject::class, 'NOT_FOUND', 'Change-Projekt nicht gefunden.');
            if ($found['error']) return $found['error'];

            $project = $found['model'];
            if ((int) $project->team_id !== $rootTeamId) {
                return ToolResult::error('ACCESS_DENIED', 'Projekt gehört nicht zum Root/Elterteam.');
            }

            $update = [];
            if (array_key_exists('name', $arguments)) {
                $val = trim((string) ($arguments['name'] ?? ''));
                if ($val === '') return ToolResult::error('VALIDATION_ERROR', 'name darf nicht leer sein.');
                $update['name'] = $val;
            }
            foreach (['code', 'description', 'urgency_statement', 'vision_statement'] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $val = (string) ($arguments[$field] ?? '');
                    $update[$field] = $val === '' ? null : $val;
                }
            }
            if (array_key_exists('status', $arguments)) {
                $update['status'] = (string) $arguments['status'];
                if ($arguments['status'] === 'completed' && ! $project->completed_at) {
                    $update['completed_at'] = now();
                } elseif ($arguments['status'] !== 'completed') {
                    $update['completed_at'] = null;
                }
            }
            if (array_key_exists('target_date', $arguments)) {
                $val = (string) ($arguments['target_date'] ?? '');
                $plannedEnd = $val === '' ? null : $val;

                $existingPeriod = OrganizationTimePeriod::where('context_type', ChangeProject::class)
                    ->where('context_id', $project->id)
                    ->where('is_active', true)
                    ->first();

                if ($existingPeriod) {
                    app(StorePlannedPeriod::class)->update($existingPeriod, ['planned_end' => $plannedEnd]);
                } elseif ($plannedEnd) {
                    app(StorePlannedPeriod::class)->store([
                        'team_id' => $rootTeamId,
                        'user_id' => $context->user?->id,
                        'context_type' => ChangeProject::class,
                        'context_id' => $project->id,
                        'planned_start' => null,
                        'planned_end' => $plannedEnd,
                        'note' => null,
                        'is_active' => true,
                    ]);
                }
            }
            if (array_key_exists('owner_entity_id', $arguments)) {
                $val = $arguments['owner_entity_id'];
                $update['owner_entity_id'] = (! empty($val) && (int) $val > 0) ? (int) $val : null;
            }
            if (array_key_exists('metadata', $arguments)) {
                $update['metadata'] = $arguments['metadata'];
            }

            if (! empty($update)) $project->update($update);
            $project->refresh();

            return ToolResult::success([
                'id'      => $project->id,
                'uuid'    => $project->uuid,
                'name'    => $project->name,
                'status'  => $project->status,
                'team_id' => $project->team_id,
                'message' => 'Change-Projekt aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren: '.$e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category'      => 'action',
            'tags'          => ['change', 'projects', 'update'],
            'read_only'     => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level'    => 'write',
            'idempotent'    => true,
        ];
    }
}
