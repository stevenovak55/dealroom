# MA Deal Room - Quick Start User Guide

## 🎯 How to Add Vendors and Manage Tasks

### Finding the Features

The plugin uses a **tab-based interface** within each transaction. Here's where everything is:

---

## 📍 **Step-by-Step: Adding Vendors to a Transaction**

### 1. Navigate to a Transaction

**Option A: From Dashboard**
```
WordPress Admin → MA Deal Room → Dashboard → Click on a Transaction
```

**Option B: From Transactions List**
```
WordPress Admin → MA Deal Room → Transactions → Click "View" on any transaction
```

### 2. Click the "Parties" Tab

Once you're viewing a transaction, you'll see these tabs at the top:
```
┌─────────┬──────┬─────────┬───────────┬──────────┐
│ Details │ Tasks│ PARTIES │ Documents │ Activity │
└─────────┴──────┴─────────┴───────────┴──────────┘
```

**Click on "PARTIES"** - This is where vendors and all transaction participants are managed!

### 3. Add a Vendor (Party)

In the Parties tab, click the **"+ Add Party"** button

You'll see a form with these options:

**Available Party Types (including vendors):**
- Buyer
- Seller
- Buyer Attorney
- Seller Attorney
- Buyer Lender
- Buyer Agent
- Seller Agent
- Title Company
- **Inspector** ← Vendor
- **Appraiser** ← Vendor
- **HOA Manager** ← Vendor
- **Septic Inspector** ← Vendor
- **Fire Department** ← Vendor
- Other

**Fill in the form:**
```
Role: [Select from dropdown - e.g., "Inspector"]
Contact Name: John Smith
Company Name: Smith Inspections LLC
Email: john@smithinspections.com
Phone: (555) 123-4567
Address: 123 Main St, Boston, MA
```

Click **"Save"** and the vendor/party is added to the transaction!

---

## ✅ **Step-by-Step: Managing Tasks**

### 1. Go to the Tasks Tab

From the transaction detail page:
```
┌─────────┬──────┬─────────┬───────────┬──────────┐
│ Details │TASKS │ Parties │ Documents │ Activity │
└─────────┴──────┴─────────┴───────────┴──────────┘
```

**Click on "TASKS"**

### 2. Add a New Task

Click the **"+ Add Task"** button

**Fill in the task form:**
```
Task Name: Schedule Home Inspection
Description: Contact inspector and schedule property inspection
Due Date: [Select date]
Assigned To: [Select role - e.g., "Listing Agent"]
Priority: High
Status: Pending
```

Click **"Create Task"** to save

### 3. Apply Template Tasks (Faster!)

Instead of adding tasks one by one, you can apply a template:

**In the Tasks tab:**
1. Click **"Apply Template"** button
2. Select a template (e.g., "MA Single Family Home Sale")
3. All tasks from the template will be added automatically!

Templates include:
- Title V Septic Inspection
- Smoke/CO Certificate
- Lead Paint Disclosure
- Attorney Review
- And all MA-required tasks

---

## 🎨 **Understanding the Interface Layout**

### Transaction Detail Page Structure

When you open any transaction, here's what you see:

```
┌────────────────────────────────────────────────────┐
│  [<- Back]           123 Main St, Boston, MA       │
│                                       [Edit][Delete]│
├────────────────────────────────────────────────────┤
│  Status: Active    Type: SFH    Close: Jan 15     │
├────────────────────────────────────────────────────┤
│  [ Details ] [ Tasks ] [ Parties ] [ Documents ]   │  ← TABS
├────────────────────────────────────────────────────┤
│                                                    │
│  TAB CONTENT SHOWS HERE                           │
│                                                    │
│  • Details tab: Timeline, property info           │
│  • Tasks tab: Task list, add tasks               │
│  • Parties tab: Vendors, attorneys, agents       │
│  • Documents tab: Upload/manage files            │
│  • Activity tab: Event log                        │
│                                                    │
└────────────────────────────────────────────────────┘
```

---

## 🔄 **Common Workflows**

### Workflow 1: Setting Up a New Transaction with Vendors

