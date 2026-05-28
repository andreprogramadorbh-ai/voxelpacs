# VOXEL PACS

**Plataforma Cloud PACS para Teleradiologia**

Sistema multi-tenant de gerenciamento de imagens médicas DICOM, integrado ao servidor Orthanc, com worklist de estudos, viewer DICOM e painel administrativo da plataforma.

---

## Funcionalidades

- **Worklist de Estudos** — Interface similar ao RAIOSS com dark theme, filtros avançados e paginação
- **Viewer DICOM** — Integração com OHIF Viewer / Weasis via iframe
- **Sidebar expansível** — Menu lateral com submenus colapsáveis
- **Multi-tenant** — Múltiplas clínicas/unidades em uma única instalação
- **Painel da Plataforma** — Gerenciamento de negócios, planos e servidor PACS (superadmin)
- **Servidor PACS** — Configuração e monitoramento do Orthanc, sincronização e roteamento por InstitutionName

## Telas

| Tela | URL | Descrição |
|------|-----|-----------|
| Login | `/login` | Autenticação com logo VOXEL PACS |
| Worklist | `/estudos` | Lista de estudos DICOM com filtros |
| Viewer | `/estudos/{id}/abrir` | Abertura de imagem no viewer DICOM |
| Agendamentos | `/agendamentos` | Lista de agendamentos |
| Médicos | `/medicos` | Cadastro de médicos/laudadores |
| Unidades | `/unidades` | Cadastro de unidades/clínicas |
| Modalidades | `/modalidades` | Cadastro de modalidades DICOM |
| Usuários | `/usuarios` | Gerenciamento de usuários |
| Configurações | `/configuracoes` | Configurações do sistema |
| **Plataforma** | `/platform/dashboard` | Dashboard do superadmin |
| Negócios | `/platform/negocios` | Gerenciamento de tenants |
| Planos | `/platform/plans` | Planos de assinatura |
| Servidor PACS | `/platform/servidor-pacs` | Configuração do Orthanc |

## Colunas da Worklist

| Coluna | Descrição |
|--------|-----------|
| Dt Estudo | Data e hora do estudo DICOM |
| Paciente | Nome, sexo, idade e número de acesso |
| Unidade | InstitutionName (DICOM) |
| M | Modalidade (CR, CT, MR, US, etc.) |
| Especialidade | Especialidade / Médico solicitante |
| Estudo | Descrição do estudo e número de imagens |
| Situação | Status (Novo, Aberto, Rascunho, Assinado, etc.) |
| Ações | Botão **Abrir** — abre o viewer DICOM |

## Instalação

### Requisitos

- PHP 8.1+
- MySQL 8.0+ / MariaDB 10.6+
- Servidor Orthanc (opcional para produção)
- Apache / Nginx / LiteSpeed

### Configuração

```bash
# 1. Clone o repositório
git clone https://github.com/ASOARESBH/voxelpacs.git
cd voxelpacs

# 2. Configure o ambiente
cp .env.example .env
# Edite o .env com suas credenciais

# 3. Execute as migrations
mysql -u root -p voxel_pacs < database/migrations/2026-01-01_bi_multitenant_schema.sql
mysql -u root -p voxel_pacs < database/migrations/2026-05-26_servidor_pacs.sql
mysql -u root -p voxel_pacs < database/migrations/2026-05-26_pacs_estudos_dicom_tags.sql

# 4. Execute o seed do superadmin
mysql -u root -p voxel_pacs < database/seeds/001_superadmin_pacs.sql
```

### Credenciais Padrão

| Campo | Valor |
|-------|-------|
| E-mail | `admin@voxelpacs.com.br` |
| Senha | `Admin259087@` |

> **Atenção:** Altere a senha após o primeiro acesso em produção.

## Estrutura

```
voxelpacs/
├── app/
│   ├── Controllers/          # Controllers PHP
│   │   └── Platform/         # Controllers do painel da plataforma
│   ├── Core/                 # Núcleo: Router, Auth, Database, View
│   ├── Models/               # Models do banco de dados
│   ├── Middlewares/          # Middlewares (TenantMiddleware)
│   └── Views/                # Views PHP
│       ├── auth/             # Login e seleção de empresa
│       ├── estudos/          # Worklist e viewer
│       ├── layout/           # Layouts (pacs, platform, auth)
│       └── platform/         # Views do painel da plataforma
├── database/
│   ├── migrations/           # Scripts SQL de migração
│   └── seeds/                # Seeds de dados iniciais
├── public/
│   ├── assets/
│   │   ├── css/              # CSS (pacs.css, auth.css)
│   │   └── img/              # Imagens e logos
│   └── index.php             # Entry point
└── routes/
    ├── web.php               # Rotas públicas e do PACS
    └── platform.php          # Rotas do painel da plataforma
```

## Tecnologias

- **Backend:** PHP 8.1+ (MVC sem framework)
- **Frontend:** HTML5 + CSS3 (dark theme) + Bootstrap 5.3 + Font Awesome 6
- **Banco:** MySQL / MariaDB
- **PACS:** Orthanc (REST API + DICOM)
- **Viewer:** OHIF Viewer / Weasis (configurável)

---

© 2026 VOXEL PACS — Smart Imaging. Secure Data. Better Care.
