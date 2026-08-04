import fs from 'node:fs/promises';
import { SpreadsheetFile, Workbook } from '@oai/artifact-tool';

const deps = 'C:/Users/anklesh_chauhan/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules';
const outputDir = 'C:/Herd/docupharma/outputs/permission-matrix';
await fs.mkdir(outputDir, { recursive: true });

const workbook = Workbook.create();
const instructions = workbook.worksheets.add('Instructions');
const matrix = workbook.worksheets.add('Permission Matrix');

const navy = '#17365D';
const blue = '#D9EAF7';
const lightBlue = '#EEF5FB';
const green = '#E2F0D9';
const yellow = '#FFF2CC';
const gray = '#F2F2F2';
const border = '#B7C9D6';

instructions.showGridLines = false;
instructions.getRange('A1:F1').merge();
instructions.getRange('A1').values = [['Customer Permission Matrix – Document Management Workflow']];
instructions.getRange('A1:F1').format = { fill: navy, font: { bold: true, color: '#FFFFFF', size: 16 }, horizontalAlignment: 'center', verticalAlignment: 'center' };
instructions.getRange('A1:F1').format.rowHeight = 32;
instructions.getRange('A3:B7').values = [
  ['Purpose', 'Confirm which permissions should be assigned to each user role before implementation.'],
  ['How to complete', 'Review the recommended baseline, then select Yes or No in the Customer Decision columns.'],
  ['Role definitions', 'Maker creates and updates documents; Checker verifies quality; Approval provides final authorization; Document Controller manages records and configuration.'],
  ['Decision rule', 'Use least privilege. Grant only the permissions needed for the role’s responsibilities.'],
  ['Return to implementation team', 'Share the completed workbook with the approved role owners and implementation contact.'],
];
instructions.getRange('A3:A7').format = { fill: blue, font: { bold: true, color: navy }, verticalAlignment: 'top' };
instructions.getRange('B3:B7').format = { fill: lightBlue, wrapText: true, verticalAlignment: 'top' };
instructions.getRange('A3:B7').format.borders = { preset: 'all', style: 'thin', color: border };
instructions.getRange('A9:F9').merge();
instructions.getRange('A9').values = [['Legend']];
instructions.getRange('A9:F9').format = { fill: navy, font: { bold: true, color: '#FFFFFF' } };
instructions.getRange('A10:B12').values = [['Yes', 'Permission is requested'], ['No', 'Permission is not requested'], ['Customer Decision', 'Customer-approved value to be implemented']];
instructions.getRange('A10:A12').format = { font: { bold: true }, horizontalAlignment: 'center' };
instructions.getRange('B10:B12').format.wrapText = true;
instructions.getRange('A10:B12').format.borders = { preset: 'all', style: 'thin', color: border };
instructions.getRange('A10').format.fill = green;
instructions.getRange('A11').format.fill = gray;
instructions.getRange('A12').format.fill = yellow;
instructions.getRange('A:A').format.columnWidth = 22;
instructions.getRange('B:B').format.columnWidth = 72;
instructions.getRange('C:F').format.columnWidth = 14;

matrix.showGridLines = false;
matrix.freezePanes.freezeRows(6);
matrix.getRange('A1:L1').values = [['Customer Permission Matrix – Document Management Workflow', null, null, null, null, null, null, null, null, null, null, null]];
matrix.getRange('A1:N1').format = { fill: navy, font: { bold: true, color: '#FFFFFF', size: 16 }, horizontalAlignment: 'center', verticalAlignment: 'center' };
matrix.getRange('A1:N1').format.rowHeight = 32;
matrix.getRange('A3:B4').values = [['Customer / Project', ''], ['Prepared / Reviewed by', '']];
matrix.getRange('A3:A4').format = { fill: blue, font: { bold: true, color: navy } };
matrix.getRange('B3:B4').format = { fill: yellow };
matrix.getRange('B3:B4').format.borders = { preset: 'outside', style: 'thin', color: border };

