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
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:under_review,resolved,closed'],
        ]);

        if ($validated['status'] === 'under_review') {
            $dispute->markAsUnderReview();
        } elseif ($validated['status'] === 'resolved') {
            $request->validate([
                'notes' => ['required', 'string', 'max:2000'],
            ]);
            $dispute->resolve($request->user(), $validated['notes']);
        } else {
            $request->validate([
                'notes' => ['required', 'string', 'max:2000'],
            ]);
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
