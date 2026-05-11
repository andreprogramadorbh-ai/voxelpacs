<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\TenantContext;
use App\Models\User;

class UsuariosController extends Controller {
    public function index(): void {
        $usuarios = (new User())->findByTenant(TenantContext::id());
        $this->view('usuarios/index', ['usuarios' => $usuarios]);
    }

    public function create(): void {
        $this->view('usuarios/form', ['usuario' => null, 'title' => 'Novo Usuário']);
    }

    public function store(): void {
        $userModel = new User();
        $userId    = $userModel->create([
            'name'     => $_POST['name']     ?? '',
            'email'    => $_POST['email']    ?? '',
            'password' => $_POST['password'] ?? '',
            'role'     => $_POST['role']     ?? 'viewer',
            'status'   => 'ativo',
        ]);
        $userModel->attachToTenant($userId, TenantContext::id(), $_POST['role'] ?? 'viewer');
        $this->redirect('/usuarios');
    }

    public function edit(int $id): void {
        $this->view('usuarios/form', ['usuario' => null, 'title' => 'Editar Usuário']);
    }

    public function update(int $id): void {
        $this->redirect('/usuarios');
    }

    public function toggleStatus(int $id): void {
        $this->redirect('/usuarios');
    }
}
