<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Auth;
use App\Core\TenantContext;

/**
 * VOXEL PACS — EstudosController
 * Worklist principal: lista, busca e abertura de estudos DICOM
 */
class EstudosController extends Controller {

    public function index(): void {
        $pdo = Database::getInstance();

        // ── Filtros da URL ──────────────────────────────────────
        $filtros = [
            'q'            => trim($_GET['q']            ?? ''),
            'paciente'     => trim($_GET['paciente']     ?? ''),
            'unidade'      => trim($_GET['unidade']      ?? ''),
            'modalidade'   => trim($_GET['modalidade']   ?? ''),
            'especialidade'=> trim($_GET['especialidade']?? ''),
            'situacao'     => trim($_GET['situacao']     ?? ''),
            'dt_inicio'    => trim($_GET['dt_inicio']    ?? ''),
            'dt_fim'       => trim($_GET['dt_fim']       ?? ''),
            'ordenar'      => trim($_GET['ordenar']      ?? 'study_date'),
            'direcao'      => in_array($_GET['direcao'] ?? '', ['ASC','DESC']) ? $_GET['direcao'] : 'DESC',
            'pagina'       => max(1, (int)($_GET['pagina'] ?? 1)),
            'por_pagina'   => 25,
        ];

        // ── Monta a query ───────────────────────────────────────
        $where  = ['1=1'];
        $params = [];

        // Filtro de tenant (multi-tenant)
        $tenantId = Auth::tenantId();
        if ($tenantId) {
            $where[]  = 'e.tenant_id = :tenant_id';
            $params[':tenant_id'] = $tenantId;
        }

        if ($filtros['paciente'] !== '') {
            $where[]  = 'e.patient_name LIKE :paciente';
            $params[':paciente'] = '%' . $filtros['paciente'] . '%';
        }

        if ($filtros['unidade'] !== '') {
            $where[]  = 'e.institution_name LIKE :unidade';
            $params[':unidade'] = '%' . $filtros['unidade'] . '%';
        }

        if ($filtros['modalidade'] !== '') {
            $where[]  = 'e.modalities LIKE :modalidade';
            $params[':modalidade'] = '%' . $filtros['modalidade'] . '%';
        }

        if ($filtros['especialidade'] !== '') {
            $where[]  = 'e.study_description LIKE :especialidade';
            $params[':especialidade'] = '%' . $filtros['especialidade'] . '%';
        }

        if ($filtros['situacao'] !== '') {
            $where[]  = 'e.situacao = :situacao';
            $params[':situacao'] = $filtros['situacao'];
        }

        if ($filtros['dt_inicio'] !== '') {
            $where[]  = 'e.study_date >= :dt_inicio';
            $params[':dt_inicio'] = $filtros['dt_inicio'];
        }

        if ($filtros['dt_fim'] !== '') {
            $where[]  = 'e.study_date <= :dt_fim';
            $params[':dt_fim'] = $filtros['dt_fim'];
        }

        if ($filtros['q'] !== '') {
            $where[]  = '(e.patient_name LIKE :q OR e.study_description LIKE :q2 OR e.accession_number LIKE :q3)';
            $params[':q']  = '%' . $filtros['q'] . '%';
            $params[':q2'] = '%' . $filtros['q'] . '%';
            $params[':q3'] = '%' . $filtros['q'] . '%';
        }

        $whereStr = implode(' AND ', $where);

        // Colunas permitidas para ordenação
        $colsPermitidas = ['study_date', 'patient_name', 'institution_name', 'modalities', 'study_description', 'situacao'];
        $orderCol = in_array($filtros['ordenar'], $colsPermitidas) ? 'e.' . $filtros['ordenar'] : 'e.study_date';
        $orderDir = $filtros['direcao'];

        // Total de registros
        try {
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM pacs_estudos e WHERE {$whereStr}");
            $stmtCount->execute($params);
            $total = (int) $stmtCount->fetchColumn();
        } catch (\Throwable $e) {
            $total = 0;
        }

        $totalPages  = max(1, (int) ceil($total / $filtros['por_pagina']));
        $currentPage = min($filtros['pagina'], $totalPages);
        $offset      = ($currentPage - 1) * $filtros['por_pagina'];

        // Busca os estudos
        try {
            $sql = "
                SELECT
                    e.id,
                    e.orthanc_id,
                    e.study_date,
                    e.study_time,
                    e.patient_name,
                    e.patient_sex,
                    e.patient_age,
                    e.patient_birth_date,
                    e.institution_name,
                    e.modalities,
                    e.study_description,
                    e.accession_number,
                    e.referring_physician_name,
                    e.num_series,
                    e.num_instances,
                    e.situacao,
                    e.especialidade,
                    e.orthanc_url
                FROM pacs_estudos e
                WHERE {$whereStr}
                ORDER BY {$orderCol} {$orderDir}
                LIMIT {$filtros['por_pagina']} OFFSET {$offset}
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $estudos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $estudos = [];
        }

        // Listas para filtros
        try {
            $unidades = $pdo->query("SELECT DISTINCT institution_name FROM pacs_estudos WHERE institution_name IS NOT NULL AND institution_name != '' ORDER BY institution_name")->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Throwable $e) {
            $unidades = [];
        }

        $situacoes = ['novo', 'aberto', 'rascunho', 'assinado', 'sem_imagens', 'sem_pendencia', 'urgente'];

        $this->view('estudos/index', [
            'title'       => 'Worklist — VOXEL PACS',
            'estudos'     => $estudos,
            'filtros'     => $filtros,
            'total'       => $total,
            'totalPages'  => $totalPages,
            'currentPage' => $currentPage,
            'unidades'    => $unidades,
            'situacoes'   => $situacoes,
        ], 'pacs');
    }

