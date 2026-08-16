<?php

namespace Database\Seeders;

use App\Models\Campus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CampusSeeder extends Seeder
{
    public function run(): void
    {
        $path = public_path('data/sksu-academic-options.json');
        $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        foreach ($data['campuses'] as $campus) {
            $code = Str::before(Str::slug($campus['name']), '-campus');
            $code = $code === 'access' ? 'access' : $code;
            Campus::updateOrCreate(['name' => $campus['name']], ['code' => $code, 'is_active' => true]);
        }
    }
}
