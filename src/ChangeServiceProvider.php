<?php

namespace Platform\Change;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Platform\Core\PlatformCore;
use Platform\Core\Routing\ModuleRouter;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ChangeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Config laden
        $this->mergeConfigFrom(__DIR__.'/../config/change.php', 'change');

        // Modul registrieren
        if (
            config()->has('change.routing') &&
            config()->has('change.navigation') &&
            Schema::hasTable('modules')
        ) {
            PlatformCore::registerModule([
                'key'        => 'change',
                'title'      => 'Change',
                'group'      => 'admin',
                'routing'    => config('change.routing'),
                'guard'      => config('change.guard'),
                'navigation' => config('change.navigation'),
                'sidebar'    => config('change.sidebar'),
            ]);
        }

        // Routes laden
        if (PlatformCore::getModule('change')) {
            ModuleRouter::group('change', function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });
        }

        // Migrationen laden
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Config veröffentlichen
        $this->publishes([
            __DIR__.'/../config/change.php' => config_path('change.php'),
        ], 'config');

        // Views & Livewire
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'change');
        $this->registerLivewireComponents();

        // Tools registrieren
        $this->registerTools();

        // Error Reporter
        try {
            resolve(\Platform\Core\Services\ErrorReporterRegistry::class)
                ->register('change', 'Platform\\Change');
        } catch (\Throwable $e) {}
    }

    protected function registerLivewireComponents(): void
    {
        $basePath = __DIR__ . '/Livewire';
        $baseNamespace = 'Platform\\Change\\Livewire';
        $prefix = 'change';

        if (!is_dir($basePath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $classPath = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $class = $baseNamespace . '\\' . $classPath;

            if (!class_exists($class)) {
                continue;
            }

            $aliasPath = str_replace(['\\', '/'], '.', Str::kebab(str_replace('.php', '', $relativePath)));
            $alias = $prefix . '.' . $aliasPath;

            Livewire::component($alias, $class);
        }
    }

    protected function registerTools(): void
    {
        try {
            $registry = resolve(\Platform\Core\Tools\ToolRegistry::class);

            // ChangeProject CRUD
            $registry->register(new \Platform\Change\Tools\ListChangeProjectsTool());
            $registry->register(new \Platform\Change\Tools\CreateChangeProjectTool());
            $registry->register(new \Platform\Change\Tools\UpdateChangeProjectTool());
            $registry->register(new \Platform\Change\Tools\DeleteChangeProjectTool());

            // ChangePhase
            $registry->register(new \Platform\Change\Tools\ListChangePhasesTool());
            $registry->register(new \Platform\Change\Tools\UpdateChangePhaseTool());

            // ChangeStakeholder CRUD
            $registry->register(new \Platform\Change\Tools\ListChangeStakeholdersTool());
            $registry->register(new \Platform\Change\Tools\CreateChangeStakeholderTool());
            $registry->register(new \Platform\Change\Tools\UpdateChangeStakeholderTool());
            $registry->register(new \Platform\Change\Tools\DeleteChangeStakeholderTool());

            // ChangeAction CRUD
            $registry->register(new \Platform\Change\Tools\ListChangeActionsTool());
            $registry->register(new \Platform\Change\Tools\CreateChangeActionTool());
            $registry->register(new \Platform\Change\Tools\UpdateChangeActionTool());
            $registry->register(new \Platform\Change\Tools\DeleteChangeActionTool());

            // ChangeLog CRUD
            $registry->register(new \Platform\Change\Tools\ListChangeLogsTool());
            $registry->register(new \Platform\Change\Tools\CreateChangeLogTool());
            $registry->register(new \Platform\Change\Tools\UpdateChangeLogTool());
            $registry->register(new \Platform\Change\Tools\DeleteChangeLogTool());

            // Analytics
            $registry->register(new \Platform\Change\Tools\GetChangeProgressTool());
            $registry->register(new \Platform\Change\Tools\GetChangeBoardTool());

        } catch (\Throwable $e) {
            \Log::warning('Change: Tool-Registrierung fehlgeschlagen', ['error' => $e->getMessage()]);
        }
    }
}
