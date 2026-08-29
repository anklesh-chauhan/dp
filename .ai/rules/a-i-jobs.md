---
paths:
  - 'app/{Filament/Pages,Services/AI,Jobs}/**'
---

# A I Jobs

## Queue long controlled-document drafting turns
Never run a controlled-document drafting model call inside the Filament request. Create a durable ControlledDocumentDraftRequest, dispatch ProcessControlledDocumentDraftRequest after commit, and expose queued/processing/completed/failed state through polling. Preserve one active request per session and the preview-revision idempotency check on retries.
