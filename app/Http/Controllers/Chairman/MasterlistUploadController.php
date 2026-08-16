<?php

namespace App\Http\Controllers\Chairman;

use App\Http\Controllers\Controller;
use App\Http\Requests\PreviewMasterlistUploadRequest;
use App\Models\Agency;
use App\Models\ScholarshipMasterlist;
use App\Services\MasterlistCsvService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterlistUploadController extends Controller
{
    public function index(): View
    {
        return view('chairman.uploads.index', [
            'masterlists' => ScholarshipMasterlist::query()->with('agency')->withCount('records')->latest()->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('chairman.uploads.create', [
            'agencies' => Agency::query()->where('status', 'active')->orderBy('agency_name')->get(),
            'requiredColumns' => MasterlistCsvService::REQUIRED_COLUMNS,
        ]);
    }

    public function preview(PreviewMasterlistUploadRequest $request, MasterlistCsvService $csv): View
    {
        $agency = Agency::query()->findOrFail($request->integer('agency_id'));
        $temporaryPath = $csv->storeTemporary($request->file('masterlist'));
        $preview = $csv->preview($temporaryPath);

        session(['masterlist_preview' => [
            'agency_id' => $agency->id,
            'temporary_path' => $temporaryPath,
            'original_file_name' => $request->file('masterlist')->getClientOriginalName(),
        ]]);

        return view('chairman.uploads.preview', compact('agency', 'preview') + ['requiredColumns' => MasterlistCsvService::REQUIRED_COLUMNS]);
    }

    public function store(Request $request, MasterlistCsvService $csv): RedirectResponse
    {
        $preview = $request->session()->get('masterlist_preview');
        abort_unless($preview, 419);
        $agency = Agency::query()->findOrFail($preview['agency_id']);
        $masterlist = $csv->import($agency, $preview['temporary_path'], $preview['original_file_name']);
        $request->session()->forget('masterlist_preview');

        return redirect()->route('chairman.uploads.show', $masterlist)->with('status', 'Masterlist imported successfully.');
    }

    public function show(ScholarshipMasterlist $masterlist): View
    {
        return view('chairman.uploads.show', [
            'agency' => $masterlist->agency,
            'masterlist' => $masterlist->load('agency'),
            'records' => $masterlist->records()->latest()->paginate(25),
        ]);
    }
}
