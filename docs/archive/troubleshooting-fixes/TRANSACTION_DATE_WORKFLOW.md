# MA Deal Room - Transaction Date Workflow

**Last Updated:** 2025-10-31
**Version:** 2.1

---

## 📅 **Complete Date Workflow**

MA Deal Room tracks 6 key milestone dates throughout the transaction lifecycle. **All dates are optional** and can be set as the transaction progresses through each stage.

### **Transaction Date Milestones**

| # | Date Field | Database Column | Description | When Set | Required |
|---|------------|----------------|-------------|----------|----------|
| 1 | **Creation Date** | `created_at` | When transaction record was created | Automatic | ✅ Auto |
| 2 | **Listing Signed** | `listing_date` | When listing agreement signed (sell side) | Manual | ❌ Optional |
| 3 | **Offer Acceptance** | `offer_accepted_date` | When seller accepts buyer's offer | Manual | ❌ Optional |
| 4 | **P&S Date** | `ps_agreement_date` | Purchase & Sale agreement signed | Manual | ❌ Optional |
| 5 | **Loan Commitment** | `loan_commitment_date` | Lender commits to financing | Manual | ❌ Optional |
| 6 | **Closing Date** | `closing_date` | Planned/scheduled closing date | Manual | ❌ Optional |

**Additional Date Fields:**
- `actual_closing_date` - Actual date transaction closed (may differ from planned `closing_date`)
- `updated_at` - Last time transaction was modified (automatic)

---

## 🎯 **Date Workflow By Transaction Type**

### **Sell Side (Listing) Transaction:**

```
1. Creation Date (auto) → Transaction created in system
                ↓
2. Listing Signed → Agent signs listing agreement with seller
                ↓
3. Offer Acceptance → Seller accepts buyer's offer
                ↓
4. P&S Date → Purchase & Sale agreement executed
                ↓
5. Loan Commitment → Buyer's lender commits to loan
                ↓
6. Closing Date → Scheduled closing appointment
                ↓
   Actual Closing → Transaction completes, deed recorded
```

### **Buy Side Transaction:**

```
1. Creation Date (auto) → Transaction created in system
                ↓
2. (Listing Signed skipped - not applicable to buy side)
                ↓
3. Offer Acceptance → Seller accepts buyer's offer
                ↓
4. P&S Date → Purchase & Sale agreement executed
                ↓
5. Loan Commitment → Lender commits to financing buyer
                ↓
6. Closing Date → Scheduled closing appointment
                ↓
   Actual Closing → Transaction completes, buyer takes possession
```

---

## 🔗 **Task Due Date Anchors**

Tasks in templates can be scheduled relative to any of these date milestones using **date anchors**:

### **Available Date Anchors:**

| Anchor | Maps To | Example Usage | Description |
|--------|---------|---------------|-------------|
| `Listing` | listing_date | `Listing+0d` | Task due on listing date |
| `Offer` | offer_accepted_date | `Offer+5d` | Task due 5 days after offer accepted |
| `PS` | ps_agreement_date | `PS+14d` | Task due 14 days after P&S |
| `LoanCommitment` | loan_commitment_date | `LoanCommitment+7d` | Task due 7 days after loan commitment |
| `Closing` | closing_date | `Closing-21d` | Task due 21 days before closing |

### **Date Anchor Syntax:**

```yaml
due: Listing        # Exact anchor date
due_offset: +0d     # No offset

due: Offer          # Offer accepted date
due_offset: +14d    # 14 days after

due: PS             # P&S signed date
due_offset: +30d    # 30 days after

due: Closing        # Closing date
due_offset: -21d    # 21 days before (negative offset)

due: LoanCommitment # Loan commitment date
due_offset: +7d     # 7 days after
```

**Format Rules:**
- Anchor: `Listing`, `Offer`, `PS`, `LoanCommitment`, `Closing`
- Offset: `+Nd` (days after) or `-Nd` (days before)
- Combined: `Anchor` + `Offset` (e.g., `Closing-21d`)

---

## 📋 **Example Task Scheduling**

### **Listing Stage Tasks:**

```yaml
# Task due when listing agreement signed
- id: property_info_mls
  title: Enter Property Information into MLS
  due: Listing
  due_offset: +0d
  # Due: Same day as listing signed

# Task due 7 days after listing
- id: schedule_photography
  title: Schedule Professional Photography
  due: Listing
  due_offset: +7d
  # Due: 7 days after listing signed
```

### **Offer Stage Tasks:**

```yaml
# Task due when offer accepted
- id: notify_seller
  title: Notify Seller of Accepted Offer
  due: Offer
  due_offset: +0d
  # Due: Same day as offer accepted

# Task due 5 days after offer
- id: collect_earnest_money
  title: Collect Earnest Money Deposit
  due: Offer
  due_offset: +5d
  # Due: 5 business days after offer accepted
```