    /**
     * Abre o viewer DICOM para um estudo
     */
    public function abrir(int $id): void {
        $pdo = Database::getInstance();

        try {
            $stmt = $pdo->prepare("SELECT * FROM pacs_estudos WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $estudo = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $estudo = null;
        }

        if (!$estudo) {
            http_response_code(404);
            echo '<h1>Estudo não encontrado</h1>';
            return;
        }

        // URL do viewer (Ohif, Weasis, ou Orthanc Explorer)
        $orthancUrl  = $estudo['orthanc_url'] ?? getenv('ORTHANC_URL') ?: 'http://localhost:8042';
        $orthancId   = $estudo['orthanc_id']  ?? '';
        $viewerUrl   = $orthancId
            ? rtrim($orthancUrl, '/') . '/ohif/viewer?StudyInstanceUIDs=' . urlencode($estudo['study_instance_uid'] ?? $orthancId)
            : $orthancUrl . '/app/explorer.html';

        $this->view('estudos/viewer', [
            'title'      => 'Viewer — ' . htmlspecialchars($estudo['patient_name'] ?? 'Estudo'),
            'estudo'     => $estudo,
            'viewerUrl'  => $viewerUrl,
        ], 'pacs');
    }

    /**
     * API: contadores por situação para o topbar
     */
    public function contadores(): void {
        $pdo = Database::getInstance();
        try {
            $stmt = $pdo->query("
                SELECT situacao, COUNT(*) as total
                FROM pacs_estudos
                GROUP BY situacao
            ");
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $data = ['novo' => 0, 'aberto' => 0, 'rascunho' => 0, 'assinado' => 0];
            foreach ($rows as $r) {
                if (isset($data[$r['situacao']])) {
                    $data[$r['situacao']] = (int) $r['total'];
                }
            }
            $this->json($data);
        } catch (\Throwable $e) {
            $this->json(['novo' => 0, 'aberto' => 0, 'rascunho' => 0, 'assinado' => 0]);
        }
    }
}
