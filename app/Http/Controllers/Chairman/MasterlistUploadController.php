<?php

namespace App\Http\Controllers\Chairman;

use App\Http\Controllers\Controller;
use App\Http\Requests\PreviewMasterlistUploadRequest;
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
            'masterlists' => ScholarshipMasterlist::query()->withCount(['records', 'campusBatches'])->latest()->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('chairman.uploads.create', [
            'requiredColumns' => MasterlistCsvService::REQUIRED_COLUMNS,
        ]);
    }

    public function preview(PreviewMasterlistUploadRequest $request, MasterlistCsvService $csv): View
    {
        $temporaryPath = $csv->storeTemporary($request->file('masterlist'));
        $preview = $csv->preview($temporaryPath);

        session(['masterlist_preview' => [
            'temporary_path' => $temporaryPath,
            'original_file_name' => $request->file('masterlist')->getClientOriginalName(),
        ]]);

        return view('chairman.uploads.preview', compact('preview') + ['requiredColumns' => MasterlistCsvService::REQUIRED_COLUMNS]);
    }

    public function store(Request $request, MasterlistCsvService $csv): RedirectResponse
    {
        $preview = $request->session()->get('masterlist_preview');
        abort_unless($preview, 419);
        $masterlist = $csv->import($preview['temporary_path'], $preview['original_file_name']);
        $request->session()->forget('masterlist_preview');

        return redirect()->route('chairman.uploads.show', $masterlist)->with('status', 'Masterlist imported successfully.');
    }

    public function show(ScholarshipMasterlist $masterlist): View
    {
        return view('chairman.uploads.show', [
            'masterlist' => $masterlist->load(['campusBatches.campus']),
            'records' => $masterlist->records()->latest()->paginate(25),
        ]);
    }
}
