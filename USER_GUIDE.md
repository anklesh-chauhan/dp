# Docupharma User Guide

## Overview
Docupharma is an SOP management system built in Laravel with Filament. It supports:
- SOP Templates
- SOP Template Versions
- SOP Template Sections
- SOP Template Variables
- SOP Documents
- SOP Document Sections
- SOP Document Variables
- SOP Workflows and Approval Steps
- Audit logs

## SOP Templates
### Purpose
Templates define the structure of an SOP before it is published.

### Core workflow
1. Create a template.
2. Add template versions.
3. Add sections and variables to a version.
4. Publish the template version.

### Key fields
- Name
- Code
- Department
- Category
- Document type
- Status
- Current version
- Description

### Version management
- Each template version belongs to a template.
- Draft versions can be edited.
- Publish a draft version to make it available for SOP document generation.
- The published version is the source for document sections and variables.

### Sections
- Sections belong to a template version.
- Each section has:
  - Version
  - Title
  - Order
  - Content
  - Type
  - Required flag

### Variables
- Variables belong to a template version.
- Each variable has:
  - Name
  - Label
  - Data type
  - Default value
  - Validation rules
  - Required flag

## SOP Documents
### Purpose
Documents are generated from published templates and capture the final SOP content.

### Creation flow
- Create a document by choosing a template and (optionally) a published version.
- Provide a title, owner, effective date, review date, and template variable values.
- The system auto-generates sections and resolves variables.

### What is generated
- Document sections are copied from the selected published template version.
- Variable placeholders in section content are replaced with the provided values.
- Document variables are saved for auditing and future reference.

### Editable fields
- Owner
- Effective date
- Review date
- Title

### Related data
- Sections: detailed content blocks within the SOP document.
- Variables: final values used in the document.
- Approvals: workflow approval records.
- Audit logs: history of generated changes and actions.

## SOP Workflows
### Purpose
Workflows define approval steps for SOP documents.

### Core workflow
1. Create a workflow.
2. Add steps with role approvals.
3. Associate workflow approval records with documents.

### Approval actions
- Approve document
- Reject document

## Users and Roles
- Users can be assigned as owners and approvers.
- Roles are used in workflow steps.

## Project status
### Current status
- The template model and versioning system exist.
- The generator service exists and now integrates with document creation.
- Sections and variables are created during SOP generation.

### Notes
- SOP document creation should now use the published template version and resolve variable values.
- The default Filament create form has been updated to route creation through the generator service.

## Troubleshooting
### If sections are missing
- Confirm the chosen template is published.
- Confirm its selected version is published.

### If variables are not resolving
- Validate variable names match the `{{ variable }}` syntax.
- Provide required variable values when generating the document.

## Recommended next steps
- Test creating a new template, publish a version, then generate a document.
- Verify sections and variables appear in the new document record.
- Review audit logs and approvals for generated documents.
