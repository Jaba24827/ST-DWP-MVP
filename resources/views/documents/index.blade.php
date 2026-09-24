@extends('layouts.app')
@section('title', 'Documents')
@section('crumb', 'Documents')
@section('content')
  <div class="page__head">
    <h1>Documents</h1>
    <p>Files are filtered by classification before the list is built, so you only see what your role permits.</p>
    @can_perm('documents.upload')
      <div class="page__actions" style="margin-top:14px">
        <button class="btn" onclick="document.getElementById('uploadForm').showModal()">Upload a document</button>
      </div>
    @endcan_perm
  </div>

  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
    <label class="sr-only" for="q">Search documents</label>
    <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Search documents">
    <label class="sr-only" for="classification">Filter by classification</label>
    <select id="classification" name="classification" onchange="this.form.submit()">
      <option value="">All classifications</option>
      @foreach (['public','internal','restricted'] as $c)
        <option value="{{ $c }}" {{ request('classification') === $c ? 'selected' : '' }}>{{ ucfirst($c) }}</option>
      @endforeach
    </select>
    <button class="btn btn--ghost btn--sm">Filter</button>
  </form>

  <div class="table-wrap">
    <table>
      <thead><tr><th>Document</th><th>Sector</th><th>Classification</th><th>Owner</th><th>Updated</th><th>Actions</th></tr></thead>
      <tbody>
      @forelse ($documents as $d)
        <tr>
          <td><b>{{ $d->title }}</b></td>
          <td>{{ $d->sector?->name }}</td>
          <td><span class="tag {{ $d->classification === 'restricted' ? 'tag--deny' : ($d->classification === 'internal' ? 'tag--warn' : 'tag--ok') }}">{{ $d->classification }}</span></td>
          <td>{{ $d->owner->name }}</td>
          <td>{{ $d->created_at->format('j M Y') }}</td>
          <td style="white-space:nowrap">
            <a class="btn btn--ghost btn--sm" href="{{ route('documents.download', $d) }}">Open</a>
            @can_perm('documents.delete')
              <form method="POST" action="{{ route('documents.destroy', $d) }}" style="display:inline" onsubmit="return confirm('Delete this document?')">
                @csrf @method('DELETE')
                <button class="btn btn--ghost btn--sm">Delete</button>
              </form>
            @endcan_perm
          </td>
        </tr>
      @empty
        <tr><td colspan="6" class="empty"><b>Nothing to show</b>No document matches these filters within your access.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  {{ $documents->links() }}
  <p style="font-size:12.5px;color:var(--text-3);margin-top:10px">
    @if ($hidden > 0)
      {{ $hidden }} restricted document{{ $hidden > 1 ? 's are' : ' is' }} held in the repository but filtered out of the query for your role.
    @else
      Your role can open every classification held in the repository.
    @endif
  </p>

  <dialog id="uploadForm">
    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" style="padding:20px;min-width:min(420px,90vw)">
      @csrf
      <h3 style="color:var(--brand)">Upload a document</h3>
      <p style="font-size:13.5px;color:var(--text-2)">Classification decides who can open the file.</p>
      <div class="field"><label for="title">Title</label><input id="title" name="title" required></div>
      <div class="field"><label for="sector_id">Sector</label>
        <select id="sector_id" name="sector_id">
          @foreach (\App\Models\Sector::orderBy('name')->get() as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
        </select></div>
      <div class="field"><label for="classification">Classification</label>
        <select id="classification" name="classification" required>
          <option value="public">Public</option><option value="internal" selected>Internal</option><option value="restricted">Restricted</option>
        </select></div>
      <div class="field"><label for="file">File (PDF, Word, Excel or image, up to 10 MB)</label><input id="file" name="file" type="file" required></div>
      <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px">
        <button type="button" class="btn btn--ghost" onclick="document.getElementById('uploadForm').close()">Cancel</button>
        <button class="btn" type="submit">Upload</button>
      </div>
    </form>
  </dialog>
@endsection
