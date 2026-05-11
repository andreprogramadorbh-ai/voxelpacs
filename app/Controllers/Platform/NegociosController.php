<?php
namespace App\Controllers\Platform;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Tenant;
use App\Models\TenantPlan;
use App\Models\User;

class NegociosController extends Controller {

    public function index(): void {
        $pdo = Database::getInstance();
        $negocios = $pdo->query("
            SELECT t.*, p.nome as plano_nome 
            FROM bi_tenants t 
            LEFT JOIN bi_plans p ON p.id = t.plan_id 
            ORDER BY t.nome ASC
        ")->fetchAll();

        $this->view('platform/negocios/index', compact('negocios'), 'platform');
    }

    public function create(): void {
        $planos = (new TenantPlan())->all();
        $this->view('platform/negocios/form', compact('planos'), 'platform');
    }

    public function store(): void {
        try {
            $pdo = Database::getInstance();
            $pdo->beginTransaction();

            // 1. Salva Negócio (Tenant)
            // Campos base (sempre existem na tabela original)
            $tenantData = [
                'nome'          => $_POST['nome'] ?? '',
                'slug'          => $_POST['slug'] ?? '',
                'cnpj'          => $_POST['cnpj'] ?? null,
                'email_contato' => $_POST['email_contato'] ?? null,
                'telefone'      => $_POST['telefone'] ?? null,
                'plan_id'       => $_POST['plan_id'] ?? null,
                'status'        => $_POST['status'] ?? 'trial',
                'cor_primaria'  => $_POST['cor_primaria'] ?? '#3b82f6',
            ];

            // Campos opcionais (adicionados pela migration de colunas individuais)
            // Só inclui se a coluna existir na tabela para evitar erro de SQL
            $camposOpcionais = [
                'razao_social'       => $_POST['razao_social'] ?? null,
                'nome_fantasia'      => $_POST['nome'] ?? null, // form usa name="nome" para o nome de exibição
                'inscricao_estadual' => $_POST['inscricao_estadual'] ?? null,
                'inscricao_municipal'=> $_POST['inscricao_municipal'] ?? null,
                'cep'                => $_POST['cep'] ?? null,
                'logradouro'         => $_POST['logradouro'] ?? null,
                'numero'             => $_POST['numero'] ?? null,
                'complemento'        => $_POST['complemento'] ?? null,
                'bairro'             => $_POST['bairro'] ?? null,
                'cidade'             => $_POST['cidade'] ?? null,
                'estado'             => $_POST['estado'] ?? null,
            ];
            try {
                $colunas = $pdo->query("SHOW COLUMNS FROM bi_tenants")->fetchAll(\PDO::FETCH_COLUMN);
                foreach ($camposOpcionais as $campo => $valor) {
                    if (in_array($campo, $colunas)) {
                        $tenantData[$campo] = $valor;
                    }
                }
            } catch (\Exception $e) {
                // Ignora se não conseguir verificar colunas
            }

            $tenantId = (new Tenant())->create($tenantData);

            // 2. Salva Contatos (com try/catch para ignorar se a tabela não existir)
            if (!empty($_POST['contatos']) && is_array($_POST['contatos'])) {
                try {
                    $stmtContato = $pdo->prepare("
                        INSERT INTO bi_negocio_contatos 
                        (tenant_id, nome, cargo, departamento, email, telefone, celular, whatsapp, principal)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($_POST['contatos'] as $index => $contato) {
                        if (empty($contato['nome'])) continue;
                        $stmtContato->execute([
                            $tenantId,
                            $contato['nome'],
                            $contato['cargo'] ?? null,
                            $contato['departamento'] ?? null,
                            $contato['email'] ?? null,
                            $contato['telefone'] ?? null,
                            $contato['celular'] ?? null,
                            $contato['whatsapp'] ?? null,
                            ($index === 0) ? 1 : 0 // Primeiro contato é o principal
                        ]);
                    }
                } catch (\Exception $e) {
                    error_log("Aviso: Falha ao salvar contatos (tabela pode não existir): " . $e->getMessage());
                }
            }

            // 3. Salva Institution Names (DICOM) (com try/catch)
            if (!empty($_POST['institution_names'])) {
                try {
                    $names = array_map('trim', explode(',', $_POST['institution_names']));
                    $stmtInst = $pdo->prepare("
                        INSERT IGNORE INTO bi_negocio_institution_names (tenant_id, institution_name)
                        VALUES (?, ?)
                    ");
                    foreach ($names as $name) {
                        if (!empty($name)) {
                            $stmtInst->execute([$tenantId, $name]);
                        }
                    }
                } catch (\Exception $e) {
                    error_log("Aviso: Falha ao salvar institution names (tabela pode não existir): " . $e->getMessage());
                }
            }

            // 4. Cria usuário administrador do negócio (Acesso)
            if (!empty($_POST['admin_email']) && !empty($_POST['admin_senha'])) {
                // Verifica se usuário já existe
                $stmt = $pdo->prepare("SELECT id FROM bi_users WHERE email = ?");
                $stmt->execute([$_POST['admin_email']]);
                $userId = $stmt->fetchColumn();

                if (!$userId) {
                    $stmt = $pdo->prepare("
                        INSERT INTO bi_users (name, email, password, role, status)
                        VALUES (?, ?, ?, 'admin', 'ativo')
                    ");
                    $stmt->execute([
                        $_POST['admin_nome'] ?? 'Administrador',
                        $_POST['admin_email'],
                        password_hash($_POST['admin_senha'], PASSWORD_BCRYPT)
                    ]);
                    $userId = $pdo->lastInsertId();
                }

                // Vincula usuário ao negócio com role admin
                $pdo->prepare("INSERT IGNORE INTO bi_user_tenants (user_id, tenant_id, role) VALUES (?, ?, 'admin')")
                    ->execute([$userId, $tenantId]);
            }

            $pdo->commit();
            $_SESSION['success'] = "Negócio cadastrado com sucesso!";
            $this->redirect('/platform/negocios');

        } catch (\Exception $e) {
            if (isset($pdo)) $pdo->rollBack();
            error_log("Erro ao salvar negócio: " . $e->getMessage());
            $_SESSION['error'] = "Erro ao salvar negócio. Verifique os logs.";
            $this->redirect('/platform/negocios/create');
        }
    }

    public function edit(int $id): void {
        $pdo = Database::getInstance();
        $negocio = (new Tenant())->find($id);
        if (!$negocio) {
            $this->redirect('/platform/negocios');
        }

        $planos = (new TenantPlan())->all();
        
        // Busca contatos (com try/catch)
        $contatos = [];
        try {
            $contatos = $pdo->prepare("SELECT * FROM bi_negocio_contatos WHERE tenant_id = ? ORDER BY principal DESC, id ASC");
            $contatos->execute([$id]);
            $contatos = $contatos->fetchAll();
        } catch (\Exception $e) {}

        // Busca institution names (com try/catch)
        $institution_names = [];
        try {
            $inst = $pdo->prepare("SELECT institution_name FROM bi_negocio_institution_names WHERE tenant_id = ?");
            $inst->execute([$id]);
            $institution_names = $inst->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Exception $e) {}
        
        $institution_names_str = implode(', ', $institution_names);

        // Busca admin principal
        $admin = [];
        try {
            $adminStmt = $pdo->prepare("SELECT * FROM bi_users WHERE tenant_id = ? AND role = 'tenant_admin' ORDER BY id ASC LIMIT 1");
            $adminStmt->execute([$id]);
            $admin = $adminStmt->fetch(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {}

        $this->view('platform/negocios/form', compact('negocio', 'planos', 'contatos', 'institution_names_str', 'admin'), 'platform');
    }

    public function update(int $id): void {
        try {
            $pdo = Database::getInstance();
            $pdo->beginTransaction();

            // 1. Atualiza Negócio
            $tenantData = [
                'nome'          => $_POST['nome'] ?? '',
                'slug'          => $_POST['slug'] ?? '',
                'cnpj'          => $_POST['cnpj'] ?? null,
                'email_contato' => $_POST['email_contato'] ?? null,
                'telefone'      => $_POST['telefone'] ?? null,
                'plan_id'       => $_POST['plan_id'] ?? null,
                'status'        => $_POST['status'] ?? 'trial',
                'cor_primaria'  => $_POST['cor_primaria'] ?? '#3b82f6',
            ];

            $camposOpcionais = [
                'razao_social'        => $_POST['razao_social'] ?? null,
                'nome_fantasia'       => $_POST['nome'] ?? null,
                'inscricao_estadual'  => $_POST['inscricao_estadual'] ?? null,
                'inscricao_municipal' => $_POST['inscricao_municipal'] ?? null,
                'cep'                 => $_POST['cep'] ?? null,
                'logradouro'          => $_POST['logradouro'] ?? null,
                'numero'              => $_POST['numero'] ?? null,
                'complemento'         => $_POST['complemento'] ?? null,
                'bairro'              => $_POST['bairro'] ?? null,
                'cidade'              => $_POST['cidade'] ?? null,
                'estado'              => $_POST['estado'] ?? null,
            ];
            try {
                $colunas = $pdo->query("SHOW COLUMNS FROM bi_tenants")->fetchAll(\PDO::FETCH_COLUMN);
                foreach ($camposOpcionais as $campo => $valor) {
                    if (in_array($campo, $colunas)) {
                        $tenantData[$campo] = $valor;
                    }
                }
            } catch (\Exception $e) {
                // Ignora se não conseguir verificar colunas
            }

            (new Tenant())->update($id, $tenantData);

            // 2. Atualiza Contatos (com try/catch)
            try {
                $pdo->prepare("DELETE FROM bi_negocio_contatos WHERE tenant_id = ?")->execute([$id]);
                if (!empty($_POST['contatos']) && is_array($_POST['contatos'])) {
                    $stmtContato = $pdo->prepare("
                        INSERT INTO bi_negocio_contatos 
                        (tenant_id, nome, cargo, departamento, email, telefone, celular, whatsapp, principal)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($_POST['contatos'] as $index => $contato) {
                        if (empty($contato['nome'])) continue;
                        $stmtContato->execute([
                            $id,
                            $contato['nome'],
                            $contato['cargo'] ?? null,
                            $contato['departamento'] ?? null,
                            $contato['email'] ?? null,
                            $contato['telefone'] ?? null,
                            $contato['celular'] ?? null,
                            $contato['whatsapp'] ?? null,
                            ($index === 0) ? 1 : 0
                        ]);
                    }
                }
            } catch (\Exception $e) {
                error_log("Aviso: Falha ao atualizar contatos: " . $e->getMessage());
            }

            // 3. Atualiza Institution Names (com try/catch)
            try {
                $pdo->prepare("DELETE FROM bi_negocio_institution_names WHERE tenant_id = ?")->execute([$id]);
                if (!empty($_POST['institution_names'])) {
                    $names = array_map('trim', explode(',', $_POST['institution_names']));
                    $stmtInst = $pdo->prepare("
                        INSERT IGNORE INTO bi_negocio_institution_names (tenant_id, institution_name)
                        VALUES (?, ?)
                    ");
                    foreach ($names as $name) {
                        if (!empty($name)) {
                            $stmtInst->execute([$id, $name]);
                        }
                    }
                }
            } catch (\Exception $e) {
                error_log("Aviso: Falha ao atualizar institution names: " . $e->getMessage());
            }

            $pdo->commit();
            $_SESSION['success'] = "Negócio atualizado com sucesso!";
            $this->redirect('/platform/negocios');

        } catch (\Exception $e) {
            if (isset($pdo)) $pdo->rollBack();
            error_log("Erro ao atualizar negócio: " . $e->getMessage());
            $_SESSION['error'] = "Erro ao atualizar negócio. Verifique os logs.";
            $this->redirect("/platform/negocios/{$id}/edit");
        }
    }

    public function buscarCnpj(string $cnpj): void {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        if (strlen($cnpj) !== 14) {
            $this->json(['error' => 'CNPJ inválido'], 400);
        }

        // 1. ReceitaWS
        $url = "https://www.receitaws.com.br/v1/cnpj/{$cnpj}";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if (!isset($data['status']) || $data['status'] !== 'ERROR') {
                $this->json([
                    'source'       => 'ReceitaWS',
                    'razao_social' => $data['nome'] ?? '',
                    'nome_fantasia'=> $data['fantasia'] ?? '',
                    'cep'          => preg_replace('/[^0-9]/', '', $data['cep'] ?? ''),
                    'logradouro'   => $data['logradouro'] ?? '',
                    'numero'       => $data['numero'] ?? '',
                    'complemento'  => $data['complemento'] ?? '',
                    'bairro'       => $data['bairro'] ?? '',
                    'cidade'       => $data['municipio'] ?? '',
                    'estado'       => $data['uf'] ?? '',
                    'telefone'     => $data['telefone'] ?? '',
                    'email'        => $data['email'] ?? '',
                ]);
            }
        }

        // 2. BrasilAPI (Fallback)
        $url = "https://brasilapi.com.br/api/cnpj/v1/{$cnpj}";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            $this->json([
                'source'       => 'BrasilAPI',
                'razao_social' => $data['razao_social'] ?? '',
                'nome_fantasia'=> $data['nome_fantasia'] ?? '',
                'cep'          => preg_replace('/[^0-9]/', '', $data['cep'] ?? ''),
                'logradouro'   => $data['logradouro'] ?? '',
                'numero'       => $data['numero'] ?? '',
                'complemento'  => $data['complemento'] ?? '',
                'bairro'       => $data['bairro'] ?? '',
                'cidade'       => $data['municipio'] ?? '',
                'estado'       => $data['uf'] ?? '',
                'telefone'     => $data['ddd_telefone_1'] ?? '',
                'email'        => '',
            ]);
        }

        $this->json(['error' => 'CNPJ não encontrado nas APIs'], 404);
    }
}
