<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $term = $request->string('q')->toString();

        $logs = AuditLog::ofKind($request->string('kind')->toString() ?: null)
            ->when($term, fn ($q) => $q->where(fn ($s) => $s
                ->where('actor', 'like', '%'.addcslashes($term, '%_\\').'%')
                ->orWhere('action', 'like', '%'.addcslashes($term, '%_\\').'%')))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('audit.index', compact('logs'));
    }
}
