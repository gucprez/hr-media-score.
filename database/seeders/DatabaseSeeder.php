<?php

namespace Database\Seeders;

use App\Services\AuditLogger;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        AuditLogger::disable();
        $this->call([
            RolesAndPermissionsSeeder::class,
            AdminUserSeeder::class,
            GustavoBehallSeeder::class,
        ]);
        AuditLogger::enable();
    }
}