1. **Create Transaction**
   ```
   Transactions → Create New Transaction
   Fill in: Address, Closing Date, Sale Price, Property Type
   Click "Create"
   ```

2. **Add All Parties/Vendors**
   ```
   Open Transaction → Parties Tab → Add Party

   Add these parties:
   - Seller (client)
   - Buyer
   - Seller Attorney
   - Buyer Attorney
   - Inspector (vendor)
   - Appraiser (vendor)
   - Septic Inspector (vendor - if needed)
   ```

3. **Apply Task Template**
   ```
   Tasks Tab → Apply Template → Select Template → Apply
   ```

4. **Assign Tasks to Parties**
   ```
   Tasks Tab → Click on a task → Edit
   Assign to: [Select party/role]
   Save
   ```

### Workflow 2: Requesting Work from a Vendor

While there isn't a dedicated "Request Task" button, here's how to work with vendors:

**Method 1: Assign Task to Vendor**
```
1. Tasks Tab → Add Task (or edit existing)
2. Task Name: "Complete Home Inspection"
3. Assigned To: Select "Inspector" (or specific inspector name)
4. Due Date: [Set deadline]
5. Save
```

**Method 2: Vendor Portal (Phase 2 Feature)**
```
Note: The Vendor Portal is in development (see KNOWN_TODOS.md)
When complete, vendors will be able to:
- Log in to their own portal
- See tasks assigned to them
- Upload inspection reports
- Update task status
```

**Current Workaround:**
- Add vendors as Parties
- Assign tasks to them
- Email them directly with task details
- Manually update task status when complete

---

## 📋 **Task Management Features**

### Task Filters

In the Tasks tab, you can filter by:
```
┌─────────────────────────────────────────────┐
│ Status: [All] ▼    Role: [All] ▼   🔍 Search│
└─────────────────────────────────────────────┘
```

- **Status:** Pending, In Progress, Completed, Overdue
- **Role:** Filter by who it's assigned to
- **Search:** Find specific tasks by name

### Task Actions

For each task, you can:
- ✏️ **Edit** - Change details, reassign, update due date
- ✅ **Mark Complete** - Check off when done
- 🗑️ **Delete** - Remove task
- 📎 **View Dependencies** - See related tasks

---

## 🎯 **Quick Reference: Where is Everything?**

| What You Want to Do | Where to Go |
|---------------------|-------------|
| **Add a vendor (inspector, appraiser, etc.)** | Transaction → **Parties Tab** → Add Party |
| **Add a task** | Transaction → **Tasks Tab** → Add Task |
| **Apply template tasks** | Transaction → **Tasks Tab** → Apply Template |
| **Upload documents** | Transaction → **Documents Tab** → Upload |
| **See timeline/milestones** | Transaction → **Details Tab** → Timeline |
| **Edit property details** | Transaction → **Details Tab** → Property Details |
| **View activity log** | Transaction → **Activity Tab** |

---

## 🚀 **Pro Tips**

### Tip 1: Use Templates to Save Time
Instead of adding 20+ tasks manually:
```
Tasks Tab → Apply Template → Select "MA Single Family Home"
```
This adds all MA-required tasks automatically!

### Tip 2: Parties = Everyone Involved
The "Parties" tab includes:
- Your clients (buyers/sellers)
- Other agents
- Attorneys
- **ALL VENDORS** (inspectors, appraisers, etc.)
- Service providers

Think of "Party" as "anyone involved in this transaction"

### Tip 3: Assign Tasks by Role
When creating tasks, assign by role (not person):
```
✅ Good: Assigned to "Inspector" (role)
❌ Not: Assigned to "John Smith" (person)
```

This way, you can reassign if vendors change.

### Tip 4: Dashboard Overview
```
WordPress Admin → MA Deal Room
```
The dashboard shows:
- Active transactions
- Overdue tasks
- Upcoming deadlines
- Recent activity

---

## 🔍 **Still Can't Find It?**

### Make Sure You're in the Right Place

**✅ Correct Navigation:**
```
WordPress Admin (wp-admin)
  └─ MA Deal Room (in left sidebar)
      └─ Dashboard OR Transactions
          └─ Click on a Transaction
              └─ See the 5 tabs at top
```

