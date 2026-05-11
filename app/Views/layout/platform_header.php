<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'VOXEL B.I — Plataforma') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/bi.css">
</head>
<body>
<div class="d-flex" id="wrapper">
    <nav id="sidebar" class="d-flex flex-column text-white" style="min-width:250px;min-height:100vh;background:#1e1b4b;">
        <div class="p-3 border-bottom" style="border-color:rgba(255,255,255,.1)!important;">
            <div class="d-flex align-items-center gap-2">
                <i class="fa fa-cube text-indigo-400 fs-4" style="color:#818cf8;"></i>
                <div>
                    <div class="fw-bold fs-5">VOXEL B.I</div>
                    <small style="color:#a5b4fc;font-size:.7rem;">Painel da Plataforma</small>
                </div>
            </div>
        </div>
        <ul class="nav flex-column p-2 flex-grow-1">
            <li class="nav-item"><a href="/platform/dashboard" class="nav-link text-white <?= str_contains($_SERVER['REQUEST_URI'],'/platform/dashboard')?'active':'' ?>"><i class="fa fa-tachometer-alt me-2"></i>Dashboard</a></li>
            <li class="nav-item"><a href="/platform/negocios" class="nav-link text-white <?= str_contains($_SERVER['REQUEST_URI'],'/platform/negocios')?'active':'' ?>"><i class="fa fa-building me-2"></i>Negócios</a></li>
            <li class="nav-item"><a href="/platform/plans" class="nav-link text-white <?= str_contains($_SERVER['REQUEST_URI'],'/platform/plans')?'active':'' ?>"><i class="fa fa-tags me-2"></i>Planos</a></li>
            <li class="nav-item"><a href="/platform/reports" class="nav-link text-white <?= str_contains($_SERVER['REQUEST_URI'],'/platform/reports')?'active':'' ?>"><i class="fa fa-chart-line me-2"></i>Relatórios</a></li>
        </ul>
        <div class="p-3" style="border-top:1px solid rgba(255,255,255,.1);">
            <a href="/logout" class="btn btn-outline-light btn-sm w-100"><i class="fa fa-sign-out-alt me-1"></i>Sair</a>
        </div>
    </nav>
    <div id="page-content-wrapper" class="flex-grow-1 bg-light">
        <div class="container-fluid p-4">
