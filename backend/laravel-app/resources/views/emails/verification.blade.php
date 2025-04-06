<!DOCTYPE html>
<html>
<head>
    <title>Email Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #f9f9f9;
            border-radius: 5px;
            padding: 20px;
            border: 1px solid #ddd;
        }
        .verification-code {
            background-color: #eee;
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 5px;
            text-align: center;
            padding: 10px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #777;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Verify Your Email Address</h2>
        <p>Hello {{ $user->username }},</p>
        <p>Thank you for registering with URSekai. Please use the verification code below to verify your email address:</p>
        
        <div class="verification-code">{{ $verificationCode }}</div>
        
        <p>This code will expire after 60 minutes.</p>
        
        <p>If you did not create an account with URSekai, no further action is required.</p>
        
        <p>Regards,<br>The URSekai Team</p>
    </div>
    
    <div class="footer">
        <p>This email was sent to {{ $user->email }}. If you have any questions, please contact support.</p>
    </div>
</body>
</html>
