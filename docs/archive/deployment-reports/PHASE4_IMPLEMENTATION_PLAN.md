# Phase 4: Add Missing Metadata - IMPLEMENTATION PLAN

**Date:** 2025-10-31
**Status:** Ready to Begin
**Scope:** Enhance 214 tasks with citations, durations, priorities, and other metadata

---

## 📊 **Current State Analysis**

### **Metadata Coverage (as of Phase 3 completion):**

| Field | Coverage | Tasks | Status |
|-------|----------|-------|--------|
| **notes** | 60.3% | 129/214 | 🟡 Moderate |
| **documents** | 15.9% | 34/214 | 🔴 Low |
| **citations** | 3.7% | 8/214 | 🔴 Critical |
| **estimated_duration** | 2.3% | 5/214 | 🔴 Critical |
| **vendor_type** | 6.1% | 13/214 | 🟢 OK (vendor-specific) |
| **actions** | 2.8% | 6/214 | 🟡 Low |
| **priority** | 0.0% | 0/214 | 🔴 Missing |

---

## 🎯 **Phase 4 Goals**

### **1. Add Legal Citations** (Priority: HIGH)
- **Target:** 29 compliance/title/HOA tasks currently missing citations
- **Focus Areas:**
  - MA real estate regulations (M.G.L. chapters)
  - EPA federal requirements (lead paint)
  - Municipal requirements (smoke/CO, septic, water)
  - Condo law (M.G.L. c.183A)

### **2. Add Estimated Durations** (Priority: HIGH)
- **Target:** 209 tasks missing timing information
- **Purpose:** Help users/agents plan timeline expectations
- **Format:** Human-readable strings (e.g., "2-3 weeks", "Schedule 1 week ahead")

### **3. Add Priority Levels** (Priority: MEDIUM)
- **Target:** All 214 tasks (none currently have priority)
- **Levels:**
  - `critical` - Statutory deadlines, closing blockers
  - `high` - Important milestones, financing deadlines
  - `normal` - Standard workflow tasks
  - `low` - Optional or nice-to-have tasks

### **4. Enhance Vendor Types** (Priority: LOW)
- **Target:** ~20 additional vendor-related tasks
- **Purpose:** Enable vendor marketplace/matching features

---

## 📋 **Task Categories Requiring Attention**

### **1. Compliance Tasks (6 tasks + HOA/Title)**
**Missing Citations:** 29 tasks total

**High-Priority Compliance:**
- `agency_disclosure` ✅ (already has citation)
- `lead_paint_disclosure` ✅ (already has citation)
- `smoke_co_inspection` ✅ (already has citation)
- `smoke_co_compliance` ✅ (already has citation)
- `municipal_lien` ✅ (already has citation)
- `title5_septic_inspection` - **NEEDS CITATION** (310 CMR 15.000)
- `title5_certificate` - **NEEDS CITATION** (310 CMR 15.000)

### **2. Title Work Tasks (6 tasks)**
**Missing Citations:** All title-related tasks

- `order_prelim_title_search` - **NEEDS CITATION**
- `order_title_insurance` - **NEEDS CITATION**
- `review_title_commitment` - **NEEDS CITATION**
- `clear_title_issues` - **NEEDS CITATION**

### **3. HOA/Condo Tasks (22 tasks)**
**Missing Citations:** Condo law references

- `condo_6d_certificate` - **NEEDS CITATION** (M.G.L. c.183A §6)
- `hoa_financials_review` - **NEEDS CITATION**
- `condo_questionnaire` - **NEEDS CITATION**
- `condo_master_insurance_review` - **NEEDS CITATION**

### **4. Inspection Tasks (13 tasks)**
**Missing Durations:** All need timing estimates

- `home_inspection_schedule` - "Schedule 1-2 weeks ahead; inspection takes 2-4 hours"
- `title5_septic_inspection` - "Schedule 2-3 weeks ahead; inspection takes 1-2 hours"
- `well_water_comprehensive_test` - "Lab results 5-7 business days"

### **5. Financing Tasks (17 tasks)**
**Missing Durations:** Processing timelines

