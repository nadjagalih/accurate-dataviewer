<aside id="inspector-drawer" class="fixed inset-y-0 right-0 z-50 hidden w-full max-w-xl border-l border-slate-200 bg-white shadow-2xl">
    <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-5 py-4">
        <div>
            <h2 class="font-semibold text-slate-900">API Inspector</h2>
            <span class="block text-xs text-slate-500">Panggilan terakhir dari halaman ini</span>
        </div>
        <button type="button" data-inspector-close class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-100 hover:text-slate-900">Tutup</button>
    </div>
    <div class="flex h-[calc(100%-73px)] flex-col">
        <div id="inspector-summary" class="border-b border-slate-200 bg-white p-5 text-sm text-slate-500">Belum ada panggilan.</div>
        <div class="flex items-center gap-2 border-b border-slate-200 px-5 py-3">
            <button type="button" data-inspector-copy class="rounded bg-slate-800 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-700">Salin JSON</button>
            <button type="button" data-inspector-download class="rounded border border-slate-600 px-3 py-1.5 text-xs hover:bg-slate-800">Unduh JSON</button>
        </div>
        <pre id="inspector-json" class="flex-1 overflow-auto bg-slate-50 p-5 text-xs leading-6 text-slate-700"></pre>
    </div>
</aside>
