<?php

namespace App\Console\Commands;

use App\Enums\Papel;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('crevelhos:utilizador {email? : Email do utilizador} {--admin : Dar o papel de administrador}')]
#[Description('Cria (ou atualiza) um utilizador do backoffice')]
class CriarUtilizador extends Command
{
    public function handle(): int
    {
        $email = $this->argument('email') ?: text('Email', required: true, validate: ['email' => 'email']);
        $existente = User::where('email', $email)->first();

        $nome = text('Nome', default: $existente->name ?? '', required: true);
        $papel = $this->option('admin') ? Papel::Admin->value : select('Papel', [
            Papel::Editor->value => Papel::Editor->getLabel(),
            Papel::Admin->value => Papel::Admin->getLabel(),
        ], default: $existente?->papel?->value ?? Papel::Editor->value);
        $palavraPasse = password('Palavra-passe (mínimo 10 caracteres)', required: true, validate: ['p' => 'min:10']);

        User::updateOrCreate(['email' => $email], ['name' => $nome, 'papel' => $papel, 'password' => $palavraPasse]);
        $this->info(($existente ? 'Atualizado' : 'Criado').": {$email} ({$papel}).");

        return self::SUCCESS;
    }
}
