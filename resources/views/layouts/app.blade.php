<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') — OMG Back Office</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Core layout styles live here rather than in Tailwind utilities so the
           back office renders correctly even when the compiled CSS bundle
           predates these views (Tailwind only emits classes it saw at build time). */
        .bo-aside { background:#0b1220; color:#d1d5db; }
        .bo-brand { color:#fff; font-weight:700; font-size:.875rem; }
        .bo-section { color:#6b7280; }
        .bo-link { display:block; padding:.5rem 1.25rem; color:#d1d5db; text-decoration:none; border-right:2px solid transparent; }
        .bo-link:hover { background:rgba(255,255,255,.06); color:#fff; }
        .bo-link.active { background:rgba(79,70,229,.25); color:#fff; border-right-color:#6366f1; }
        .bo-user { color:#9ca3af; }
        .bo-stats { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
        @media (min-width:768px) { .bo-stats { grid-template-columns:repeat(4,minmax(0,1fr)); } }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">
<div class="flex min-h-screen">
    {{-- Sidebar --}}
    <aside class="bo-aside w-60 flex-shrink-0 flex flex-col">
        <div class="px-5 py-4 border-b border-white/10">
            <div class="bo-brand">OMG Back Office</div>
            <div class="text-[11px] text-gray-500 mt-0.5">{{ auth()->user()->hasRole('webmaster') ? 'Webmaster' : 'Merchant' }}</div>
        </div>

        <nav class="flex-1 overflow-y-auto py-3 text-sm">
            @php
                $nav = auth()->user()->hasRole('webmaster') ? [
                    'Merchant' => [
                        ['label' => 'Merchant Credits', 'route' => 'webmaster.merchant-credits.index'],
                        ['label' => 'Game Config Changes', 'route' => 'webmaster.game-config-changes.index'],
                        ['label' => 'Merchant Account', 'route' => 'webmaster.merchant-account.index'],
                    ],
                ] : [
                    'Overview' => [
                        ['label' => 'Dashboard', 'route' => 'backoffice.dashboard'],
                        ['label' => 'Merchant Credits', 'route' => 'backoffice.credits.index'],
                    ],
                    'Comprehensive Data' => [
                        ['label' => 'Rankings', 'route' => 'backoffice.rankings.index'],
                        ['label' => 'Trend Data', 'route' => 'backoffice.trend.index'],
                        ['label' => 'Betting Retention', 'route' => 'backoffice.retention.index'],
                    ],
                    'Player Data' => [
                        ['label' => 'Player List', 'route' => 'backoffice.players.index'],
                        ['label' => 'Game Records', 'route' => 'backoffice.game-records.index'],
                        ['label' => 'Order Records', 'route' => 'backoffice.order-records.index'],
                    ],
                    'Game Ctrl' => [
                        ['label' => 'Game Config', 'route' => 'backoffice.game-config.index', 'match' => ['backoffice.game-config.index', 'backoffice.game-config.edit']],
                        ['label' => 'Batch Changes', 'route' => 'backoffice.game-config.batch'],
                        ['label' => 'RTP Control', 'route' => 'backoffice.rtp.index'],
                        ['label' => 'Nurturing & Killing', 'route' => 'backoffice.patron.index'],
                    ],
                    'Log Mgmt' => [
                        ['label' => 'Operation Log', 'route' => 'backoffice.logs.index'],
                    ],
                    'Setting' => [
                        ['label' => 'Callback URLs', 'route' => 'backoffice.settings.index'],
                    ],
                ];
            @endphp

            @foreach ($nav as $section => $items)
                <div class="bo-section px-5 mt-4 mb-1 text-[10px] font-semibold uppercase tracking-wide">{{ $section }}</div>
                @foreach ($items as $item)
                    @if (!empty($item['soon']))
                        <div class="px-5 py-2 flex items-center justify-between text-gray-600 cursor-not-allowed">
                            <span>{{ $item['label'] }}</span>
                            <span class="text-[9px] bg-white/5 text-gray-500 px-1.5 py-0.5 rounded">Soon</span>
                        </div>
                    @else
                        <a href="{{ route($item['route']) }}"
                           class="bo-link {{ request()->routeIs(...(array) ($item['match'] ?? $item['route'])) ? 'active' : '' }}">
                            {{ $item['label'] }}
                        </a>
                    @endif
                @endforeach
            @endforeach
        </nav>

        <div class="px-5 py-4 border-t border-white/10 text-xs">
            <div class="bo-user">{{ auth()->user()->name ?? auth()->user()->username }}</div>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button type="submit" class="text-red-400 hover:text-red-300">Log out</button>
            </form>
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex-1 flex flex-col min-w-0">
        <header class="bg-white border-b border-gray-200 px-6 py-3">
            <h1 class="text-lg font-bold text-gray-800">@yield('title', 'Dashboard')</h1>
        </header>

        <main class="flex-1 p-6">
            @if (session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-800 text-sm px-4 py-3 rounded-lg">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3 rounded-lg">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
@include('partials.password-toggle')
</body>
</html>
