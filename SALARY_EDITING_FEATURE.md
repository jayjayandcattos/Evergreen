# Salary Editing Feature - HRIS to Payroll Integration

## Overview
Added the ability for Admin and HR to edit employee salary rates directly from the HRIS system, which automatically reflects in the Payroll Management system.

## Feature Location
**HRIS Employee Management**: `http://localhost/evergreen/hris-sia/pages/employees.php`
- Click the **Edit** button on any active employee
- Salary section appears at the bottom of the edit form

## What Was Added

### 1. Salary Information Section in Edit Modal
When editing an employee, a new section appears with three fields:

#### **Monthly Salary** (Editable)
- Input field where you can enter the employee's monthly salary
- Format: Philippine Peso (₱)
- Example: 25000.00

#### **Daily Rate** (Auto-calculated)
- Automatically calculated as: Monthly Salary ÷ 22 working days
- Read-only field
- Updates in real-time as you type the monthly salary

#### **Hourly Rate** (Auto-calculated)
- Automatically calculated as: Daily Rate ÷ 8 hours
- Read-only field
- Updates in real-time as you type the monthly salary

### 2. Real-time Calculation
As you type the monthly salary, the daily and hourly rates update automatically using Philippine labor standards:
- **Working Days per Month**: 22 days
- **Hours per Day**: 8 hours

### 3. Database Integration
The salary information is stored in the `employee_refs` table in the accounting database, which is the same table used by the Payroll Management system.

## How It Works

### Step-by-Step Process

#### 1. Edit Employee Salary in HRIS
1. Go to `http://localhost/evergreen/hris-sia/pages/employees.php`
2. Find the employee you want to edit
3. Click the **Edit** button (blue button)
4. Scroll down to the **Salary Information** section
5. Enter the **Monthly Salary** (e.g., 25000)
6. Watch the Daily Rate and Hourly Rate calculate automatically
7. Click **Save**

#### 2. View Updated Salary in Payroll
1. Go to `http://localhost/Evergreen/accounting-and-finance/modules/payroll-management.php`
2. Select the employee from the dropdown
3. The **SALARY RATES** card will show:
   - Monthly Salary: ₱25,000.00
   - Daily Rate: ₱1,136.36
   - Hourly Rate: ₱142.05

### Data Flow
```
HRIS employees.php (Edit Employee)
         ↓
    Enter Monthly Salary
         ↓
    Save to employee_refs table
         ↓
Payroll Management reads from employee_refs
         ↓
    Displays in SALARY RATES card
```

## Technical Implementation

### Database Tables

#### `employee` (HRIS Table)
- Stores basic employee information
- Fields: employee_id, first_name, last_name, department_id, position_id, etc.

#### `employee_refs` (Accounting Table)
- Stores payroll-specific information
- Fields: external_employee_no, base_monthly_salary, employment_type, etc.
- **Key Field**: `base_monthly_salary` - This is what gets updated

### Employee Number Mapping
- HRIS `employee.employee_id = 1` → Accounting `employee_refs.external_employee_no = 'EMP001'`
- HRIS `employee.employee_id = 27` → Accounting `employee_refs.external_employee_no = 'EMP027'`

### Backend Logic (employees.php)

#### When Editing Employee:
1. Updates basic employee info in `employee` table
2. Checks if `employee_refs` record exists for this employee
3. If exists: Updates the `base_monthly_salary` field
4. If not exists: Creates new `employee_refs` record with salary info
5. Logs the action for audit trail

### Frontend Features

