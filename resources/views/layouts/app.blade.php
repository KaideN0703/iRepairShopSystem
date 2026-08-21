<!DOCTYPE html>
<html lang="en" class="h-full bg-ir-void text-ir-bone">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') — iRepair Gadget Service Center</title>
    <meta name="description" content="iRepair Gadget Service Center — Staff Management System">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@600;700&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Tailwind CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'ir-void':         '#0B0B0C',
                        'ir-carbon':       '#17181A',
                        'ir-gold':         '#F5A623',
                        'ir-amber-deep':   '#B97A1A',
                        'ir-copper':       '#7A4A12',
                        'ir-bone':         '#EDE6D6',
                        'ir-signal-green': '#35D07F',
                        'ir-alert':        '#E5484D',
                    },
                    fontFamily: {
                        'display': ['Oswald', 'sans-serif'],
                        'body':    ['Inter', 'sans-serif'],
                        'mono':    ['JetBrains Mono', 'monospace'],
                    }
                }
            }
        }
    </script>

    {{-- FontAwesome Icons --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    {{-- Brand Stylesheet --}}
    @vite(['resources/css/app.css'])

    <style>
        :root {
            --ir-void:         #0B0B0C;
            --ir-carbon:       #17181A;
            --ir-gold:         #F5A623;
            --ir-amber-deep:   #B97A1A;
            --ir-copper:       #7A4A12;
            --ir-bone:         #EDE6D6;
            --ir-signal-green: #35D07F;
            --ir-alert:        #E5484D;
        }
        [x-cloak] { display: none !important; }
    </style>

    @stack('styles')
</head>

<body class="h-full font-body antialiased bg-ir-void text-ir-bone flex flex-col" x-data="{ sidebarOpen: false }">

    <div class="flex h-screen overflow-hidden relative">

        {{-- ============================================================
             SIDEBAR NAVIGATION
             ============================================================ --}}
        <aside
            class="fixed inset-y-0 left-0 z-40 w-64 bg-ir-carbon border-r border-ir-copper transform transition-transform duration-300 ease-in-out -translate-x-full md:translate-x-0 md:static md:inset-0 flex flex-col justify-between"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            {{-- Sidebar Header (64px height to align perfectly with topbar) --}}
            <div>
                <div class="h-16 flex items-center justify-between px-5 border-b border-ir-copper shrink-0">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 text-decoration-none">
                        <div class="w-10 h-10 rounded-full overflow-hidden flex items-center justify-center shrink-0">
                            <img src="{{ asset('assets/eb2becaa-00dc-4db2-822d-a5fa787a3d54.jpeg') }}"
                                 alt="iRepair Logo"
                                 class="w-full h-full object-cover scale-[1.24] origin-center">
                        </div>
                        <div>
                            <span class="font-display font-bold text-base uppercase tracking-wider text-ir-gold leading-none block">iRepair</span>
                            <span class="font-body text-[10px] text-ir-copper tracking-wider uppercase block mt-0.5">Gadget Service Center</span>
                        </div>
                    </a>
                    <button @click="sidebarOpen = false" class="md:hidden text-ir-copper hover:text-ir-gold p-1" aria-label="Close sidebar">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                {{-- Navigation Links --}}
                <nav class="p-3 overflow-y-auto max-h-[calc(100vh-130px)]" aria-label="Main Navigation">

                    {{-- Section: Core Operations --}}
                    <div class="pt-1 pb-1 px-1 mb-1">
                        <span class="nav-section-label">Repair Workflow</span>
                    </div>

                    <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="fa-solid fa-chart-pie"></i>
                        <span>Dashboard</span>
                    </a>

                    {{-- Repair Tickets: visible to anyone who can view or create jobs --}}
                    @canany(['repairs.view.own', 'repairs.view.status', 'repairs.manage', 'jobs.create', 'jobs.manage.full'])
                    <a href="{{ route('job_orders.index') }}" class="nav-item {{ request()->routeIs('job_orders.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                        <span>Repair Tickets</span>
                    </a>
                    @endcanany

                    {{-- Customers: visible to those who can view or manage customers --}}
                    @canany(['customers.view', 'customers.view.scoped', 'customers.manage'])
                    <a href="{{ route('customers.index') }}" class="nav-item {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-users"></i>
                        <span>Customers</span>
                    </a>
                    @endcanany

                    {{-- Devices: visible to those who can view customers or create jobs --}}
                    @canany(['customers.view', 'customers.view.scoped', 'jobs.create'])
                    <a href="{{ route('devices.index') }}" class="nav-item {{ request()->routeIs('devices.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-mobile-screen-button"></i>
                        <span>Devices</span>
                    </a>
                    @endcanany

                    {{-- Technicians: only managers who can manage technicians --}}
                    @can('technicians.manage')
                    <a href="{{ route('technicians.index') }}" class="nav-item {{ request()->routeIs('technicians.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-user-gear"></i>
                        <span>Technicians &amp; Staff</span>
                    </a>
                    @endcan

                    {{-- Section: Inventory & Supply --}}
                    @canany(['inventory.view', 'parts.usage.view', 'parts.catalog.manage', 'suppliers.view', 'suppliers.manage'])
                    <div class="pt-4 pb-1 px-1 mb-1">
                        <span class="nav-section-label">Inventory &amp; Supply</span>
                    </div>
                    @endcanany

                    @canany(['inventory.view', 'parts.usage.view', 'parts.catalog.manage'])
                    <a href="{{ route('inventory.index') }}" class="nav-item {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-boxes-stacked"></i>
                        <span>Inventory &amp; Parts</span>
                    </a>
                    @endcanany

                    @canany(['suppliers.view', 'suppliers.manage'])
                    <a href="{{ route('suppliers.index') }}" class="nav-item {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-truck-field"></i>
                        <span>Suppliers &amp; Restock</span>
                    </a>
                    @endcanany

                    {{-- Section: Billing & Compliance --}}
                    @canany(['invoices.manage', 'warranty.view', 'warranty.view.scoped', 'warranty.claim', 'warranty.manage'])
                    <div class="pt-4 pb-1 px-1 mb-1">
                        <span class="nav-section-label">Billing &amp; Compliance</span>
                    </div>
                    @endcanany

                    @can('invoices.manage')
                    <a href="{{ route('invoices.index') }}" class="nav-item {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                        <span>Invoices &amp; Billing</span>
                    </a>
                    @endcan

                    @canany(['warranty.view', 'warranty.view.scoped', 'warranty.claim', 'warranty.manage'])
                    <a href="{{ route('warranties.index') }}" class="nav-item {{ request()->routeIs('warranties.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Warranties</span>
                    </a>
                    @endcanany

                    {{-- Section: Analytics --}}
                    @canany(['reports.view.own', 'reports.view.financial', 'reports.view.inventory', 'reports.view.sales'])
                    <div class="pt-4 pb-1 px-1 mb-1">
                        <span class="nav-section-label">Analytics</span>
                    </div>

                    <a href="{{ route('reports.index') }}" class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-chart-line"></i>
                        <span>Reports &amp; Analytics</span>
                    </a>
                    @endcanany

                    {{-- Section: System Admin — users.manage or audit.view or backup.manage --}}
                    @canany(['users.manage.full', 'users.manage.limited', 'audit.view', 'backup.manage'])
                    <div class="pt-4 pb-1 px-1 mb-1 border-t border-ir-copper/40 mt-3">
                        <span class="nav-section-label">System Admin</span>
                    </div>
                    @endcanany

                    @canany(['users.manage.full', 'users.manage.limited'])
                    <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-user-shield"></i>
                        <span>Staff &amp; Roles</span>
                    </a>
                    @endcanany

                    @can('audit.view')
                    <a href="{{ route('audit_logs.index') }}" class="nav-item {{ request()->routeIs('audit_logs.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-list-check"></i>
                        <span>Audit Logs</span>
                    </a>
                    @endcan

                    @can('backup.manage')
                    <a href="{{ route('backups.index') }}" class="nav-item {{ request()->routeIs('backups.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-database"></i>
                        <span>Backup &amp; Restore</span>
                    </a>
                    @endcan

                </nav>
            </div>

            {{-- User Footer Chip --}}
            <div class="ir-user-chip border-t border-ir-copper p-3 flex items-center gap-3">
                <div class="ir-user-avatar w-8 h-8 rounded-full bg-ir-gold/10 border border-ir-gold/30 flex items-center justify-center font-mono font-bold text-xs text-ir-gold shrink-0">
                    {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="ir-user-name text-xs font-semibold text-ir-bone truncate">{{ Auth::user()->name ?? 'Staff User' }}</div>
                    <div class="ir-user-role text-[10px] font-mono text-ir-amber-deep truncate">{{ Auth::user()->roles->first()?->name ?? 'Staff' }}</div>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="ir-logout-btn text-ir-copper hover:text-ir-alert hover:bg-ir-alert/10 p-1.5 rounded transition-colors" title="Sign Out">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Mobile Sidebar Backdrop --}}
        <div
            x-show="sidebarOpen"
            @click="sidebarOpen = false"
            x-transition:enter="transition-opacity ease-linear duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-cloak
            class="fixed inset-0 bg-ir-void/80 backdrop-blur-xs z-30 md:hidden"
        ></div>

        {{-- ============================================================
             MAIN CONTENT AREA
             ============================================================ --}}
        <div class="flex-1 flex flex-col overflow-hidden min-w-0">

            {{-- Top Navbar (64px height aligned with sidebar header) --}}
            <header class="h-16 bg-ir-carbon/95 border-b border-ir-copper backdrop-blur-md px-6 flex items-center justify-between z-20 sticky top-0 shrink-0">
                <div class="flex items-center gap-4">
                    {{-- Mobile Menu Trigger --}}
                    <button
                        @click="sidebarOpen = !sidebarOpen"
                        class="md:hidden p-2 rounded-lg bg-ir-copper/20 border border-ir-copper text-ir-bone hover:text-ir-gold"
                        aria-label="Open sidebar"
                    >
                        <i class="fa-solid fa-bars"></i>
                    </button>

                    {{-- Title --}}
                    <h1 class="font-display font-bold text-lg uppercase tracking-wide text-ir-bone hidden sm:block">
                        @yield('title', 'Dashboard')
                    </h1>
                </div>

                <div class="flex items-center gap-3">
                    {{-- Customer Public Portal Link --}}
                    <a href="{{ route('status.index') }}" target="_blank"
                       class="hidden sm:flex items-center gap-2 px-3.5 py-1.5 rounded-lg border border-ir-copper bg-ir-carbon text-xs font-medium text-ir-bone/80 hover:text-ir-gold hover:border-ir-gold transition-colors">
                        <i class="fa-solid fa-globe text-ir-amber-deep"></i>
                        <span>Customer Portal</span>
                    </a>

                    {{-- New Job Order Action Button --}}
                    @can('jobs.create')
                    <a href="{{ route('job_orders.create') }}" class="btn-primary">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>New Job Order</span>
                    </a>
                    @endcan
                </div>
            </header>

            {{-- Alert Flash Banners --}}
            <div class="px-6 pt-4 shrink-0">
                @if(session('success'))
                    <div class="ir-alert-success mb-2">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="ir-alert-dismiss" aria-label="Dismiss">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="ir-alert-error mb-2">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>{{ session('error') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="ir-alert-dismiss" aria-label="Dismiss">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                @endif

                @if(session('info'))
                    <div class="ir-alert-info mb-2">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>{{ session('info') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="ir-alert-dismiss" aria-label="Dismiss">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="ir-alert-error mb-2">
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                            <ul class="m-0 p-0 list-none text-xs space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <button onclick="this.parentElement.remove()" class="ir-alert-dismiss self-start" aria-label="Dismiss">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                @endif
            </div>

            {{-- Main Scrollable View Area --}}
            <main class="flex-1 overflow-y-auto px-6 py-4 ir-fade-in">
                @yield('content')
            </main>
        </div>

    </div>

    @stack('scripts')
</body>
</html>