const headers = ['Permission Area', 'Permission', 'Maker – Recommended', 'Maker – Customer Decision', 'Checker – Recommended', 'Checker – Customer Decision', 'Approval – Recommended', 'Approval – Customer Decision', 'Document Controller – Recommended', 'Document Controller – Customer Decision', 'Notes / Restrictions', 'Owner / Comments'];
matrix.getRange('A6:L6').values = [headers];
matrix.getRange('A6:L6').format = { fill: navy, font: { bold: true, color: '#FFFFFF' }, wrapText: true, horizontalAlignment: 'center', verticalAlignment: 'center' };
matrix.getRange('A6:L6').format.rowHeight = 42;

const rows = [
  ['Document lifecycle', 'Create new document', 'Yes', '', 'No', '', 'No', '', 'Yes', '', 'Document type and template restrictions may apply', ''],
  ['Document lifecycle', 'Edit document before submission', 'Yes', '', 'No', '', 'No', '', 'Yes', '', 'Maker edits working copies only', ''],
  ['Document lifecycle', 'Submit document for checking', 'Yes', '', 'No', '', 'No', '', 'Yes', '', 'Submission creates the review task', ''],
  ['Document lifecycle', 'Return document for correction', 'No', '', 'Yes', '', 'No', '', 'Yes', '', 'Checker may return to Maker with comments', ''],
  ['Document lifecycle', 'Approve document', 'No', '', 'No', '', 'Yes', '', 'No', '', 'Final approval authority only', ''],
  ['Document lifecycle', 'Reject document', 'No', '', 'Yes', '', 'Yes', '', 'No', '', 'Reason should be recorded', ''],
  ['Document lifecycle', 'View document and workflow history', 'Yes', '', 'Yes', '', 'Yes', '', 'Yes', '', 'Limit by department / business unit if required', ''],
  ['Document lifecycle', 'Download / print document', 'Yes', '', 'Yes', '', 'Yes', '', 'Yes', '', 'Consider confidential-document controls', ''],
  ['Document lifecycle', 'Archive / withdraw document', 'No', '', 'No', '', 'No', '', 'Yes', '', 'Document Controller executes controlled archival', ''],
  ['Document lifecycle', 'Restore archived document', 'No', '', 'No', '', 'No', '', 'Yes', '', 'Use only under documented change control', ''],
  ['Quality & compliance', 'Add review comments', 'Yes', '', 'Yes', '', 'Yes', '', 'Yes', '', 'Comments remain part of the audit trail', ''],
  ['Quality & compliance', 'Complete checklist / verification', 'No', '', 'Yes', '', 'No', '', 'Yes', '', 'Checker confirms completeness and compliance', ''],
  ['Quality & compliance', 'View audit trail', 'No', '', 'Yes', '', 'Yes', '', 'Yes', '', 'Read-only access recommended', ''],
  ['Administration', 'Manage users and role assignments', 'No', '', 'No', '', 'No', '', 'Yes', '', 'May be restricted to nominated administrators', ''],
  ['Administration', 'Manage document types / templates', 'No', '', 'No', '', 'No', '', 'Yes', '', 'Configuration changes should be approved', ''],
  ['Administration', 'Configure workflow / approval routes', 'No', '', 'No', '', 'No', '', 'Yes', '', 'Separate system-admin permission may be required', ''],
  ['Administration', 'Manage metadata / classifications', 'No', '', 'No', '', 'No', '', 'Yes', '', 'Define mandatory metadata with customer', ''],
  ['Administration', 'Run reports / export records', 'Yes', '', 'Yes', '', 'Yes', '', 'Yes', '', 'Apply data-visibility restrictions', ''],
];
matrix.getRange(`A7:L${6 + rows.length}`).values = rows;
matrix.getRange(`A7:L${6 + rows.length}`).format = { wrapText: true, verticalAlignment: 'top' };
matrix.getRange(`A7:L${6 + rows.length}`).format.borders = { insideHorizontal: { style: 'thin', color: border }, insideVertical: { style: 'thin', color: border }, bottom: { style: 'thin', color: border } };
matrix.getRange(`A7:A${6 + rows.length}`).format = { fill: lightBlue, font: { bold: true, color: navy }, verticalAlignment: 'top', wrapText: true };
matrix.getRange(`C7:C${6 + rows.length}`).format.fill = green;
matrix.getRange(`E7:E${6 + rows.length}`).format.fill = green;
matrix.getRange(`G7:G${6 + rows.length}`).format.fill = green;
matrix.getRange(`I7:I${6 + rows.length}`).format.fill = green;
matrix.getRange(`D7:D${6 + rows.length}`).format.fill = yellow;
matrix.getRange(`F7:F${6 + rows.length}`).format.fill = yellow;
matrix.getRange(`H7:H${6 + rows.length}`).format.fill = yellow;
matrix.getRange(`J7:J${6 + rows.length}`).format.fill = yellow;
matrix.getRange(`D7:D${6 + rows.length}`).dataValidation = { rule: { type: 'list', values: ['Yes', 'No', 'Conditional'] } };
matrix.getRange(`F7:F${6 + rows.length}`).dataValidation = { rule: { type: 'list', values: ['Yes', 'No', 'Conditional'] } };
matrix.getRange(`H7:H${6 + rows.length}`).dataValidation = { rule: { type: 'list', values: ['Yes', 'No', 'Conditional'] } };
matrix.getRange(`J7:J${6 + rows.length}`).dataValidation = { rule: { type: 'list', values: ['Yes', 'No', 'Conditional'] } };
matrix.getRange(`C7:J${6 + rows.length}`).format.horizontalAlignment = 'center';

