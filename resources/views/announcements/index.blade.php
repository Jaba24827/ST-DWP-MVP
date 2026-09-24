@extends('layouts.app')
@section('title', 'Notices')
@section('crumb', 'Notices')
@section('content')
  <div class="page__head">
    <h1>Notices</h1><p>Notices published to your sector and to the whole Foundation.</p>
    @can_perm('announcements.publish')
      <div class="page__actions" style="margin-top:14px">
        <button class="btn" onclick="document.getElementById('newNotice').showModal()">Publish a notice</button>
      </div>
    @endcan_perm
  </div>
  <div class="card">
    @forelse ($notices as $n)
      <div style="padding:14px 0;border-bottom:1px solid var(--line)">
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
          <b>{{ $n->title }}</b><span class="tag">{{ $n->audience }}</span>
          @can_perm('announcements.publish')
            <form method="POST" action="{{ route('notices.destroy', $n) }}" style="margin-left:auto" onsubmit="return confirm('Withdraw this notice?')">
              @csrf @method('DELETE')<button class="btn btn--ghost btn--sm">Withdraw</button>
            </form>
          @endcan_perm
        </div>
        <p style="font-size:13.5px;color:var(--text-2)">{{ $n->body }}</p>
        <div style="font-size:12px;color:var(--text-3)">{{ $n->author->name }} · {{ $n->published_at->format('j M Y') }}</div>
      </div>
    @empty
      <div class="empty"><b>Nothing published yet</b></div>
    @endforelse
  </div>
  {{ $notices->links() }}

  <dialog id="newNotice">
    <form method="POST" action="{{ route('notices.store') }}" style="padding:20px;min-width:min(440px,90vw)">
      @csrf
      <h3 style="color:var(--brand)">Publish a notice</h3>
      <div class="field"><label for="title">Title</label><input id="title" name="title" required></div>
      <div class="field"><label for="body">Message</label><textarea id="body" name="body" rows="4" required></textarea></div>
      <div class="field"><label for="audience">Audience</label>
        <select id="audience" name="audience">
          <option value="all">All sectors</option>
          @foreach ($sectors as $s)<option value="{{ $s->name }}">{{ $s->name }}</option>@endforeach
        </select></div>
      <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px">
        <button type="button" class="btn btn--ghost" onclick="document.getElementById('newNotice').close()">Cancel</button>
        <button class="btn" type="submit">Publish</button>
      </div>
    </form>
  </dialog>
@endsection
