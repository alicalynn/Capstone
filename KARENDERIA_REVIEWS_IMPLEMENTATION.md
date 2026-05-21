# Karenderia Feedback & Ratings System - Implementation Guide

## Backend Implementation Complete ✅

### Database Tables Created
1. **karenderia_reviews** - Stores customer ratings, feedback, and status reports about karenderias
2. **karenderia_reports** - Stores serious issue reports (allergy issues, permanent closure, health violations, etc.)

### Models Created
- `KarenderiaReview` - Model for customer reviews with approval workflow
- `KarenderiaReport` - Model for issue reports with verification system

### API Endpoints

#### Public Endpoints (No Authentication Required)
```
GET /api/karenderia-reviews/{karenderiaId}
- Get all approved reviews for a karenderia
- Returns: stats (average, distribution, status breakdown) + paginated reviews
```

#### Authenticated Endpoints (Bearer Token Required)
```
POST /api/karenderia-reviews/{karenderiaId}
- Create a new review
- Body: {
    rating: 1-5 (required),
    comment: string (max 2000 chars),
    karenderia_status: 'open' | 'closed_temporary' | 'closed_permanent' | 'unknown' (required),
    food_feedback: string (optional),
    food_quality_rating: 1-5 (optional),
    delivery_experience_rating: 1-5 (optional),
    tags: array (optional, max 5 items)
  }
- Returns: Review object with ID (status='pending' - requires admin approval)

POST /api/karenderia-reviews/{karenderiaId}/report
- Report a serious karenderia issue
- Body: {
    report_type: 'permanent_closure' | 'temporary_closure' | 'allergy_issue' | 'food_safety' | 'health_violation' | 'quality_issue' | 'other' (required),
    description: string (required, min 10 chars, max 3000),
    evidence: string (optional),
    attachments: array of URLs (optional, max 3)
  }
- Returns: Report object (status='new' or 'under_review' if similar reports exist)
```

#### Admin Endpoints
```
GET /api/admin/reports
- Get unresolved reports for moderation

GET /api/admin/reports/pending-reviews
- Get pending reviews awaiting approval

PATCH /api/admin/reviews/{reviewId}/moderate
- Approve or reject a review
- Body: {
    action: 'approve' | 'reject',
    moderation_note: string (optional)
  }
```

### Key Features

#### Review System
- ⭐ 1-5 star ratings with text comments
- 📝 Food quality and delivery experience sub-ratings
- 🏷️ Tagging system for quick categorization
- 👥 Reviewer information displayed (name, email)
- ✅ Moderation workflow (pending → approved/rejected)
- 📊 Rating statistics: average, distribution, status breakdown

#### Reporting System
- 🚨 Report serious issues: allergy issues, health violations, permanent closure, etc.
- 📸 Support for evidence and attachments (URLs)
- 🔍 Automatic verification when multiple similar reports exist
- 📈 Track similar reports count from other users
- 🗂️ Admin workflow: new → under_review → acknowledged → resolved/rejected

#### Smart Features
- 🔄 Auto-create permanent closure report when user reviews with 'closed_permanent' status
- 🚩 Auto-escalate to 'under_review' when 2+ similar serious reports exist
- 🔒 Prevent duplicate reviews from same user for same karenderia
- 📱 Full audit trail with timestamps and user tracking

---

## Frontend Implementation Needed

### Service Already Created
`karenderia-review.service.ts` - Provides methods for:
- `getReviews(karenderiaId)` - Fetch reviews and stats
- `createReview(karenderiaId, data)` - Submit a review
- `reportIssue(karenderiaId, data)` - Report an issue

### Components to Create

#### 1. Karenderia Reviews Display Component
**Location**: `src/app/pages/karenderia-detail/reviews-section/`

Shows:
- Average rating with star display (1-5 stars)
- Rating distribution (5★: 45, 4★: 20, 3★: 10, 2★: 5, 1★: 2)
- Karenderia status breakdown (open, closed_temporary, closed_permanent)
- Paginated list of reviews with:
  - Reviewer name
  - Date
  - Rating (stars)
  - Comment text
  - Food quality and delivery ratings (if available)

#### 2. Leave Review Modal/Page
**Location**: `src/app/pages/karenderia-detail/leave-review/`

Form with fields:
- Star rating picker (1-5)
- Karenderia status dropdown (open, closed_temporary, closed_permanent, unknown)
- Optional food quality rating (1-5)
- Optional delivery experience rating (1-5)
- Optional food feedback textarea
- Optional comment textarea (max 2000 chars)
- Optional tags input (max 5 tags)
- Submit button

