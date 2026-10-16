<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Models\BackOfficeOperationLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(Request $request): View
    {
        $action = $request->input('action', '');

        $logs = BackOfficeOperationLog::with('admin')
            ->when($action !== '', fn ($q) => $q->where('action', $action))
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();

        $actions = BackOfficeOperationLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return view('backoffice.logs.index', compact('logs', 'action', 'actions'));
    }
}
