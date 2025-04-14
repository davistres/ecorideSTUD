<?php
// script de génération de mot de passe haché: dans le terminal entrer:
// "php artisan password:hash MonMotDePasse123" => ça nous renverra le mot de passe en crypté
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class GenerateHashedPassword extends Command
{
    protected $signature = 'password:hash {password?}';
    protected $description = 'Génère un hash bcrypt pour un mot de passe donné';

    public function handle()
    {
        $password = $this->argument('password') ?? $this->secret('Entrez le mot de passe à hasher');

        $hashedPassword = Hash::make($password);

        $this->info('Mot de passe hashé (bcrypt) :');
        $this->line($hashedPassword);

        return Command::SUCCESS;
    }
}
