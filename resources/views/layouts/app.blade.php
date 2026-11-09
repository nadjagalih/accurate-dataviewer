<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Accurate Explorer' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <div class="min-h-screen">
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur">
                <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-4 px-5 py-4 sm:px-8">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-800 text-lg font-bold text-white shadow-lg shadow-slate-200">A</div>
                        <div>
                            <a href="{{ route('dashboard') }}" class="font-semibold tracking-tight text-slate-900">Accurate Explorer</a>
                        </div>
                    </div>
                    <nav class="order-3 flex w-full gap-1 overflow-x-auto border-t border-slate-100 pt-3 text-sm sm:order-2 sm:ml-5 sm:w-auto sm:border-0 sm:pt-0">
                        <a href="{{ route('dashboard') }}" class="whitespace-nowrap rounded-lg px-3 py-2 {{ request()->routeIs('dashboard') ? 'bg-blue-50 font-medium text-blue-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">Overview</a>
                        <a href="{{ route('accurate.databases') }}" class="whitespace-nowrap rounded-lg px-3 py-2 {{ request()->routeIs('accurate.databases') ? 'bg-blue-50 font-medium text-blue-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">Databases</a>
                        <a href="{{ route('accurate.list', ['module' => 'purchase-order']) }}" class="whitespace-nowrap rounded-lg px-3 py-2 {{ request()->is('accurate/purchase-order/*') ? 'bg-blue-50 font-medium text-blue-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">Purchase Orders</a>
                        <a href="{{ route('accurate.inbound') }}" class="whitespace-nowrap rounded-lg px-3 py-2 {{ request()->routeIs('accurate.inbound') ? 'bg-blue-50 font-medium text-blue-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">Inbound</a>
                        <a href="{{ route('accurate.daily-inbound') }}" class="whitespace-nowrap rounded-lg px-3 py-2 {{ request()->routeIs('accurate.daily-inbound') ? 'bg-blue-50 font-medium text-blue-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">Daily Inbound</a>
                        <a href="{{ route('accurate.inbound-pr') }}" class="whitespace-nowrap rounded-lg px-3 py-2 {{ request()->routeIs('accurate.inbound-pr') ? 'bg-blue-50 font-medium text-blue-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">Inbound (PR)</a>
                        <a href="{{ route('accurate.list', ['module' => 'item-transfer']) }}" class="whitespace-nowrap rounded-lg px-3 py-2 {{ request()->is('accurate/item-transfer/*') ? 'bg-blue-50 font-medium text-blue-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">Internal Transfers</a>
                    </nav>
                    <div class="ml-auto flex items-center gap-3">
                        <div class="hidden items-center gap-2 text-xs text-slate-500 md:flex"><span class="h-2 w-2 rounded-full bg-slate-400"></span>{{ config('accurate.access_token') ? 'Provider ready' : 'Setup required' }}</div>
                        <button type="button" data-inspector-open class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 transition hover:border-slate-400 hover:bg-slate-100 hover:text-slate-900"><span class="h-2 w-2 rounded-full bg-slate-500"></span>API Inspector</button>
                    </div>
                </div>
        </header>

        <main class="mx-auto max-w-7xl px-5 py-7 sm:px-8">
                @yield('content')
        </main>
    @include('components.inspector.drawer')
    <script>window.accurateInspector = @json($envelope ?? null);</script>
    @stack('scripts')
</body>
</html>
