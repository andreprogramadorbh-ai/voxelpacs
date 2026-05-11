<h4 class="fw-bold mb-4"><i class="fa fa-tachometer-alt me-2 text-primary"></i>Dashboard da Plataforma</h4>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fa fa-building fa-2x text-primary mb-2"></i>
                <h3 class="fw-bold"><?= number_format($stats->tenants_ativos ?? 0) ?></h3>
                <p class="text-muted small mb-0">Tenants Ativos</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fa fa-hourglass-half fa-2x text-warning mb-2"></i>
                <h3 class="fw-bold"><?= number_format($stats->tenants_trial ?? 0) ?></h3>
                <p class="text-muted small mb-0">Em Trial</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fa fa-x-ray fa-2x text-info mb-2"></i>
                <h3 class="fw-bold"><?= number_format($stats->total_exames ?? 0) ?></h3>
                <p class="text-muted small mb-0">Total de Exames</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fa fa-dollar-sign fa-2x text-success mb-2"></i>
                <h3 class="fw-bold text-success">R$ <?= number_format($stats->mrr ?? 0, 2, ',', '.') ?></h3>
                <p class="text-muted small mb-0">MRR Estimado</p>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($trialExpirando)): ?>
<div class="card border-0 shadow-sm border-start border-warning border-3 mb-4">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-bold mb-0 text-warning"><i class="fa fa-exclamation-triangle me-2"></i>Trials Expirando em 7 dias</h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Tenant</th><th>Expira em</th><th>Contato</th></tr></thead>
            <tbody>
            <?php foreach ($trialExpirando as $t): ?>
            <tr>
                <td><?= htmlspecialchars($t->nome) ?></td>
                <td><?= htmlspecialchars($t->trial_expira_em) ?></td>
                <td><?= htmlspecialchars($t->email_contato) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-bold mb-0"><i class="fa fa-chart-line me-2 text-primary"></i>Crescimento de Tenants</h6>
    </div>
    <div class="card-body">
        <canvas id="chartCrescimento" height="80"></canvas>
    </div>
</div>

<script>
new Chart(document.getElementById('chartCrescimento'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_reverse(array_column($crescimento, 'mes'))) ?>,
        datasets: [{ label: 'Novos Tenants', data: <?= json_encode(array_reverse(array_column($crescimento, 'total'))) ?>, backgroundColor: 'rgba(99,102,241,.7)', borderRadius: 4 }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});
</script>
