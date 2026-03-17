<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Mobile Login OTP</title>
</head>
<body style="font-family: Arial, sans-serif; color: #222;">
    <h2>Mobile Login OTP</h2>
    <p>Your OTP for {{ ucfirst($role) }} mobile login is:</p>
    <p style="font-size: 28px; font-weight: bold; letter-spacing: 4px;">{{ $otp }}</p>
    <p>This OTP will expire soon. Do not share it with anyone.</p>
</body>
</html>
