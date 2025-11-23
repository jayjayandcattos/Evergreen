# Quick Test Guide - Points System

## 🚀 Quick Test (2 Minutes)

### 1. Clear Browser Cache
```
Press: Ctrl + Shift + Delete
Clear: Cached images and files
```

### 2. Login
```
URL: http://localhost/Evergreen/bank-system/evergreen-marketing/login.php
Use your credentials (aaron / xeroha6543@okcdeals.com)
```

### 3. Go to Points Page
```
URL: http://localhost/Evergreen/bank-system/evergreen-marketing/cards/points.php
```

### 4. Open Console (F12)
```
Look for:
✅ "Points API Response: {success: true, total_points: "0.00"}"
✅ "Missions API Response: {success: true, missions: Array(2)}"
✅ "Points page initialization complete"

❌ If you see "Not logged in" - refresh page with Ctrl+F5
```

### 5. Check Missions Tab
```
Should see:
✅ Mission 7: Share referral code (30 points) - COLLECT button
✅ Mission 12: Use referral code (10 points) - COLLECT button
```

### 6. Collect a Mission
```
1. Click "Collect" on Mission 7
2. Watch for success animation ✓
3. Points should change: 0.00 → 30.00
4. Mission disappears from list
```

### 7. Check Other Tabs
```
Point History tab:
✅ Shows: "Share your referral code..." +30.00 points

Completed tab:
✅ Shows: Mission with timestamp
```

---

## 🐛 If Not Working

### Console shows "Not logged in"?
```
1. Hard refresh: Ctrl + F5
2. Clear cache completely
3. Logout and login again
4. Check test_points_debug.php
```

### Missions not showing?
```
1. Check console for errors
2. Verify session: test_points_debug.php
3. Check database: total_points column exists
```

### Points not updating?
```
1. Check console for API response
2. Verify database: SELECT * FROM bank_customers WHERE customer_id = 7
3. Check if total_points is NULL (should be 0.00)
```

---

## ✅ Success Checklist

- [ ] Console shows "success: true" for all API calls
- [ ] Mission 7 and 12 are visible
- [ ] "Collect" buttons are clickable
- [ ] Clicking collect shows success animation
- [ ] Points update from 0.00 to 30.00
- [ ] Mission disappears after collection
- [ ] Point History shows transaction
- [ ] Completed tab shows mission

---

## 📊 Debug Page

Visit: `http://localhost/Evergreen/bank-system/evergreen-marketing/test_points_debug.php`

Should show:
```
Session Variables:
✅ user_id: 7
✅ customer_id: 7
✅ email: xeroha6543@okcdeals.com

Testing API Calls:
✅ Get User Points: {"success":true,"total_points":"0.00"}
✅ Get Missions: {"success":true,"missions":[...]}

Database Check:
✅ customer_id: 7
✅ total_points: 0.00
```

---

## 🎯 What Changed

**The Fix:**
Added `credentials: 'same-origin'` to all fetch() calls in `points_system.js`

**Why:**
Fetch doesn't send cookies by default → API thought user wasn't logged in

**Result:**
Session cookies now sent → API recognizes user → Missions load → Points work!

---

**Need Help?** Check console (F12) for error messages and compare with expected output above.
