<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PostComment;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostCommentController extends Controller
{

    public function index(Request $request): Response
    {

        // دریافت کاربر از گارد اصلی
        $user = Auth::guard('web')->user();

        abort_unless(
            $user,
            401,
            'کاربر وارد نشده است.'
        );

        // دریافت پروفایل پزشک
        $doctor = $user->doctorProfile;

        abort_unless(
            $doctor,
            403,
            'پروفایل پزشک برای این حساب کاربری وجود ندارد.'
        );


        $comments = PostComment::query()
            ->whereNull('parent_id') // 👈 فقط نظرات اصلی، نه ریپلای‌ها
            ->whereHas('post', function ($query) use ($doctor) {

                $query->where(
                    'doctor_id',
                    $doctor->id
                );

            })

            ->with([
                'post:id,title',
                'user:id,name',
                'replies'
            ])

            ->when(
                $request->search,
                function ($query) use ($request) {

                    $query->where(
                        'comment',
                        'like',
                        "%{$request->search}%"
                    );

                }
            )

            ->latest()

            ->paginate(20)

            ->withQueryString();

   
        return Inertia::render(
            'Portal/PostComments/Index',
            [
                'doctor' => $doctor,
                'comments' => $comments,
                'filters' => $request->only([
                    'search'
                ])
            ]
        );

    }




    /**
     * تایید نظر
     */
    public function approve(PostComment $comment)
    {

        $comment->update([

            'is_approved' => true,

            'approved_at' => now(),

        ]);


        return back()->with(
            'success',
            'نظر تایید شد.'
        );

    }




    /**
     * رد تایید
     */
    public function reject(PostComment $comment)
    {

        $comment->update([

            'is_approved' => false,

            'approved_at' => null,

        ]);


        return back()->with(
            'success',
            'نمایش نظر لغو شد.'
        );

    }





    /**
     * پاسخ پزشک به نظر
     */
    public function reply(
        Request $request,
        PostComment $comment
    ) {


        $request->validate([

            'comment' => 'required|string|min:3'

        ]);



        // دریافت کاربر از گارد اصلی
        $user = Auth::guard('web')->user();

        abort_unless(
            $user,
            401,
            'کاربر وارد نشده است.'
        );

        // دریافت پروفایل پزشک
        $doctor = $user->doctorProfile;

        abort_unless(
            $doctor,
            403,
            'پروفایل پزشک برای این حساب کاربری وجود ندارد.'
        );

        // $name = $doctor->caption . ' ' . $doctor->name . ' ' . $doctor->family;
        PostComment::create([

            'post_id' => $comment->post_id,

            'parent_id' => $comment->id,

            'user_id' => null,

            'name' => $doctor->caption . ' ' . $doctor->name . ' ' . $doctor->family,

            'comment' => $request->comment,

            'is_approved' => true,

            'is_anonymous' => false,

            'approved_at' => now(),

        ]);



        return back()->with(
            'success',
            'پاسخ ثبت شد.'
        );

    }





    /**
     * حذف نظر
     */
    public function destroy(PostComment $comment)
    {

        $comment->delete();


        return back()->with(
            'success',
            'نظر حذف شد.'
        );

    }

}