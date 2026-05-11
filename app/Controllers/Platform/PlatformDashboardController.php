<?php
namespace App\Controllers\Platform;

use App\Core\Controller;
use App\Core\Database;

class PlatformDashboardController extends Controller {
    public function index(): void {
        $pdo = Database::getInstance();

        $stats = $pdo->query("
            SELECT
                (SELECT COUNT(*) FROM bi_tenants WHERE status = 'ativo')                    AS tenants_ativos,
                (SELECT COUNT(*) FROM bi_tenants WHERE status = 'trial')                    AS tenants_trial,
                (SELECT COUNT(*) FROM bi_exames)                                            AS total_exames,
                (SELECT SUM(p.preco_mensal) FROM bi_tenants t JOIN bi_plans p ON p.id = t.plan_id WHERE t.status = 'ativo') AS mrr
        ")->fetch();

        $crescimento = $pdo->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m') AS mes, COUNT(*) AS total
            FROM bi_tenants
            GROUP BY mes
            ORDER BY mes DESC
            LIMIT 12
        ")->fetchAll();

        $trialExpirando = $pdo->query("
            SELECT nome, trial_expira_em, email_contato
            FROM bi_tenants
            WHERE status = 'trial' AND trial_expira_em <= DATE_ADD(NOW(), INTERVAL 7 DAY)
            ORDER BY trial_expira_em
        ")->fetchAll();

        $this->view('platform/dashboard', compact('stats', 'crescimento', 'trialExpirando'), 'platform');
    }
}
