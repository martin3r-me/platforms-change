<?php

namespace Platform\Change\Organization;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Platform\Organization\Contracts\EntityLinkProvider;
use Platform\Organization\Contracts\HasMetricDefinitions;

class ChangeEntityLinkProvider implements EntityLinkProvider, HasMetricDefinitions
{
    public function morphAliases(): array
    {
        return ['change_project'];
    }

    public function linkTypeConfig(): array
    {
        return [
            'change_project' => [
                'label' => 'Change-Projekte',
                'singular' => 'Change-Projekt',
                'icon' => 'arrow-path',
                'route' => 'change.projects.show',
            ],
        ];
    }

    public function applyEagerLoading(Builder $query, string $morphAlias, string $fqcn): void
    {
        $query->withCount('phases')
            ->withCount(['phases as phases_completed_count' => fn ($q) => $q->where('status', 'completed')])
            ->withCount(['phases as phases_blocked_count' => fn ($q) => $q->where('status', 'blocked')])
            ->withCount('actions')
            ->withCount(['actions as actions_open_count' => fn ($q) => $q->where('status', 'open')->orWhere('status', 'in_progress')])
            ->withCount(['actions as actions_done_count' => fn ($q) => $q->where('status', 'done')]);
    }

    public function extractMetadata(string $morphAlias, mixed $model): array
    {
        return [
            'code' => $model->code,
            'status' => $model->status?->value ?? null,
            'progress' => $model->progress ?? 0,
            'target_date' => $model->target_date?->format('d.m.Y'),
            'phases_count' => $model->phases_count ?? 0,
            'actions_count' => $model->actions_count ?? 0,
        ];
    }

    public function metadataDisplayRules(): array
    {
        return [
            'code' => ['type' => 'text', 'label' => 'Code'],
            'status' => ['type' => 'badge', 'label' => 'Status'],
            'progress' => ['type' => 'percentage', 'label' => 'Fortschritt'],
            'target_date' => ['type' => 'text', 'label' => 'Zieldatum'],
            'phases_count' => ['type' => 'number', 'label' => 'Phasen'],
            'actions_count' => ['type' => 'number', 'label' => 'Massnahmen'],
        ];
    }

    public function timeTrackableCascades(): array
    {
        return [];
    }

    public function metrics(string $morphAlias, array $linksByEntity): array
    {
        $allIds = [];
        foreach ($linksByEntity as $ids) {
            $allIds = array_merge($allIds, $ids);
        }
        $allIds = array_values(array_unique($allIds));

        if (empty($allIds)) {
            return [];
        }

        // Project statuses
        $projects = DB::table('change_projects')
            ->whereIn('id', $allIds)
            ->whereNull('deleted_at')
            ->select('id', 'status')
            ->get()
            ->keyBy('id');

        // Phase stats per project
        $phaseStats = DB::table('change_phases')
            ->whereIn('change_project_id', $allIds)
            ->select(
                'change_project_id',
                DB::raw("COUNT(*) as total"),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked"),
            )
            ->groupBy('change_project_id')
            ->get()
            ->keyBy('change_project_id');

        // Action stats per project
        $actionStats = DB::table('change_actions')
            ->whereIn('change_project_id', $allIds)
            ->whereNull('deleted_at')
            ->select(
                'change_project_id',
                DB::raw("SUM(CASE WHEN status IN ('open', 'in_progress') THEN 1 ELSE 0 END) as actions_open"),
                DB::raw("SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as actions_done"),
            )
            ->groupBy('change_project_id')
            ->get()
            ->keyBy('change_project_id');

        $result = [];
        foreach ($linksByEntity as $entityId => $ids) {
            $total = 0;
            $active = 0;
            $progressSum = 0;
            $progressCount = 0;
            $actionsOpen = 0;
            $actionsDone = 0;
            $phasesBlocked = 0;

            foreach ($ids as $id) {
                $project = $projects[$id] ?? null;
                if (! $project) {
                    continue;
                }

                $total++;
                if ($project->status === 'active') {
                    $active++;
                }

                $phases = $phaseStats[$id] ?? null;
                if ($phases && $phases->total > 0) {
                    $progressSum += $phases->completed / $phases->total;
                    $progressCount++;
                    $phasesBlocked += (int) $phases->blocked;
                }

                $actions = $actionStats[$id] ?? null;
                if ($actions) {
                    $actionsOpen += (int) $actions->actions_open;
                    $actionsDone += (int) $actions->actions_done;
                }
            }

            $result[$entityId] = [
                'change_projects_total' => $total,
                'change_projects_active' => $active,
                'change_progress_avg' => $progressCount > 0
                    ? round($progressSum / $progressCount, 2)
                    : 0,
                'change_actions_open' => $actionsOpen,
                'change_actions_done' => $actionsDone,
                'change_phases_blocked' => $phasesBlocked,
            ];
        }

        return $result;
    }

    public function activityChildren(string $morphAlias, array $linkableIds): array
    {
        return [];
    }

    public function metricDefinitions(): array
    {
        return [
            'change_projects_total' => [
                'label' => 'Change-Projekte (gesamt)',
                'group' => 'change',
                'direction' => 'neutral',
                'unit' => 'count',
                'dimension' => 'complexity',
                'type' => 'stock',
                'aggregation_mode' => 'rolled_up',
            ],
            'change_projects_active' => [
                'label' => 'Change-Projekte (aktiv)',
                'group' => 'change',
                'direction' => 'neutral',
                'unit' => 'count',
                'pair' => 'change_projects_total',
                'dimension' => 'energy',
                'type' => 'stock',
                'aggregation_mode' => 'rolled_up',
            ],
            'change_progress_avg' => [
                'label' => 'Ø Change-Fortschritt',
                'group' => 'change',
                'direction' => 'up',
                'unit' => 'ratio',
                'dimension' => 'throughput',
                'type' => 'modulator',
                'aggregation_mode' => 'rolled_up',
                'roll_up_function' => 'avg',
            ],
            'change_actions_open' => [
                'label' => 'Change-Massnahmen (offen)',
                'group' => 'change',
                'direction' => 'down',
                'unit' => 'count',
                'dimension' => 'energy',
                'type' => 'stock',
                'aggregation_mode' => 'rolled_up',
            ],
            'change_actions_done' => [
                'label' => 'Change-Massnahmen (erledigt)',
                'group' => 'change',
                'direction' => 'up',
                'unit' => 'count',
                'dimension' => 'throughput',
                'type' => 'flow',
                'aggregation_mode' => 'rolled_up',
            ],
            'change_phases_blocked' => [
                'label' => 'Change-Phasen (blockiert)',
                'group' => 'change',
                'direction' => 'down',
                'unit' => 'count',
                'dimension' => 'quality',
                'type' => 'stock',
                'aggregation_mode' => 'rolled_up',
            ],
        ];
    }
}
