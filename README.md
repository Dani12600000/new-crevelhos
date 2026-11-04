# Memória Digital de Casais de Revelhos

Museu digital da aldeia de Casais de Revelhos (Abrantes): fotografias, postais, cartazes, recortes de imprensa, mapas, livros e vídeos, com a identificação das pessoas feita pela comunidade (os números ①②③ nas fotografias expostas no café).

Substitui o site de 2017 (crevelhos.ipt.pt, PHP 5.3). O contexto completo do projeto está em [CLAUDE.md](CLAUDE.md).

- **Site público** (só de visualização): Início, Arquivo com filtros, salas (Fototeca, Imprensa, Cartazes, Mapas e plantas, Videoteca, Biblioteca), página de cada peça, pessoas, Linha do tempo, Quem é quem?, Sobre e Privacidade.
- **Backoffice** em `/admin` (Filament): peças, marcação de pessoas, sugestões de nomes, memórias da folha do café, temas, acervos, textos do site e utilizadores.

## Tecnologia

| | |
| --- | --- |
| PHP | 8.3 ou mais recente (o Laravel 13 exige 8.3) |
| Framework | Laravel 13 |
| Backoffice | Filament 5 |
| Base de dados | MySQL 8.4 (utf8mb4) |
| Imagens | Intervention Image 3 com GD (WebP + miniaturas geradas no upload) |
| Front-end | Blade, CSS e um pouco de JavaScript, compilados com Vite. Sem framework |
| Letras | Alegreya, Atkinson Hyperlegible e IBM Plex Mono, alojadas no próprio site (pacotes `@fontsource`) |

Pensado para **alojamento partilhado com cPanel**: sem Redis, filas `sync`, cache em ficheiro, tarefas agendadas por cron.

## Instalação local (Windows + Herd)

```bash
composer install
npm install
npm run build
cp .env.example .env
php artisan key:generate
```

No `.env`, preencha `DB_PASSWORD` (MySQL local) e confirme `CREVELHOS_LEGADO_PATH` (pasta com o `crevelhos.sql` e as imagens antigas). Depois:

```bash
php artisan crevelhos:carregar-dump
php artisan migrate
php artisan storage:link
php artisan db:seed
php artisan crevelhos:importar
php artisan serve --port=8001
```

- `crevelhos:carregar-dump` cria as bases `crevelhos` e `crevelhos_antigo` e carrega o dump antigo na segunda (só leitura, conexão `legado`).
- `db:seed` cria os textos do site que faltam e, **só em ambiente local**, um administrador de teste (ver `database/seeders/DatabaseSeeder.php`).
- `crevelhos:importar` importa tudo e escreve o relatório em `storage/app/importacao/relatorio.md`.

O backoffice fica em <http://localhost:8001/admin>.

## Comandos do projeto

| Comando | O que faz |
| --- | --- |
| `php artisan crevelhos:importar` | Importa a base antiga. Pode correr várias vezes (atualiza pelo par `legado_tabela` + `legado_id`; não mexe no título, acervo, publicado e destaque depois da primeira vez). Opções: `--sem-ficheiros`, `--forcar-textos`, `--pasta=`, `--relatorio=` |
| `php artisan crevelhos:carregar-dump` | Só desenvolvimento: cria as bases e carrega o `crevelhos.sql` |
| `php artisan crevelhos:utilizador email@exemplo.pt --admin` | Cria ou atualiza um utilizador do backoffice (pede nome e palavra-passe) |
| `php artisan crevelhos:copia-seguranca` | Cópia de segurança (SQL + imagens originais + fotos das folhas) em `storage/app/copias/`. Guarda as últimas 14. Corre todas as noites às 03:30 |
| `php artisan test` | Testes (importador, limpeza, marcações, pesquisa, redirecionamentos, backoffice) |

## Onde está cada coisa

| | |
| --- | --- |
| `app/Importacao/` | Importador, relatório e deteção de nomes nas descrições |
| `app/Support/Limpeza.php` | Regras de limpeza: entidades HTML, "desconhecido", HTML permitido, datas e décadas |
| `app/Support/PesquisaPecas.php` | Filtros e pesquisa do arquivo (procura também nos nomes das pessoas) |
| `app/Services/ImagensPeca.php` | Guarda o original em `pecas/{ref}/` e gera `grande.webp` e `miniatura.webp` |
| `app/Http/Middleware/RedirecionarSiteAntigo.php` | Redireciona `?pagina=fototeca&categoria=48` (e afins) para os URLs novos |
| `app/Enums/` | Tipos de peça (e prefixos da ref), secções, papéis, origens e estados |
| `app/Filament/` | Backoffice. `Resources/Pecas/Pages/MarcacoesPeca.php` é o editor de marcações |
| `config/crevelhos.php` | Textos editáveis, tamanhos das imagens, pasta dos ficheiros antigos |
| `resources/views/` | Páginas do site (Blade). `folha-cafe.blade.php` é a folha A5 para imprimir |
| `resources/css/app.css` | Todo o design do site (cores em variáveis no início; tema claro e escuro) |

