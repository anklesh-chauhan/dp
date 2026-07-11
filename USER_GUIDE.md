# User Guide: Document Categorization & Type Selection in QMS

Welcome to the Quality Management System (QMS) document creation guide. This guide is designed to help you quickly determine **when to create a document**, **which category to select**, and **which document type to assign**. 

Proper classification ensures regulatory compliance (including WHO_GMP, US_FDA_210_211, and INDIA_DPCO) and maintains the architectural integrity of our quality records.

---

## 1. Understand the 3 Main Kinds of Documents

In a pharmaceutical QMS, every document falls into one of three primary structural categories. In the application, these are represented by specific **Document Types**.

### 🏛️ Category A: Policy / Manual
*Defines rules, high-level direction, and foundational principles.*
* **Characteristics:** Long-term applicability, signed by executive leadership, non-procedural.
* **QMS App Document Types:** `POLICY`, `MANUAL`
* **Examples:** Quality Policy, Data Integrity Policy, Quality Manual.

### ⚙️ Category B: SOP / Work Instruction / Plan
*Describes how to perform processes or tasks step by step.*
* **Characteristics:** Action-oriented, procedural, role-specific, mandatory execution sequences.
  * **SOP (Standard Operating Procedure):** Department-wide or cross-functional processes.
  * **Work Instruction:** Micro-level detailed steps for a specific task, machine, or line.
  * **Plan:** A structured roadmap or approach for an objective or timeline.
* **QMS App Document Types:** `SOP`, `WORK_INSTRUCTION`, `PLAN`
* **Examples:** SOP for Document Control, Equipment Qualification Plan, GMP Training SOP.

### 📝 Category C: Template / Form / Record / Log
*Used to capture data, log data, or prove an activity was completed.*
* **Characteristics:** Blank templates or the actual completed outputs containing raw data.
  * **Template:** The blank structure or layout you fill out repeatedly.
  * **Form:** A dynamic document formatted to capture data for a specific event.
  * **Record / Log:** The finalized, completed document showing exact historical execution.
* **QMS App Document Types:** `TEMPLATE`, `FORM`, `RECORD`, `LOG`
* **Examples:** Batch Production Record (Template), Calibration Log, Deviation Log.

---

## 2. Choosing the Right Category

To select the correct system category, ask yourself: **“Which specific operational area or business function does this document mainly affect?”**

Review the 10 distinct system categories below to find the correct match, along with standard examples and regulatory compliance tags.

### 1. Quality Management System (QMS) Core
* **When to use:** The document governs how the *entire* QMS operates globally, rather than focusing on a single operational department.
* **Applicability:** Applies to all departments or is managed centrally by Quality Assurance (QA).
* **Regulations:** `INDIA_DPCO`, `WHO_GMP`, `US_FDA_210_211`
* **Standard Elements:**
  * Quality Manual
  * Quality Policy
  * QMS Scope & Objectives
  * Document Control SOP / Record Control SOP
  * Change Control SOP / CAPA SOP / Deviation Management SOP
  * Internal Audit SOP / Management Review SOP

### 2. Facility & Equipment
* **When to use:** The document is explicitly about buildings, physical utilities, plant machinery, and hardware assets.
* **Applicability:** Premises, water purification systems, HVAC, mechanical maintenance, and calibration cycles.
* **Regulations:** `WHO_GMP`, `US_FDA_210_211`, `INDIA_DPCO + Drugs & Cosmetics Rules`
* **Standard Elements:**
  * Facility Design & Maintenance SOP
  * Utilities (Water, HVAC, Air) SOP
  * Equipment Selection & Qualification Policy
  * Equipment Qualification Plan (IQ/OQ/PQ)
  * Equipment Calibration SOP / Equipment Maintenance SOP
  * Cleaning & Sanitation SOP (Facility)
  * Preventive Maintenance Log / Calibration Log

### 3. Materials & Warehouse
* **When to use:** The document covers the lifecycle of raw materials, active pharmaceutical ingredients (APIs), excipients, packaging materials, and storage logistics.
* **Applicability:** Procurement, purchasing, receiving, quarantine, material testing, storage conditions, and material disposition.
* **Regulations:** `WHO_GMP`, `US_FDA_210_211`, `INDIA_DPCO + Rules`
* **Standard Elements:**
  * Material Procurement SOP / Vendor Qualification SOP
  * Raw Material Receipt & Inspection SOP / Sampling SOP (Materials)
  * Material Storage & Handling SOP / Warehouse Temperature Monitoring SOP
  * Expired / Rejected Material SOP / Material Disposition SOP
  * Receiving Log / Storage Condition Log / Material Disposition Record

