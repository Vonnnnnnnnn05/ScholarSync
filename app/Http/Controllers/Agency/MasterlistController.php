<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\PreviewMasterlistUploadRequest;
use App\Models\Agency;
use App\Models\ScholarshipMasterlist;
use App\Services\MasterlistCsvService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterlistController extends Controller
{
    public function index(Request $request): View
    {
        $agency = $this->agencyFor($request);

        return view('agency.masterlists.index', [
            'agency' => $agency,
            'masterlists' => $agency->masterlists()
                ->withCount('records')
                ->latest()
                ->paginate(10),
        ]);
    }

    public function create(Request $request): View
    {
        return view('agency.masterlists.create', [
            'agency' => $this->agencyFor($request),
            'requiredColumns' => MasterlistCsvService::REQUIRED_COLUMNS,
        ]);
    }

    public function preview(
        PreviewMasterlistUploadRequest $request,
        MasterlistCsvService $csv,
    ): View {
        $agency = $this->agencyFor($request, $request->validated('agency_name'));
        $temporaryPath = $csv->storeTemporary($request->file('masterlist'));
        $preview = $csv->preview($temporaryPath);

        session([
            'masterlist_preview' => [
                'agency_id' => $agency->id,
                'temporary_path' => $temporaryPath,
                'original_file_name' => $request->file('masterlist')->getClientOriginalName(),
            ],
        ]);

        return view('agency.masterlists.preview', [
            'agency' => $agency,
            'preview' => $preview,
            'requiredColumns' => MasterlistCsvService::REQUIRED_COLUMNS,
        ]);
    }

    public function store(Request $request, MasterlistCsvService $csv): RedirectResponse
    {
        $preview = $request->session()->get('masterlist_preview');

        abort_unless($preview, 419);

        $agency = $this->agencyFor($request);

        abort_unless((int) $preview['agency_id'] === $agency->id, 403);

        $masterlist = $csv->import(
            agency: $agency,
            temporaryPath: $preview['temporary_path'],
            originalFileName: $preview['original_file_name'],
        );

        $request->session()->forget('masterlist_preview');

        return redirect()
            ->route('agency.masterlists.show', $masterlist)
            ->with('status', 'Masterlist imported successfully.');
    }

    public function show(Request $request, ScholarshipMasterlist $masterlist): View
    {
        $agency = $this->agencyFor($request);

        abort_unless($masterlist->agency_id === $agency->id, 404);

        return view('agency.masterlists.show', [
            'agency' => $agency,
            'masterlist' => $masterlist->load('agency'),
            'records' => $masterlist->records()->latest()->paginate(25),
        ]);
    }

    private function agencyFor(Request $request, ?string $agencyName = null): Agency
    {
        $user = $request->user();

        return Agency::updateOrCreate(
            ['user_id' => $user->id],
            [
                'agency_name' => $agencyName ?: $user->agency?->agency_name ?: $user->name,
                'contact_person' => $user->name,
                'email' => $user->email,
                'status' => 'active',
            ],
        );
    }
}
