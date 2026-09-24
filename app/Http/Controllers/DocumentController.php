<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use App\Services\AuditLogger;
use App\Services\DocumentService;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentService $documents,
        private AuditLogger $audit,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $documents = Document::visibleTo($user)
            ->search($request->string('q')->toString() ?: null)
            ->when($request->filled('classification'),
                   fn ($q) => $q->where('classification', $request->string('classification')))
            ->with(['owner:id,name','sector:id,name'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('documents.index', [
            'documents' => $documents,
            'hidden'    => Document::count() - Document::visibleTo($user)->count(),
        ]);
    }

    public function store(StoreDocumentRequest $request)
    {
        $document = $this->documents->store($request->file('file'), $request->validated(), $request->user());

        return redirect()->route('documents.index')->with('status', "Uploaded {$document->title}.");
    }

    public function download(Request $request, Document $document)
    {
        // Checked against the row, not only the route: classification lives
        // on the record, so the record decides which permission applies.
        $needed = $this->documents->requiredPermission($document);

        if (! $request->user()->can_($needed)) {
            $this->audit->denied('documents.open', $document->title, ['needed' => $needed]);
            abort(403);
        }

        $this->audit->allowed('documents.open', $document->title);
        $document->downloads()->create(['user_id' => $request->user()->id, 'ip' => $request->ip()]);

        return $this->documents->streamDownload($document);
    }

    public function destroy(Request $request, Document $document)
    {
        $title = $document->title;
        $document->delete();                       // soft delete: the trail survives

        $this->audit->allowed('documents.delete', $title);

        return back()->with('status', "Deleted {$title}.");
    }
}
