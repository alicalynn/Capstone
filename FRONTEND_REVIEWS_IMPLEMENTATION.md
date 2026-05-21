# Karenderia Reviews & Ratings System - Frontend Implementation Guide

## Overview
The karenderia feedback and ratings system is now fully implemented on the frontend. Users can view reviews, leave reviews, and report serious issues with karenderias directly from the app.

---

## Frontend Components Created

### 1. **KarenderiaReviewsComponent** (Main Reviews Display)
**Location:** `src/app/pages/karenderia-reviews/karenderia-reviews.component.ts`

**Purpose:** Displays karenderia reviews, ratings, and statistics in an organized, user-friendly card interface.

**Features:**
- **Rating Summary Dashboard:**
  - Average rating display with 5-star visualization
  - Rating distribution bar chart (5★ down to 1★)
  - Total review count
  - Karenderia status breakdown (Open, Temporarily Closed, Permanently Closed)

- **Review List:**
  - Paginated review display
  - Reviewer name, date, and rating stars
  - Review comments and karenderia status
  - Optional food quality and delivery ratings
  - Review tags (up to 5 per review)
  - Food feedback details

- **User Actions:**
  - "Leave a Review" button (opens LeaveReviewModalComponent)
  - "Report Issue" button (opens ReportIssueModalComponent)
  - Pagination controls for browsing reviews

**Key Methods:**
- `loadReviews(page)`: Fetches reviews from backend API
- `getAverageRating()`: Formats average rating to 1 decimal
- `getStarArray(count)`: Creates array for star display
- `getStarPercentage(rating)`: Calculates distribution percentages
- `openLeaveReviewModal()`: Opens review submission form
- `openReportIssueModal()`: Opens issue reporting form

**Authentication:** Checks localStorage for `auth_token` before allowing review/report actions

**Styling:** Premium card design with gradient headers, star ratings, distribution charts, status badges

---

### 2. **LeaveReviewModalComponent** (Review Submission Form)
**Location:** `src/app/pages/karenderia-reviews/leave-review-modal/leave-review-modal.component.ts`

**Purpose:** Modal form for customers to submit reviews about their karenderia experience.

**Form Fields:**
1. **Overall Rating** (Required)
   - 5-star selector with dynamic labels (Poor → Excellent)

2. **Karenderia Status** (Required)
   - Dropdown: Open, Temporarily Closed, Permanently Closed, Unknown

3. **Detailed Experience** (Optional)
   - Food Quality Rating (1-5 stars)
   - Delivery Experience Rating (1-5 stars)

4. **Your Feedback** (Optional)
   - Review Comment (max 2000 characters)
   - Food Feedback (max 1000 characters)

5. **Tags** (Optional)
   - Up to 5 tags from predefined suggestions
   - Tags: "Good food quality", "Poor service", "Great variety", "Hygiene concerns", "Quick delivery", "Slow service", "Fair pricing", "Expensive", "Friendly staff", "Rude staff", "Clean place", "Dirty place"

**Features:**
- Interactive star pickers for all rating fields
- Real-time character count for textareas
- Tag selection UI with visual feedback
- Form validation (rating required, comment optional)
- Loading state during submission
- Success/error toast notifications
- Moderation disclaimer ("Your review will be moderated...")

**API Integration:**
```typescript
this.reviewService.createReview(this.karenderiaId, {
  rating: number,
  karenderia_status: string,
  comment?: string,
  food_feedback?: string,
  food_quality_rating?: number,
  delivery_experience_rating?: number,
  tags?: string[]
})
```

**Response:** Returns success message, marks review as pending approval

---

### 3. **ReportIssueModalComponent** (Issue Reporting Form)
**Location:** `src/app/pages/karenderia-reviews/report-issue-modal/report-issue-modal.component.ts`

**Purpose:** Modal form for reporting serious karenderia issues (food safety, allergies, violations).

**Report Types:**
- **Permanent Closure** - Karenderia permanently closed
- **Temporary Closure** - Temporarily not operating
- **Allergy/Dietary Mishap** ⚠️ (Critical)
- **Food Safety Issue** ⚠️ (Critical)
- **Health/Sanitation Violation** ⚠️ (Critical)
- **Quality Issue** - Product quality problems
- **Other** - Miscellaneous issues

