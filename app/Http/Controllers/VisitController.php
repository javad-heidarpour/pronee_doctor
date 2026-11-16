<?php

namespace App\Http\Controllers;

use App\Actions\Visit\ShowVisitAction;
use Inertia\Inertia;
use Inertia\Response;

class VisitController extends Controller
{
    public function show(int $visit_number, ShowVisitAction $action): Response
    {
        return Inertia::render('Visit/Show', [
            'visit' => $action->handle($visit_number),
        ]);
    }
}
