<?php

namespace Platform\Change\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Change\Models\ChangeLog;
use Platform\Change\Models\ChangeProject;
use Platform\Change\Tools\Concerns\ResolvesChangeTeam;

class CreateChangeLogTool implements ToolContract, ToolMetadataContract
{
    use ResolvesChangeTeam;

    public function getName(): string { return 'change.logs.POST'; }

    public function getDescription(): string
    {
        return 'POST /change/logs - Erstellt einen Log-Eintrag (Notiz, Meilenstein, Entscheidung, Risiko, Blocker).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id'    => ['type' => 'integer'],
                'project_id' => ['type' => 'integer', 'description' => 'ERFORDERLICH.'],
                'title'      => ['type' => 'string', 'description' => 'ERFORDERLICH.'],
                'type'       => ['type' => 'string', 'description' => 'note | milestone | decision | risk | blocker. Default: note.'],
                'content'    => ['type' => 'string'],
                'phase_id'   => ['type' => 'integer', 'description' => 'Optional: Zuordnung zu einer Phase.'],
                'metadata'   => ['type' => 'object'],
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

            $log = ChangeLog::create([
                'team_id'            => $rootTeamId,
                'user_id'            => $context->user?->id,
                'change_project_id'  => $project->id,
                'change_phase_id'    => ! empty($arguments['phase_id']) ? (int) $arguments['phase_id'] : null,
                'type'               => $arguments['type'] ?? 'note',
                'title'              => $title,
                'content'            => ($arguments['content'] ?? null) ?: null,
                'metadata'           => $arguments['metadata'] ?? null,
            ]);

            return ToolResult::success([
                'id' => $log->id, 'uuid' => $log->uuid,
                'title' => $log->title, 'type' => $log->type,
                'message' => 'Log-Eintrag erstellt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: '.$e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['change', 'logs', 'create'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'write', 'idempotent' => false,
        ];
    }
}
