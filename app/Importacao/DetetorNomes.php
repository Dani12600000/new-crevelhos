<?php

namespace App\Importacao;

/**
 * Procura possíveis nomes de pessoas numa descrição, só para o relatório.
 * Não cria pessoas: quem decide é um editor no backoffice.
 */
class DetetorNomes
{
    /** Palavras com maiúscula que não são nomes de pessoas. */
    private const NAO_NOMES = [
        'casais', 'revelhos', 'abrantes', 'angola', 'luanda', 'serra', 'estrela', 'portugal', 'lisboa', 'tomar',
        'escola', 'primária', 'primaria', 'nova', 'velha', 'turma', 'masculina', 'feminina', 'ano', 'foto', 'fotos',
        'sociedade', 'recreativa', 'pró', 'pro', 'bilhete', 'identidade', 'sr', 'srª', 'sra', 'srº', 'dª', 'cabo', 'chefe',
        'festas', 'verão', 'cartaz', 'evento', 'recordar', 'teatro', 'tecnologia', 'identificadas', 'família', 'familia',
        'equipa', 'grupo', 'visita', 'igreja', 'aldeia', 'mirante', 'tardes', 'concurso', 'fotografia', 'rancho',
        'folclórico', 'etnográfico', 'câmara', 'municipal', 'biblioteca', 'senhora', 'necessidades', 'nossa', 'palácio',
        'anadia', 'quinta', 'bom', 'sucesso', 'campo', 'futebol', 'padre', 'santo', 'são', 'santa', 'uma', 'um', 'venha',
        'aberto', 'homenagem', 'homengam', 'cartazes', 'divulgação',
    ];

    private const LIGACOES = ['de', 'da', 'do', 'das', 'dos'];

    /** @return list<string> */
    public static function candidatos(string $texto): array
    {
        $encontrados = [];

        foreach (preg_split('/(?<=[.!?:;])\s+/u', trim($texto), -1, PREG_SPLIT_NO_EMPTY) as $frase) {
            // Cada nome de uma lista acaba numa vírgula ou num "e".
            $partes = preg_split('/\s*[,()"]\s*|\s+e\s+/u', $frase, -1, PREG_SPLIT_NO_EMPTY);

            foreach ($partes as $p => $parte) {
                $palavras = preg_split('/\s+/u', trim($parte, " .!?:;"), -1, PREG_SPLIT_NO_EMPTY);
                $inicioDeFrase = $p === 0;
                $atual = [];
                $comeco = 0;

                foreach ($palavras as $i => $palavra) {
                    if (self::pareceNome($palavra)) {
                        $comeco = $atual ? $comeco : $i;
                        $atual[] = $palavra;
                    } elseif ($atual && in_array($palavra, self::LIGACOES, true) && self::pareceNome($palavras[$i + 1] ?? '')) {
                        $atual[] = $palavra; // "Maria do Rosário"
                    } else {
                        self::guardar($encontrados, $atual, $inicioDeFrase && $comeco === 0);
                        $atual = [];
                    }
                }

                self::guardar($encontrados, $atual, $inicioDeFrase && $comeco === 0);
            }
        }

        return array_values(array_unique($encontrados));
    }

    private static function pareceNome(string $palavra): bool
    {
        return preg_match('/^\p{Lu}[\p{L}\']+$/u', $palavra) === 1
            && ! in_array(mb_strtolower($palavra), self::NAO_NOMES, true);
    }

    /** Uma palavra só no início de uma frase é, quase sempre, só a maiúscula da frase. */
    private static function guardar(array &$encontrados, array $palavras, bool $noInicioDaFrase): void
    {
        if (! $palavras || (count($palavras) === 1 && $noInicioDaFrase)) {
            return;
        }

        $encontrados[] = implode(' ', $palavras);
    }
}
