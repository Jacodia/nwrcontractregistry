# Contract Email Notification System - Setup Guide

## ✅ Issues Fixed

### 1. **PHPMailer Path Issue**
- **Problem**: `Contract.php` model was using incorrect PHPMailer paths
- **Solution**: Updated to use Composer autoload: `require_once __DIR__ . '/../../vendor/autoload.php'`

### 2. **Invalid Email Addresses**
- **Problem**: `admin@example.com` was causing email sending to hang
- **Solution**: Updated admin user email to valid address: `uraniathomas23@gmail.com`

### 3. **Email Timeouts**
- **Problem**: Gmail SMTP connections were hanging without timeout
- **Solution**: Added 20-second timeout and 1-second delay between emails to prevent rate limiting

### 4. **No Test Contracts**
- **Problem**: No contracts existed that expire in exactly 30, 60, or 90 days
- **Solution**: Added test contracts with correct expiry dates

## ✅ System Status

**All email notification scripts are now working:**

- `send_30.php` - Sends daily notifications for contracts expiring in 30 days ✅
- `send_60.php` - Sends notifications on Mon/Wed/Thu for contracts expiring in 60 days ✅  
- `send_90.php` - Sends weekly notifications (Mondays) for contracts expiring in 90 days ✅

**Test Results:**
- ✅ Email functionality verified with `simple_email_test.php`
- ✅ Database connections working
- ✅ Contract queries returning correct results
- ✅ Emails being sent successfully

## 📋 Current Schedule Logic

| Script | Frequency | Days | When it Runs |
|--------|-----------|------|--------------|
| `send_30.php` | daily | Every day | All contracts expiring in exactly 30 days |
| `send_60.php` | twice | Mon, Wed, Thu | All contracts expiring in exactly 60 days |
| `send_90.php` | weekly | Monday only | All contracts expiring in exactly 90 days |

## 🕒 Setting Up Automated Scheduling (Windows)

Since you're on Windows, you'll need to use **Task Scheduler** instead of cron:

### Method 1: Using Task Scheduler GUI

1. **Open Task Scheduler**
   - Press `Win + R`, type `taskschd.msc`, press Enter

2. **Create Basic Task**
   - Right-click "Task Scheduler Library" → "Create Basic Task"

3. **For send_30.php (Daily)**
   - Name: "Contract Notifications - 30 Days"
   - Trigger: Daily, start at 9:00 AM
   - Action: Start a program
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `C:\xampp\htdocs\nwrcontractregistry\backend\send_30.php`
   - Start in: `C:\xampp\htdocs\nwrcontractregistry\backend`

4. **For send_60.php (Mon/Wed/Thu)**
   - Name: "Contract Notifications - 60 Days"
   - Trigger: Weekly, select Monday, Wednesday, Thursday at 9:00 AM
   - Action: Same as above but with `send_60.php`

5. **For send_90.php (Weekly Monday)**
   - Name: "Contract Notifications - 90 Days"
   - Trigger: Weekly, select Monday at 9:00 AM
   - Action: Same as above but with `send_90.php`

### Method 2: Using Command Line (schtasks)

Run these commands in Command Prompt as Administrator:

```cmd
REM 30-day notifications (daily at 9 AM)
schtasks /create /tn "Contract-30Days" /tr "C:\xampp\php\php.exe C:\xampp\htdocs\nwrcontractregistry\backend\send_30.php" /sc daily /st 09:00

REM 60-day notifications (Mon/Wed/Thu at 9 AM)  
schtasks /create /tn "Contract-60Days" /tr "C:\xampp\php\php.exe C:\xampp\htdocs\nwrcontractregistry\backend\send_60.php" /sc weekly /d MON,WED,THU /st 09:00

REM 90-day notifications (Monday at 9 AM)
schtasks /create /tn "Contract-90Days" /tr "C:\xampp\php\php.exe C:\xampp\htdocs\nwrcontractregistry\backend\send_90.php" /sc weekly /d MON /st 09:00
```

## 🔧 Manual Testing Commands

```powershell
# Test individual scripts
cd c:\xampp\htdocs\nwrcontractregistry\backend
php send_30.php
php send_60.php
php send_90.php

# Test email functionality
php simple_email_test.php

# Check database contracts
php test_db.php
```

## 📝 Configuration Files

### Email Settings (in ContractNotifier.php)
```php
$mail->Username   = 'uraniathomas23@gmail.com';
$mail->Password   = 'lkmkivxthjizqojc';  // Gmail App Password
```

### Database Settings
```php
$host = 'localhost';
$db   = 'nwr_crdb';
$user = 'root';
$pass = '';
```

## 🚨 Important Notes

1. **Gmail App Password**: Make sure the Gmail App Password (`lkmkivxthjizqojc`) remains valid
2. **XAMPP Running**: Ensure XAMPP (Apache/MySQL) is running when tasks execute
3. **File Paths**: Update paths in Task Scheduler if you move the project
4. **Time Zone**: Tasks run in system local time
5. **Logging**: Consider adding logging to track email sends (check `logs/reminder_log.txt`)

## 📊 Monitoring

To monitor if emails are being sent, check:
- Gmail sent folder for confirmation
- Task Scheduler → Task Scheduler Library → Check "Last Run Result" 
- Add logging to scripts for debugging

The system is now fully functional and ready for production use!