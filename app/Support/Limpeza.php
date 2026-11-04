<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Regras de limpeza dos dados antigos (e de texto colado no backoffice).
 * Funções puras: fáceis de testar sem base de dados.
 */
class Limpeza
{
    /** Etiquetas permitidas na descrição. Tudo o resto sai (ficam só os textos). */
    public const ETIQUETAS = ['p', 'br', 'strong', 'em', 'ul', 'ol', 'li', 'a'];

    private static ?HtmlSanitizer $sanitizer = null;

    /** Decodifica entidades, garante UTF-8, junta espaços e faz trim. Vazio passa a null. */
    public static function texto(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $valor = self::utf8($valor);
        $valor = html_entity_decode($valor, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $valor = str_replace("\u{00A0}", ' ', $valor);
        $valor = trim(preg_replace('/\s+/u', ' ', $valor));

        return $valor === '' ? null : $valor;
    }

    /** Como texto(), mas os "desconhecido" (e gralhas) passam a null. */
    public static function nome(?string $valor): ?string
    {
        $valor = self::texto($valor);

        return ($valor === null || self::ehDesconhecido($valor)) ? null : $valor;
    }

    public static function ehDesconhecido(string $valor): bool
    {
        // desconhecido, Desconhecido , Descolnhecido, desconhecida...
        return (bool) preg_match('/^desco[a-z]{0,2}nhecid[oa]s?\.?$/iu', trim($valor));
    }

    /**
     * HTML limpo: só p, br, strong, em, ul, ol, li, a. Sem style=, sem restos do Word,
     * sem parágrafos vazios. Devolve null se não sobrar texto.
     */
    public static function html(?string $valor): ?string
    {
        if ($valor === null || trim($valor) === '') {
            return null;
        }

        $valor = self::utf8($valor);
        // Blocos que não queremos passam a parágrafos (assim o texto não se perde).
        $valor = preg_replace('~<(/?)(div|h[1-6]|blockquote)\b[^>]*>~i', '<$1p>', $valor);
        $valor = preg_replace(['~<(/?)b\b[^>]*>~i', '~<(/?)i\b[^>]*>~i'], ['<$1strong>', '<$1em>'], $valor);

        $html = self::sanitizer()->sanitize($valor);

        $html = str_replace(["\u{00A0}", "\r"], [' ', ''], $html);
        $html = preg_replace('/[ \t]+/u', ' ', $html);

        // Remove etiquetas vazias (repetido para apanhar <p><strong><em></em></strong></p>).
        do {
            $antes = $html;
            $html = preg_replace('~<(p|strong|em|li|ul|ol)>(\s|<br\s*/?>)*</\1>~u', '', $html);
        } while ($html !== $antes);

        $html = preg_replace('~\s*(<br\s*/?>\s*)+</p>~u', '</p>', $html);
        $html = preg_replace('~<p>\s+~u', '<p>', $html);
        $html = preg_replace('~\s+</p>~u', '</p>', $html);
        $html = preg_replace("~>\s*\n\s*<~u", ">\n<", $html);
        $html = trim($html);

        return trim(strip_tags($html)) === '' ? null : $html;
    }

    /** Texto simples a partir de HTML (para pesquisa, SEO e deteção de nomes). */
    public static function semHtml(?string $html): string
    {
        $texto = preg_replace('~<(br|/p|/li)\b[^>]*>~i', ' ', (string) $html);

        return (string) self::texto(strip_tags($texto));
    }

    /** Ano de 4 dígitos entre 1850 e 2030, se houver. */
    public static function ano(?string $data): ?int
    {
        if ($data && preg_match('/(?<!\d)(18[5-9]\d|19\d\d|20[0-2]\d|2030)(?!\d)/', $data, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    public static function decada(?string $data): ?int
    {
        $ano = self::ano($data);

        return $ano === null ? null : intdiv($ano, 10) * 10;
    }

    /** Texto da data, sem o local colado no fim ("1955 - Casais de Revelhos" → "1955"). */
    public static function dataTexto(?string $data): ?string
    {
        $data = self::texto($data);
        if ($data === null) {
            return null;
        }

        $data = preg_replace('/\s+-\s+(Casais de Revelhos|Casais|Abrantes)\s*$/iu', '', $data);

        return trim($data) ?: null;
    }

    /**
     * Chave para comparar nomes de temas: sem acentos, minúsculas, espaços únicos.
     * Assim "Pró"/"Pro" e "Folclórico"/"Folclorico" dão a mesma chave.
     */
    public static function chaveTema(string $nome): string
    {
        $nome = self::texto($nome) ?? '';
        $nome = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $nome);

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $nome));
    }

    private static function utf8(string $valor): string
    {
        return mb_check_encoding($valor, 'UTF-8') ? $valor : mb_convert_encoding($valor, 'UTF-8', 'Windows-1252');
    }

    private static function sanitizer(): HtmlSanitizer
    {
        if (self::$sanitizer) {
            return self::$sanitizer;
        }

        $config = (new HtmlSanitizerConfig)
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowRelativeLinks()
            ->withMaxInputLength(-1);

        foreach (self::ETIQUETAS as $etiqueta) {
            $config = $config->allowElement($etiqueta, $etiqueta === 'a' ? ['href'] : []);
        }

        // Etiquetas de formatação que saem mas deixam o texto lá dentro.
        foreach (['span', 'font', 'u', 'small', 'sup', 'sub', 'o:p', 'section', 'article'] as $etiqueta) {
            $config = $config->blockElement($etiqueta);
        }

        return self::$sanitizer = new HtmlSanitizer($config);
    }
}
