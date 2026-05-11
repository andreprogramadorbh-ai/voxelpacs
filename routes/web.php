<?php
use App\Core\Router;

// ============================================================
// VOXES PACS — Rotas Web
// ============================================================

// Autenticação
Router::get('/login',               'AuthController@showLogin');
Router::post('/login',              'AuthController@login');
Router::get('/logout',              'AuthController@logout');
Router::get('/selecionar-empresa',  'AuthController@selectTenant');
Router::post('/selecionar-empresa', 'AuthController@setTenant');
Router::get('/', fn() => header('Location: /dashboard'));

// Dashboard
Router::get('/dashboard',               'DashboardController@index');
Router::get('/api/dashboard',           'DashboardController@apiDados');

// Exames
Router::get('/exames',                  'ExamesController@index');
Router::get('/exames/{id}',             'ExamesController@detalhe');
Router::get('/api/exames',              'ExamesController@apiDados');
Router::get('/api/exames/exportar',     'ExamesController@exportar');

// Médicos
Router::get('/medicos',                 'MedicosController@index');
Router::get('/medicos/{id}',            'MedicosController@detalhe');

// Unidades, Modalidades, Financeiro, SLA
Router::get('/unidades',                'UnidadesController@index');
Router::get('/modalidades',             'ModalidadesController@index');
Router::get('/financeiro',              'FinanceiroController@index');
Router::get('/sla',                     'SlaController@index');

// Análise Preditiva
Router::get('/preditivo',               'PreditivoController@index');
Router::get('/api/preditivo',           'PreditivoController@apiDados');

// Benchmark
Router::get('/benchmark',               'BenchmarkController@index');
Router::get('/api/benchmark',           'BenchmarkController@apiDados');

// Relatórios
Router::get('/relatorios',              'RelatoriosController@index');
Router::get('/relatorios/exportar',     'RelatoriosController@exportar');

// Importação
Router::get('/importacao',              'ImportacaoController@index');
Router::post('/importacao',             'ImportacaoController@processar');
Router::get('/importacao/{id}/log',     'ImportacaoController@verLog');
Router::post('/importacao/{id}/delete', 'ImportacaoController@deletar');

// PACS
Router::get('/pacs',                    'PacsController@index');
Router::get('/pacs/create',             'PacsController@create');
Router::post('/pacs',                   'PacsController@store');
Router::get('/pacs/{id}/edit',          'PacsController@edit');
Router::post('/pacs/{id}/update',       'PacsController@update');
Router::post('/pacs/{id}/sync',         'PacsController@sincronizar');
Router::post('/pacs/{id}/test',         'PacsController@testar');
Router::post('/pacs/{id}/delete',       'PacsController@deletar');

// Configurações
Router::get('/configuracoes',           'ConfiguracoesController@index');
Router::post('/configuracoes/salvar',   'ConfiguracoesController@salvar');

// Usuários
Router::get('/usuarios',                'UsuariosController@index');
Router::get('/usuarios/create',         'UsuariosController@create');
Router::post('/usuarios',               'UsuariosController@store');
Router::get('/usuarios/{id}/edit',      'UsuariosController@edit');
Router::post('/usuarios/{id}/update',   'UsuariosController@update');
Router::post('/usuarios/{id}/toggle',   'UsuariosController@toggleStatus');

// Servidor Orthanc
Router::get('/servidor',                'ServidorController@index');
Router::get('/servidor/create',         'ServidorController@create');
Router::post('/servidor',               'ServidorController@store');
Router::get('/servidor/{id}/edit',      'ServidorController@edit');
Router::post('/servidor/{id}/update',   'ServidorController@update');
Router::get('/servidor/{id}/testar',    'ServidorController@testar');

// API: Status Orthanc (usado pelo badge de login — público, sem auth)
Router::get('/api/orthanc/ping', 'PacsController@pingPublic');
