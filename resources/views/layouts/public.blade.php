<!DOCTYPE html>
<html lang="en" style="height:100%; background:#0B0B0C; color:#EDE6D6;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Repair Status') — iRepair Gadget Service Center</title>
    <meta name="description" content="Track your device repair status at iRepair Gadget Service Center.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@600;700&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

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
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

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
<body style="
    min-height:100vh;
    background:#0B0B0C;
    color:#EDE6D6;
    font-family:'Inter',sans-serif;
    display:flex;
    flex-direction:column;
    -webkit-font-smoothing:antialiased;
    margin:0;
">
    {{-- Hex texture --}}
    <div aria-hidden="true" style="
        position:fixed; inset:0; pointer-events:none; z-index:0;
        background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='56' height='100'%3E%3Cpath d='M28 66 L56 50 L56 16 L28 0 L0 16 L0 50 Z' fill='none' stroke='%23F5A623' stroke-width='0.6' opacity='0.06'/%3E%3Cpath d='M28 100 L56 84 L56 50 L28 34 L0 50 L0 84 Z' fill='none' stroke='%23F5A623' stroke-width='0.6' opacity='0.06'/%3E%3C/svg%3E\");
        background-size:56px 100px;
    "></div>

    {{-- Navbar --}}
    <nav style="
        position:sticky; top:0; z-index:30;
        background:rgba(23,24,26,0.92);
        border-bottom:1px solid #7A4A12;
        backdrop-filter:blur(8px);
        -webkit-backdrop-filter:blur(8px);
        padding:1rem 1.5rem;
    ">
        <div style="max-width:960px; margin:0 auto; display:flex; align-items:center; justify-content:space-between;">
            <a href="{{ route('status.index') }}" style="display:flex; align-items:center; gap:0.75rem; text-decoration:none;">
                <div style="width:42px; height:42px; border-radius:50%; overflow:hidden; flex-shrink:0; display:flex; align-items:center; justify-content:center;">
                    <img src="{{ asset('assets/eb2becaa-00dc-4db2-822d-a5fa787a3d54.jpeg') }}"
                         alt="iRepair Logo"
                         style="width:100%; height:100%; object-fit:cover; object-position:center center; transform:scale(1.24); transform-origin:center center;">
                </div>
                <div>
                    <div style="font-family:'Oswald',sans-serif; font-weight:700; font-size:1.1rem; text-transform:uppercase; letter-spacing:0.05em; color:#F5A623; line-height:1.1;">iRepair</div>
                    <div style="font-family:'Inter',sans-serif; font-size:0.6rem; color:#7A4A12; letter-spacing:0.08em; text-transform:uppercase; margin-top:1px;">Gadget Service Center</div>
                </div>
            </a>

            <a href="{{ route('login') }}"
               style="display:flex; align-items:center; gap:0.5rem; padding:0.45rem 0.9rem; border:1px solid #7A4A12; border-radius:5px; font-size:0.75rem; font-family:'Inter',sans-serif; color:#9a8f7e; text-decoration:none; background:#17181A; transition:border-color 150ms, color 150ms;"
               onmouseover="this.style.borderColor='#F5A623';this.style.color='#F5A623'"
               onmouseout="this.style.borderColor='#7A4A12';this.style.color='#9a8f7e'"
            >
                <i class="fa-solid fa-lock" style="color:#B97A1A; font-size:0.7rem;"></i>
                Staff Login
            </a>
        </div>
    </nav>

    {{-- Main --}}
    <main style="flex:1; max-width:960px; width:100%; margin:0 auto; padding:2.5rem 1.5rem; position:relative; z-index:1;">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer style="border-top:1px solid #7A4A12; padding:1.25rem; text-align:center; position:relative; z-index:1;">
        <p style="font-family:'JetBrains Mono',monospace; font-size:0.65rem; color:#7A4A12; letter-spacing:0.04em; margin:0;">
            &copy; {{ date('Y') }} iRepair Gadget Service Center &mdash; All rights reserved
        </p>
    </footer>

    @stack('scripts')
</body>
</html>
