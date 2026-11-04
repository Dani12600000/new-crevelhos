<?php

return [

    // Pasta com os ficheiros do site antigo (imagens + crevelhos.sql). Só usada pelo importador.
    'legado_path' => env('CREVELHOS_LEGADO_PATH', 'C:/Users/IPT/Documents/crevelhos'),

    // Tamanhos (lado maior, em px) das versões WebP geradas no upload.
    'imagens' => [
        'grande' => 2000,
        'miniatura' => 480,
        'qualidade' => 82,
    ],

    // Textos editáveis no backoffice: chave => [rótulo, tipo (html|texto)].
    // O design não se edita; só estes conteúdos.
    'textos' => [
        'inicio' => ['Início: apresentação', 'html'],
        'sobre' => ['Sobre o projeto', 'html'],
        'ficha_tecnica' => ['Ficha técnica', 'html'],
        'seccao_fototeca' => ['Introdução: Fototeca', 'html'],
        'seccao_imprensa' => ['Introdução: Imprensa', 'html'],
        'seccao_cartazes' => ['Introdução: Cartazes', 'html'],
        'seccao_mapas_e_plantas' => ['Introdução: Mapas e plantas', 'html'],
        'seccao_videoteca' => ['Introdução: Videoteca', 'html'],
        'seccao_biblioteca' => ['Introdução: Biblioteca', 'html'],
        'quem_e_quem' => ['Introdução: Quem é quem?', 'html'],
        'privacidade' => ['Privacidade', 'html'],
        'rodape' => ['Rodapé', 'texto'],
        'email_contacto' => ['Email de contacto', 'texto'],
        'frase_folha' => ['Frase da folha do café', 'texto'],
    ],

];