### 4. Production / Manufacturing
* **When to use:** The document relates directly to active manufacturing processes, compounding, formulation, processing lines, and packaging operations.
* **Applicability:** Step-by-step production runs, line clearance, processing steps, yield monitoring, and primary/secondary packaging.
* **Regulations:** `WHO_GMP`, `US_FDA_210_211`, `INDIA_DPCO + Rules`
* **Standard Elements:**
  * Master Production Instruction (MPI) Template
  * Batch Production Record (BPR) Template
  * Line Clearance SOP / In-process Control SOP
  * Weighing & Dispensing SOP / Packaging SOP / Line Changeover SOP
  * Production Deviation Log / Batch Disposition Record / Yield Calculation Record

### 5. Quality Control (QC) & Laboratory
* **When to use:** The document controls laboratory analytics, chemical/microbiological testing, instrument assays, and sample analysis.
* **Applicability:** Lab testing methodologies, wet chemistry, chromatography, reference standards, and Out-of-Specification (OOS) results.
* **Regulations:** `WHO_GMP`, `US_FDA_210_211`, `INDIA_DPCO + Rules`
* **Standard Elements:**
  * Laboratory Organization & Responsibilities SOP
  * Sample Collection SOP / Analytical Method Validation SOP
  * Method Transfer SOP / Standard Preparation SOP
  * Instrument Qualification & Calibration SOP
  * Chromatography System SOP / Reference Standard Management SOP
  * OOS/OOT Investigation SOP / Lab Deviation Log
  * Analytical Test Report Template / COA (Certificate of Analysis) Template

### 6. Validation & Qualification
* **When to use:** The document validates that a system, process, equipment piece, or software environment repeatedly yields compliant outputs.
* **Applicability:** Process, cleaning, software, or computer systems validation protocols, execution reports, and data integrity infrastructure.
* **Regulations:** `WHO_GMP`, `US_FDA_210_211 + 21 CFR Part 11`, `INDIA_DPCO + Rules`
* **Standard Elements:**
  * Validation Master Plan (VMP) Template
  * Process Validation Plan SOP / Process Validation Protocol & Report Templates
  * Cleaning Validation Plan/Protocol/Report Templates
  * Computerized Systems Validation (CSV) SOP
  * Data Integrity Policy / Electronic Records & Signatures SOP (21 CFR Part 11)
  * Validation Log

### 7. Compliance & Risk Management
* **When to use:** The document targets systemic risk assessments, deviation investigations, root-cause resolution, product failures, and regulatory non-conformances.
* **Applicability:** CAPA paths, formal complaints, market recalls, self-inspections, and failure mode analysis.
* **Regulations:** `WHO_GMP`, `US_FDA_210_211`, `INDIA_DPCO + Rules`
* **Standard Elements:**
  * Risk Assessment Methodology SOP / Product Quality Risk Assessment Template
  * Deviation Management SOP / Investigation SOP (Root Cause Analysis) / CAPA SOP
  * Complaint Handling SOP / Product Recall SOP
  * Self-Inspection / Internal Audit SOP
  * Audit Report Template / Non-Conformance Report Template

### 8. Training & Personnel
* **When to use:** The document defines staff training programs, organizational charts, job descriptions, and personnel evaluations.
* **Applicability:** Employee onboarding, continuous GMP training, technical training matrices, and qualifications.
* **Regulations:** `WHO_GMP`, `US_FDA_210_211`, `INDIA_DPCO + Rules`
* **Standard Elements:**
  * Training Policy / Training Needs Assessment SOP
  * Onboarding Training SOP / GMP Training SOP
  * Job Description Template / Training Record Template
  * Training Matrix Template / Competency Assessment Template / Training Log

### 9. Distribution & Supply Chain
* **When to use:** The document covers finished product storage, external transportation, logistics partnerships, and outbound supply chains.
* **Applicability:** Cold chain parameters, distribution routing, transit logging, and physical market recall execution.
* **Regulations:** `WHO_GMP`, `US_FDA_210_211`, `INDIA_DPCO + Rules`
* **Standard Elements:**
  * Distribution SOP / Cold Chain Management SOP
  * Transportation & Storage Conditions SOP
  * Supplier/Customer Agreement Template
  * Recall Execution Record
  * Distribution Log / Temperature Monitoring Record (Transport)

### 10. Regulatory & Product Licensing
* **When to use:** The document deals with dossier compilation, marketing authorizations, licensing applications, regulatory changes, and compliance labeling.
* **Applicability:** Communications with regulatory bodies (CDSCO, FDA, WHO), license upkeep, and labeling verification.
* **Regulations:** `INDIA_DPCO + Drugs & Cosmetics Rules`, `WHO_GMP`, `US_FDA_210_211`
* **Standard Elements:**
  * Product License Application Checklist (India)
  * GMP Certificate Application Checklist
  * Regulatory Submission SOP / Label Review SOP / Labeling Compliance Checklist
  * Regulatory Change Control SOP
  * Dossiers / Technical File Templates

