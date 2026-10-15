<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('enrollment:clear {--campus= : Optional campus ID}', function () {
    $campusId = $this->option('campus');
    if ($campusId) {
        $count = \App\Models\RegistrarStudent::where('campus_id', $campusId)->delete();
        $this->info("Cleared {$count} enrollment record(s) for campus ID {$campusId}.");
    } else {
        $count = \App\Models\RegistrarStudent::count();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \App\Models\RegistrarStudent::truncate();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->info("Cleared all {$count} registrar enrollment records.");
    }
})->purpose('Clear/empty enrolled student records from the database');

