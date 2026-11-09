# Crevelhos — Memória Digital de Casais de Revelhos

> Contexto para o Claude Code. Guarda este ficheiro como `CLAUDE.md` na raiz do projeto novo.

## O que é

Renovação do site **Memória Digital de Casais de Revelhos** (crevelhos.ipt.pt): um museu digital da aldeia de Casais de Revelhos (Abrantes), com fotografias, cartazes, recortes de imprensa, mapas, livros e vídeos.

- Projeto de voluntariado do Daniel, supervisionado pelo **Dr. Joaquim Pombo** (CIS do IPT, coordenador do projeto original).
- Parceiros: Sociedade Recreativa Pró Casais de Revelhos, Câmara Municipal de Abrantes (Finaabrantes), CIS do IPT.
- O site atual é de 2017 e corre em **PHP 5.3.6 / MySQL 5.1.57**. Vai ser **refeito de raiz** e alojado **fora do IPT**, num alojamento partilhado com cPanel.
- Quem mantém depois é pessoal não técnico (backoffice) e, eventualmente, o CIS (código). Por isso: código convencional, "à maneira do Laravel", e nada exótico.

## Conceito

- **Museu com memória coletiva.** As fotografias estão expostas no café da aldeia, cada uma numa mica com uma folha onde as pessoas escrevem comentários e quem aparece na foto.
- **Identificação por números.** Cada pessoa na foto tem um número (①②③). Quem a reconhece diz quem é, e com o tempo a foto fica toda identificada.
- **O site é só de visualização.** Não há contas nem comentários públicos. O que se escreve na folha do café é transcrito no backoffice.
- **Cada mica tem um QR code** que abre a página da peça no site.
- **Backoffice:** edita os **textos e imagens**. O **design fica fixo no código**: nada de page builder.

## Stack

| Área | Escolha |
| --- | --- |
| Linguagem | PHP 8.2+ |
| Framework | Laravel (última versão estável) |
| Backoffice | Filament (versão compatível) |
| Base de dados | MySQL 8.4 LTS (utf8mb4) |
| Front-end | Blade + um componente pequeno em JavaScript (ou Vue) para marcar pessoas nas fotos |
| Imagens | Conversão para WebP + miniaturas geradas no upload (por exemplo, Intervention Image) |
| Vídeos | YouTube/Vimeo incorporado (só o URL fica guardado) |

Antes de instalar pacotes, confirma as versões atuais e a compatibilidade entre Laravel e Filament.

**Compatível com alojamento partilhado:**
- sem Redis;
- queue `sync` ou `database`;
- cache em ficheiro ou base de dados;
- `storage:link`;
- tarefas agendadas só via cron.

## Ambiente de desenvolvimento

