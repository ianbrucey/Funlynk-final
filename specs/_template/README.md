# specs/_template — how to use

Copy this whole `_template/` folder to `specs/<NNN>-<feature-slug>/` and fill in
every `<...>` placeholder. Do not delete sections — if a section does not apply,
write "N/A — <reason>" so the omission is explicit.

Order: `00-brief.md` → `01-archaeology.md` → `02-schema-delta.md` →
`03-contract.md` → `04-fixtures.json` → `05-ui-mockup.html` → `05-ui.md` →
`06-plan.md` → `decisions.md`. Then `07-evidence/` and `08-retrospective.md`
at Freeze.

No implementation work begins until `00-brief.md` is APPROVED by the product
owner, and no tickets are planned until `05-ui-mockup.html` is approved (for
features with UI).
