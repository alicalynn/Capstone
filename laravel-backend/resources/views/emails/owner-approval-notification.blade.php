@component('mail::message')
# 🎉 Application Approved - Welcome to KaPlato!

Hello {{ $owner->name }},

Congratulations! Your Karenderia application has been **APPROVED**! 

Your business is now ready to go live on the **KaPlato** platform.

## Business Information
- **Business Name:** {{ $karenderia->business_name }}
- **Location:** {{ $karenderia->city }}, {{ $karenderia->province }}
- **Approval Date:** {{ $karenderia->approved_at->format('F d, Y') }}

## What's Next?

Your account is now active and ready to use. You can log in immediately with your credentials:

@component('mail::button', ['url' => config('app.url') . '/login', 'color' => 'success'])
Log In to Your Dashboard
@endcomponent

Once logged in, you can:
- ✅ Add menu items and meals
- ✅ Set your operating hours and delivery options
- ✅ Manage orders from customers
- ✅ View analytics and reports

## Quick Start Guide

1. **Log in** with your email and password
2. **Complete your profile** with high-quality photos and descriptions
3. **Add your menu** with meals and prices
4. **Go live** and start accepting orders!

## Need Help?

If you have any questions or need assistance getting started, please contact our support team.

---

Best regards,

**KaPlato Admin Team**  
{{ config('app.name') }}

*Thank you for choosing KaPlato to grow your food business!*
@endcomponent
