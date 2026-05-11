<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SupplierQuote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Karenderia;

class OwnerSupplierQuoteController extends Controller
{
    /**
     * Accept a supplier quote
     */
    public function accept(Request $request, SupplierQuote $quote)
    {
        $user = Auth::user();
        $ingredientRequest = $quote->ingredientRequest;
        $karenderia = $ingredientRequest->karenderia;

        if ($karenderia->owner_id !== $user->id) {
            return redirect()->back()
                ->with('error', 'Unauthorized');
        }

        if ($ingredientRequest->status !== 'open') {
            return redirect()->back()
                ->with('error', 'This request is no longer open for acceptance');
        }

        $quote->accept();

        return redirect()->back()
            ->with('success', 'Quote accepted! You can now communicate with the supplier.');
    }

    /**
     * Reject a supplier quote
     */
    public function reject(Request $request, SupplierQuote $quote)
    {
        $user = Auth::user();
        $ingredientRequest = $quote->ingredientRequest;
        $karenderia = $ingredientRequest->karenderia;

        if ($karenderia->owner_id !== $user->id) {
            return redirect()->back()
                ->with('error', 'Unauthorized');
        }

        $quote->reject();

        return redirect()->back()
            ->with('success', 'Quote rejected');
    }
}