- Windows 11 x64; MySQL 8.4.13 em `localhost:3306` (root); MySQL Workbench.
- O Daniel já tem outro projeto Laravel + Filament (o FAQ do IPT), por isso já conhece a stack.
- **Ficheiros antigos:** `C:\Users\IPT\Documents\crevelhos\`
  - `crevelhos.sql`: dump phpMyAdmin da base de dados antiga;
  - 65 imagens na raiz (`fototeca.<hash>.jpg`, `cartaz.<hash>.jpg`, `imprensa.<hash>.jpg`, `videoteca.<hash>.jpg`), cerca de 17 MB;
  - `tmbs/`: miniaturas antigas, muito pequenas (2 a 5 KB). **Ignorar**: as miniaturas são geradas de novo. Tem 5 ficheiros a mais, de registos apagados.
  - `web.config`: do IIS antigo, ignorar.

Bases de dados locais:
- `crevelhos`: a nova, criada pelas migrações;
- `crevelhos_antigo`: o dump antigo importado tal como está, só para leitura. Usar uma conexão `legado` em `config/database.php`.

## Estrutura de dados (nova)

| Tabela | Campos principais | Notas |
| --- | --- | --- |
| `pecas` | id, ref (único, ex.: CRV-F-0001), tipo, titulo, descricao (HTML limpo), data_texto, decada (nullable), autor, cedido_por, local, ficheiro, video_url, publicado, destaque, legado_tabela, legado_id, timestamps | `tipo`: foto, postal, cartaz, imprensa, video, mapa, livro |
| `pecas_imprensa` (ou colunas extra) | jornal, data_publicacao, url_original | Só para o tipo imprensa |
| `pecas_livro` (ou colunas extra) | edicao, editora, data_edicao, paginas | Para a biblioteca (hoje vazia) |
| `temas` | id, nome, slug | **Um conjunto único** para todo o site (ver migração) |
| `peca_tema` | peca_id, tema_id | |
| `acervos` | id, nome | Ex.: Sociedade Recreativa, Arquivo Municipal, Biblioteca Municipal António Botto, Comunidade |
| `pessoas` | id, nome, alcunha, notas | Cada pessoa tem página própria: "aparece em X fotos" |
| `marcacoes` | id, peca_id, numero, x (%), y (%), pessoa_id (nullable) | Posição em **percentagem** |
| `sugestoes` | id, marcacao_id, nome_sugerido, fonte (folha do café, email, conversa), data, estado | Várias sugestões para o mesmo número; o editor aceita uma |
| `comentarios` | id, peca_id, autor, texto, origem, data, foto_folha (nullable), publicado | Nada é público sem ser publicado no backoffice |
| `textos` | chave, valor | Textos editáveis: início, sobre, ficha técnica, introdução de cada secção, rodapé, contacto, frase da folha do café |
| `users` | padrão + papel (admin, editor) | Só backoffice |

Prefixo da ref por tipo:

| Tipo | Prefixo |
| --- | --- |
| foto e postal | F |
| cartaz | C |
| imprensa | I |
| vídeo | V |
| mapa | M |
| livro | B |

A ref é gerada automaticamente com numeração sequencial por tipo.

## Migração da base de dados antiga

Implementar como **comando Artisan idempotente**: `php artisan crevelhos:importar`. Pode correr várias vezes; atualiza pelo par `legado_tabela` + `legado_id`. No fim gera um **relatório** em `storage/app/importacao/relatorio.md` com tudo o que precisa de revisão manual.

### Estrutura antiga

**Tabelas de conteúdo:**

| Tabela | Registos | Notas |
| --- | --- | --- |
| `fototeca` | 45 | ids com buracos: 36, 38, 44, 48, 49 foram apagados |
| `cartazes` | 11 | |
| `imprensa` | 8 | O id 1 é **teste** ("ddfdsfsdfsdf") → não importar |
| `videoteca` | 1 | É **teste** ("aa") → não importar |
| `biblioteca` | 0 | Vazia |
| `mapaplanta` | 0 | Vazia |

**Tabelas de apoio:**
- `temas`: 67 temas, numa só tabela com `destino` (1 imprensa, 2 fototeca, 3 cartazes, 4 mapas, 5 videoteca, 6 biblioteca).
- `tipo_foto`: 1 Fotografia, 2 Postal.
- `paginas`: textos das páginas (inicio, ficha_tecnica, uma intro por secção) → importar para `textos`.
- `menu`: lixo de um template → ignorar.
- Não existe tabela de utilizadores.

### Mapeamento de campos

**fototeca:**

| Campo antigo | Campo novo |
| --- | --- |
| `fotografo` | `autor` |
| `fonte` | `cedido_por` (quem partilhou a foto) |
| `localizacao` | `local` |
| `data` | `data_texto` + `decada` |
| `descricao` | `descricao` |
| `nome_ficheiro` | `ficheiro` |
| `data_submissao` | `created_at` |
| `id_tipo_foto` | `tipo` (1 → foto, 2 → postal) |
| `editor` | não tem campo novo próprio; vai para as notas internas |

**cartazes:** `autor`, `fonte` → `cedido_por`, `data` → `data_texto`, `descricao`.

**imprensa:** `jornal`, `data_publicacao`, `fonte` (às vezes é um URL → `url_original`), `descricao`.

**Todos:** `temas` vem como texto, por exemplo `'|48|84|51|'`. Fazer split → `peca_tema`, através do mapa de temas (abaixo).

### Limpeza obrigatória

- **Texto:** decodificar entidades HTML (`&oacute;`, `&#245;`, `&atilde;`…) e garantir UTF-8. O dump mistura entidades com caracteres normais.
- **HTML:** retirar `style="..."` e outros restos do Word. Manter só `p, br, strong, em, ul, ol, li, a`. Converter `<p>&nbsp;</p>` vazios em nada.
- **Valores "desconhecido":** normalizar todas as variantes ("desconhecido", "Desconhecido ", "Descolnhecido", "Desconhecido") para `NULL` nos campos de autor e cedido_por.
- **Espaços:** fazer trim em tudo, incluindo os espaços a mais nos nomes de temas.

