<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request; // 🔴 این خط حیاتی اضافه شد
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withCommands()
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'portal.auth' => \App\Http\Middleware\DoctorAuth::class,
        ]);

        $middleware->redirectGuestsTo(fn() => route('portal.login'));
        $middleware->validateCsrfTokens(except: [
            'payment/callback',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // مدیریت خطای حجم فایل
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, Request $request) {
            return back()->withErrors([
                'featured_image' => 'حجم فایل انتخابی بیش از حد مجاز است. لطفاً فایل کوچکتری انتخاب کنید.',
            ]);
        });

        // مدیریت خطای 404
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'منبع مورد نظر یافت نشد'], 404);
            }
            return Inertia::render('Errors/NotFound')->toResponse($request)->setStatusCode(404);
        });

        // مدیریت خطای 403
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'دسترسی غیرمجاز'], 403);
            }
            return Inertia::render('Errors/Forbidden')->toResponse($request)->setStatusCode(403);
        });

    })
    ->create();