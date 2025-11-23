# Loan & Marketing Integration - Quick Test Guide

## 🚀 Quick Test (5 Minutes)

### Step 1: Login to Marketing System
```
1. Go to: http://localhost/Evergreen/bank-system/evergreen-marketing/login.php
2. Login with your credentials
3. You should see the marketing home page (viewingpage.php)
```

### Step 2: Navigate to Loan System
```
1. Click "Loans" in the navigation menu
2. You should go to: http://localhost/Evergreen/LoanSubsystem/index.php
3. ✅ You should be AUTOMATICALLY logged in (no login prompt)
4. ✅ Your name should appear in the top right corner
```

### Step 3: Test Loan Navigation
```
From the Loan System, test these links:

1. Click "Home" → Should go to marketing viewingpage.php
2. Click "Cards" dropdown → Should show:
   - Credit Cards
   - Debit Cards  
   - Prepaid Cards
   - Card Rewards
3. Click any card option → Should go to marketing card pages
4. Click "About Us" → Should go to marketing about page
5. Click "Loans" → Should stay in/return to loan system
```

### Step 4: Test User Dropdown
```
1. Click your avatar (circle with initials) in loan system
2. Should show dropdown with:
   ✅ Profile
   ✅ Missions
   ✅ Logout
3. Click "Profile" → Should go to Basic-operation account page
4. Go back to loan system
5. Click "Missions" → Should go to points/missions page
```

### Step 5: Test Logout
```
1. Go back to loan system
2. Click your avatar → Click "Logout"
3. ✅ Should redirect to marketing home
4. ✅ Marketing home should redirect to login (session destroyed)
5. ✅ You should see the login page
```

## ✅ Success Criteria

All of these should be TRUE:

- [ ] Can login at marketing system
- [ ] Clicking "Loans" goes to loan dashboard without re-login
- [ ] User name displays correctly in loan header
- [ ] "Home" link goes back to marketing
- [ ] "Cards" dropdown shows all options
- [ ] Card links go to marketing card pages
- [ ] User avatar shows correct initials
- [ ] User dropdown shows Profile, Missions, Logout
- [ ] Logout redirects to marketing home
- [ ] After logout, trying to access loan system redirects to login

## 🐛 Troubleshooting

### Problem: "Not logged in" when clicking Loans
**Fix**: 
1. Make sure you're logged in at marketing first
2. Check browser console for errors
3. Try clearing cookies and login again

### Problem: User name shows "Guest"
**Fix**:
1. Check that login sets session variables
2. Verify `$_SESSION['full_name']` or `$_SESSION['first_name']` is set
3. Check browser console for session errors

### Problem: Navigation links don't work
**Fix**:
1. Check that you're using the correct URLs
2. Verify paths start with `/Evergreen/`
3. Check for typos in file paths

### Problem: Logout doesn't redirect
**Fix**:
1. Clear browser cache
2. Check `logout.php` has correct redirect URL
3. Verify no errors in browser console

## 📊 Expected Behavior

### Login Flow:
```
Marketing Login → Set Session → Access Granted
                      ↓
                Loan System Checks Session
                      ↓
                Auto Login (No Prompt)
```

### Navigation Flow:
```
Marketing ←→ Loan System
    ↓           ↓
  Cards      Dashboard
  Points     Applications
  About      Notifications
```

### Logout Flow:
```
Loan Logout → Destroy Session → Redirect to Marketing Home
                                        ↓
                                  No Session Detected
                                        ↓
                                  Redirect to Login
```

## 🎯 Key Features

✅ **Single Sign-On**: Login once, access both systems  
✅ **Shared Session**: Session maintained across systems  
✅ **Seamless Navigation**: Click between systems freely  
✅ **Unified Logout**: Logout from anywhere works everywhere  
✅ **Consistent UI**: Same navigation structure  

## 📝 Test Results Template

```
Date: ___________
Tester: ___________

[ ] Login successful
[ ] Loan system auto-login works
[ ] User name displays correctly
[ ] Home link works
[ ] Cards dropdown works
[ ] Card links work
[ ] About link works
[ ] User dropdown works
[ ] Profile link works
[ ] Missions link works
[ ] Logout works
[ ] Redirect to login after logout works

Issues Found:
_________________________________
_________________________________
_________________________________

Overall Status: PASS / FAIL
```

---

**Ready to test?** Start with Step 1 and work through each step!
