<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnnouncementRequest;
use App\Models\Announcement;
use App\Models\Sector;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request)
    {
        return view('announcements.index', [
            'notices' => Announcement::forUser($request->user())->with('author:id,name')
                            ->latest('published_at')->paginate(15),
            'sectors' => Sector::orderBy('name')->get(),
        ]);
    }

    public function store(StoreAnnouncementRequest $request)
    {
        $notice = Announcement::create($request->validated() + [
            'author_id'    => $request->user()->id,
            'published_at' => now(),
        ]);

        $this->audit->allowed('announcements.publish', $notice->title, ['audience' => $notice->audience]);

        return back()->with('status', 'Published.');
    }

    public function destroy(Request $request, Announcement $announcement)
    {
        $title = $announcement->title;
        $announcement->delete();

        $this->audit->allowed('announcements.withdraw', $title);

        return back()->with('status', 'Notice withdrawn.');
    }
}
