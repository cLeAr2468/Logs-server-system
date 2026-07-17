# Quick Start Guide - Dashboard Date Range & Recent Transactions

## 🚀 What's New

You can now filter dashboard data by custom date ranges and view all transactions in a dedicated page with search functionality!

---

## 📅 Dashboard Date Range Picker

### How to Use:
1. **Open Dashboard**: Navigate to `/dashboard` after logging in
2. **Select Date Range**: Click the date range picker button at the top
3. **Choose Dates**: 
   - Pick a start date
   - Pick an end date
   - Click "Apply"
4. **View Results**: All dashboard stats and tables update instantly

### Default Behavior:
- Shows current month's data by default (e.g., May 1 - May 31, 2026)

---

## 🔍 Recent Transactions Page

### How to Access:
1. **From Dashboard**: Click the "View All" button on the Recent Transactions card
2. **Direct URL**: Navigate to `/recent-transact`

### Features:
- **Date Range Filter**: Select custom date range at the top
- **Search Bar**: Search by student name, purpose, or address
- **Pagination**: Browse through pages (20 transactions per page)
- **Status Colors**:
  - 🟢 Green = Completed
  - 🟠 Orange = Pending
  - 🔵 Blue = Approved
  - 🔴 Red = Rejected
  - ⚫ Gray = Cancelled

### How to Search:
1. Type your search query in the search bar
2. Press Enter or click the "Search" button
3. Results update instantly

### How to Navigate Pages:
- Click "Previous" or "Next" buttons
- Click page numbers directly
- See total count at bottom (e.g., "Showing 1 to 20 of 95 transactions")

---

## 💻 For Developers

### Run Locally:
```bash
# Frontend
cd c:\Users\User\Desktop\Transact-logs-system\logs-system
npm run dev

# Backend (Already running on Railway)
# No changes needed - API endpoints are live
```

### New API Endpoint:
```
GET /admin/dashboard/all-transactions
Query Params:
  - per_page: Number of items per page (default: 20)
  - page: Current page number (default: 1)
  - start_date: Start date (format: YYYY-MM-DD)
  - end_date: End date (format: YYYY-MM-DD)
  - search: Search query string
```

### Updated API Endpoints:
All dashboard endpoints now support date ranges:
- `/admin/dashboard/statistics?start_date=2026-05-01&end_date=2026-05-31`
- `/admin/dashboard/recent-transactions?start_date=2026-05-01&end_date=2026-05-31`
- `/admin/dashboard/performance?start_date=2026-05-01&end_date=2026-05-31`

---

## 🎨 UI Components

### DateRangePicker Component
**Location:** `src/components/ui/date-range-picker.jsx`

**Usage:**
```jsx
import { DateRangePicker } from '@/components/ui/date-range-picker';

<DateRangePicker
  value={dateRange}
  onChange={setDateRange}
/>
```

**Props:**
- `value`: Object with `start` and `end` dates (ISO format)
- `onChange`: Callback function when date range changes
- `className`: Optional CSS classes

---

## 📝 Code Examples

### Initialize Date Range (Current Month):
```javascript
const now = new Date();
const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);

const [dateRange, setDateRange] = useState({
  start: firstDay.toISOString().split('T')[0],
  end: lastDay.toISOString().split('T')[0],
});
```

### Fetch Data with Date Range:
```javascript
const params = new URLSearchParams();
if (dateRange.start && dateRange.end) {
  params.append('start_date', dateRange.start);
  params.append('end_date', dateRange.end);
}

const response = await fetch(
  `${API_BASE_URL}/admin/dashboard/statistics?${params}`,
  { headers: { 'Authorization': `Bearer ${token}` } }
);
```

---

## 🐛 Troubleshooting

### Dashboard Not Updating?
- **Check:** Browser console for errors
- **Verify:** You're logged in as admin or staff
- **Clear:** Browser cache and reload

### Date Range Picker Not Working?
- **Check:** Start date is before end date
- **Verify:** Both dates are selected before clicking Apply
- **Try:** Click outside to close picker, then reopen

### Recent Transactions Page Empty?
- **Check:** Selected date range has transactions
- **Try:** Widen the date range
- **Verify:** API endpoint is accessible (check Network tab)

### Search Not Working?
- **Check:** Search query is at least 2 characters
- **Try:** Search by full name (first + last)
- **Verify:** Network tab shows API call with search parameter

### Pagination Issues?
- **Check:** Total transaction count
- **Verify:** Per page is set to 20
- **Try:** Click page numbers directly instead of prev/next

---

## ✅ Testing Checklist

Before deploying, test these scenarios:

### Dashboard:
- [ ] Default shows current month
- [ ] Date picker opens and closes
- [ ] Selecting date range updates all stats
- [ ] Recent transactions table updates
- [ ] Performance summary updates
- [ ] "View All" button navigates correctly

### Recent Transactions:
- [ ] Page loads with current month data
- [ ] Date picker works
- [ ] Search by student name works
- [ ] Search by purpose works
- [ ] Search by address works
- [ ] Pagination next/prev works
- [ ] Clicking page numbers works
- [ ] Status badges show correct colors
- [ ] Total count is accurate

### Mobile Responsive:
- [ ] Dashboard looks good on mobile
- [ ] Date picker works on mobile
- [ ] Recent transactions table scrollable on mobile
- [ ] Search bar works on mobile
- [ ] Pagination buttons work on mobile

---

## 🚀 Deployment

### Frontend (Cloudflare Pages):
```bash
cd c:\Users\User\Desktop\Transact-logs-system\logs-system
npm run build
npm run pages:deploy
```

### Backend (Railway):
- ✅ Already deployed
- ✅ New endpoints are live
- ✅ No migrations needed

---

## 📞 Support

If you encounter issues:
1. Check this guide first
2. Review browser console for errors
3. Check Network tab for failed API calls
4. Verify authentication tokens are valid
5. Test with different date ranges

---

## 🎉 You're All Set!

The dashboard and recent transactions features are ready to use. Enjoy filtering data by custom date ranges and searching through transactions effortlessly!
