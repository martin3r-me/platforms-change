<?php

namespace Platform\Change\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Change\Models\ChangeProject;
use Platform\Change\Tools\Concerns\ResolvesChangeTeam;

class GetChangeProgressTool implements ToolContract, ToolMetadataContract
{
    use ResolvesChangeTeam;

    public function getName(): string { return 'change.progress.GET'; }

    public function getDescription(): string
    {
        return 'GET /change/progress - Fortschritts-Übersicht eines Change-Projekts: Phasen-Status, Action-Statistik, Stakeholder-Verteilung.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id'    => ['type' => 'integer'],
                'project_id' => ['type' => 'integer', 'description' => 'ERFORDERLICH.'],
            ],
            'required' => ['project_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeamAndRoot($arguments, $context);
            if ($resolved['error']) return $resolved['error'];
            $rootTeamId = (int) $resolved['root_team_id'];

            $project = ChangeProject::with(['phases', 'actions', 'stakeholders', 'logs'])
                ->find((int) ($arguments['project_id'] ?? 0));
            if (! $project || (int) $project->team_id !== $rootTeamId) {
                return ToolResult::error('NOT_FOUND', 'Change-Projekt nicht gefunden.');
            }

            $phases = $project->phases;
            $phaseStats = $phases->groupBy(fn ($p) => $p->status->value ?? $p->status)->map->count();

            $actions = $project->actions;
            $actionStats = $actions->groupBy(fn ($a) => $a->status->value ?? $a->status)->map->count();

            $stakeholders = $project->stakeholders;
            $influenceStats = $stakeholders->groupBy(fn ($s) => $s->influence_level->value ?? $s->influence_level)->map->count();
            $supportStats = $stakeholders->groupBy(fn ($s) => $s->support_level->value ?? $s->support_level)->map->count();

            $logStats = $project->logs->groupBy(fn ($l) => $l->type->value ?? $l->type)->map->count();

            return ToolResult::success([
                'project_id'   => $project->id,
                'project_name' => $project->name,
                'status'       => $project->status,
                'progress'     => $project->progress,
                'phases'       => [
                    'total'     => $phases->count(),
                    'by_status' => $phaseStats->toArray(),
                ],
                'actions'      => [
                    'total'     => $actions->count(),
                    'by_status' => $actionStats->toArray(),
                    'overdue'   => $actions->filter(fn ($a) => $a->due_date && $a->due_date->isPast() && ! in_array($a->status->value ?? $a->status, ['done', 'cancelled']))->count(),
                ],
                'stakeholders' => [
                    'total'        => $stakeholders->count(),
                    'by_influence' => $influenceStats->toArray(),
                    'by_support'   => $supportStats->toArray(),
                ],
                'logs'         => [
                    'total'   => $project->logs->count(),
                    'by_type' => $logStats->toArray(),
                ],
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: '.$e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'read', 'tags' => ['change', 'analytics', 'progress'],
            'read_only' => true, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'safe', 'idempotent' => true,
        ];
    }
}
