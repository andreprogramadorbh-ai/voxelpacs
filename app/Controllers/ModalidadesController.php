<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Modalidade;

class ModalidadesController extends Controller {
    public function index(): void {
        $modalidades = (new Modalidade())->findAll();
        $this->view('modalidades/index', ['modalidades' => $modalidades]);
    }
}