#### 3. Report Issue Modal
**Location**: `src/app/pages/karenderia-detail/report-issue/`

Form with fields:
- Report type dropdown (permanent_closure, temporary_closure, allergy_issue, food_safety, health_violation, quality_issue, other)
- Description textarea (min 10, max 3000 chars)
- Optional evidence textarea
- Optional file upload for attachments (max 3)
- Submit button

#### 4. Admin Review Management Page
**Location**: `src/app/pages/admin/reviews-moderation/`

Shows:
- List of pending reviews
- Approve/reject buttons for each
- Moderation notes textarea
- List of unresolved reports with:
  - Karenderia name
  - Report type
  - Reporter info
  - Similar reports count
  - Status badge

---

## Integration Points

### On Karenderia Browse Page
- Show average rating next to karenderia name/card
- Display review count as badge
- Use color coding: 5★ green, 4★ blue, 3★ yellow, 2★ orange, 1★ red

### On Karenderia Detail Page
- **Reviews Section** showing all approved reviews with stats
- **"Leave a Review" button** (for authenticated users)
- **"Report Issue" button** (for serious problems)
- Star rating display with hover effect for interactivity

### On User Profile/Dashboard
- Show reviews I've left
- Show reports I've submitted
- List of karenderia statuses

---

## Testing Checklist

- [ ] Create review with all fields
- [ ] Create review with partial fields
- [ ] View reviews and statistics
- [ ] Report permanent closure issue
- [ ] Report allergy issue with attachments
- [ ] Verify duplicate review prevention
- [ ] Admin approve review
- [ ] Admin reject review
- [ ] Multiple reports trigger auto-escalation
- [ ] Average rating calculation
- [ ] Rating distribution calculation

---

## Database Schema Reference

### karenderia_reviews table
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | Primary key |
| karenderia_id | bigint | Foreign key to karenderias |
| reviewer_id | bigint | Foreign key to users |
| reviewer_type | varchar | 'supplier', 'owner', etc |
| rating | int | 1-5 stars |
| comment | text | Review text |
| karenderia_status | enum | Current karenderia status |
| status | enum | 'approved', 'pending', 'rejected' |
| food_quality_rating | int | 1-5 optional |
| delivery_experience_rating | int | 1-5 optional |
| tags | json | Array of tag strings |
| reviewed_at | timestamp | When review was submitted |

### karenderia_reports table
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | Primary key |
| karenderia_id | bigint | Foreign key to karenderias |
| reporter_id | bigint | Foreign key to users |
| report_type | enum | Issue type |
| description | text | Detailed description |
| evidence | text | Supporting evidence |
| attachments | json | Array of file URLs |
| status | enum | 'new', 'under_review', 'resolved', etc |
| verified | boolean | Admin verification |
| similar_reports_count | int | Count of similar reports |
| admin_response | text | Admin's response |

---

## API Response Examples

### Get Reviews Response
```json
{
  "data": {
    "karenderia": {
      "id": 1,
      "name": "Maria's Karenderia"
    },
    "stats": {
      "average": 4.3,
      "total_reviews": 87,
      "distribution": {
        "5": 45,
        "4": 20,
        "3": 12,
        "2": 7,
        "1": 3
      },
      "status_breakdown": {
        "open": 80,
        "closed_temporary": 5,
        "closed_permanent": 2
      }
    },
    "reviews": {
      "current_page": 1,
      "data": [
        {
          "id": 123,
          "rating": 5,
          "comment": "Excellent food!",
          "karenderia_status": "open",
          "food_quality_rating": 5,
          "delivery_experience_rating": 4,
          "reviewer": {
            "id": 10,
            "name": "John Supplier",
            "email": "john@example.com"
          },
          "created_at": "2026-05-20T10:30:00Z"
        }
      ]
    }
  }
}
```

### Create Review Response
```json
{
  "message": "Review submitted successfully. It will be approved by our team.",
  "data": {
    "review": {
      "id": 124,
      "karenderia_id": 1,
      "reviewer_id": 10,
      "rating": 5,
      "comment": "Great experience!",
      "karenderia_status": "open",
      "status": "pending",
      "created_at": "2026-05-20T14:22:00Z"
    }
  }
}
```

---

## Next Steps

1. Create the Angular components for review display and forms
2. Update karenderia-detail page to include reviews section
3. Add "Leave a Review" and "Report Issue" buttons
4. Create admin moderation dashboard
5. Update karenderia browse page with rating badges
6. Add user profile section showing submitted reviews/reports
7. Implement notification system for admins when serious reports are filed
8. Add analytics dashboard for review/report trends