### Temas: um conjunto único

1. Normalizar o nome: decodificar entidades, fazer trim, juntar espaços duplos, uniformizar variantes (Pró/Pro, Folclórico/Folclorico, "Rancho Etnográfico e Folclórico do Casais" / "Rancho Folclórico e Etnográfico de Casais de Revelhos").
2. Os temas com o mesmo nome normalizado passam a ser **um só**. Exemplos: "Aldeia" aparece 3 vezes, "Sociedade Recreativa" 3, e "Desporto", "Cidade", "Palácio da Anadia" e "Nossa Senhora das Necessidades" 2 vezes cada.
3. Guardar o mapa `id antigo → id novo` (tabela `temas_legado` ou array no comando).
4. Corrigir a gralha "Vellhice" → "Velhice".
5. **35 dos 67 temas não são usados por nenhuma peça.** Importá-los na mesma, mas listá-los no relatório para a Sociedade decidir.

### Datas

O campo `data` é texto livre. Exemplos reais:
- `1962-05-01`
- `1950-Fevereiro-14`
- `Outubro de 1967`
- `1955 - Casais de Revelhos`
- `16/12/2017`
- `Janeiro de 2018`
- `11-12-13-14-15` (dias de festa, sem ano)
- `11 de Novembro`
- `22-Outubro-2017`
- `NULL`

Regras:
- `data_texto` = texto original limpo (sem "- Casais de Revelhos" quando for claramente o local).
- `decada` = extraída quando houver um ano de 4 dígitos (1850–2030). Caso contrário, `NULL` e entrada no relatório.
- **Imprensa:** 5 dos 8 recortes têm `data_publicacao = 1999-04-13`, um valor por defeito falso. Tratar essa data como desconhecida: `NULL` e entrada no relatório.

### Ficheiros