### **P&S Stage Tasks:**

```yaml
# Task due 14 days after P&S
- id: ps_to_lender
  title: Send P&S Agreement to Lender
  due: PS
  due_offset: +0d
  # Due: Same day as P&S signed

# Task due 30 days after P&S
- id: loan_commitment_deadline
  title: Loan Commitment Deadline
  due: PS
  due_offset: +30d
  # Due: 30 days after P&S (typical MA timeline)
```

### **Loan Commitment Stage Tasks:**

```yaml
# Task due when loan commitment received
- id: verify_loan_terms
  title: Verify Loan Commitment Terms
  due: LoanCommitment
  due_offset: +0d
  # Due: Same day as loan commitment

# Task due 7 days after loan commitment
- id: order_final_appraisal
  title: Order Final Appraisal
  due: LoanCommitment
  due_offset: +7d
  # Due: 7 days after loan commitment
```

### **Closing Stage Tasks:**

```yaml
# Task due 21 days before closing
- id: municipal_lien_certificate
  title: Order Municipal Lien Certificate
  due: Closing
  due_offset: -21d
  # Due: 3 weeks before closing (MA requirement)

# Task due 2 days before closing
- id: final_walkthrough
  title: Conduct Final Walkthrough
  due: Closing
  due_offset: -2d
  # Due: 48 hours before closing

# Task due on closing day
- id: closing_attendance
  title: Attend Closing
  due: Closing
  due_offset: +0d
  # Due: Closing day
```

---

## 🔧 **Technical Implementation**

### **Database Schema:**

```sql
CREATE TABLE wp_ma_deal_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  -- Date fields (all optional except created_at)
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  listing_date DATE NULL,
  offer_accepted_date DATE NULL,
  ps_agreement_date DATE NULL,
  loan_commitment_date DATE NULL,
  closing_date DATE NULL,
  actual_closing_date DATE NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- Other fields...
  -- Indexes on date fields for filtering/sorting
  INDEX idx_listing_date (listing_date),
  INDEX idx_offer_accepted_date (offer_accepted_date),
  INDEX idx_ps_agreement_date (ps_agreement_date),
  INDEX idx_loan_commitment_date (loan_commitment_date),
  INDEX idx_closing_date (closing_date)
);
```

### **PHP Transaction Model:**

```php
class Transaction {
    public string $created_at;              // Auto-set on creation
    public ?string $listing_date = null;    // Optional
    public ?string $offer_accepted_date = null;  // Optional
    public ?string $ps_agreement_date = null;    // Optional
    public ?string $loan_commitment_date = null; // Optional (NEW)
    public ?string $closing_date = null;    // Optional
    public ?string $actual_closing_date = null; // Optional
    public string $updated_at;              // Auto-updated
}
```

### **TemplateEngine Date Calculation:**

```php
// Date anchor mapping
private function getAnchorDate(string $anchor, array $key_dates): ?string {
    $mapping = [
        'Listing' => 'listing_date',
        'Offer' => 'offer_accepted_date',
        'PS' => 'ps_date',
        'LoanCommitment' => 'loan_commitment_date',
        'Closing' => 'closing_date',
    ];

    $key = $mapping[$anchor] ?? null;
    return $key && isset($key_dates[$key]) ? $key_dates[$key] : null;
}

// Calculate task due date
public function calculateDueDate(string $relative_date, array $key_dates): ?string {
    // Parse: "Closing-21d" → anchor="Closing", operator="-", days=21
    if (preg_match('/^(Listing|Offer|PS|LoanCommitment|Closing)([\+\-])(\d+)d$/', $relative_date, $matches)) {
        $anchor = $matches[1];
        $operator = $matches[2];
        $days = (int)$matches[3];

        $anchor_date = $this->getAnchorDate($anchor, $key_dates);

        if (!$anchor_date) {
            return null; // Anchor date not set yet
        }

        $timestamp = strtotime($anchor_date);
        $timestamp += ($operator === '+' ? $days : -$days) * 86400;

        return date('Y-m-d H:i:s', $timestamp);
    }

    return null;
}
```

---

## 💡 **Best Practices**

### **Setting Dates:**

1. **Creation Date** - Automatically set when transaction created ✅
2. **Listing Signed** - Set immediately when listing agreement executed
3. **Offer Acceptance** - Set when seller accepts offer (triggers P&S timeline)
4. **P&S Date** - Set when P&S agreement signed (triggers financing timeline)
5. **Loan Commitment** - Set when lender provides commitment letter
6. **Closing Date** - Set as soon as scheduled (can be updated if rescheduled)

