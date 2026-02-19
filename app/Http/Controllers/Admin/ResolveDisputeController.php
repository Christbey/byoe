<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ResolveDisputeController extends Controller
{
    public function __invoke(Request $request, Dispute $dispute): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['required', 'string', 'max:2000'],
            'status' => ['required', 'in:resolved,closed'],
        ]);

        if ($validated['status'] === 'resolved') {
            $dispute->resolve($request->user(), $validated['notes']);
        } else {
            $dispute->update([
                'status' => 'closed',
                'resolution_notes' => $validated['notes'],
                'resolved_by_user_id' => $request->user()->id,
                'resolved_at' => now(),
            ]);
        }

        return redirect()->route('admin.disputes')
            ->with('success', 'Dispute has been '.$validated['status'].'.');
    }
}