**❌ Common Mistakes:**
- Looking in WordPress Posts/Pages (wrong area)
- Looking at the Transactions List (need to OPEN a transaction)
- Missing the tabs (they're horizontal tabs below the transaction header)

### The Tabs Look Like This:

```
┌─────────┬──────┬─────────┬───────────┬──────────┐
│ Details │ Tasks│ Parties │ Documents │ Activity │
└─────────┴──────┴─────────┴───────────┴──────────┘
     ↑       ↑        ↑          ↑          ↑
     |       |        |          |          |
  Timeline  Add    Add      Upload     View
  & Info   Tasks  Vendors   Files      Log
```

---

## 🎓 **Example Walkthrough**

### Complete Example: Adding Inspector and Scheduling Inspection

**Step 1: Open Transaction**
```
WordPress Admin → MA Deal Room → Transactions
Click "View" on: 123 Main St, Boston, MA
```

**Step 2: Add Inspector (Vendor)**
```
Click "Parties" tab
Click "+ Add Party" button

Form:
  Role: Inspector
  Contact Name: John Smith
  Company: Smith Home Inspections
  Email: john@smithinspections.com
  Phone: (617) 555-1234
  Address: 50 State St, Boston, MA

Click "Save"
```

**Step 3: Create Inspection Task**
```
Click "Tasks" tab
Click "+ Add Task" button

Form:
  Task Name: Complete Home Inspection
  Description: Full property inspection including foundation, roof, systems
  Due Date: January 10, 2024
  Assigned To: Inspector
  Priority: High
  Status: Pending

Click "Create Task"
```

**Step 4: Track Progress**
```
When inspection is done:
  Click task → Change Status to "Completed"

Upload report:
  Click "Documents" tab
  Upload inspection report PDF
```

**Done!** ✅

---

## ❓ **Frequently Asked Questions**

### Q: I don't see "Parties" tab?
**A:** Make sure you've OPENED a transaction (clicked "View"). The tabs only appear inside the transaction detail page.

### Q: Can I add custom party types?
**A:** Currently, use "Other" for custom types. The role dropdown has 14 predefined options including common vendors.

### Q: How do I notify a vendor of their task?
**A:** Currently, you need to email them manually. The Vendor Portal (where vendors can log in and see tasks) is planned for version 2.2.0.

### Q: Can I bulk-assign tasks?
**A:** Not in the UI yet, but you can use Templates to add multiple tasks at once (Tasks Tab → Apply Template).

### Q: Where are reminders sent from?
**A:** Automatic email/SMS reminders are configured in:
```
WordPress Admin → MA Deal Room → Settings → Notifications
```

### Q: Can vendors see their assigned tasks?
**A:** Vendor Portal is in development (see KNOWN_TODOS.md). Current workaround: email tasks to vendors manually.

---

## 📞 **Need More Help?**

### Documentation Locations

**In Plugin:**
- `README.md` - Plugin overview
- `USER_GUIDE.md` - Detailed user guide
- `docs/` - API and technical docs

**For Administrators:**
- `PRODUCTION_READY_CHECKLIST.md` - Deployment guide
- `PLUGIN_ACTIVATION_TEST_REPORT.md` - Testing details

### Check Your Installation

**Verify REST API is working:**
```
Visit: https://your-site.com/wp-json/ma-deal/v1/
Should return JSON with available routes
```

**Check for errors:**
```
Location: /wp-content/debug.log
Look for: Any PHP errors or warnings
```

---

## 🎉 **You're Ready!**

Key takeaways:
1. **Parties Tab** = Where you add ALL vendors
2. **Tasks Tab** = Where you create and manage tasks
3. **Templates** = Fast way to add standard tasks
4. **Think "Party"** not "Vendor" (same thing in the UI)

**Start by:**
1. Creating or opening a transaction
2. Clicking the **Parties** tab
3. Adding your first vendor with **+ Add Party**

---

**Last Updated:** 2025-11-05
**Plugin Version:** 2.0.0
**Guide Version:** 1.0.0
