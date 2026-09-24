<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Document handling. Files are stored on a private disk under a generated
 * name: the original name is data, never a filesystem path, which removes
 * the directory-traversal and double-extension routes in one step.
 */
class DocumentService
{
    public function __construct(private AuditLogger $audit) {}

    public function store(UploadedFile $file, array $data, User $owner): Document
    {
        $disk = config('sldwp.doc_disk', 'local');
        $name = Str::uuid()->toString().'.'.$file->extension();   // never the user's string
        $path = $file->storeAs('documents/'.now()->format('Y/m'), $name, $disk);

        $document = Document::create([
            'title'          => $data['title'],
            'stored_path'    => $path,
            'original_name'  => $file->getClientOriginalName(),
            'mime'           => $file->getMimeType(),             // sniffed, not trusted from the client
            'size_bytes'     => $file->getSize(),
            'checksum_sha256'=> hash_file('sha256', $file->getRealPath()),
            'sector_id'      => $data['sector_id'] ?? $owner->sector_id,
            'classification' => $data['classification'],
            'owner_id'       => $owner->id,
        ]);

        $this->audit->allowed('documents.upload', $document->title, ['classification' => $document->classification]);

        return $document;
    }

    /** The permission a given row demands, derived from the row itself. */
    public function requiredPermission(Document $document): string
    {
        return $document->classification === 'restricted'
            ? 'documents.view.restricted'
            : 'documents.view';
    }

    public function streamDownload(Document $document)
    {
        $disk = config('sldwp.doc_disk', 'local');

        abort_unless(Storage::disk($disk)->exists($document->stored_path), 404);

        return Storage::disk($disk)->download($document->stored_path, $document->original_name, [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition'    => 'attachment',
        ]);
    }
}
