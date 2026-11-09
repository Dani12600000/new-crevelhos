<?php

use App\Enums\Seccao;
use App\Http\Controllers\ArquivoController;
use App\Http\Controllers\CopiaSegurancaController;
use App\Http\Controllers\FolhaCafeController;
use App\Http\Controllers\PecaController;
use App\Http\Controllers\PessoaController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

// Site público (só de visualização). Os URLs antigos (?pagina=...) são
// redirecionados pelo middleware RedirecionarSiteAntigo.

Route::get('/', [SiteController::class, 'inicio'])->name('inicio');
Route::get('/index.php', [SiteController::class, 'inicio']); // o middleware redireciona ?pagina=...
Route::get('/sobre', [SiteController::class, 'sobre'])->name('sobre');
Route::get('/privacidade', [SiteController::class, 'privacidade'])->name('privacidade');
Route::get('/linha-do-tempo', [SiteController::class, 'linhaDoTempo'])->name('linha-do-tempo');
Route::get('/quem-e-quem', [SiteController::class, 'quemEQuem'])->name('quem-e-quem');

Route::get('/arquivo', [ArquivoController::class, 'index'])->name('arquivo');
Route::get('/peca-ao-acaso', [PecaController::class, 'aoAcaso'])->name('peca.acaso');
Route::get('/peca/{peca}', [PecaController::class, 'show'])->name('peca');
Route::get('/pessoa/{pessoa}', [PessoaController::class, 'show'])->name('pessoa');

// Folha para imprimir e pôr na mica do café (só backoffice).
Route::get('/admin/folha/{peca}', FolhaCafeController::class)->middleware('auth')->name('folha-cafe');

Route::get('/admin/copia-seguranca', CopiaSegurancaController::class)->middleware('auth')->name('copia-seguranca');

// /fototeca, /imprensa, /cartazes, /mapas-e-plantas, /videoteca, /biblioteca
Route::get('/{seccao}', [ArquivoController::class, 'seccao'])
    ->whereIn('seccao', array_column(Seccao::cases(), 'value'))
    ->name('seccao');
