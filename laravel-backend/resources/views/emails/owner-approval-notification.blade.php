<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 5px; }
        .content { padding: 20px 0; }
        .button { display: inline-block; padding: 12px 24px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
        .section-title { color: #28a745; font-weight: bold; margin-top: 20px; font-size: 18px; }
        .list-item { margin-left: 20px; margin-bottom: 10px; }
        .footer { border-top: 1px solid #ddd; padding-top: 20px; margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>🎉 Application Approved - Welcome to KaPlato!</h2>
        </div>
        
        <div class="content">
            <p>Hello {{ $owner?->name ?? 'Karenderia Owner' }},</p>
            
            <p>Congratulations! Your Karenderia application has been <strong>APPROVED</strong>!</p>
            
            <p>Your business is now ready to go live on the <strong>KaPlato</strong> platform.</p>
            
            <div class="section-title">Business Information</div>
            <ul>
                <li><strong>Business Name:</strong> {{ $karenderia->business_name ?? 'N/A' }}</li>
                <li><strong>Location:</strong> {{ $karenderia->city ?? 'N/A' }}, {{ $karenderia->province ?? 'N/A' }}</li>
                <li><strong>Approval Date:</strong> {{ $karenderia->approved_at ? $karenderia->approved_at->format('F d, Y') : now()->format('F d, Y') }}</li>
            </ul>
            
            <div class="section-title">What's Next?</div>
            <p>Your account is now active and ready to use. You can log in immediately with your credentials:</p>
            
            <center>
                <a href="{{ config('app.url') }}/login" class="button">Log In to Your Dashboard</a>
            </center>
            
            <p>Once logged in, you can:</p>
            <div class="list-item">✅ Add menu items and meals</div>
            <div class="list-item">✅ Set your operating hours and delivery options</div>
            <div class="list-item">✅ Manage orders from customers</div>
            <div class="list-item">✅ View analytics and reports</div>
            
            <div class="section-title">Quick Start Guide</div>
            <ol>
                <li><strong>Log in</strong> with your email and password</li>
                <li><strong>Complete your profile</strong> with high-quality photos and descriptions</li>
                <li><strong>Add your menu</strong> with meals and prices</li>
                <li><strong>Go live</strong> and start accepting orders!</li>
            </ol>
            
            <div class="section-title">Need Help?</div>
            <p>If you have any questions or need assistance getting started, please contact our support team.</p>
        </div>
        
        <div class="footer">
            <p>Thank you for choosing KaPlato to grow your food business! 🚀</p>
            <p>Best regards,<br>
            <strong>KaPlato Admin Team</strong><br>
            {{ config('app.name') }}</p>
        </div>
    </div>
</body>
</html>
