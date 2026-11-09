<?php

namespace App\Http\Controllers;

use App\Services\CopiasSeguranca;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Descarrega a última cópia de segurança. Só para administradores: contém o arquivo todo. */
class CopiaSegurancaController extends Controller
{
    public function __invoke(CopiasSeguranca $copias): BinaryFileResponse
    {
        abort_unless(auth()->user()->ehAdmin(), 403);

        $ultima = $copias->ultima();
        abort_if($ultima === null, 404);

        $copias->registarDescarga();

        return response()->download($ultima);
    }
}
