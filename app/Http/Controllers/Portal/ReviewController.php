<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReviewController extends Controller
{
    use HasActiveDoctor;

    public function index(Request $request)
    {
        $doctor = $this->currentDoctor();

        $query = $doctor->reviews()->with('user');

        if ($request->input('filter') === 'approved') {
            $query->where('is_approved', true);
        } elseif ($request->input('filter') === 'pending') {
            $query->where('is_approved', false);
        }

        $reviews = $query->latest()->get()->map(fn (Review $review) => [
            'id' => $review->id,
            'patient_name' => $review->patient_display_name,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'is_approved' => $review->is_approved,
            'persian_date' => $review->persian_date,
        ]);

        return Inertia::render('Portal/Reviews', [
            'doctor' => $doctor,
            'reviews' => $reviews,
            'filter' => $request->input('filter', 'all'),
        ]);
    }

    public function toggleApproval(Review $review)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeReview($review, $doctor);

        $approved = !$review->is_approved;

        $review->update([
            'is_approved' => $approved,
            'approved_at' => $approved ? now() : null,
            'approved_by' => $approved ? Auth::guard('web')->id() : null,
        ]);

        return back()->with('success', 'وضعیت نظر تغییر کرد.');
    }

    public function destroy(Review $review)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeReview($review, $doctor);

        $review->delete();

        return back()->with('success', 'نظر حذف شد.');
    }

    private function authorizeReview(Review $review, \App\Models\Doctor $doctor): void
    {
        abort_unless(
            (int) $review->doctor_id === (int) $doctor->id,
            403,
            'شما به این نظر دسترسی ندارید.'
        );
    }
}