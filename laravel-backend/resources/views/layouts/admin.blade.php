<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') - KaPlato</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            color: #212529;
            font-weight: 500;
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.95);
            border-radius: 8px;
            margin: 2px 0;
            font-weight: 600;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.15);
            color: white;
            font-weight: 700;
        }
        .main-content {
            background-color: #f8f9fa;
        }
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card-title {
            color: #495057 !important;
            font-weight: 700 !important;
            font-size: 0.95rem !important;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            font-weight: 700;
        }
        .card-header h5, .card-header h6 {
            font-weight: 800;
            color: #212529 !important;
        }
        .stat-card {
            border-left: 4px solid;
        }
        .stat-card.primary { border-left-color: #007bff; }
        .stat-card.success { border-left-color: #28a745; }
        .stat-card.warning { border-left-color: #ffc107; }
        .stat-card.danger { border-left-color: #dc3545; }
        .stat-card h3 {
            font-weight: 800 !important;
            color: inherit !important;
        }
        .navbar-brand {
            font-weight: bold;
            color: #212529 !important;
            font-size: 1.4rem !important;
        }
        table tbody td {
            color: #212529;
            font-weight: 600;
        }
        table thead th {
            background-color: #e9ecef;
            color: #212529;
            font-weight: 700;
            border-color: #dee2e6;
        }
        .text-muted {
            color: #6c757d !important;
            font-weight: 500 !important;
        }
        .badge {
            font-weight: 700;
            font-size: 0.85rem;
        }
        .btn {
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-3">
                <div class="d-flex flex-column">
                    <h4 class="text-white mb-4">
                        <i class="fas fa-utensils me-2"></i>KaPlato Admin
                    </h4>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.pending') ? 'active' : '' }}" href="{{ route('admin.pending') }}">
                            <i class="fas fa-clock me-2"></i>Pending Approvals
                            @if($pendingCount ?? 0 > 0)
                                <span class="badge bg-warning ms-2">{{ $pendingCount }}</span>
                            @endif
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}" href="{{ route('admin.users') }}">
                            <i class="fas fa-users me-2"></i>Users
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.karenderias') ? 'active' : '' }}" href="{{ route('admin.karenderias') }}">
                            <i class="fas fa-store me-2"></i>Karenderias
                        </a>
                    </nav>
                    
                    <div class="mt-auto">
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-light w-100">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Top Navigation -->
                <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
                    <div class="container-fluid">
                        <span class="navbar-brand mb-0 h1">@yield('page-title', 'Admin Panel')</span>
                        <div class="navbar-nav ms-auto">
                            <span class="nav-item nav-link">
                                <i class="fas fa-user-circle me-1"></i>
                                {{ Auth::user()->name }}
                            </span>
                        </div>
                    </div>
                </nav>

                <!-- Alerts -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Page Content -->
                <div class="px-3 pb-4">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>