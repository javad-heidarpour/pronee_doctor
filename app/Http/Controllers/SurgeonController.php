<?php

namespace App\Http\Controllers;

use App\Actions\Surgeon\BuildSurgeonPageDataAction;
use Inertia\Inertia;
use Inertia\Response;

class SurgeonController extends Controller
{
    public function index(BuildSurgeonPageDataAction $action): Response
    {
        return Inertia::render(
            'SurgeonPage',
            $action->handle()
        );
    }
}
