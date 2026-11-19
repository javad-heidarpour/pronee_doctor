<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>صورتحساب همکاری تخصصی #{{ $due->id }}</title>
<style>
body{font-family:Tahoma,Arial,sans-serif;background:#f3f4f6;color:#111827;margin:0;padding:24px}.sheet{max-width:780px;margin:auto;background:#fff;padding:36px;border:1px solid #e5e7eb;border-radius:16px}.muted{color:#6b7280}.row{display:flex;justify-content:space-between;gap:20px;padding:14px 0;border-bottom:1px solid #e5e7eb}.amount{font-size:26px;font-weight:bold;color:#0f766e}.status{display:inline-block;background:#fef3c7;color:#92400e;padding:7px 12px;border-radius:20px}.status.done{background:#dcfce7;color:#166534}button{border:0;border-radius:8px;padding:12px 20px;background:#0f766e;color:#fff;font-weight:bold;cursor:pointer;margin-bottom:18px}
@media print{body{background:#fff;padding:0}.sheet{border:0;border-radius:0}.no-print{display:none}}
</style>
</head>
<body>
<div class="sheet">
<button class="no-print" onclick="window.print()">چاپ / ذخیره PDF</button>
<p class="muted">PRONEEX · صورت‌حساب داخلی همکاری حرفه‌ای</p>
<h1>صورتحساب دوره‌ای شماره {{ $due->id }}</h1>
<p class="muted">این سند، صورتحساب داخلی توافق طرفین است و فاکتور رسمی مالیاتی محسوب نمی‌شود.</p>
<div class="row"><span>کلینیک / شعبه</span><strong>{{ $due->partnership?->clinic?->name ?? '—' }}</strong></div>
<div class="row"><span>متخصص / همکار</span><strong>{{ $due->partnership?->provider?->name ?? '—' }}</strong></div>
<div class="row"><span>موضوع همکاری</span><strong>{{ $due->partnership?->service_name ?? '—' }}</strong></div>
<div class="row"><span>بازه صورتحساب</span><strong>{{ $due->period_start?->format('Y/m/d') }} تا {{ $due->period_end?->format('Y/m/d') }}</strong></div>
<div class="row"><span>سررسید</span><strong>{{ $due->due_date?->format('Y/m/d') }}</strong></div>
<div class="row"><span>جهت پرداخت</span><strong>{{ $due->payment_direction === 'clinic_to_professional' ? 'کلینیک به متخصص' : 'متخصص به کلینیک' }}</strong></div>
<div class="row"><span>مبلغ</span><strong class="amount">{{ number_format($due->amount_rial) }} ریال</strong></div>
<div class="row"><span>وضعیت</span><strong class="status {{ $due->status === 'confirmed' ? 'done' : '' }}">{{ $due->status === 'confirmed' ? 'تأیید دوطرفه' : ($due->status === 'partially_confirmed' ? 'تأیید یک طرف؛ در انتظار طرف دیگر' : 'در انتظار تأیید') }}</strong></div>
<div class="row"><span>شناسه مرجع پرداخت</span><strong>{{ $due->payer_reference ?: $due->receiver_reference ?: 'ثبت نشده' }}</strong></div>
<p class="muted" style="line-height:2;margin-top:28px">تأیید این صورتحساب در پرونیکس به معنی ثبت توافق و اعلام طرفین است؛ به‌تنهایی اثبات انتقال وجه بانکی یا تأییدیه مالیاتی نیست.</p>
</div>
</body></html>