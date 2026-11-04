<?php

namespace Database\Seeders;

use App\Enums\Papel;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(TextosSeeder::class);

        // Só em desenvolvimento: um administrador de teste para entrar no /admin.
        // Em produção crie os utilizadores com `php artisan crevelhos:utilizador`.
        if (app()->isLocal()) {
            User::firstOrCreate(
                ['email' => 'admin@crevelhos.test'],
                ['name' => 'Administrador (teste local)', 'password' => 'crevelhos-local', 'papel' => Papel::Admin],
            );
        }
    }
}
