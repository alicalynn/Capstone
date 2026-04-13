<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Karenderia;
use App\Models\User;

class PendingController extends Controller
{
    public function index()
    {
        $pendingKarenderias = Karenderia::with('owner')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $pendingSuppliers = User::where('role', 'supplier')
            ->where(function ($query) {
                $query->where('application_status', 'pending')
                      ->orWhereNull('application_status');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $pendingCount = $pendingKarenderias->total() + $pendingSuppliers->count();

        return view('admin.pending', compact('pendingKarenderias', 'pendingSuppliers'))->with('pendingCount', $pendingCount);
    }

    public function approve(Request $request, $id)
    {
        try {
            $karenderia = Karenderia::findOrFail($id);
            $karenderia->status = 'approved';
            $karenderia->approved_at = now();
            $karenderia->save();

            if ($karenderia->owner) {
                $karenderia->owner->application_status = 'approved';
                $karenderia->owner->verified = true;
                $karenderia->owner->save();
            }

            return redirect()->route('admin.pending')
                ->with('success', "Karenderia '{$karenderia->name}' has been approved successfully!");
        } catch (\Exception $e) {
            return redirect()->route('admin.pending')
                ->with('error', 'Failed to approve karenderia. Please try again.');
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        try {
            $karenderia = Karenderia::findOrFail($id);
            $karenderia->status = 'rejected';
            $karenderia->rejection_reason = $request->rejection_reason;
            $karenderia->rejected_at = now();
            $karenderia->save();

            if ($karenderia->owner) {
                $karenderia->owner->application_status = 'rejected';
                $karenderia->owner->verified = false;
                $karenderia->owner->save();
            }

            return redirect()->route('admin.pending')
                ->with('success', "Karenderia '{$karenderia->name}' has been rejected.");
        } catch (\Exception $e) {
            return redirect()->route('admin.pending')
                ->with('error', 'Failed to reject karenderia. Please try again.');
        }
    }

    public function approveUser(Request $request, $id)
    {
        try {
            $user = \App\Models\User::findOrFail($id);
            $user->application_status = 'approved';
            $user->verified = true;
            $user->save();

            return redirect()->route('admin.pending')
                ->with('success', "User '{$user->name}' has been approved successfully!");
        } catch (\Exception $e) {
            return redirect()->route('admin.pending')
                ->with('error', 'Failed to approve user. Please try again.');
        }
    }

    public function rejectUser(Request $request, $id)
    {
        try {
            $user = \App\Models\User::findOrFail($id);
            $user->application_status = 'rejected';
            $user->verified = false;
            $user->save();

            return redirect()->route('admin.pending')
                ->with('success', "User '{$user->name}' has been rejected.");
        } catch (\Exception $e) {
            return redirect()->route('admin.pending')
                ->with('error', 'Failed to reject user. Please try again.');
        }
    }

    public function showPendingDashboard()
    {
        $pendingKarenderias = Karenderia::select('id', 'name', 'description')
            ->where('status', 'pending')
            ->get()
            ->toArray();

        return view('pending.pendingDashboard', ['pendingKarenderias' => $pendingKarenderias]);
    }
}
