<?php

namespace Database\Seeders;

use App\Models\Texto;
use Illuminate\Database\Seeder;

/**
 * Cria as chaves de texto que ainda não existem, com um texto inicial.
 * Nunca substitui o que já foi escrito no backoffice.
 * Os textos das páginas antigas (início, ficha técnica, secções) vêm do importador.
 */
class TextosSeeder extends Seeder
{
    public function run(): void
    {
        $iniciais = [
            'email_contacto' => 's.r.casaisrevelhos@gmail.com',
            'rodape' => 'Projeto da Sociedade Recreativa Pró Casais de Revelhos, com a Câmara Municipal de Abrantes (Finaabrantes) e o Centro de Informática e Sistemas do IPT.',
            'frase_folha' => 'Reconhece alguém nesta fotografia? Escreva o número e o nome. Conte-nos também o que se lembra.',
            'quem_e_quem' => '<p>Estas pessoas ainda estão por identificar. Se reconhecer alguém, diga-nos o número e a referência da fotografia: na folha do café ou por email.</p>',
            'privacidade' => implode("\n", [
                '<p>Este arquivo guarda fotografias, documentos e nomes de pessoas reais da aldeia, algumas ainda vivas. Os nomes só aparecem no site depois de confirmados no backoffice por um editor da Sociedade Recreativa.</p>',
                '<p>O site não tem contas, formulários nem comentários públicos, e não usa cookies de publicidade nem de estatísticas. As letras do site estão alojadas no próprio servidor.</p>',
                '<p>Se aparece numa fotografia, ou é familiar de alguém que aparece, pode pedir que o nome seja corrigido ou retirado, ou que a fotografia deixe de estar visível.</p>',
            ]),
        ];

        foreach (array_keys(config('crevelhos.textos')) as $chave) {
            Texto::firstOrCreate(['chave' => $chave], ['valor' => $iniciais[$chave] ?? null]);
        }
    }
}