- Copiar de `C:\Users\IPT\Documents\crevelhos\` para o disco do Laravel (ex.: `storage/app/public/pecas/`), com nomes novos (por exemplo, a ref).
- Gerar a versão WebP e as miniaturas. Guardar o original também.
- **Detetar duplicados por hash (SHA-1)** e listá-los no relatório. Há pelo menos 3 pares com o mesmo tamanho exato:
  - `fototeca` 15 e 25 (`a3d14d…` / `fac251…`);
  - `fototeca` 14 e `imprensa` 1 (`521edd…` / `4ec006…`);
  - `cartazes` 2 e `videoteca` 1 (`d5f49a…` / `c4968c…`).

### Pessoas

- **Não extrair nomes automaticamente** das descrições.
- **Listar no relatório** as peças cujas descrições têm nomes, por exemplo a `fototeca` 3, com 18 nomes "identificadas nas tardes de Tecnologia", e as 1, 21 e 23. Um editor cria depois as pessoas e as marcações no backoffice.

### Relatório final

Deve incluir:
- totais importados por tipo;
- registos ignorados e porquê;
- temas unidos (antigo → novo);
- temas sem uso;
- datas sem década;
- datas falsas da imprensa;
- duplicados por hash;
- descrições vazias;
- peças com nomes nas descrições.

## Site público

- **Secções sempre visíveis**, mesmo vazias (Biblioteca e Mapas estão vazias hoje). Uma secção vazia mostra um estado vazio com o texto editável da secção e um convite a contribuir (email de contacto).
- **Navegação:** Início, Arquivo (com filtros), Linha do tempo, Quem é quem?, Sobre. As secções por tipo (Fototeca, Imprensa, Cartazes, Mapas e plantas, Videoteca, Biblioteca) ficam acessíveis no Início e no Arquivo.
- **Página da peça:**
  - imagem com os números ①②③ por cima; tocar ou passar o rato mostra o nome;
  - botão para esconder os números;
  - lista "Quem está na fotografia" ligada às pessoas;
  - ficha (ref, tipo, data, tema, acervo, cedido por);
  - memórias publicadas;
  - anterior e seguinte.
- **Página da pessoa:** recortes da cara em cada foto onde aparece, ao longo dos anos, e as fotos.
- **"Quem é quem?":** recortes das pessoas ainda por identificar, para a comunidade ajudar.
- **Linha do tempo** por décadas.
- **Pesquisa** com filtros por tipo, década e tema; procura também nos nomes das pessoas.
- **URLs amigáveis:** `/peca/CRV-F-0001`, `/pessoa/maria-...`, `/arquivo?tipo=foto&decada=1950`. **Redirecionar os URLs antigos** (`?pagina=fototeca&categoria=48`) para os novos.
- Título e descrição por página (SEO).
- **Público mais velho:** letra grande (base ~18 px), bom contraste, navegação simples, boa leitura no telemóvel.

**Direção visual** (vem do protótipo aprovado pelo Daniel):
- ar de catálogo de museu: fichas com referência, fotografias como provas em papel;
- azul de azulejo como única cor de destaque;
- títulos em **Alegreya**, texto em **Atkinson Hyperlegible**, referências em **IBM Plex Mono**;
- tema claro e escuro.

## Backoffice (Filament)

- **Peças:**
  - CRUD completo, com upload em lote;
  - ref automática;
  - publicar e destacar;
  - filtros por tipo, tema e estado.
- **Editor de marcações** numa página da peça: clicar na imagem cria o próximo número na posição clicada (x/y em %). Para cada número:
  - escolher uma pessoa existente ou criar uma nova;
  - registar sugestões (nome escrito na folha);
  - aceitar ou rejeitar sugestões;
  - remover o número.
- **Pessoas:** CRUD, com o número de aparições.
- **Sugestões:** fila de todas as sugestões pendentes, com o recorte da cara; avisa quando há sugestões diferentes para o mesmo número.
- **Comentários:** transcrever o que está na folha (autor, texto, origem, foto da folha opcional); fila "por publicar".
- **Textos do site:** só campos de texto. O design não se edita.
- **Folha para o café:** página por peça pronta a imprimir (A5), com a imagem e os números, título, ref, a frase da folha, QR code para `/peca/{ref}` e linhas para escrever.
- Papéis **admin** e **editor**; interface em português de Portugal.

## RGPD

O arquivo tem **nomes de pessoas reais**, algumas vivas.
- Nada vai para o site público sem passar pelo backoffice.
- Página de privacidade e contacto para pedir que alguém seja retirado de uma foto.
- Possibilidade de esconder uma marcação ou pessoa sem a apagar.

## Plano (8 h/semana, partilhadas com o FAQ do IPT)

1. **Fase 1:** base, comando de importação e site público com as secções atuais.
2. **Fase 2:** backoffice (peças, temas, textos, utilizadores).
3. **Fase 3:** pessoas, marcações e sugestões.
4. **Fase 4:** comentários, QR code e folha para o café.
5. **Fase 5:** experiência de museu (linha do tempo, "Quem é quem?", destaques, peça ao acaso).

Transversal:
- testes (Pest/PHPUnit) para o importador, as marcações, a pesquisa e os redirecionamentos antigos;
- README de instalação e de deploy em cPanel;
- backups (mysqldump + ficheiros) por cron.

## Forma de trabalhar

- Começar pelo **importador e pelo relatório**: é o que valida a estrutura nova com dados reais.
- Explica as decisões e os conceitos novos do Laravel ou do Filament quando aparecem.
- Commits pequenos e claros, em [Conventional Commits](https://www.conventionalcommits.org/) e em inglês (por exemplo `fix: repair .env.example encoding`).
- Não inventar conteúdo: o arquivo é real. Para testes, usar factories marcadas como exemplo, nunca nomes inventados em fotos reais.

## Perguntas ainda em aberto (Dr. Joaquim Pombo)

- Como funciona o login do backoffice atual? Não há tabela de utilizadores no dump.
- Domínio novo e quem paga e é dono do alojamento (Sociedade Recreativa?).
- Redirecionamento de crevelhos.ipt.pt para o novo domínio.
- Há mais conteúdo para a Biblioteca e os Mapas (fala-se do acervo da Biblioteca Municipal e da Divisão de Urbanismo da CMA)?
- Há vídeos reais para a Videoteca?
- Quem transcreve as folhas do café, e quantas fotos vão estar expostas?

## Tarefas pendentes (revisão de 9 nov 2026)

O projeto base está feito (importação, site público, backoffice, marcações, folha do café, cópias de segurança). Faz estas tarefas **por ordem**, uma de cada vez, com um commit por tarefa. Corre `php artisan test` no fim de cada uma. Quando terminares uma, marca-a aqui com `[x]`.

### 1. Correções

- [x] **Testes a falhar.** Corre `php artisan test` e corrige o que falhar. Na última execução (5 nov) falharam 8: todos os de `PesquisaTest`, `ImportadorTest::test_relatorio_tem_tudo_o_que_precisa_de_revisao` e `MarcacoesTest::test_quem_e_quem_lista_os_numeros_por_identificar`. Se for um problema do ambiente (por exemplo, a base de dados de testes), explica ao Daniel em vez de mudar os testes.
- [x] **Acentos estragados no `.env.example`.** Corrige `APP_NAME` ("MemÃ³ria" → "Memória") e os comentários ("estÃ¡", "sÃ³"). Grava em UTF-8 sem BOM e confirma que não há mais ficheiros com `Ã`.
- [x] **Versões no `composer.json`.** Muda `"bacon/bacon-qr-code": "*"` para `^` + a versão major instalada (vê no `composer.lock`), e `"filament/filament": "5.11"` e `"intervention/image": "3.11"` para `^5.11` e `^3.11`. Corre `composer update --lock` e os testes.
- [x] **WebP maiores do que o original.** Em algumas peças, o `grande.webp` fica maior do que o original (por exemplo, CRV-I-0006). Em `ImagensPeca`, se a versão gerada ficar maior do que o original e o original já couber no tamanho máximo, baixa a qualidade até ficar mais pequena. Cria um comando `crevelhos:regenerar-imagens` para refazer as versões de todas as peças.

### 2. Backoffice para a catalogação

A Sociedade Recreativa vai catalogar as 63 peças (títulos, datas, descrições, acervos). O backoffice tem de tornar isso rápido.

- [x] **Filtros em falta na lista de peças.** Já há o filtro "sem título". Acrescenta "sem data" (`decada` vazia), "sem descrição" e "sem acervo".
- [x] **Edição rápida na tabela.** Permite editar título, data (texto) e acervo diretamente na lista de peças, sem abrir cada peça.
- [x] **"Cedido por" uniformizado.** No formulário da peça, o campo deve sugerir os valores que já existem (autocompletar), para não aparecerem variantes como "Srª Eugénia" e "Sra. Eugénia". Acrescenta uma ação de administrador para trocar todas as ocorrências de um valor por outro.

### 3. Pessoas

- [x] **Juntar pessoas duplicadas.** Ação "Juntar com…" na pessoa: passa todas as marcações da pessoa duplicada para a pessoa escolhida, junta alcunha e notas (sem perder nada), regista o nome antigo nas notas e apaga a duplicada. Tudo numa transação, com confirmação e com testes.

### 4. Segurança e manutenção

- [x] **Cópias de segurança fora do servidor.** As cópias ficam em `storage/app/copias`, no próprio alojamento. Acrescenta no painel do backoffice (só para administradores) um widget com a data da última cópia e um botão para a descarregar. Mostra um aviso se ninguém descarregar uma cópia há mais de 30 dias (guarda a data da última descarga).
- [x] **Convenção dos commits.** A partir de agora, usa [Conventional Commits](https://www.conventionalcommits.org/) **em inglês**, como no projeto do FAQ (por exemplo `fix: repair .env.example encoding`).

### 5. Melhorias menores

- [ ] **`sitemap.xml` e `robots.txt`**, com as páginas públicas, as secções, as peças publicadas e as pessoas visíveis.
- [ ] **Imagem de partilha em JPEG.** O `og:image` usa o WebP, que algumas redes sociais não mostram bem. Gera também uma versão JPEG para partilha.
- [ ] **Registo de pedidos de remoção (RGPD).** Tabela e ecrã no backoffice para registar cada pedido: quem pediu, quando, sobre que pessoa ou marcação, o que se fez e quando.

### Não fazer ainda (depende de decisões)

- Download do original em alta resolução ("Ver o original"): espera pela decisão da Sociedade sobre os direitos das imagens.
- Domínio, alojamento e publicação: esperam pelas respostas do Dr. Joaquim Pombo.
