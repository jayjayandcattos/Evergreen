# Quick Guide: Edit Employee Salary

## 🎯 Where to Find It

### Step 1: Go to HRIS Employees Page
```
http://localhost/evergreen/hris-sia/pages/employees.php
```

### Step 2: Click Edit Button
- Find the employee in the table
- Click the blue **Edit** button in the Actions column

### Step 3: Scroll to Salary Information Section
- The salary section appears at the bottom of the edit form
- It has a green icon and title: "💰 Salary Information"

## 📝 What You'll See

```
┌─────────────────────────────────────────────────────────┐
│  💰 Salary Information                                  │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  Monthly Salary (₱)    Daily Rate (₱)    Hourly Rate (₱)│
│  [  25000.00  ]        [  1136.36  ]     [  142.05  ]   │
│                        Monthly ÷ 22      Daily ÷ 8      │
│                        (read-only)       (read-only)    │
│                                                          │
│  ℹ️ Note: Salary changes will be reflected in the      │
│     Payroll Management system immediately.              │
└─────────────────────────────────────────────────────────┘
```

## ⚡ How It Works

1. **Type Monthly Salary** → Daily and Hourly rates calculate automatically
2. **Click Save** → Salary is saved to database
3. **Go to Payroll** → New salary appears immediately

## 🔗 Check in Payroll

### Go to Payroll Management:
```
http://localhost/Evergreen/accounting-and-finance/modules/payroll-management.php
```

### Select the Employee:
- Use the "Select Employee" dropdown
- Choose the employee you just edited

### View Salary Rates Card:
```
┌─────────────────────────────────────────┐
│         SALARY RATES                    │
├─────────────────────────────────────────┤
│  Monthly Salary        ₱25,000.00       │
│  Daily Rate            ₱1,136.36        │
│  Hourly Rate           ₱142.05          │
└─────────────────────────────────────────┘
```

## 💡 Quick Tips

✅ **Only Admins** can edit employee salary
✅ **Auto-calculation** uses Philippine standards (22 days, 8 hours)
✅ **Instant reflection** - no waiting or syncing needed
✅ **Salary section** only appears when editing (not when adding new employee)

## 📊 Example Salaries

| Monthly Salary | Daily Rate | Hourly Rate |
|---------------|------------|-------------|
| ₱15,000       | ₱681.82    | ₱85.23      |
| ₱20,000       | ₱909.09    | ₱113.64     |
| ₱25,000       | ₱1,136.36  | ₱142.05     |
| ₱30,000       | ₱1,363.64  | ₱170.45     |
| ₱50,000       | ₱2,272.73  | ₱284.09     |

## 🚨 Common Issues

**Q: I don't see the Salary Information section**
- A: Make sure you clicked **Edit** (not Add)
- A: Salary section only appears when editing existing employees

**Q: Salary not showing in Payroll**
- A: Make sure you clicked **Save** after entering the salary
- A: Refresh the Payroll Management page

**Q: Can't edit the Daily/Hourly Rate fields**
- A: These are auto-calculated and read-only
- A: Just edit the Monthly Salary, the others update automatically

## 📞 Need Help?

If you encounter any issues:
1. Check that you're logged in as Admin
2. Verify the employee exists in both HRIS and Payroll
3. Clear browser cache and try again
4. Check the browser console for any JavaScript errors