**Form Fields:**
1. **Issue Type** (Required)
   - Dropdown with icons
   - Critical issues highlighted in red

2. **Issue Details** (Required)
   - Main Description (minimum 10 characters)
   - Evidence/Additional Details (optional, max 2000 chars)
   - Real-time character counters

3. **File Attachments** (Optional)
   - Upload up to 3 files (images or PDFs)
   - File browser with drag-and-drop support
   - Remove individual files
   - Display file names and sizes

**Features:**
- Critical issue warning banner at top
- Issue type badge display
- File attachment management
- Information sections about confidentiality and escalation
- Loading state during submission
- Success/error notifications

**API Integration:**
```typescript
this.reviewService.reportIssue(this.karenderiaId, FormData {
  report_type: string,
  description: string,
  evidence?: string,
  attachments?: File[]
})
```

**Backend Handling:**
- Critical issues (allergy_issue, food_safety, health_violation) trigger immediate escalation
- Auto-escalates to 'under_review' if 2+ similar reports exist
- Attachments stored as file references

---

### 4. **KarenderiaReviewsModule** (Feature Module)
**Location:** `src/app/pages/karenderia-reviews/karenderia-reviews.module.ts`

**Purpose:** Encapsulates all review-related components and services.

**Declarations:**
- KarenderiaReviewsComponent
- LeaveReviewModalComponent
- ReportIssueModalComponent

**Imports:**
- CommonModule (Angular utilities)
- IonicModule (Ionic UI components)
- ReactiveFormsModule (Form building)
- FormsModule (Template forms)

**Exports:** All three components for use in other modules

---

## Service Layer

### **KarenderiaReviewService**
**Location:** `src/app/services/karenderia-review.service.ts`

**API Endpoints:**

1. **Get Reviews (Public)**
```typescript
GET /api/karenderia-reviews/{karenderiaId}
Response: {
  data: {
    stats: RatingStats,
    reviews: { data: KarenderiaReview[], last_page: number }
  }
}
```

2. **Create Review (Authenticated)**
```typescript
POST /api/karenderia-reviews/{karenderiaId}
Headers: Authorization: Bearer {token}
Body: { rating, karenderia_status, comment?, food_feedback?, tags? }
Response: { message, review }
```

3. **Report Issue (Authenticated)**
```typescript
POST /api/karenderia-reviews/{karenderiaId}/report
Headers: Authorization: Bearer {token}
Body: FormData { report_type, description, evidence?, attachments[] }
Response: { message, report }
```

**Key Features:**
- Automatic bearer token extraction from localStorage
- FormData support for file uploads
- HTTP header management (multipart for FormData, JSON for regular data)
- Observable-based API calls for reactive programming
- Type-safe interfaces for responses

**Interfaces:**
```typescript
interface KarenderiaReview {
  id, karenderia_id, reviewer_id, rating, comment, karenderia_status,
  status, food_feedback, food_quality_rating, delivery_experience_rating,
  tags, reviewed_at, reviewer { id, name, email }, created_at, updated_at
}

interface RatingStats {
  average: number,
  total_reviews: number,
  distribution: { [1-5]: count },
  status_breakdown: { open, closed_temporary, closed_permanent }
}

interface KarenderiaReport {
  id, karenderia_id, reporter_id, report_type, description,
  evidence, attachments, status, verified, similar_reports_count
}
```

---

## Integration Points

### **1. Karenderia Detail Page Integration**
**File:** `src/app/karenderia-detail/karenderia-detail.page.html`

**Added:**
```html
<!-- Karenderia Reviews Section -->
<div *ngIf="!isLoading && karenderia" class="reviews-section">
  <app-karenderia-reviews 
    [karenderiaId]="karenderiaId" 
    [karenderiaName]="karenderia.name">
  </app-karenderia-reviews>
</div>
```

**Location in Page:** Below menu items, above cart FAB

**Module Updates:** `karenderia-detail.page.module.ts`
- Added import: `KarenderiaReviewsModule`
- Added to imports array

**Styling:** `karenderia-detail.page.scss`
- Added `.reviews-section` with appropriate spacing and styling

---

## User Workflows

### **Workflow 1: View Reviews**
1. User navigates to karenderia detail page
2. Reviews section loads at bottom showing:
   - Average rating with stars
   - Distribution of ratings
   - Status breakdown
   - Paginated list of approved reviews
