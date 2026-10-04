<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $isApiRequest = static fn (Request $request): bool =>
            $request->is('api/*') || $request->expectsJson();

        $exceptions->render(function (AuthenticationException $exception, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json(['message' => 'กรุณาเข้าสู่ระบบก่อนดำเนินการ'], 401);
            }
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json(['message' => 'ไม่พบข้อมูลที่ต้องการ'], 404);
            }
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) use ($isApiRequest) {
            if (! $isApiRequest($request)) {
                return;
            }

            $status = $exception->getStatusCode();
            $exceptionMessage = trim($exception->getMessage());
            $message = preg_match('/\p{Thai}/u', $exceptionMessage) === 1
                ? $exceptionMessage
                : match ($status) {
                403 => 'คุณไม่มีสิทธิ์ดำเนินการนี้',
                404 => 'ไม่พบหน้าหรือข้อมูลที่ต้องการ',
                409 => 'ไม่สามารถดำเนินการได้เนื่องจากข้อมูลขัดแย้งกัน',
                default => 'เกิดข้อผิดพลาดในการดำเนินการ',
            };

            return response()->json(['message' => $message], $status, $exception->getHeaders());
        });
    })->create();