---

## 3. Quick Decision Flow

Follow these two quick steps when creating any new document entry in the system.

### Step 1: Identify the Document Type
* Ask yourself: **"What is the core format and objective of this document?"**
  * It is a high-level directive or organizational rule $ightarrow$ Choose **`POLICY`** or **`MANUAL`**.
  * It details sequential action or a concrete strategy $ightarrow$ Choose **`SOP`**, **`WORK_INSTRUCTION`**, or **`PLAN`**.
  * It captures fields, blank lines, data tables, or raw inputs $ightarrow$ Choose **`TEMPLATE`**, **`FORM`**, **`RECORD`**, or **`LOG`**.

### Step 2: Identify the Operational Area
* Ask yourself: **"Where is the document executed or managed?"**
  * Global/Centrally by QA $ightarrow$ **QMS Core**
  * Facilities/Utilities/Eng $ightarrow$ **Facility & Equipment**
  * Receiving/Stores/Warehouse $ightarrow$ **Materials & Warehouse**
  * Manufacturing Floors $ightarrow$ **Production / Manufacturing**
  * Testing Benches/Labs $ightarrow$ **QC & Laboratory**
  * Validation protocols/CSV $ightarrow$ **Validation & Qualification**
  * Audits/CAPAs/Complaints $ightarrow$ **Compliance & Risk Management**
  * HR/Training coordinators $ightarrow$ **Training & Personnel**
  * Shipping/Transit/Logistics $ightarrow$ **Distribution & Supply Chain**
  * Regulatory Affairs/Licensing $ightarrow$ **Regulatory & Product Licensing**

---

## 4. Quick-Reference Cross-Mapping Matrix

Use this definitive index table to look up specific document templates or items and verify their configuration inside the QMS application.