- `loan_commitment_track` - "Typical commitment 3-4 weeks after P&S"
- `appraisal_track` - "Ordered 1-2 weeks after P&S, completed 2-3 weeks"
- `clear_to_close_confirm` - "Final underwriting 3-5 business days before closing"

---

## 🔧 **Implementation Strategy**

### **Phase 4A: Research & Gather Information**

#### **Legal Citations Research:**
1. **MA General Laws (M.G.L.)**
   - Chapter 60 - Municipal Lien Certificates
   - Chapter 148 - Smoke & CO Detectors
   - Chapter 183A - Condominiums
   - Chapter 111 - Lead Paint

2. **MA Regulations (CMR)**
   - 310 CMR 15.000 - Septic Systems (Title 5)
   - 105 CMR 460 - Lead Paint

3. **Federal Regulations**
   - EPA Lead Paint Disclosure (40 CFR 745)

#### **Duration Research:**
Interview local agents/attorneys for typical timelines:
- Inspection scheduling (how far ahead to book)
- Government processing times (lien certificates, permits)
- Vendor turnaround times (septic, well, surveys)
- Lender timelines (appraisal, underwriting, clear to close)

#### **Priority Classification:**
Categorize by:
- **Critical:** Legal deadlines with penalties
- **High:** Financing contingencies, closing dependencies
- **Normal:** Standard workflow tasks
- **Low:** Post-closing follow-up, optional items

### **Phase 4B: Template Development**

#### **Citation Format:**
```yaml
citations:
  - url: "https://malegislature.gov/Laws/GeneralLaws/..."
    title: "M.G.L. Chapter X, Section Y - Description"
  - url: "https://www.mass.gov/regulations/..."
    title: "310 CMR X.XX - Regulation Name"
```

#### **Duration Format:**
```yaml
estimated_duration: "Schedule 2-3 weeks ahead; inspection takes 1-2 hours; certificate issued within 3 business days"
```
OR
```yaml
estimated_duration: "Processing time: 10 business days (excluding weekends/holidays)"
```

#### **Priority Format:**
```yaml
priority: "critical"  # or "high", "normal", "low"
```

#### **Enhanced Reminder Format:**
```yaml
reminders:
  - offset: "-14d"
    channels: ["email"]
    priority: "normal"
  - offset: "-3d"
    channels: ["email", "sms"]
    priority: "high"
```

### **Phase 4C: Bulk Updates**

#### **Step 1: Create Metadata Spreadsheet**
Export all tasks to CSV with columns:
- task_id
- title
- category
- current_citations (Y/N)
- current_duration (Y/N)
- current_priority (Y/N)
- suggested_priority
- research_notes

#### **Step 2: Research & Fill Spreadsheet**
For each task category:
1. Research applicable regulations
2. Consult with domain experts (agents/attorneys)
3. Document typical timelines
4. Assign priority levels

#### **Step 3: Apply Updates Programmatically**
Create Python script to:
1. Read metadata spreadsheet
2. Parse YAML templates
3. Insert/update metadata fields
4. Preserve existing formatting
5. Validate YAML after changes

---

## 📚 **Citation Library**

### **MA General Laws (M.G.L.)**

#### **Lead Paint:**
- M.G.L. Chapter 111, §§ 190-199B - Lead Poisoning Prevention
- 105 CMR 460 - Lead Paint Regulations
- Federal: 40 CFR 745 - EPA Lead-Based Paint Disclosure

#### **Smoke & CO Detectors:**
- M.G.L. Chapter 148, § 26F - Smoke Detectors
- M.G.L. Chapter 148, § 26F½ - Carbon Monoxide Alarms
- https://www.mass.gov/doc/consumer-guide-to-smoke-detectors-when-selling-home/

#### **Septic Systems (Title 5):**
- 310 CMR 15.000 - The State Environmental Code, Title 5
- https://www.mass.gov/title-5-septic-system-information

#### **Municipal Liens:**
- M.G.L. Chapter 60, Section 23 - Municipal Lien Certificates
- Valid for 150 days when recorded

