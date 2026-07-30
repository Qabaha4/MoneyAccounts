<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ActivityHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::where('user_id', Auth::id())->latest();

        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('description', 'like', "%{$search}%");
        }

        $activities = $query->paginate(20);
        $activities->loadMorph('subject', [
            Transaction::class => ['account.currency', 'transferToAccount'],
        ]);

        return Inertia::render('Activity/Index', [
            'activities' => $activities,
            'filters' => $request->only(['subject_type', 'action', 'date_from', 'date_to', 'search']),
        ]);
    }
}
