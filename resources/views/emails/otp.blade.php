<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رمز التحقق - مستودع الأدوية</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap');

        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            color: #334155;
            line-height: 1.6;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }

        .header {
            background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
            padding: 32px;
            text-align: center;
        }

        .header h1 {
            color: white;
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }

        .content {
            padding: 40px;
            text-align: center;
        }

        .welcome-text {
            font-size: 20px;
            color: #1e293b;
            margin-bottom: 24px;
        }

        .otp-box {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 20px;
            margin: 32px auto;
            display: inline-block;
            font-size: 32px;
            font-weight: 700;
            color: #3b82f6;
            letter-spacing: 4px;
            border: 2px solid #e2e8f0;
        }

        .instructions {
            color: #64748b;
            font-size: 16px;
            margin-bottom: 32px;
        }

        .footer {
            background: #f1f5f9;
            padding: 24px;
            text-align: center;
            font-size: 14px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }

        .expiry-notice {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #ef4444;
            font-weight: 500;
            margin-top: 16px;
        }

        .logo {
            height: 40px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>مستودع الأدوية</h1>
        </div>

        <div class="content">
            <div class="welcome-text">مرحبًا بك في نظام مستودع الأدوية</div>

            <p>لإكمال عملية التسجيل، يرجى استخدام رمز التحقق التالي:</p>

            <div class="otp-box">{{ $code }}</div>

            <p class="instructions">الرجاء إدخال هذا الرمز في الصفحة المخصصة للتحقق</p>

            <div class="expiry-notice">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span>هذا الرمز صالح لمدة 10 دقائق فقط</span>
            </div>
        </div>

        <div class="footer">
            <p>إذا لم تطلب هذا الرمز، يرجى تجاهل هذه الرسالة</p>
            <p>© 2023 مستودع الأدوية. جميع الحقوق محفوظة</p>
        </div>
    </div>
</body>
</html>
