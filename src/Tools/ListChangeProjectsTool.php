<?php

namespace Platform\Change\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Change\Models\ChangeProject;
use Platform\Change\Tools\Concerns\ResolvesChangeTeam;

class ListChangeProjectsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesChangeTeam;

    public function getName(): string { return 'change.projects.GET'; }

    public function getDescription(): string
    {
        return 'GET /change/projects - Listet Change-Projekte (Kotter 8-Step). Filter: status, owner_entity_id.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(['team_id', 'status', 'owner_entity_id']),
            [
                'properties' => [
                    'team_id'         => ['type' => 'integer'],
                    'status'          => ['type' => 'string', 'description' => 'Optional: draft | active | paused | completed | cancelled.'],
                    'owner_entity_id' => ['type' => 'integer', 'description' => 'Optional: Filter nach Owner-Entity.'],
                ],
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeamAndRoot($arguments, $context);
            if ($resolved['error']) return $resolved['error'];
            $rootTeamId = (int) $resolved['root_team_id'];

            $q = ChangeProject::query()->where('team_id', $rootTeamId);

            if (array_key_exists('status', $arguments) && $arguments['status'] !== null && $arguments['status'] !== '') {
                $q->where('status', (string) $arguments['status']);
            }
            if (! empty($arguments['owner_entity_id'])) {
                $q->where('owner_entity_id', (int) $arguments['owner_entity_id']);
            }

            $this->applyStandardFilters($q, $arguments, ['team_id', 'status', 'owner_entity_id', 'created_at']);
            $this->applyStandardSearch($q, $arguments, ['name', 'code', 'description']);
            $this->applyStandardSort($q, $arguments, ['name', 'code', 'status', 'id', 'created_at', 'target_date'], 'name', 'asc');

            $result = $this->applyStandardPaginationResult($q, $arguments);
            $items = $result['data']->map(fn (ChangeProject $p) => [
                'id'              => $p->id,
                'uuid'            => $p->uuid,
                'name'            => $p->name,
                'code'            => $p->code,
                'description'     => $p->description,
                'status'          => $p->status,
                'target_date'     => $p->target_date?->toDateString(),
                'owner_entity_id' => $p->owner_entity_id,
                'progress'        => $p->progress,
                'team_id'         => $p->team_id,
            ])->values()->toArray();

            return ToolResult::success([
                'data'         => $items,
                'pagination'   => $result['pagination'] ?? null,
                'team_id'      => $resolved['team_id'],
                'root_team_id' => $rootTeamId,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Change-Projekte: '.$e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category'      => 'read',
            'tags'          => ['change', 'projects', 'lookup'],
            'read_only'     => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level'    => 'safe',
            'idempotent'    => true,
        ];
    }
}