3. User can scroll through reviews and read comments
4. User clicks "Leave a Review" or "Report Issue" buttons

### **Workflow 2: Leave a Review**
1. User clicks "Leave a Review" button
2. Modal opens with review form
3. User fills form:
   - Selects 1-5 star rating (required)
   - Selects karenderia status
   - (Optional) Adds food quality and delivery ratings
   - (Optional) Writes review comment and food feedback
   - (Optional) Selects up to 5 tags
4. User submits form
5. Review appears in pending status
6. Admin approves review via admin panel
7. Review appears publicly with "approved" status

### **Workflow 3: Report an Issue**
1. User clicks "Report Issue" button
2. Modal opens with report form
3. User selects issue type:
   - Critical types (allergy, food safety, health) trigger special handling
   - Other types for closure or quality issues
4. User provides detailed description (min 10 chars)
5. (Optional) Adds evidence details
6. (Optional) Attaches up to 3 files (photos/documents)
7. User submits report
8. Backend:
   - Stores report with reporter info
   - If critical: Escalates to admin immediately
   - If duplicate: Auto-marks as "under_review" if 2+ similar reports
   - Creates permanent closure marker if review mentions closed status

---

## Authentication & Permissions

**Public Actions:**
- ✅ View reviews and ratings
- ✅ View statistics and breakdowns

**Authenticated Actions (require `auth_token` in localStorage):**
- ✅ Leave a review
- ✅ Report an issue
- ⚠️ Cannot edit/delete own reviews (handled by backend)

**Token Management:**
- Stored in `localStorage` under key `auth_token`
- Automatically included in Authorization headers: `Bearer {token}`
- Cleared on logout

---

## Styling & Theming

### **Color Scheme:**
- **Primary:** Purple/Blue gradient (default Ionic primary)
- **Success:** Green (allergen-safe chips)
- **Warning:** Orange/Yellow (temporary closure, caution tags)
- **Danger:** Red (permanent closure, critical issues)
- **Secondary:** Teal/Cyan (tags, accents)

### **Component Styling:**
- **KarenderiaReviewsComponent:** Gradient card headers, star ratings, distribution charts
- **LeaveReviewModalComponent:** Form with clear sections, interactive star pickers, tag selector
- **ReportIssueModalComponent:** Critical warning banner, form groups with icons, file list

### **Responsive Design:**
- Mobile-first approach (< 500px)
- Tablet optimization (500px - 768px)
- Desktop support (> 768px)
- Full-width on small screens, constrained width (520px max) on larger screens

---

## Testing the Implementation

### **1. Verify Components Load**
```typescript
// In browser console:
// Navigate to a karenderia detail page
// Should see reviews section at bottom
// No console errors
```

### **2. Test Leave Review Flow**
1. Click "Leave a Review" button
2. Should open modal with form
3. Select rating (must have value)
4. Fill optional fields
5. Click Submit
6. Success toast: "Thank you! Your review is pending approval."
7. Modal closes

### **3. Test Report Issue Flow**
1. Click "Report Issue" button
2. Modal opens
3. Select "Allergy/Dietary Mishap" (critical type)
4. Enter description (min 10 chars)
5. Attach a file
6. Click Submit
7. Success toast: "Thank you for reporting. Our team will review it."

### **4. Test Authentication**
1. Logout user
2. Try to click "Leave a Review"
3. Should see warning toast: "Please log in to leave a review"

### **5. Verify API Calls**
1. Open browser Network tab
2. Submit a review
3. Should see POST to `/api/karenderia-reviews/{id}`
4. Response status: 200 or 201
5. Response body contains message and review data

---

## Known Limitations & Future Enhancements

### **Current Limitations:**
1. **Admin Moderation Dashboard:** Not yet implemented (backend ready)
   - Task: Create admin page to approve/reject pending reviews
   - Location: `src/app/pages/admin/reviews-moderation/`

2. **Edit/Delete Reviews:** Not implemented
   - Users can't modify reviews after submission
   - Can only leave new reviews

3. **Filter Reviews:** Basic pagination only
   - Could add filters by rating, status, date
   - Could add sort options (newest, highest-rated, most helpful)