### **Date Dependencies:**

Dates should generally be set in chronological order:
```
Listing → Offer → P&S → Loan Commitment → Closing
```

However, **all dates are independent** - you can set any date at any time without requiring others to be set first.

### **Handling Missing Dates:**

- ✅ **Tasks with unset anchor dates** will have `due_at = NULL`
- ✅ Once the anchor date is set, task due dates can be recalculated
- ✅ Reminders won't fire for tasks with NULL due dates
- ✅ UI should clearly indicate "Date pending" for tasks without due dates

### **Updating Dates:**

When a transaction date changes:
1. Update the date field in the transaction record
2. Optionally recalculate dependent task due dates
3. Send notifications about changed dates/deadlines

---

## 📊 **Common MA Timeline Examples**

### **Typical SFH Transaction (60-day close):**

```
Day 0   - Listing Signed
Day 14  - Offer Accepted
Day 21  - P&S Signed (7 days after offer)
Day 51  - Loan Commitment (30 days after P&S)
Day 60  - Closing (60 days from listing)
```

### **Typical Condo Transaction (45-day close):**

```
Day 0   - Listing Signed
Day 10  - Offer Accepted
Day 17  - P&S Signed (7 days after offer)
Day 42  - Loan Commitment (25 days after P&S)
Day 45  - Closing (45 days from listing)
```

### **Cash Buyer (30-day close, no loan):**

```
Day 0   - Listing Signed
Day 7   - Offer Accepted
Day 14  - P&S Signed (7 days after offer)
N/A     - Loan Commitment (not applicable)
Day 30  - Closing (30 days from listing)
```

---

## 🎯 **Task Scheduling Strategy**

### **Early Stage (Listing → Offer):**
- Use `Listing` anchor for agent prep tasks
- Marketing, photography, open houses
- Typically 0-30 days

### **Mid Stage (Offer → P&S → Loan Commitment):**
- Use `Offer` anchor for initial due diligence
- Use `PS` anchor for contingency period tasks
- Home inspection, appraisal, loan processing
- Typically 30-50 days

### **Late Stage (Loan Commitment → Closing):**
- Use `LoanCommitment` anchor for post-commitment tasks
- Use `Closing` anchor (with negative offsets) for pre-closing tasks
- Final walkthrough, attorney review, municipal certificates
- Typically last 10-21 days

---

## 🚨 **Important Notes**

### **Date Flexibility:**

- ❌ **NO dates are required fields** (except auto-set `created_at`)
- ✅ Set dates as transaction progresses
- ✅ Can skip dates not applicable to transaction type
- ✅ Can update dates if timelines change

### **Null Date Handling:**

```php
// Task with Closing anchor when closing_date is not yet set:
$transaction->closing_date = null;

$due_at = $templateEngine->calculateDueDate('Closing-21d', $key_dates);
// Returns: null (not an error - date just pending)

// Later, when closing date is set:
$transaction->closing_date = '2026-01-15';

$due_at = $templateEngine->calculateDueDate('Closing-21d', $key_dates);
// Returns: '2025-12-25 00:00:00' (21 days before closing)
```

### **Transaction Side Considerations:**

**Sell Side:**
- Use `listing_date` for listing agreement tasks
- Full date workflow applies

**Buy Side:**
- Skip `listing_date` (not applicable)
- Start with `Offer` or create transaction when offer submitted
- Use `created_at` as fallback for early tasks

---

## 📝 **Migration Notes**

**Database Change:** Added `loan_commitment_date` field (2025-10-31)

```sql
-- Migration 007: Add loan commitment date
ALTER TABLE wp_ma_deal_transactions
ADD COLUMN loan_commitment_date DATE NULL
AFTER ps_agreement_date;

ALTER TABLE wp_ma_deal_transactions
ADD INDEX idx_loan_commitment_date (loan_commitment_date);
```

**Code Updates:**
- ✅ Transaction.php - Added `$loan_commitment_date` property
- ✅ TemplateEngine.php - Added `LoanCommitment` and `Offer` date anchors
- ✅ Date anchor regex updated to recognize new anchors
- ✅ Key dates arrays updated to include all date fields

---

## 📚 **References**

- **MA P&S Timeline**: Typically 5-14 days from offer acceptance
- **MA Loan Commitment**: Typically 30-45 days from P&S
- **MA Closing Timeline**: Typically 30-60 days from listing (varies by financing)
- **Municipal Lien Certificate**: Order 21+ days before closing
- **Title 5 Inspection**: Must be completed before closing (septic properties)

---

**Document Version:** 2.1
**Last Updated:** 2025-10-31
**Changes:** Added loan_commitment_date field and Offer/LoanCommitment anchors

---

*End of Transaction Date Workflow Documentation*
