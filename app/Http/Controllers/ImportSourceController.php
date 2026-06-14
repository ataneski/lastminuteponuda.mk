<?php

namespace App\Http\Controllers;

use App\Jobs\SyncImportSourceJob;
use App\Models\ImportSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportSourceController extends Controller
{
    public function index(): View
    {
        $sources = ImportSource::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->get();

        return view('agency.import-sources.index', compact('sources'));
    }

    public function create(): View
    {
        return view('agency.import-sources.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'url' => ['required', 'url', 'max:500'],
            'label' => ['nullable', 'string', 'max:120'],
            'schedule' => ['required', 'in:manual,hourly,6h,daily'],
        ]);

        $source = ImportSource::create([
            'user_id' => auth()->id(),
            'url' => $data['url'],
            'label' => $data['label'] ?? null,
            'schedule' => $data['schedule'],
            'active' => true,
        ]);

        return redirect()
            ->route('agency.import-sources.show', $source)
            ->with('status', 'Извор додаден. Кликни „Sync сега" за прв обид.');
    }

    public function show(ImportSource $importSource): View
    {
        abort_unless($importSource->user_id === auth()->id(), 404);

        return view('agency.import-sources.show', ['source' => $importSource]);
    }

    public function sync(ImportSource $importSource): RedirectResponse
    {
        abort_unless($importSource->user_id === auth()->id(), 404);

        SyncImportSourceJob::dispatchSync($importSource->id);

        $importSource->refresh();
        $message = match ($importSource->last_status) {
            ImportSource::STATUS_SUCCESS => '✓ Извлечени '.$importSource->last_extracted_count.' нови огласи. Прегледи ги во Мои огласи.',
            ImportSource::STATUS_PARTIAL => 'Sync помина, но ништо ново. Можеби сите се веќе додадени.',
            ImportSource::STATUS_FAILED  => 'Sync не успеа: '.$importSource->last_error,
            default => 'Sync извршен.',
        };

        return back()->with('status', $message);
    }

    public function toggle(ImportSource $importSource): RedirectResponse
    {
        abort_unless($importSource->user_id === auth()->id(), 404);

        $importSource->update(['active' => ! $importSource->active]);

        return back()->with('status', $importSource->active ? 'Активен.' : 'Паузиран.');
    }

    public function destroy(ImportSource $importSource): RedirectResponse
    {
        abort_unless($importSource->user_id === auth()->id(), 404);

        $importSource->delete();

        return redirect()
            ->route('agency.import-sources')
            ->with('status', 'Извор избришан.');
    }
}