### Conceitos

- **Ref** (`CRV-F-0001`): criada ao gravar, sequencial por prefixo (F fotografia e postal, C cartaz, I imprensa, V vídeo, M mapa, B livro). Mudar o tipo de uma peça não muda a ref.
- **Marcação**: um número numa fotografia, com posição `x`/`y` em percentagem e o tamanho do recorte da cara. Os números não se reaproveitam quando se apaga um, porque a folha do café pode já estar impressa.
- **Sugestão**: um nome escrito na folha (ou dito numa conversa, ou enviado por email) para um número. Ao aceitar, o número fica ligado à pessoa e as outras sugestões pendentes passam a rejeitadas. A fila avisa quando há nomes diferentes para o mesmo número.
- **RGPD**: nada aparece no site sem passar pelo backoffice. Uma marcação ou uma pessoa pode ser **escondida** sem se apagar; a pesquisa e as páginas públicas ignoram-nas. As memórias só aparecem depois de publicadas.

## Publicar num alojamento cPanel

1. **PHP**: no *MultiPHP Manager*, escolha PHP 8.3 ou superior, com as extensões `pdo_mysql`, `gd` (com WebP), `intl`, `zip`, `fileinfo` e `mbstring`. No *MultiPHP INI Editor*: `upload_max_filesize = 32M`, `post_max_size = 40M`, `memory_limit = 256M` (o GD precisa de memória para fotografias grandes).
2. **Base de dados**: em *MySQL Databases*, crie a base e o utilizador, com todas as permissões.
3. **Ficheiros**: envie o projeto para uma pasta **fora** do `public_html` (por exemplo `~/crevelhos`), sem `node_modules`. Compile antes, no computador, com `npm run build` e envie também `public/build`.
4. **Pasta pública**: em *Domains*, aponte a raiz do domínio para `~/crevelhos/public`. Se não for possível, substitua o `public_html` por uma ligação simbólica para essa pasta.
5. **Terminal** (cPanel → *Terminal*, ou SSH), dentro de `~/crevelhos`:

   ```bash
   composer install --no-dev --optimize-autoloader
   cp .env.example .env
   php artisan key:generate
   ```

   No `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://dominio-novo`, os dados `DB_*` e os dados `MAIL_*` (para recuperar palavras-passe).

   ```bash
   php artisan migrate --force
   php artisan storage:link
   php artisan db:seed --class=TextosSeeder --force
   php artisan crevelhos:utilizador email@exemplo.pt --admin
   php artisan optimize
   php artisan filament:optimize
   ```

6. **Conteúdo**: o mais simples é importar no computador e levar o resultado:
   - exporte a base local (`mysqldump crevelhos > crevelhos.sql`, ou no Workbench) e importe-a no phpMyAdmin do alojamento;
   - envie a pasta `storage/app/public/pecas` para o mesmo sítio no alojamento.

   Também pode importar diretamente no alojamento: crie uma segunda base com o dump antigo, preencha `DB_LEGADO_*`, envie as imagens antigas e corra `php artisan crevelhos:importar --pasta=/caminho/das/imagens`.
7. **Cron** (cPanel → *Cron Jobs*), uma vez por minuto:

   ```
   * * * * * cd ~/crevelhos && php artisan schedule:run >> /dev/null 2>&1
   ```

   Faz a cópia de segurança diária. Convém descarregar de vez em quando uma cópia de `storage/app/copias/` para fora do alojamento.

Ao atualizar o código: enviar os ficheiros, `composer install --no-dev`, `php artisan migrate --force`, `php artisan optimize`.

## Estado e próximos passos

Feito: base, importador com relatório, site público, backoffice, marcações e sugestões, memórias, folha do café com QR code, linha do tempo, Quem é quem?, peça ao acaso, redirecionamentos antigos, cópias de segurança e testes.

Falta decidir ou rever com a Sociedade e o Dr. Joaquim Pombo:

- rever o **relatório da importação** (temas sem uso, duplicados, datas sem década, nomes nas descrições);
- escrever **títulos** para as peças (o site antigo não os tinha);
- confirmar o **email de contacto**: os textos antigos usam `s.r.casaisrevelhos@gmail.com` e `de.revelhos@gmail.com`;
- rever o texto da página de **Privacidade** (é um texto inicial);
- as perguntas em aberto do [CLAUDE.md](CLAUDE.md): domínio e alojamento, redirecionamento de crevelhos.ipt.pt, conteúdo para a Biblioteca, os Mapas e a Videoteca, e quem transcreve as folhas do café.
