# QualiGxP Document Import Tutorial

Use document import to bring existing customer SOPs, policies, manuals, and records into QualiGxP without recreating them manually.

## Before importing

Prepare the customer files:

- Supported document formats: PDF, DOC, and DOCX.
- Remove duplicate copies where possible.
- Use clear filenames, preferably including the existing document number.
- For large migrations, place the documents in one ZIP file.
- Keep a copy of the original customer files outside the application until migration verification is complete.

## Import individual or multiple files

1. Open **DMS → Controlled Documents**.
2. Select **Import Documents**.
3. Enter an optional import batch name, such as `ABC Pharma SOP Migration`.
4. Select one or more PDF or Word files.
5. Select **Import**.

QualiGxP stores the original files privately and creates an import batch. Each file is processed independently, so a problem with one file does not stop the remaining files.

## Import a ZIP batch

1. Open **DMS → Controlled Documents**.
2. Select **Import ZIP Batch**.
3. Enter an optional batch name.
4. Select the ZIP archive.
5. Select **Import**.

The ZIP should contain the customer documents at any folder level. QualiGxP ignores folders and imports supported PDF, DOC, and DOCX files.

Do not include executable files or confidential files that are not intended for migration.

## Optional CSV metadata

Add a CSV file inside the ZIP to provide document metadata. The CSV must include a `filename` column matching the document filename.

Example:

```csv
filename,document_number,title,document_type,category,department,revision,effective_date,review_date
SOP-001.pdf,SOP-001,Cleaning Procedure,SOP,Quality,Production,3,2024-05-01,2025-05-01
SOP-002.docx,SOP-002,Deviation Handling,SOP,Quality,Quality Assurance,2,2024-04-15,2025-04-15
```

Metadata is retained with each imported item for review. A CSV does not automatically approve or activate a document.

## Monitor the import

Open **DMS → Import Batches**.

Each batch shows:

- Total files
- Successfully imported files
- Failed files
- Batch status
- Import user
- Import date

Processing occurs in the background. You may leave the page while the import continues.

## Batch statuses

- **Processing** — files are still being handled.
- **Completed** — every file was imported successfully.
- **Completed with errors** — some files succeeded and some failed.
- **Failed** — no files were successfully imported or the batch could not be completed.

## Retry failed files

1. Open **DMS → Import Batches**.
2. Select **Retry Failed Items**.
3. Correct or replace any source files that caused the failure.
4. Monitor the batch until it completes.

QualiGxP automatically retries temporary processing failures up to three times. The manual retry action is useful for files that were missing, unsupported, or corrupted.

## After import

Imported files are preserved as original artifacts and should be reviewed before becoming active controlled documents.

For each document:

1. Confirm the title and document number.
2. Confirm document type, category, department, owner, revision, and dates.
3. Verify that the original file opens correctly.
4. Confirm that the content is current and complete.
5. Submit the document through the normal QualiGxP review and approval workflow.

An imported document must not be treated as approved merely because it was approved in the customer’s previous system. Its QualiGxP approval history begins when it is reviewed and approved inside QualiGxP.

## Troubleshooting

**File is rejected:** Confirm that it is PDF, DOC, or DOCX and that it is not corrupted.

**CSV metadata is missing:** Confirm that the CSV is inside the ZIP and that its `filename` values exactly match the document filenames.

**A batch has errors:** Open the batch, review the failed items, correct the source files, and use **Retry Failed Items**.

**A document is not active:** This is expected until its metadata and content have been verified and it completes the QualiGxP approval workflow.
