# dev-journal

Agent working memory for FunLink. Three folders, each with one job:

```text
dev-journal/
  bug-fixes/          # symptom → root cause → fix → the recurrence guard
  domain-knowledge/   # topic-named durable findings not already in formal docs
  progress/           # done / in progress / next / decisions and open threads
```

**Rules:**
- Bug fixes: no guard, no completed fix. Every significant fix ends with a
  regression test, architecture test, or recorded rule.
- Domain knowledge: durable and topic-named (e.g. `postgis-radius-queries.md`).
  Formal decisions stay in `docs/` and spec folders; the journal links to them.
- Progress: one entry per work session — done, in progress, next, decisions.
- Scan the journal before starting work. Write to it when you finish work.
