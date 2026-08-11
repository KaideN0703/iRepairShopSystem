<!DOCTYPE html>
<html lang="en" class="h-full bg-ir-void text-ir-bone">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login — iRepair Gadget Service Center</title>
    <meta name="description" content="Staff login portal for iRepair Gadget Service Center management system.">

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

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    @vite(['resources/css/app.css'])

    <style>
        :root {
            --ir-void:       #0B0B0C;
            --ir-carbon:     #17181A;
            --ir-gold:       #F5A623;
            --ir-amber-deep: #B97A1A;
            --ir-copper:     #7A4A12;
            --ir-bone:       #EDE6D6;
        }

        body {
            background-color: #0B0B0C;
            font-family: 'Inter', sans-serif;
            margin: 0;
            overflow-x: hidden;
        }

        /* PCB Hexagon Background Pattern */
        .bg-pcb-pattern {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='56' height='100'%3E%3Cpath d='M28 66 L56 50 L56 16 L28 0 L0 16 L0 50 Z' fill='none' stroke='%23F5A623' stroke-width='0.75' opacity='0.05'/%3E%3Cpath d='M28 100 L56 84 L56 50 L28 34 L0 50 L0 84 Z' fill='none' stroke='%23F5A623' stroke-width='0.75' opacity='0.05'/%3E%3C/svg%3E");
            background-size: 56px 100px;
        }

        /* Frameless Circle Logo Container — Logo image fitted 100% edge-to-edge inside circle */
        .store-logo-wrap {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            border: none;
            background-color: transparent;
            overflow: hidden;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            transition: transform 300ms ease;
        }

        .store-logo-wrap:hover {
            transform: scale(1.05);
        }

        .store-logo-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center center;
            transform: scale(1.32);
            transform-origin: center center;
            border-radius: 50%;
            display: block;
        }

        /* Flat Carbon Panel with Copper Accent Line */
        .auth-card {
            background-color: #17181A;
            border: 1px solid rgba(122, 74, 18, 0.7);
            border-radius: 12px;
            position: relative;
            overflow: hidden;
        }

        .auth-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #7A4A12 0%, #F5A623 50%, #7A4A12 100%);
        }

        /* Flex Input Group with Icon Alignment */
        .ir-input-group {
            background-color: #0B0B0C;
            border: 1px solid #7A4A12;
            border-radius: 6px;
            padding: 0.65rem 0.85rem;
            transition: border-color 150ms ease;
        }

        .ir-input-group:focus-within {
            border-color: #F5A623;
            background-color: rgba(245, 166, 35, 0.02);
        }

        .ir-input-group:focus-within i {
            color: #F5A623;
        }

        /* Button */
        .btn-web-submit {
            width: 100%;
            padding: 0.8rem;
            background: linear-gradient(180deg, #F5A623 0%, #E09310 100%);
            color: #0B0B0C;
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 0.02em;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 150ms ease, transform 100ms ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-web-submit:hover {
            background: linear-gradient(180deg, #FFB438 0%, #F5A623 100%);
            transform: translateY(-1px);
        }

        .role-chip-btn {
            background: #0B0B0C;
            border: 1px solid rgba(122, 74, 18, 0.6);
            border-radius: 6px;
            padding: 0.65rem 0.85rem;
            cursor: pointer;
            transition: border-color 150ms ease, background-color 150ms ease;
        }

        .role-chip-btn:hover {
            border-color: #F5A623;
            background-color: rgba(245, 166, 35, 0.08);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between bg-pcb-pattern text-ir-bone">

    {{-- Main Container — Centered Single Card Layout --}}
    <main class="flex-1 max-w-lg w-full mx-auto px-4 py-8 sm:py-12 flex items-center justify-center z-10">
        <div class="w-full">
            <div class="auth-card p-6 sm:p-8">

                {{-- Form Header with Frameless Store Logo directly on top of form --}}
                <div class="text-center mb-6">
                    <div class="store-logo-wrap mb-4">
                        <img src="{{ asset('assets/eb2becaa-00dc-4db2-822d-a5fa787a3d54.jpeg') }}" alt="iRepair Store Logo">
                    </div>

                    <div class="inline-flex items-center gap-2 px-3 py-0.5 rounded-full bg-ir-void border border-ir-copper text-[10px] font-mono text-ir-gold mb-2">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Staff Terminal Portal</span>
                    </div>

                    <h1 class="font-display text-2xl font-extrabold uppercase tracking-wide text-ir-gold leading-tight">
                        iREPAIR GADGET SERVICE CENTER
                    </h1>
                    <p class="text-xs text-ir-bone/60 mt-1 font-body">Sign in to access system controls &amp; tickets</p>
                </div>

                {{-- Flash Error Alerts --}}
                @if($errors->any())
                    <div class="p-3 mb-4 rounded-md bg-ir-alert/10 border border-ir-alert/40 text-ir-alert text-xs flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-sm shrink-0"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                {{-- Authentication Form --}}
                <form action="{{ route('login') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="block text-xs font-semibold text-ir-bone/90 uppercase tracking-wider mb-1.5 font-mono">Email Address</label>
                        <div class="ir-input-group flex items-center">
                            <i class="fa-solid fa-envelope text-ir-copper text-sm shrink-0 mr-3 transition-colors"></i>
                            <input type="email" id="email" name="email" value="{{ old('email', 'admin@irepair.com') }}" required autocomplete="email" placeholder="staff@irepair.com" class="w-full bg-transparent border-0 text-ir-bone text-sm focus:outline-none p-0">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-semibold text-ir-bone/90 uppercase tracking-wider mb-1.5 font-mono">Password</label>
                        <div class="ir-input-group flex items-center">
                            <i class="fa-solid fa-lock text-ir-copper text-sm shrink-0 mr-3 transition-colors"></i>
                            <input type="password" id="password" name="password" value="password" required autocomplete="current-password" placeholder="••••••••" class="w-full bg-transparent border-0 text-ir-bone text-sm focus:outline-none p-0">
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs pt-0.5">
                        <label class="flex items-center gap-2 cursor-pointer text-ir-bone/70 hover:text-ir-bone">
                            <input type="checkbox" name="remember" class="w-3.5 h-3.5 rounded border-ir-copper bg-ir-void accent-ir-gold cursor-pointer">
                            <span>Remember session</span>
                        </label>
                        <a href="{{ route('status.index') }}" class="text-ir-amber-deep hover:text-ir-gold transition-colors">Public Ticket Lookup</a>
                    </div>

                    <button type="submit" class="btn-web-submit mt-1">
                        <i class="fa-solid fa-right-to-bracket text-xs"></i>
                        <span>Sign In to Terminal</span>
                    </button>
                </form>

                {{-- Quick Account Switcher --}}
                <div class="mt-6 pt-5 border-t border-ir-copper/40">
                    <span class="block text-[10px] font-mono font-bold text-ir-copper uppercase tracking-wider mb-2.5 text-center">
                        <i class="fa-solid fa-bolt text-ir-gold mr-1"></i> Quick Demo Staff Login
                    </span>

                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" onclick="fillCreds('admin@irepair.com')" class="role-chip-btn flex items-center justify-between">
                            <div class="min-w-0 pr-2">
                                <div class="text-xs font-bold text-ir-bone">Admin</div>
                                <div class="text-[10px] font-mono text-ir-bone/50 truncate">admin@irepair.com</div>
                            </div>
                            <i class="fa-solid fa-user-shield text-ir-gold text-sm shrink-0"></i>
                        </button>

                        <button type="button" onclick="fillCreds('manager@irepair.com')" class="role-chip-btn flex items-center justify-between">
                            <div class="min-w-0 pr-2">
                                <div class="text-xs font-bold text-ir-bone">Manager</div>
                                <div class="text-[10px] font-mono text-ir-bone/50 truncate">manager@irepair.com</div>
                            </div>
                            <i class="fa-solid fa-user-gear text-ir-gold text-sm shrink-0"></i>
                        </button>

                        <button type="button" onclick="fillCreds('tech1@irepair.com')" class="role-chip-btn flex items-center justify-between">
                            <div class="min-w-0 pr-2">
                                <div class="text-xs font-bold text-ir-bone">Technician</div>
                                <div class="text-[10px] font-mono text-ir-bone/50 truncate">tech1@irepair.com</div>
                            </div>
                            <i class="fa-solid fa-wrench text-ir-gold text-sm shrink-0"></i>
                        </button>

                        <button type="button" onclick="fillCreds('inventory@irepair.com')" class="role-chip-btn flex items-center justify-between">
                            <div class="min-w-0 pr-2">
                                <div class="text-xs font-bold text-ir-bone">Inventory</div>
                                <div class="text-[10px] font-mono text-ir-bone/50 truncate">inventory@irepair.com</div>
                            </div>
                            <i class="fa-solid fa-boxes-stacked text-ir-gold text-sm shrink-0"></i>
                        </button>

                        <button type="button" onclick="fillCreds('cashier@irepair.com')" class="role-chip-btn flex items-center justify-between col-span-2 sm:col-span-1">
                            <div class="min-w-0 pr-2">
                                <div class="text-xs font-bold text-ir-bone">Cashier</div>
                                <div class="text-[10px] font-mono text-ir-bone/50 truncate">cashier@irepair.com</div>
                            </div>
                            <i class="fa-solid fa-cash-register text-ir-gold text-sm shrink-0"></i>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </main>

    {{-- Footer --}}
    <footer class="w-full border-t border-ir-copper/40 bg-ir-void px-6 py-2.5 text-center text-[11px] font-mono text-ir-copper z-20 shrink-0">
        &copy; {{ date('Y') }} iRepair Gadget Service Center &mdash; Management System
    </footer>

    <script>
        function fillCreds(email) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = 'password';
        }
    </script>
</body>
</html>
