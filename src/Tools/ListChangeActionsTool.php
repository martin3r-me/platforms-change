<?php

namespace Platform\Change\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Change\Models\ChangeAction;
use Platform\Change\Models\ChangeProject;
use Platform\Change\Tools\Concerns\ResolvesChangeTeam;

class ListChangeActionsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesChangeTeam;

    public function getName(): string { return 'change.actions.GET'; }

    public function getDescription(): string
    {
        return 'GET /change/actions - Listet Massnahmen eines Change-Projekts. Filter: status, phase_id.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(['team_id', 'project_id', 'status', 'phase_id']),
            [
                'properties' => [
                    'team_id'    => ['type' => 'integer'],
                    'project_id' => ['type' => 'integer', 'description' => 'ERFORDERLICH.'],
                    'status'     => ['type' => 'string', 'description' => 'Optional: open | in_progress | done | cancelled.'],
                    'phase_id'   => ['type' => 'integer', 'description' => 'Optional: Filter nach Phase.'],
                ],
                'required' => ['project_id'],
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeamAndRoot($arguments, $context);
            if ($resolved['error']) return $resolved['error'];
            $rootTeamId = (int) $resolved['root_team_id'];

            $projectId = $arguments['project_id'] ?? null;
            if (! $projectId) return ToolResult::error('VALIDATION_ERROR', 'project_id ist erforderlich.');

            $project = ChangeProject::find((int) $projectId);
            if (! $project || (int) $project->team_id !== $rootTeamId) {
                return ToolResult::error('NOT_FOUND', 'Change-Projekt nicht gefunden.');
            }

            $q = ChangeAction::query()->where('change_project_id', $project->id);

            if (! empty($arguments['status'])) $q->where('status', $arguments['status']);
            if (! empty($arguments['phase_id'])) $q->where('change_phase_id', (int) $arguments['phase_id']);

            $this->applyStandardSearch($q, $arguments, ['title', 'description', 'responsible']);
            $this->applyStandardSort($q, $arguments, ['title', 'status', 'due_date', 'created_at'], 'created_at', 'desc');

            $result = $this->applyStandardPaginationResult($q, $arguments);
            $items = $result['data']->map(fn (ChangeAction $a) => [
                'id'              => $a->id,
                'uuid'            => $a->uuid,
                'title'           => $a->title,
                'description'     => $a->description,
                'status'          => $a->status,
                'due_date'        => $a->due_date?->toDateString(),
                'responsible'     => $a->responsible,
                'change_phase_id' => $a->change_phase_id,
                'completed_at'    => $a->completed_at?->toIso8601String(),
            ])->values()->toArray();

            return ToolResult::success([
                'data'       => $items,
                'pagination' => $result['pagination'] ?? null,
                'project_id' => $project->id,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: '.$e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'read', 'tags' => ['change', 'actions', 'lookup'],
            'read_only' => true, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'safe', 'idempotent' => true,
        ];
    }
}
