const envelope = window.accurateInspector ?? null;
const drawer = document.querySelector('#inspector-drawer');
const jsonOutput = document.querySelector('#inspector-json');
const summary = document.querySelector('#inspector-summary');

function openInspector() {
    drawer?.classList.remove('hidden');
}

function closeInspector() {
    drawer?.classList.add('hidden');
}

if (envelope && jsonOutput && summary) {
    jsonOutput.textContent = JSON.stringify(envelope, null, 2);
    const meta = envelope.meta ?? {};
    summary.innerHTML = `<div class="grid grid-cols-2 gap-2"><span>Endpoint</span><strong class="text-slate-200">${meta.endpoint ?? '-'}</strong><span>Status</span><strong class="text-slate-200">${meta.httpStatus ?? '-'}</strong><span>Durasi</span><strong class="text-slate-200">${meta.durationMs ?? 0} ms</strong><span>Sumber</span><strong class="text-slate-200">${meta.source ?? 'live'}</strong></div>`;
}

document.querySelector('[data-inspector-open]')?.addEventListener('click', openInspector);
document.querySelector('[data-inspector-close]')?.addEventListener('click', closeInspector);
document.querySelector('[data-inspector-copy]')?.addEventListener('click', async () => {
    if (envelope) await navigator.clipboard.writeText(JSON.stringify(envelope, null, 2));
});
document.querySelector('[data-inspector-download]')?.addEventListener('click', () => {
    if (!envelope) return;
    const blob = new Blob([JSON.stringify(envelope, null, 2)], { type: 'application/json' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `${envelope.meta?.id ?? 'accurate-call'}.json`;
    link.click();
    URL.revokeObjectURL(link.href);
});
