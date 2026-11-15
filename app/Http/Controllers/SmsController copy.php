<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;

use Ghasedaksms\GhasedaksmsLaravel\GhasedaksmsFacade;
use Ghasedak\DataTransferObjects\Request\SingleMessageDTO;
use Ghasedak\Exceptions\GhasedakSMSException;
use Illuminate\Support\Facades\Http;

class SmsController extends Controller
{
    public function index(int $visit_number)
    {
      
        $response = Http::withOptions([
            // فقط برای تست موقت؛ در محیط عملیاتی true باشد
            'verify' => false,
        ])->withHeaders([
                    'ApiKey' => "75a1d66cd0ffb4ad4149b69993084fdb1bf7471051288c07d9d63fc2a8086a315TNKMFasgdbRFKnB",
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->post(
                'https://gateway.ghasedak.me/rest/api/v1/WebService/SendSingleSMS',
                [
                    'receptor' => '09153859085',
                    'message' => 'برای پرداخت ویزیت دکتر فرهاد وفایی وارد لینک زیر شوید '
                        . 'https://doctorvafaei.com/visit/ '
                        . $visit_number
                        . ' لغو11',
                    'lineNumber' => '10008566',
                    'sendDate' => null,
                    'clientReferenceId' => 'test-001',
                ]
            );

        dd($response->status(), $response->json());
        return $response;
        //     try {
        //         $response = GhasedaksmsFacade::sendSingle(
        //             new SingleMessageDTO(
        //                 sendDate: Carbon::now(),
        //                 lineNumber: '30005006007',
        //                 receptor: '0912xxxxxxxx',
        //                 message: 'تست'
        //             )
        //         );

        //         return response()->json([
        //             'success' => true,
        //             'message' => 'ارسال موفق',
        //             'id' => $response->getId(),
        //         ]);

        //     } catch (GhasedakSMSException $e) {
        //         Log::error('Ghasedak Error: ' . $e->getMessage());

        //         return response()->json([
        //             'success' => false,
        //             'message' => 'خطا در ارسال پیامک',
        //         ], 500);

        //     } catch (Exception $e) {
        //         Log::error('SMS System Error: ' . $e->getMessage());

        //         return response()->json([
        //             'success' => false,
        //             'message' => 'خطای سیستمی رخ داد',
        //         ], 500);
        //     }
    }
}