#### **Water & Sewer:**
- Municipal ordinances (varies by city/town)
- Boston: https://www.bwsc.org/residential-customers/services/buying-or-selling-property

#### **Condominiums:**
- M.G.L. Chapter 183A - Condominiums
- Section 6 - Resale Certificates (6D Certificate)
- https://www.mass.gov/condominium-law

#### **Real Estate Disclosure:**
- M.G.L. Chapter 112, § 87AAA½ - Licensee-Consumer Relationship Disclosure
- https://www.mass.gov/doc/real-estate-board-agency-disclosure-form-english/

---

## ⏱️ **Typical Duration Reference**

### **Government Processing:**
- Municipal Lien Certificate: 10 business days
- Title 5 Certificate: Issued at inspection (valid 2 years for passing)
- Smoke/CO Certificate: Issued at inspection (valid 60 days)
- Water Final Reading (Boston): 2-3 weeks advance notice required
- Condo 6D Certificate: 10 business days (M.G.L. requirement)

### **Inspections:**
- Home Inspection: Schedule 1-2 weeks ahead; 2-4 hours onsite
- Title 5 Septic: Schedule 2-3 weeks ahead; 1-2 hours onsite
- Well Water Test: 5-7 business days for lab results
- Smoke/CO Inspection: Schedule 2-3 weeks ahead; 30-45 minutes
- Final Walkthrough: 24-48 hours before closing; 30-60 minutes

### **Financing:**
- Loan Application to Commitment: 30-45 days
- Appraisal Order to Completion: 2-3 weeks
- Clear to Close: 3-5 business days before closing
- Title Search: 2-3 weeks
- Title Insurance: Available at closing once title clear

### **Legal:**
- P&S Review: 3-5 business days
- Attorney Document Review: 1 week before closing
- Deed Preparation: 1 week before closing

---

## 🎯 **Priority Classification Guide**

### **Critical Priority** (Statutory/Closing Blockers)
Tasks with:
- Legal penalties for non-compliance
- Must be complete for closing to proceed
- Statutory deadlines
- Examples:
  - Lead paint disclosure (federal penalties)
  - Title 5 certificate (required for closing)
  - Smoke/CO certificate (required at closing)
  - Municipal lien certificate (must be current)
  - Clear to close (lender requirement)

### **High Priority** (Important Milestones)
Tasks with:
- Financing contingency deadlines
- Inspection contingency periods
- Time-sensitive negotiations
- Examples:
  - Loan commitment deadline
  - Home inspection response
  - Appraisal value check
  - P&S negotiation
  - Attorney review

### **Normal Priority** (Standard Workflow)
Tasks that:
- Follow typical transaction timeline
- Important but not urgent
- No penalties for reasonable delays
- Examples:
  - Property marketing tasks
  - Routine status checks
  - Document gathering
  - Utility transfers

### **Low Priority** (Optional/Post-Closing)
Tasks that:
- Nice to have but not required
- Post-closing follow-up
- Additional services
- Examples:
  - Post-closing thank you notes
  - Review requests
  - Referral follow-up
  - Homeowner resource sharing

---

## 📊 **Implementation Phases**

### **Phase 4A: Critical Compliance** (Week 1)
**Goal:** Add citations to all 29 compliance tasks

1. Title 5 septic tasks (310 CMR 15.000)
2. Title work tasks (M.G.L. title references)
3. Condo tasks (M.G.L. c.183A)
4. Municipal tasks (M.G.L. c.60 §23)

**Deliverable:** All compliance tasks have regulatory citations

### **Phase 4B: Duration Estimates** (Week 2)
**Goal:** Add estimated_duration to time-sensitive tasks

1. All inspection tasks (scheduling + completion time)
2. All financing tasks (processing timelines)
3. All government filing tasks (turnaround times)
4. All vendor tasks (typical wait times)

**Deliverable:** Users can estimate timeline for each task

### **Phase 4C: Priority Levels** (Week 2-3)
**Goal:** Assign priority to all 214 tasks