matrix.getRange(`A${8 + rows.length}:L${8 + rows.length}`).merge();
matrix.getRange(`A${8 + rows.length}`).values = [['Customer approval / implementation notes:']];
matrix.getRange(`A${8 + rows.length}:L${8 + rows.length}`).format = { fill: blue, font: { bold: true, color: navy } };
matrix.getRange(`A${9 + rows.length}:L${11 + rows.length}`).merge();
matrix.getRange(`A${9 + rows.length}`).values = [['']];
matrix.getRange(`A${9 + rows.length}:L${11 + rows.length}`).format = { fill: yellow, wrapText: true, verticalAlignment: 'top', borders: { preset: 'outside', style: 'thin', color: border } };

matrix.getRange('A:A').format.columnWidth = 20;
matrix.getRange('B:B').format.columnWidth = 31;
for (const col of ['C','D','E','F','G','H']) matrix.getRange(`${col}:${col}`).format.columnWidth = 17;
matrix.getRange('I:I').format.columnWidth = 22;
matrix.getRange('J:J').format.columnWidth = 19;
matrix.getRange('K:K').format.columnWidth = 44;
matrix.getRange('L:L').format.columnWidth = 28;
matrix.getRange(`A7:L${6 + rows.length}`).format.rowHeight = 34;
matrix.getRange(`K7:K${6 + rows.length}`).format.rowHeight = 44;

const preview = await workbook.render({ sheetName: 'Permission Matrix', range: `A1:L${11 + rows.length}`, scale: 1, format: 'png' });
await fs.writeFile(`${outputDir}/permission-matrix-preview.png`, new Uint8Array(await preview.arrayBuffer()));
const check = await workbook.inspect({ kind: 'table', range: `Permission Matrix!A1:L${11 + rows.length}`, include: 'values,formulas', tableMaxRows: 8, tableMaxCols: 12 });
console.log(check.ndjson);
const errors = await workbook.inspect({ kind: 'match', searchTerm: '#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A', options: { useRegex: true, maxResults: 100 }, summary: 'final formula error scan' });
console.log(errors.ndjson);
const output = await SpreadsheetFile.exportXlsx(workbook);
await output.save(`${outputDir}/customer-permission-matrix.xlsx`);
