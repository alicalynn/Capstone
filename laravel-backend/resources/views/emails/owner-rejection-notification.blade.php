@component('mail::message')
# Application Rejection Notice

Hello {{ $owner->name }},

We appreciate your interest in becoming a Karenderia owner on **KaPlato**. However, we are unable to approve your application at this time.

## Rejection Reason

{{ $rejectionReason }}

## Business Information
- **Business Name:** {{ $karenderia->business_name }}
- **Rejection Date:** {{ $karenderia->rejected_at->format('F d, Y') }}

## Next Steps

If you believe this was a mistake or would like to resubmit your application with corrected information, you can re-apply by clicking the button below:

@component('mail::button', ['url' => $reapplyUrl, 'color' => 'primary'])
Re-apply as Karenderia Owner
@endcomponent

You can also visit: `{{ $reapplyUrl }}`

## Common Reasons for Rejection

- **Invalid Permit:** The business permit file is unclear, expired, or incomplete
- **Suspicious Activity:** Information provided appears inconsistent or fraudulent
- **Incomplete Information:** Required information is missing or unclear

If you have any questions or need assistance, please contact our support team.

---

Best regards,

**KaPlato Admin Team**  
{{ config('app.name') }}
@endcomponent
