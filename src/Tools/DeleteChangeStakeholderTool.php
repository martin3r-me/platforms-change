<?php

namespace Platform\Change\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Change\Models\ChangeStakeholder;
use Platform\Change\Tools\Concerns\ResolvesChangeTeam;

class DeleteChangeStakeholderTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesChangeTeam;

    public function getName(): string { return 'change.stakeholders.DELETE'; }

    public function getDescription(): string
    {
        return 'DELETE /change/stakeholders/{id} - Löscht einen Stakeholder (soft delete).';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id'        => ['type' => 'integer'],
                'stakeholder_id' => ['type' => 'integer', 'description' => 'ERFORDERLICH.'],
            ],
            'required' => ['stakeholder_id'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeamAndRoot($arguments, $context);
            if ($resolved['error']) return $resolved['error'];
            $rootTeamId = (int) $resolved['root_team_id'];

            $found = $this->validateAndFindModel($arguments, $context, 'stakeholder_id', ChangeStakeholder::class, 'NOT_FOUND', 'Stakeholder nicht gefunden.');
            if ($found['error']) return $found['error'];

            $stakeholder = $found['model'];
            if ((int) $stakeholder->team_id !== $rootTeamId) {
                return ToolResult::error('ACCESS_DENIED', 'Stakeholder gehört nicht zum Team.');
            }

            $stakeholder->delete();

            return ToolResult::success(['id' => $stakeholder->id, 'message' => 'Stakeholder gelöscht.']);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: '.$e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['change', 'stakeholders', 'delete'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'write', 'idempotent' => true,
        ];
    }
}
