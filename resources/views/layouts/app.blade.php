<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Costsheet Dashboard') - BCCl</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <style>
        body { background-color: #f4f6f9; }
        .sidebar {
            min-height: 100vh; background: #1a2035; width: 250px;
            position: fixed; top: 0; left: 0; z-index: 1000; overflow-y: auto;
            transition: width .25s ease, transform .25s ease;
        }
        .sidebar.collapsed { width: 60px; overflow: hidden; }
        .sidebar.collapsed .nav-label,
        .sidebar.collapsed .nav-section,
        .sidebar.collapsed .brand-text { display: none !important; }
        .sidebar.collapsed .nav-link { justify-content: center; padding: 10px 0; margin: 2px 4px; overflow: hidden; }
        .sidebar.collapsed .nav-link i { margin: 0; width: auto; font-size: 18px; }
        .sidebar.collapsed .brand { padding: 16px 6px; text-align: center; justify-content: center; }
        .sidebar .nav-link { overflow: hidden; }
        .sidebar .brand { padding: 20px 15px; background: #141728; border-bottom: 1px solid #2d3555; display: flex; align-items: center; justify-content: space-between; }
        .sidebar .brand h5 { color: #fff; font-weight: 700; margin: 0; font-size: 14px; }
        .sidebar .brand small { color: #8898aa; font-size: 11px; }
        .sidebar-toggle-btn { background: none; border: none; color: #8898aa; cursor: pointer; padding: 2px 4px; font-size: 18px; flex-shrink: 0; }
        .sidebar-toggle-btn:hover { color: #fff; }
        .sidebar .nav-link { color: #8898aa; padding: 10px 15px; font-size: 13px; border-radius: 6px; margin: 2px 8px; transition: all 0.2s; display: flex; align-items: center; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #fff; background: #5e72e4; }
        .sidebar .nav-link i { width: 20px; margin-right: 8px; flex-shrink: 0; }
        .sidebar .nav-section { color: #6b7a99; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; padding: 15px 15px 5px; }
        .main-content { margin-left: 250px; padding: 20px; min-height: 100vh; transition: margin-left .25s ease; }
        .main-content.expanded { margin-left: 60px; }
        .topbar { background: #fff; padding: 12px 20px; margin: -20px -20px 20px; box-shadow: 0 1px 3px rgba(0,0,0,.1); display: flex; align-items: center; justify-content: space-between; }
        .card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,.06); border-radius: 10px; }
        .card-header { background: #fff; border-bottom: 1px solid #e9ecef; font-weight: 600; padding: 15px 20px; }
        .stat-card { border-radius: 10px; color: #fff; padding: 20px; }
        .stat-card.blue { background: linear-gradient(135deg, #5e72e4, #825ee4); }
        .stat-card.green { background: linear-gradient(135deg, #2dce89, #2dcecc); }
        .stat-card.orange { background: linear-gradient(135deg, #fb6340, #fbb140); }
        .stat-card.red { background: linear-gradient(135deg, #f5365c, #f56036); }
        .badge-admin { background: #dc3545; }
        .badge-uploader { background: #0d6efd; }
        .badge-modifier { background: #198754; }
        .badge-viewer { background: #6c757d; }
        .table-costsheet th { background: #1a2035; color: #fff; font-size: 12px; white-space: nowrap; }
        .table-costsheet th .mine-name { white-space: normal; word-break: break-word; font-size: 11px; display: block; }
        .table-costsheet td { font-size: 13px; white-space: nowrap; }
        .table-costsheet td.metric-label { white-space: normal; min-width: 200px; }
        .negative-val { color: #dc3545; font-weight: 500; }
        .positive-val { color: #198754; font-weight: 500; }
        .metric-label { font-weight: 600; background: #f8f9fa; }
        .pagination { flex-wrap: wrap; gap: 2px; }
        .pagination .page-link { font-size: 13px; padding: 4px 10px; }
        @media (max-width: 768px) { .sidebar { width: 60px; } .sidebar .nav-label, .sidebar .nav-section, .sidebar .brand-text { display:none; } .sidebar .nav-link { justify-content:center; padding:10px 0; margin:2px 4px; } .sidebar .nav-link i { margin:0; width:auto; } .main-content { margin-left: 60px; } }
    </style>
    @stack('styles')
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand">
            <div class="brand-text">
                <h5><i class="bi bi-bar-chart-fill me-2"></i>Costsheet Dash</h5>
                <small>BCCL Analytics</small>
            </div>
            <button class="sidebar-toggle-btn" id="sidebarToggle" title="Toggle sidebar">
                <i class="bi bi-layout-sidebar-reverse"></i>
            </button>
        </div>
        <nav class="mt-2">
            <div class="nav-section">Main</div>
            <a href="{{ route('dashboard') }}" title="Dashboard"
               class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i><span class="nav-label"> Dashboard</span>
            </a>

            <div class="nav-section">Data</div>
            <a href="{{ route('data.view') }}" title="Costsheet View"
               class="nav-link {{ request()->routeIs('data.view') ? 'active' : '' }}">
                <i class="bi bi-table"></i><span class="nav-label"> Costsheet View</span>
            </a>

            @role('super_admin|admin|area_admin')
            <div class="nav-section">Upload</div>
            <a href="{{ route('upload.index') }}" title="Upload CSV"
               class="nav-link {{ request()->routeIs('upload.index') || request()->routeIs('upload.preview') || request()->routeIs('upload.store') ? 'active' : '' }}">
                <i class="bi bi-cloud-upload"></i>
                <span class="nav-label"> Upload CSV@role('area_admin') <small class="opacity-75">(My Areas)</small>@endrole</span>
            </a>
            @role('super_admin|admin')
            <a href="{{ route('upload.bulk.index') }}" title="Upload CSV – All Areas"
               class="nav-link {{ request()->routeIs('upload.bulk.*') ? 'active' : '' }}">
                <i class="bi bi-cloud-arrow-up"></i>
                <span class="nav-label"> Upload CSV (All Areas)</span>
            </a>
            @endrole
            @endrole

            @role('super_admin|admin')
            <div class="nav-section">Administration</div>
            @role('super_admin')
            <a href="{{ route('users.index') }}" title="User Management"
               class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i>
                <span class="nav-label"> User Management</span>
            </a>
            @endrole
            <a href="{{ route('areas.index') }}" title="Area Management"
               class="nav-link {{ request()->routeIs('areas.*') ? 'active' : '' }}">
                <i class="bi bi-map"></i>
                <span class="nav-label"> Area Management</span>
            </a>
            <a href="{{ route('mines.index') }}" title="Mine Management"
               class="nav-link {{ request()->routeIs('mines.*') ? 'active' : '' }}">
                <i class="bi bi-geo-alt"></i>
                <span class="nav-label"> Mine Management</span>
            </a>
            @endrole
            @role('super_admin')
            <a href="{{ route('activity-logs.index') }}" title="Activity Logs"
               class="nav-link {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}">
                <i class="bi bi-journal-check"></i>
                <span class="nav-label"> Activity Logs</span>
            </a>
            @endrole
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <h6 class="mb-0 fw-bold text-dark">@yield('page-title', 'Dashboard')</h6>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted small">
                    <i class="bi bi-person-circle me-1"></i>
                    {{ auth()->user()->name }}
                    @php
                        $role = auth()->user()->roles->first()?->name ?? 'viewer';
                        $roleLabels = ['super_admin'=>'Super Admin','admin'=>'Admin','area_admin'=>'Area Admin','viewer'=>'Viewer'];
                        $roleBadge  = ['super_admin'=>'badge-admin','admin'=>'badge-uploader','area_admin'=>'badge-modifier','viewer'=>'badge-viewer'];
                    @endphp
                    <span class="badge {{ $roleBadge[$role] ?? 'badge-viewer' }} ms-1">{{ $roleLabels[$role] ?? ucfirst($role) }}</span>
                </span>
                <a href="{{ route('password.change') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-shield-lock"></i> Change Password
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                </form>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                @foreach($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @stack('scripts')
<script>
(function() {
    var sidebar = document.querySelector('.sidebar');
    var main    = document.querySelector('.main-content');
    var btn     = document.getElementById('sidebarToggle');
    var collapsed = localStorage.getItem('sidebar_collapsed') === '1';

    function apply() {
        sidebar.classList.toggle('collapsed', collapsed);
        main.classList.toggle('expanded', collapsed);
    }
    apply();

    if (btn) {
        btn.addEventListener('click', function() {
            collapsed = !collapsed;
            localStorage.setItem('sidebar_collapsed', collapsed ? '1' : '0');
            apply();
        });
    }
})();
</script>
</body>
</html>
