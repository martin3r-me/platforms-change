<?php

namespace Platform\Change\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Change\Models\ChangeLog;
use Platform\Change\Models\ChangeProject;
use Platform\Change\Tools\Concerns\ResolvesChangeTeam;

class ListChangeLogsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesChangeTeam;

    public function getName(): string { return 'change.logs.GET'; }

    public function getDescription(): string
    {
        return 'GET /change/logs - Listet Log-Einträge eines Change-Projekts. Filter: type, phase_id.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(['team_id', 'project_id', 'type', 'phase_id']),
            [
                'properties' => [
                    'team_id'    => ['type' => 'integer'],
                    'project_id' => ['type' => 'integer', 'description' => 'ERFORDERLICH.'],
                    'type'       => ['type' => 'string', 'description' => 'Optional: note | milestone | decision | risk | blocker.'],
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

            $project = ChangeProject::find((int) ($arguments['project_id'] ?? 0));
            if (! $project || (int) $project->team_id !== $rootTeamId) {
                return ToolResult::error('NOT_FOUND', 'Change-Projekt nicht gefunden.');
            }

            $q = ChangeLog::query()->where('change_project_id', $project->id);

            if (! empty($arguments['type'])) $q->where('type', $arguments['type']);
            if (! empty($arguments['phase_id'])) $q->where('change_phase_id', (int) $arguments['phase_id']);

            $this->applyStandardSearch($q, $arguments, ['title', 'content']);
            $this->applyStandardSort($q, $arguments, ['title', 'type', 'created_at'], 'created_at', 'desc');

            $result = $this->applyStandardPaginationResult($q, $arguments);
            $items = $result['data']->map(fn (ChangeLog $l) => [
                'id'              => $l->id,
                'uuid'            => $l->uuid,
                'type'            => $l->type,
                'title'           => $l->title,
                'content'         => $l->content,
                'change_phase_id' => $l->change_phase_id,
                'user_id'         => $l->user_id,
                'created_at'      => $l->created_at?->toIso8601String(),
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
            'category' => 'read', 'tags' => ['change', 'logs', 'lookup'],
            'read_only' => true, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'safe', 'idempotent' => true,
        ];
    }
}
