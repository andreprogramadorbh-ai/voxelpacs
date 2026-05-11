<?php
use App\Core\Router;

// ============================================================
// VOXEL B.I — Rotas da Plataforma (Superadmin)
// ============================================================

Router::get('/platform/dashboard',                    'Platform\PlatformDashboardController@index');

// ============================================================
// Negócios (Multi-Tenant — renomeado de Tenants)
// ============================================================
Router::get('/platform/negocios',                          'Platform\NegociosController@index');
Router::get('/platform/negocios/create',                   'Platform\NegociosController@create');
Router::post('/platform/negocios',                         'Platform\NegociosController@store');
Router::get('/platform/negocios/{id}/edit',                'Platform\NegociosController@edit');
Router::post('/platform/negocios/{id}/update',             'Platform\NegociosController@update');
Router::post('/platform/negocios/{id}/suspend',            'Platform\TenantsController@suspend');
Router::post('/platform/negocios/{id}/impersonate',        'Platform\TenantsController@impersonate');
Router::get('/platform/impersonate/exit',                  'Platform\TenantsController@exitImpersonate');

// API interna: busca CNPJ via JavaScript/fetch
Router::get('/platform/api/cnpj/{cnpj}',                   'Platform\NegociosController@buscarCnpj');

// Redirecionamentos de compatibilidade: /platform/tenants → /platform/negocios
Router::get('/platform/tenants',                           'Platform\TenantsController@redirectToNegocios');
Router::get('/platform/tenants/create',                    'Platform\TenantsController@redirectToNegocios');
Router::get('/platform/tenants/{id}/edit',                 'Platform\TenantsController@redirectToNegocios');

// Planos
Router::get('/platform/plans',                        'Platform\PlansController@index');
Router::get('/platform/plans/create',                 'Platform\PlansController@create');
Router::post('/platform/plans',                       'Platform\PlansController@store');
Router::get('/platform/plans/{id}/edit',              'Platform\PlansController@edit');
Router::post('/platform/plans/{id}/update',           'Platform\PlansController@update');

// Relatórios da Plataforma
Router::get('/platform/reports',                      'Platform\PlatformReportsController@index');
Router::get('/platform/reports/exportar',             'Platform\PlatformReportsController@exportar');
