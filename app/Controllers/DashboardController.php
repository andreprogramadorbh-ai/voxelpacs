<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\TenantContext;
use App\Models\Exame;
use App\Models\Importacao;
use App\Services\KpiService;
use App\Services\PreditivoService;

class DashboardController extends Controller {

    public function index(): void {
        // Garante que o usuário está autenticado
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        $tenantId = TenantContext::id();

        // Se não há tenant selecionado, redireciona para seleção ou login
        if (!$tenantId && !Auth::isPlatformAdmin()) {
            $tenants = Auth::userTenants();
            if (count($tenants) > 1) {
                $this->redirect('/selecionar-empresa');
            } elseif (count($tenants) === 1) {
                Auth::setTenant($tenants[0]->tenant_id);
                $tenantId = TenantContext::id();
            } else {
                $this->redirect('/login?error=sem_acesso');
            }
        }

        $pacsId          = !empty($_GET['pacs_id']) ? (int) $_GET['pacs_id'] : null;
        $exameModel      = new Exame();
        $kpiService      = new KpiService();
        $predService     = new PreditivoService();
        $importacaoModel = new Importacao();

        $kpis           = $kpiService->resumoDashboard($pacsId);
        $evolucao       = $exameModel->evolucaoMensal($pacsId);
        $modalidades    = $exameModel->distribuicaoModalidade($pacsId);
        $topMedicos     = $exameModel->topMedicos(5, $pacsId);
        $slaStatus      = $exameModel->slaStatus($pacsId);
        // Só gera alertas se tenantId estiver disponível
        $alertas        = $tenantId ? $predService->gerarAlertas($tenantId) : [];
        $ultimasImports = $importacaoModel->findAll();

        $this->view('dashboard/index', compact(
            'kpis', 'evolucao', 'modalidades', 'topMedicos',
            'slaStatus', 'alertas', 'ultimasImports', 'pacsId'
        ));
    }

    public function apiDados(): void {
        if (!Auth::check()) {
            $this->json(['error' => 'Não autorizado'], 401);
        }

        $pacsId     = !empty($_GET['pacs_id']) ? (int) $_GET['pacs_id'] : null;
        $exameModel = new Exame();
        $kpiService = new KpiService();

        $this->json([
            'kpis'        => $kpiService->resumoDashboard($pacsId),
            'evolucao'    => $exameModel->evolucaoMensal($pacsId),
            'modalidades' => $exameModel->distribuicaoModalidade($pacsId),
            'topMedicos'  => $exameModel->topMedicos(5, $pacsId),
            'slaStatus'   => $exameModel->slaStatus($pacsId),
        ]);
    }
}
