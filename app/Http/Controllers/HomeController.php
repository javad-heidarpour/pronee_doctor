<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Actions\Home\HomeIndexAction;
use App\Actions\Home\ShowDoctorProfileAction;
use App\Actions\Home\BookAppointmentAction;

class HomeController extends Controller
{
    /**
     * صفحه اصلی عمومی
     */
    public function index(Request $request, HomeIndexAction $action)
    {
        return $action->handle($request);
    }

    /**
     * نمایش قالب قبلی پزشک
     */
    public function showDoctor(
        Request $request,
        ShowDoctorProfileAction $action
    ) {
        return $action->handle('dr-farhad-vafaei');
    }

    /**
     * نمایش قالب Home1 پزشک
     */
    public function Home1(
        Request $request,
        ShowDoctorProfileAction $action
    ) {
        return $action->handleHome1('dr-farhad-vafaei');
    }

    /**
     * ثبت نوبت
     */
    public function bookAppointment(
        Request $request,
        BookAppointmentAction $action
    ) {
        return $action->handle($request);
    }
}
