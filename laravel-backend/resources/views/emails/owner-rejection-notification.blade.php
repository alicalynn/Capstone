<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 5px; }
        .content { padding: 20px 0; }
        .button { display: inline-block; padding: 12px 24px; background-color: #ffc107; color: #333; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
        .section-title { color: #d32f2f; font-weight: bold; margin-top: 20px; }
        .list-item { margin-left: 20px; margin-bottom: 10px; }
        .footer { border-top: 1px solid #ddd; padding-top: 20px; margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>❌ Application Rejection Notice</h2>
        </div>
        
        <div class="content">
            <p>Hello {{ $owner?->name ?? 'Karenderia Owner' }},</p>
            
            <p>We appreciate your interest in becoming a Karenderia owner on <strong>KaPlato</strong>. However, we are unable to approve your application at this time.</p>
            
            <div class="section-title">Rejection Reason</div>
            <p><strong>{{ $rejectionReason ?? 'No specific reason provided' }}</strong></p>
            
            <div class="section-title">Business Information</div>
            <ul>
                <li><strong>Business Name:</strong> {{ $karenderia->business_name ?? 'N/A' }}</li>
                <li><strong>Rejection Date:</strong> {{ $karenderia->rejected_at ? $karenderia->rejected_at->format('F d, Y') : now()->format('F d, Y') }}</li>
            </ul>
            
            <div class="section-title">What Happens Next?</div>
            <p>You have the option to <strong>reapply</strong> with updated or corrected information. If you believe this was a mistake or would like to submit new/updated documents (such as an updated business permit), please reapply below:</p>
            
            <center>
                <a href="{{ $reapplyUrl ?? '#' }}" class="button">Reapply with Updated Documents</a>
            </center>
            
            <div class="section-title">Why Was My Application Rejected?</div>
            <p>Common reasons for rejection include:</p>
            <div class="list-item">
                <strong>Invalid Permit:</strong> The business permit file is unclear, expired, incomplete, or unreadable
            </div>
            <div class="list-item">
                <strong>Incomplete Information:</strong> Required business information is missing or unclear
            </div>
            <div class="list-item">
                <strong>Suspicious Activity:</strong> Information provided appears inconsistent or fraudulent
            </div>
            <div class="list-item">
                <strong>Compliance Issues:</strong> Business does not meet KaPlato platform guidelines
            </div>
            
            <div class="section-title">How to Successfully Reapply</div>
            <p>When you reapply, please ensure:</p>
            <ol>
                <li>✅ Your business permit is <strong>clear, valid, and not expired</strong></li>
                <li>✅ All information matches your business permit exactly</li>
                <li>✅ The scanned permit is in <strong>PDF, JPG, or PNG format</strong> (max 5MB)</li>
                <li>✅ The image/document is <strong>readable</strong> and <strong>not blurry</strong></li>
            </ol>
            
            <div class="section-title">Need Help?</div>
            <p>If you have questions about the rejection or need assistance, please contact our support team. We're here to help you succeed!</p>
        </div>
        
        <div class="footer">
            <p>Best regards,</p>
            <p><strong>KaPlato Admin Team</strong><br>
            {{ config('app.name') }}</p>
        </div>
    </div>
</body>
</html>