#### Salary Section Visibility
- **Add Employee**: Salary section is hidden (new employees don't have salary yet)
- **Edit Employee**: Salary section is visible with current salary pre-filled

#### Auto-calculation JavaScript
```javascript
function calculateRates() {
    const monthlySalary = parseFloat(document.getElementById('monthlySalary').value) || 0;
    const workingDaysPerMonth = 22;
    const hoursPerDay = 8;
    
    const dailyRate = monthlySalary / workingDaysPerMonth;
    const hourlyRate = dailyRate / hoursPerDay;
    
    document.getElementById('dailyRate').value = dailyRate.toFixed(2);
    document.getElementById('hourlyRate').value = hourlyRate.toFixed(2);
}
```

## Salary Calculation Standards

### Philippine Labor Standards Used
- **Monthly to Daily**: Monthly Salary ÷ 22 working days
- **Daily to Hourly**: Daily Rate ÷ 8 hours

### Example Calculations

#### Example 1: ₱25,000 Monthly Salary
- Monthly Salary: ₱25,000.00
- Daily Rate: ₱25,000 ÷ 22 = ₱1,136.36
- Hourly Rate: ₱1,136.36 ÷ 8 = ₱142.05

#### Example 2: ₱50,000 Monthly Salary
- Monthly Salary: ₱50,000.00
- Daily Rate: ₱50,000 ÷ 22 = ₱2,272.73
- Hourly Rate: ₱2,272.73 ÷ 8 = ₱284.09

## Integration with Payroll Management

### Where Salary Appears in Payroll

#### 1. Employee Details Tab
- **SALARY RATES** card shows all three rates
- Located in the right column next to employee photo

#### 2. Payroll Information Tab
- Used to calculate **Basic Salary** in earnings
- Affects **Gross Earnings** calculation
- Impacts **Net Salary** after deductions

#### 3. Attendance-Based Adjustments
- Daily Rate used for absent day deductions
- Hourly Rate used for late penalties
- Hourly Rate used for overtime pay calculations

### Payroll Calculations Using Salary

#### Absent Day Deduction
```
Deduction = Daily Rate × Number of Absent Days
```

#### Late Penalty
```
Penalty = (Hourly Rate ÷ 60) × Late Minutes
```

#### Overtime Pay
```
Overtime = Hourly Rate × 1.25 × Overtime Hours
```

## Security & Permissions

### Who Can Edit Salary?
- **Admin**: Full access to edit all employee information including salary
- **HR**: Can view but editing requires admin privileges (enforced by `requireAdmin()`)

### Audit Trail
Every salary update is logged with:
- Employee ID and Name
- New salary amount
- Timestamp
- User who made the change

## Testing Checklist

### Test 1: Edit Existing Employee Salary
- [ ] Go to HRIS employees page
- [ ] Click Edit on an employee
- [ ] Verify Salary Information section is visible
- [ ] Enter a monthly salary (e.g., 30000)
- [ ] Verify daily rate calculates to 1363.64
- [ ] Verify hourly rate calculates to 170.45
- [ ] Click Save
- [ ] Verify success message appears

### Test 2: Verify Payroll Reflection
- [ ] Go to Payroll Management page
- [ ] Select the employee you just edited
- [ ] Verify SALARY RATES card shows correct amounts
- [ ] Check Employee Details tab
- [ ] Verify Monthly Salary matches what you entered
- [ ] Verify Daily Rate and Hourly Rate are correct

### Test 3: New Employee (No Salary Yet)
- [ ] Go to HRIS employees page
- [ ] Click Add Employee
- [ ] Verify Salary Information section is hidden
- [ ] Add a new employee
- [ ] Edit the new employee
- [ ] Verify Salary Information section now appears
- [ ] Add salary and save

### Test 4: Update Existing Salary
- [ ] Edit an employee who already has a salary
- [ ] Verify current salary is pre-filled
- [ ] Change the salary to a different amount
- [ ] Save and verify update in payroll

## Important Notes

1. **Single Database**: Both HRIS and Accounting systems use the same database, so changes are instant

2. **No Data Loss**: If an employee doesn't have an `employee_refs` record, one is created automatically

3. **Backward Compatible**: Existing employees without salary records can now have salary added

4. **Real-time Updates**: Changes in HRIS reflect immediately in Payroll (no sync delay)

5. **Calculation Standards**: Uses Philippine labor standards (22 working days, 8 hours per day)

## Files Modified

### HRIS System
- `hris-sia/pages/employees.php` - Added salary editing feature

### No Changes Needed
- `accounting-and-finance/modules/payroll-management.php` - Already reads from `employee_refs` table

## Troubleshooting

### Issue: Salary not showing in Payroll
**Solution**: Make sure you saved the employee after entering the salary in HRIS

### Issue: Daily/Hourly rates not calculating
**Solution**: Make sure JavaScript is enabled in your browser

### Issue: Can't edit salary
**Solution**: Make sure you're logged in as Admin (only admins can edit employee information)

### Issue: Salary shows ₱0.00 in Payroll
**Solution**: Edit the employee in HRIS and enter a monthly salary, then save

## Future Enhancements

Potential improvements for future versions:
1. Salary history tracking (track all salary changes over time)
2. Bulk salary updates (update multiple employees at once)
3. Salary increase percentage calculator
4. Salary range validation based on position
5. Export salary report to Excel/PDF
