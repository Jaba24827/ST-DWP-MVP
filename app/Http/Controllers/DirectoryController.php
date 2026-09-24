<?php

namespace App\Http\Controllers;

use App\Models\Sector;
use App\Models\User;
use Illuminate\Http\Request;

class DirectoryController extends Controller
{
    public function index(Request $request)
    {
        $term = $request->string('q')->toString();

        $people = User::where('is_active', true)
            ->when($request->filled('sector_id'), fn ($q) => $q->where('sector_id', $request->integer('sector_id')))
            ->when($term, fn ($q) => $q->where('name', 'like', '%'.addcslashes($term, '%_\\').'%'))
            ->with(['sector:id,name','site:id,name'])
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('directory.index', ['people' => $people, 'sectors' => Sector::orderBy('name')->get()]);
    }
}
