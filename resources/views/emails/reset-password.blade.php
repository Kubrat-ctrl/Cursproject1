<!doctype html>
<html lang="ru">
<head><meta charset="utf-8"><title>Сброс пароля</title></head>
<body style="margin:0;padding:24px;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    <tr><td align="center">
        <table role="presentation" width="520" cellpadding="0" cellspacing="0"
               style="max-width:520px;background:#ffffff;border-radius:8px;padding:28px;">
            <tr><td>
                <h2 style="margin:0 0 16px;color:#0f766e;">{{ $appName }}</h2>
                <p style="margin:0 0 12px;">Здравствуйте, {{ $name }}!</p>
                <p style="margin:0 0 20px;line-height:1.5;">
                    Вы получили это письмо, потому что для вашей учётной записи запрошен сброс пароля.
                </p>
                <p style="margin:0 0 24px;">
                    <a href="{{ $url }}"
                       style="display:inline-block;background:#0f766e;color:#ffffff;text-decoration:none;
                              padding:12px 22px;border-radius:6px;font-weight:bold;">Задать новый пароль</a>
                </p>
                <p style="margin:0 0 12px;line-height:1.5;">Ссылка действует {{ $minutes }} минут.</p>
                <p style="margin:0 0 12px;line-height:1.5;">
                    Если вы не запрашивали сброс пароля, просто проигнорируйте это письмо: пароль останется прежним.
                </p>
                <p style="margin:20px 0 0;font-size:12px;color:#6b7280;line-height:1.5;">
                    Если кнопка не работает, скопируйте ссылку в адресную строку браузера:<br>
                    <span style="word-break:break-all;">{{ $url }}</span>
                </p>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