4. **Review Helpfulness:** Not yet implemented
   - Could add "Was this review helpful?" voting

### **Planned Enhancements:**
1. Add review helpfulness votes
2. Add review reply functionality (karenderia owners respond)
3. Add review photos support
4. Add advanced filtering and sorting
5. Add review analytics dashboard for karenderia owners
6. Add review moderation admin interface
7. Add automatic notification to karenderia when issues reported

---

## File Structure Summary

```
src/app/
├── services/
│   └── karenderia-review.service.ts          ✅ CREATED
│
├── pages/
│   ├── karenderia-reviews/                  ✅ NEW COMPONENT
│   │   ├── karenderia-reviews.component.ts
│   │   ├── karenderia-reviews.component.html
│   │   ├── karenderia-reviews.component.scss
│   │   ├── karenderia-reviews.module.ts
│   │   ├── leave-review-modal/
│   │   │   ├── leave-review-modal.component.ts
│   │   │   ├── leave-review-modal.component.html
│   │   │   └── leave-review-modal.component.scss
│   │   └── report-issue-modal/
│   │       ├── report-issue-modal.component.ts
│   │       ├── report-issue-modal.component.html
│   │       └── report-issue-modal.component.scss
│   │
│   └── karenderia-detail/                   ✅ UPDATED
│       ├── karenderia-detail.page.ts
│       ├── karenderia-detail.page.html      (added reviews section)
│       ├── karenderia-detail.page.scss      (added reviews styling)
│       └── karenderia-detail.page.module.ts (added KarenderiaReviewsModule)
```

---

## Deployment Checklist

- ✅ All TypeScript compiles without errors
- ✅ All components properly imported in modules
- ✅ Service properly configured with API endpoints
- ✅ Authentication checks in place
- ✅ Form validation working
- ✅ Toast notifications configured
- ✅ Loading states implemented
- ✅ Error handling in place
- ⏳ Testing across devices (TODO)
- ⏳ Performance optimization (TODO)
- ⏳ Accessibility audit (TODO)

---

## Support & Debugging

### **Common Issues:**

**Issue:** Modal won't open
- **Check:** User is authenticated (has auth_token)
- **Check:** Modal components are declared in module
- **Fix:** Verify KarenderiaReviewsModule is imported

**Issue:** Reviews not loading
- **Check:** API endpoint is correct
- **Check:** Backend is running
- **Check:** Karenderia ID is passed correctly
- **Network:** Check browser Network tab for API response

**Issue:** File upload fails
- **Check:** File size is under 5MB
- **Check:** File type is allowed (images, PDF)
- **Check:** Max 3 files selected

**Issue:** Form submission fails
- **Check:** All required fields filled
- **Check:** Review rating is selected (not 0)
- **Check:** User has internet connection
- **Check:** Auth token is still valid

### **Debug Commands:**
```typescript
// Check if user is authenticated
console.log(localStorage.getItem('auth_token'));

// Check karenderia ID passed to component
console.log(this.karenderiaId);

// Check if reviews loaded
console.log(this.reviews);
```

---

## API Response Examples

### **Success: Get Reviews**
```json
{
  "data": {
    "stats": {
      "average": 4.3,
      "total_reviews": 87,
      "distribution": {
        "1": 5,
        "2": 8,
        "3": 15,
        "4": 32,
        "5": 27
      },
      "status_breakdown": {
        "open": 78,
        "closed_temporary": 5,
        "closed_permanent": 4
      }
    },
    "reviews": {
      "data": [
        {
          "id": 1,
          "rating": 5,
          "comment": "Excellent food!",
          "karenderia_status": "open",
          "reviewer": {
            "name": "Maria Santos"
          },
          "created_at": "2025-01-15T10:30:00Z"
        }
      ],
      "last_page": 5
    }
  }
}
```

### **Success: Create Review**
```json
{
  "message": "Review created successfully",
  "review": {
    "id": 123,
    "rating": 4,
    "status": "pending"
  }
}
```

### **Success: Report Issue**
```json
{
  "message": "Report submitted successfully",
  "report": {
    "id": 456,
    "report_type": "food_safety",
    "status": "under_review"
  }
}
```

---

**Implementation Complete!** ✅

The karenderia reviews and ratings system is now fully functional on both backend and frontend. All components compile without errors and are ready for testing.
