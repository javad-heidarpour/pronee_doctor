<?php

namespace App\Actions\Blog\Listing;

use App\Models\Doctor;
use App\Models\Post;
use Inertia\Inertia;
use Inertia\Response;


class DoctorBlogIndexAction
{

    public function handle(Doctor $doctor): Response
    {


        $posts = Post::published()
            ->where('doctor_id',$doctor->id)
            ->with([
                'category'
            ])
            ->latest()
            ->paginate(12);



        return Inertia::render(
            'Blog/DoctorIndex',
            [
                'doctor'=>$doctor->load('specialty'),

                'posts'=>$posts,
            ]
        );

    }

}