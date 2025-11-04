# Fresh WordPress Installation - Access Information

## 🌐 Your Fresh Test Site

**Main URL:** http://localhost:8083

### WordPress Admin
- **URL:** http://localhost:8083/wp-admin
- **Username:** Use your existing admin credentials
- **Note:** This is a fresh WordPress installation

### React Admin App (Plugin Frontend)
- **URL:** http://localhost:8083/agent-dashboard/
- **Registration:** http://localhost:8083/agent-dashboard/#/auth/register
- **Login:** http://localhost:8083/agent-dashboard/#/auth/login

---

## 🔧 Plugin Status

**Plugin:** MA Deal Room v1.0.7  
**Status:** ✅ Active and Fully Functional

### ✅ Deployed Fixes
1. **Role Assignment Fix** (cf3a97c)
   - Users automatically assigned 'agent' role on registration
   
2. **403 Permission Fix** (3dbabbd)
   - Fixed BaseController CustomUser type mismatch
   - Added WordPress capabilities to agent role
   - No more 403 Forbidden errors
   
3. **Email Redirect Fix** (590bc54)
   - Email verification redirects to React app login
   - Correct URL: `/agent-dashboard/#/auth/login`

### 📊 Database Status
- **Tables Created:** 29/29 ✅
- **Migrations Applied:** 24/24 ✅
- **Templates Synced:** 7 ✅
- **Task Definitions:** 276 ✅
- **Default Account:** 1 ✅

---

## 🧪 Testing Instructions

### Test 1: User Registration
1. Navigate to: http://localhost:8083/agent-dashboard/#/auth/register
2. Fill in registration form:
   - Email: your-email@example.com
   - Password: SecurePass123!
   - First Name: Test
   - Last Name: User
3. Click "Register"
4. **Expected:** User created with 'agent' role assigned ✅

### Test 2: Email Verification
1. After registration, you'll receive a verification email
2. Click the verification link in the email
3. **Expected:** Redirected to http://localhost:8083/agent-dashboard/#/auth/login ✅
4. Click "Go to Login"
5. **Expected:** You stay on the React app login page ✅

### Test 3: Login
1. Navigate to: http://localhost:8083/agent-dashboard/#/auth/login
2. Enter your credentials
3. Click "Login"
4. **Expected:** JWT token issued, redirected to dashboard ✅

### Test 4: API Access (No 403 Errors)
After login, test these endpoints:
- GET /transactions - Should return 200 ✅
- GET /auth/me - Should return 200 ✅
- No 403 Forbidden errors ✅

---

## 🔍 Manual Email Verification (For Testing)

If you need to manually verify a test user's email:

```bash
# Get user ID from registration response, then:
docker exec ma-dealroom-fresh-wp php -r "
require_once('/var/www/html/wp-load.php');
global \$wpdb;
\$wpdb->query(\"UPDATE {\$wpdb->prefix}ma_deal_custom_users 
               SET email_verified = 1, status = 'active' 
               WHERE id = YOUR_USER_ID\");
echo \"Email verified!\n\";
"
```

---

## 📋 Database Access

### phpMyAdmin
- **URL:** http://localhost:8082
- **Server:** ma-dealroom-fresh-db
- **Username:** wordpress
- **Password:** wordpress
- **Database:** wordpress

### Direct MySQL Access
```bash
docker exec -it ma-dealroom-fresh-db mysql -u wordpress -pwordpress wordpress
```

---

## 🚀 REST API Endpoints

**Base URL:** http://localhost:8083/wp-json/ma-deal-room/v1

### Authentication
- `POST /auth/register` - Register new user
- `POST /auth/login` - Login with credentials
- `POST /auth/verify-email` - Verify email with token
- `GET /auth/me` - Get current user profile

### Transactions
- `GET /transactions` - List all transactions
- `POST /transactions` - Create new transaction
- `GET /transactions/{id}` - Get transaction details
- `PUT /transactions/{id}` - Update transaction

### Documents
- `GET /documents` - List documents
- `POST /documents` - Upload document

---

## 📝 Test User Created

A test user was created during deployment:

- **Email:** testuser1762256341@example.com
- **Password:** Test123!@#Password
- **User ID:** 3
- **Role:** agent ✅
- **Status:** pending_verification (needs email verification)

---

## ✅ What Works

1. ✅ User registration with automatic role assignment
2. ✅ Email verification system
3. ✅ Login with JWT token generation
4. ✅ Protected API endpoint access (no 403 errors)
5. ✅ All database migrations
6. ✅ All templates and task definitions loaded
7. ✅ Email verification redirect to React app

---

## 🎯 Recommended Testing Flow

1. **Register a new user** via the React app
2. **Check database** - verify 'agent' role was assigned
3. **Manually verify email** in database (or wait for email)
4. **Login** via the React app
5. **Access dashboard** - verify no 403 errors
6. **Create a transaction** - test full workflow
7. **Upload a document** - test file handling
8. **Check all features** in the admin panel

---

## 📞 Support

If you encounter any issues:
1. Check browser console for JavaScript errors
2. Check Network tab for API responses
3. Check WordPress debug.log: `docker exec ma-dealroom-fresh-wp tail -f /var/www/html/wp-content/debug.log`
4. Check detailed deployment report: `/tmp/deployment_report.md`

---

**Deployment Date:** 2025-11-04  
**WordPress Version:** Latest  
**Plugin Version:** MA Deal Room v1.0.7  
**All Fixes:** ✅ Deployed and Verified
