<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fa fa-building me-2"></i>Negócios (Empresas)</h1>
    <a href="/platform/negocios/create" class="btn btn-primary shadow-sm">
        <i class="fa fa-plus fa-sm text-white-50 me-1"></i> Novo Negócio
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle" width="100%" cellspacing="0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Empresa</th>
                        <th>CNPJ</th>
                        <th>Plano</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($negocios)): ?>
                        <tr><td colspan="6" class="text-center py-4">Nenhum negócio cadastrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($negocios as $n): ?>
                            <tr>
                                <td>#<?= $n->id ?></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($n->nome) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($n->slug) ?></div>
                                </td>
                                <td><?= htmlspecialchars($n->cnpj ?? 'Não informado') ?></td>
                                <td><span class="badge bg-info text-dark">Plano <?= $n->plan_id ?></span></td>
                                <td>
                                    <?php if ($n->status === 'ativo'): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php elseif ($n->status === 'trial'): ?>
                                        <span class="badge bg-warning text-dark">Trial</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><?= ucfirst($n->status) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="/platform/negocios/<?= $n->id ?>/edit" class="btn btn-sm btn-outline-primary" title="Editar">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <form action="/platform/negocios/<?= $n->id ?>/impersonate" method="POST" class="d-inline">
                                        <input type="hidden" name="_csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Acessar como este negócio">
                                            <i class="fa fa-sign-in-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