| I want to create... | Correct Category Selection | Correct Document Type |
| :--- | :--- | :--- |
| Quality Manual | QMS Core | `MANUAL` |
| Quality Policy | QMS Core | `POLICY` |
| QMS Scope & Objectives | QMS Core | `POLICY` |
| Document Control SOP | QMS Core | `SOP` |
| Record Control SOP | QMS Core | `SOP` |
| Change Control SOP | QMS Core | `SOP` |
| CAPA SOP | QMS Core / Compliance & Risk | `SOP` |
| Deviation Management SOP | QMS Core / Compliance & Risk | `SOP` |
| Internal Audit SOP | QMS Core / Compliance & Risk | `SOP` |
| Management Review SOP | QMS Core | `SOP` |
| Facility Design & Maintenance SOP | Facility & Equipment | `SOP` |
| Utilities SOP | Facility & Equipment | `SOP` |
| Equipment Selection & Qualification Policy | Facility & Equipment | `POLICY` |
| Equipment Qualification Plan | Facility & Equipment / Validation | `PLAN` |
| Equipment Calibration SOP | Facility & Equipment / QC & Lab | `SOP` |
| Equipment Maintenance SOP | Facility & Equipment | `SOP` |
| Preventive Maintenance Log | Facility & Equipment | `LOG` |
| Calibration Log | Facility & Equipment / QC & Lab | `LOG` |
| Material Procurement SOP | Materials & Warehouse | `SOP` |
| Vendor Qualification SOP | Materials & Warehouse | `SOP` |
| Raw Material Receipt & Inspection SOP | Materials & Warehouse | `SOP` |
| Sampling SOP (Materials) | Materials & Warehouse | `SOP` |
| Material Storage & Handling SOP | Materials & Warehouse | `SOP` |
| Warehouse Temperature Monitoring SOP | Materials & Warehouse | `SOP` |
| Expired / Rejected Material SOP | Materials & Warehouse | `SOP` |
| Material Disposition SOP | Materials & Warehouse | `SOP` |
| Receiving Log | Materials & Warehouse | `LOG` |
| Storage Condition Log | Materials & Warehouse | `LOG` |
| Material Disposition Record | Materials & Warehouse | `RECORD` |
| Master Production Instruction (MPI) Template | Production / Manufacturing | `TEMPLATE` |
| Batch Production Record (BPR) Template | Production / Manufacturing | `TEMPLATE` |
| Line Clearance SOP | Production / Manufacturing | `SOP` |
| In-process Control SOP | Production / Manufacturing | `SOP` |
| Weighing & Dispensing SOP | Production / Manufacturing | `SOP` |
| Packaging SOP | Production / Manufacturing | `SOP` |
| Line Changeover SOP | Production / Manufacturing | `SOP` |
| Production Deviation Log | Production / Manufacturing / Compliance & Risk | `LOG` |
| Batch Disposition Record | Production / Manufacturing | `RECORD` |
| Yield Calculation Record | Production / Manufacturing | `RECORD` |
| Laboratory Organization & Responsibilities SOP | QC & Laboratory | `SOP` |
| Sample Collection SOP | QC & Laboratory | `SOP` |
| Analytical Method Validation SOP | QC & Laboratory | `SOP` |
| Method Transfer SOP | QC & Laboratory | `SOP` |
| Standard Preparation SOP | QC & Laboratory | `SOP` |
| Instrument Qualification & Calibration SOP | QC & Laboratory / Facility & Equipment | `SOP` |
| Chromatography System SOP | QC & Laboratory | `SOP` |
| Reference Standard Management SOP | QC & Laboratory | `SOP` |
| OOS/OOT Investigation SOP | QC & Laboratory | `SOP` |
| Lab Deviation Log | QC & Laboratory | `LOG` |
| Analytical Test Report Template | QC & Laboratory | `TEMPLATE` |
| COA Template | QC & Laboratory | `TEMPLATE` |
| Validation Master Plan (VMP) Template | Validation & Qualification | `TEMPLATE` |
| Process Validation Plan SOP | Validation & Qualification | `SOP` |
| Process Validation Protocol Template | Validation & Qualification | `TEMPLATE` |
| Process Validation Report Template | Validation & Qualification | `TEMPLATE` |
| Cleaning Validation Plan/Protocol/Report Templates | Validation & Qualification | `TEMPLATE` |
| Computerized Systems Validation (CSV) SOP | Validation & Qualification | `SOP` |
| Data Integrity Policy | Validation & Qualification / QMS Core | `POLICY` |
| Electronic Records & Signatures SOP | Validation & Qualification / QMS Core | `SOP` |
| Validation Log | Validation & Qualification | `LOG` |
| Risk Assessment Methodology SOP | Compliance & Risk Management | `SOP` |
| Product Quality Risk Assessment Template | Compliance & Risk Management | `TEMPLATE` |
| Investigation SOP (Root Cause Analysis) | Compliance & Risk Management | `SOP` |
| Complaint Handling SOP | Compliance & Risk Management | `SOP` |
| Product Recall SOP | Compliance & Risk Management | `SOP` |
| Self-Inspection / Internal Audit SOP | Compliance & Risk Management / QMS Core | `SOP` |
| Audit Report Template | Compliance & Risk Management | `TEMPLATE` |
| Non-Conformance Report Template | Compliance & Risk Management | `TEMPLATE` |
| Training Policy | Training & Personnel | `POLICY` |
| Training Needs Assessment SOP | Training & Personnel | `SOP` |
| Onboarding Training SOP | Training & Personnel | `SOP` |
| GMP Training SOP | Training & Personnel | `SOP` |
| Job Description Template | Training & Personnel | `TEMPLATE` |
| Training Record Template | Training & Personnel | `TEMPLATE` |
| Training Matrix Template | Training & Personnel | `TEMPLATE` |
| Competency Assessment Template | Training & Personnel | `TEMPLATE` |
| Training Log | Training & Personnel | `LOG` |
| Distribution SOP | Distribution & Supply Chain | `SOP` |
| Cold Chain Management SOP | Distribution & Supply Chain | `SOP` |
| Transportation & Storage Conditions SOP | Distribution & Supply Chain | `SOP` |
| Supplier/Customer Agreement Template | Distribution & Supply Chain | `TEMPLATE` |
| Recall Execution Record | Distribution & Supply Chain / Compliance & Risk | `RECORD` |
| Distribution Log | Distribution & Supply Chain | `LOG` |
| Temperature Monitoring Record (Transport) | Distribution & Supply Chain | `RECORD` |
| Product License Application Checklist (India) | Regulatory & Product Licensing | `TEMPLATE` / `FORM` |
| GMP Certificate Application Checklist | Regulatory & Product Licensing | `TEMPLATE` / `FORM` |
| Regulatory Submission SOP | Regulatory & Product Licensing | `SOP` |
| Label Review SOP | Regulatory & Product Licensing | `SOP` |
| Labeling Compliance Checklist | Regulatory & Product Licensing | `TEMPLATE` / `FORM` |
| Regulatory Change Control SOP | Regulatory & Product Licensing / QMS Core | `SOP` |
| Dossiers / Technical File Templates | Regulatory & Product Licensing | `TEMPLATE` |
