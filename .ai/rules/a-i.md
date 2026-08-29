---
paths:
  - 'app/Services/AI/**'
---

# A I

## Canonicalize AI draft variables before confirmation
AI drafting may return display labels for relationship and choice variables, while the DMS resolver requires canonical IDs/option keys. Normalize these values before saving previews and again when confirming legacy previews. Map effective_date and review_date into ControlledDocumentData so SopGeneratorService does not overwrite accepted dates with null.
