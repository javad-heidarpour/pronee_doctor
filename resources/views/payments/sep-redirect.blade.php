
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>انتقال به درگاه پرداخت</title>
</head>
<body>
    <p>در حال انتقال به درگاه پرداخت سامان...</p>

<form
    id="sep-payment-form"
    method="POST"
    action="https://sep.shaparak.ir/OnlinePG/OnlinePG"
>
    <input type="hidden" name="Token" value="{{ $token }}">
</form>
    <script>
        document.getElementById('sep-payment-form').submit();
    </script>
</body>
</html>
