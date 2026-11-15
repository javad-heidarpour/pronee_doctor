<?php
// app/Http/Controllers/Doctor/ReviewController.php
namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
$user = Auth::guard('web')->user();
$doctor = $user->doctorProfile;
        
        $query = $doctor->reviews()->with('user');
        
        if ($request->filter === 'approved') {
            $query->where('is_approved', true);
        } elseif ($request->filter === 'pending') {
            $query->where('is_approved', false);
        }

        $reviews = $query->orderBy('created_at', 'desc')->get()->map(fn($r) => [
            'id' => $r->id,
            'patient_name' => $r->patient_display_name,
            'rating' => $r->rating,
            'comment' => $r->comment,
            'is_approved' => $r->is_approved,
            'persian_date' => $r->persian_date,
        ]);

        return Inertia::render('Portal/Reviews', [
            'reviews' => $reviews,
            'filter' => $request->filter ?? 'all',
        ]);
    }

    public function toggleApproval(Review $review)
    {
        if ($review->doctor_id !== Auth::guard('doctor')->id()) {
            abort(403);
        }

        $review->update(['is_approved' => !$review->is_approved]);

        return back()->with('success', 'وضعیت نظر تغییر کرد.');
    }

    public function destroy(Review $review)
    {
        if ($review->doctor_id !== Auth::guard('doctor')->id()) {
            abort(403);
        }

        $review->delete();
        return back()->with('success', 'نظر حذف شد.');
    }
}