1. Critical: 20-30 tasks (compliance, closing blockers)
2. High: 40-50 tasks (contingencies, milestones)
3. Normal: 100-120 tasks (standard workflow)
4. Low: 20-30 tasks (optional, post-closing)

**Deliverable:** Tasks sortable/filterable by priority

### **Phase 4D: Enhanced Documentation** (Week 3)
**Goal:** Improve notes, add documents lists

1. Enhance notes with practical tips
2. Add required documents to document lists
3. Add vendor_type where applicable
4. Add actions for automated workflows

**Deliverable:** Comprehensive task documentation

---

## 🛠️ **Tools & Scripts Needed**

### **1. Metadata Extraction Script**
```python
# Extract all tasks to CSV for research/annotation
# Output: tasks_metadata.csv
```

### **2. Citation Updater Script**
```python
# Read citation mapping file
# Update YAML with legal references
# Validate format
```

### **3. Duration Updater Script**
```python
# Read duration mapping file
# Update YAML with timing info
# Validate format
```

### **4. Priority Updater Script**
```python
# Read priority classification file
# Update YAML with priority levels
# Validate priority values
```

### **5. Validation Script**
```python
# Check all compliance tasks have citations
# Check all time-sensitive tasks have durations
# Check all tasks have priorities
# Generate coverage report
```

---

## ✅ **Success Criteria**

### **Phase 4 Complete When:**

- [  ] **Citations:** 100% of compliance/title/HOA tasks have regulatory references
- [  ] **Durations:** 80%+ of time-sensitive tasks have estimates
- [  ] **Priorities:** 100% of tasks have priority levels assigned
- [  ] **Vendor Types:** All vendor tasks have vendor_type field
- [  ] **Validation:** All YAML files parse correctly
- [  ] **Documentation:** Phase 4 completion report created

---

## 🚀 **Quick Start Checklist**

### **Before Starting Phase 4:**
- [ ] Review MA General Laws chapters (111, 148, 183A, 60)
- [ ] Review 310 CMR 15.000 (Title 5)
- [ ] Interview 2-3 local real estate attorneys for typical timelines
- [ ] Interview 2-3 experienced agents for vendor scheduling
- [ ] Gather citation URLs from Mass.gov

### **During Phase 4:**
- [ ] Create metadata spreadsheet from current tasks
- [ ] Research citations for each compliance task
- [ ] Document typical durations from agent/attorney input
- [ ] Classify all tasks by priority level
- [ ] Create Python scripts for bulk updates
- [ ] Apply updates and validate YAML
- [ ] Create Phase 4 completion report

---

## 📝 **Notes & Considerations**

### **Citation Best Practices:**
- Always link to official Mass.gov or MaLegislature.gov sources
- Include both statute number AND descriptive title
- For federal regs, link to official EPA/HUD sources
- Keep URLs permanent (avoid date-specific pages)

### **Duration Best Practices:**
- Be realistic (don't promise faster than typical)
- Include contingencies ("typically X, but may take Y if...")
- Note seasonal variations if applicable
- Distinguish between "time to schedule" vs "time to complete"

### **Priority Best Practices:**
- When in doubt, mark "high" rather than "critical"
- Reserve "critical" for true closing blockers
- Consider user experience (too many critical = alarm fatigue)
- Use priority to drive reminder frequency

---

## 🎯 **Next Steps**

1. ✅ Create Phase 4 implementation plan (this document)
2. ⏳ Extract current tasks to metadata spreadsheet
3. ⏳ Research legal citations for compliance tasks
4. ⏳ Gather duration estimates from domain experts
5. ⏳ Classify all tasks by priority
6. ⏳ Create update scripts
7. ⏳ Apply metadata enhancements
8. ⏳ Validate and test
9. ⏳ Create Phase 4 completion report

---

**Status:** 📋 **PLAN COMPLETE - READY TO EXECUTE**
**Prerequisites:** Domain knowledge (MA real estate), agent/attorney input
**Estimated Effort:** 2-3 weeks (research + implementation)
**Impact:** Significantly improved task guidance and user experience

---

*Phase 4 Implementation Plan*
*Created: 2025-10-31*
*MA Deal Room Template Cleanup Project*
