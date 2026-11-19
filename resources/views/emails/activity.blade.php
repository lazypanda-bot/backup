<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $subjectLine }}</title>
    <style>
        @media only screen and (max-width: 480px) {
            .email-content { padding: 28px 24px !important; }
            .email-heading { font-size: 27px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f2f5f9; color: #172b42; font-family: Tahoma, Verdana, sans-serif; -webkit-text-size-adjust: 100%;">
    <div style="display: none; max-height: 0; overflow: hidden; opacity: 0; mso-hide: all;">{{ $subjectLine }} — LJS Realty update.</div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width: 100%; background-color: #f2f5f9;">
        <tr>
            <td align="center" style="padding: 40px 16px;">
                <!--[if mso]><table role="presentation" width="560" cellspacing="0" cellpadding="0" border="0"><tr><td><![endif]-->
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width: 100%; max-width: 560px; border: 1px solid #dce4ef; border-radius: 16px; background-color: #ffffff; overflow: hidden;">
                    <tr>
                        <td class="email-content" style="padding: 30px 36px 24px; border-bottom: 1px solid #e8edf4;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td width="74" style="width: 74px; padding-right: 16px; vertical-align: middle;">
                                        <img src="{{ $logoSource ?? $message->embed(public_path('images/ljs-logo.jpg')) }}" width="64" height="64" alt="LJS Realty logo" style="display: block; width: 64px; height: 64px; border: 0;">
                                    </td>
                                    <td style="vertical-align: middle;">
                                        <p style="margin: 0 0 5px; color: #172b42; font-size: 16px; font-weight: 700; line-height: 22px;">LJS Realty &amp; Brokerage</p>
                                        <p style="margin: 0; color: #6a7c90; font-size: 11px; line-height: 16px; letter-spacing: 1.5px; text-transform: uppercase;">Account update</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-content" style="padding: 34px 36px 36px;">
                            <h1 class="email-heading" style="margin: 0 0 20px; color: #172b42; font-family: Georgia, 'Times New Roman', serif; font-size: 31px; font-weight: 400; line-height: 1.25;">{{ $subjectLine }}</h1>
                            <div style="margin: 0; color: #526479; font-size: 14px; line-height: 1.75;">
                                {!! nl2br(e($messageBody)) !!}
                            </div>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width: 100%; margin-top: 28px; border-top: 1px solid #e8edf4;">
                                <tr>
                                    <td style="padding-top: 20px;">
                                        <p style="margin: 0; color: #6a7c90; font-size: 12px; line-height: 20px;">This is an automated update from LJS Realty and Brokerage.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <!--[if mso]></td></tr></table><![endif]-->
            </td>
        </tr>
    </table>
</body>
</html>