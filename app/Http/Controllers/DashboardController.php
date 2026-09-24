<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        // One grouped query rather than five counts: the aggregation the
        // chart needs is computed in the database, not in PHP.
        $holdings = Document::visibleTo($user)
            ->join('sectors', 'sectors.id', '=', 'documents.sector_id')
            ->selectRaw('sectors.name as sector, documents.classification, count(*) as total')
            ->groupBy('sectors.name', 'documents.classification')
            ->get();

        return view('dashboard', [
            'activeUsers' => User::where('is_active', true)->count(),
            'visibleDocs' => Document::visibleTo($user)->count(),
            'heldDocs'    => Document::count(),
            'notices'     => Announcement::forUser($user)->with('author:id,name')->latest('published_at')->take(3)->get(),
            'denials7d'   => AuditLog::where('result','denied')->where('created_at','>=',now()->subDays(7))->count(),
            'holdings'    => $holdings,
        ]);
    }
}
