@extends('layouts.app')

@section('content')
    <div class="mb-6"><div class="mb-3 flex items-center gap-2 text-xs text-slate-500"><span>Workspace</span><span>/</span><span class="text-slate-700">{{ $title }}</span></div><h1 class="text-2xl font-bold text-slate-900">{{ $title }} detail</h1><span class="mt-2 block text-sm text-slate-600">Payload detail dari Accurate API.</span></div>
    @if (isset($envelope['error']))
        <div class="mb-5 rounded-lg border border-slate-200 bg-slate-100 p-4 text-sm text-slate-700">
            <strong>{{ $envelope['error']['code'] }}:</strong> {{ $envelope['error']['message'] }}
        </div>
    @endif
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4"><h2 class="font-semibold text-slate-900">Raw response</h2><span class="mt-1 block text-xs text-slate-500">Gunakan API Inspector untuk metadata request.</span></div>
        <pre class="bg-white p-5 text-xs leading-6 text-slate-700">
@json($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)</pre>
    </div>
@endsection
