<?php

namespace Platform\Change\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Change\Models\ChangeAction;
use Platform\Change\Models\ChangeProject;
use Platform\Change\Tools\Concerns\ResolvesChangeTeam;

class CreateChangeActionTool implements ToolContract, ToolMetadataContract
{
    use ResolvesChangeTeam;

    public function getName(): string { return 'change.actions.POST'; }

    public function getDescription(): string
    {
        return 'POST /change/actions - Erstellt eine Massnahme für ein Change-Projekt, optional einer Phase zugeordnet.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id'     => ['type' => 'integer'],
                'project_id'  => ['type' => 'integer', 'description' => 'ERFORDERLICH.'],
                'title'       => ['type' => 'string', 'description' => 'ERFORDERLICH.'],
                'description' => ['type' => 'string'],
                'status'      => ['type' => 'string', 'description' => 'open | in_progress | done | cancelled. Default: open.'],
                'due_date'    => ['type' => 'string', 'description' => 'Optional: YYYY-MM-DD.'],
                'responsible' => ['type' => 'string'],
                'phase_id'    => ['type' => 'integer', 'description' => 'Optional: Zuordnung zu einer Phase.'],
                'metadata'    => ['type' => 'object'],
            ],
            'required' => ['project_id', 'title'],
        ];
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

            $title = trim((string) ($arguments['title'] ?? ''));
            if ($title === '') return ToolResult::error('VALIDATION_ERROR', 'title ist erforderlich.');

            $action = ChangeAction::create([
                'team_id'            => $rootTeamId,
                'user_id'            => $context->user?->id,
                'change_project_id'  => $project->id,
                'change_phase_id'    => ! empty($arguments['phase_id']) ? (int) $arguments['phase_id'] : null,
                'title'              => $title,
                'description'        => ($arguments['description'] ?? null) ?: null,
                'status'             => $arguments['status'] ?? 'open',
                'due_date'           => ($arguments['due_date'] ?? null) ?: null,
                'responsible'        => ($arguments['responsible'] ?? null) ?: null,
                'metadata'           => $arguments['metadata'] ?? null,
            ]);

            return ToolResult::success([
                'id' => $action->id, 'uuid' => $action->uuid,
                'title' => $action->title, 'message' => 'Massnahme erstellt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: '.$e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['change', 'actions', 'create'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'write', 'idempotent' => false,
        ];
    }
}
