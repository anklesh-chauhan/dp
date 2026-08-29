---
paths:
  - 'app/{Ai,Services/AI,Filament/Pages}/**'
---

# Pages

## Keep the GxP assistant local and read-only
The general QualiGxP assistant may retrieve only records authorized for the current user and must return citation URLs. It has no mutation, approval, or signature tools. Route internal GxP assistant data only to locally configured Ollama unless a separately reviewed data-classification decision explicitly changes this boundary.

## Reconcile draft assistant output server-side
For controlled-document drafting, treat the model's missing_details, ready_for_preview, and assistant_message as untrusted suggestions. Compute missing required fields and readiness from template definitions plus normalized session values, preserve existing non-empty values when a model returns blanks, and persist/render the reconciled assistant response so chat text cannot contradict draft state.
