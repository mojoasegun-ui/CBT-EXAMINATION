# Migration Foundation

No migrations are run automatically in Phase 1. Future migrations must be reviewed and invoked by an authorized operator, use the configured ExamCore table prefix (default `cbt_`), and never alter host-school tables. MySQL DDL may implicitly commit, so schema changes must be designed to be restartable and backed up before execution.