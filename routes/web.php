<?php

use Illuminate\Support\Facades\Route;
use Platform\Change\Livewire\ChangeProject\Index as ChangeProjectIndex;
use Platform\Change\Livewire\ChangeProject\Show as ChangeProjectShow;

// Module root → redirect to project list
Route::get('/', fn () => redirect()->route('change.projects.index'))->name('change.dashboard');

// Change Projects
Route::get('/projects', ChangeProjectIndex::class)->name('change.projects.index');
Route::get('/projects/{project}', ChangeProjectShow::class)->name('change.projects.show');
