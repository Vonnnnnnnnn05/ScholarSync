# ScholarSync Masterlist Verifier (v4.0.0)

Python FastAPI microservice for parsing external agency grantee masterlists (PDF, XLSX, CSV) and performing 3-tier student verification against SIS (Registrar) enrollment records.

## Run

```bash
cd microservices/masterlist-verifier
python -m uvicorn main:app --reload --host 127.0.0.1 --port 8001
```

## Endpoints

### 1. Health Check
- `GET /health-check`: Returns service status and version (`4.0.0`).

### 2. Grantee Ingestion Pipeline (Phase 2 - v4.0.0)
- `POST /parse-file`: Accepts multipart file upload (`.pdf`, `.xlsx`, `.csv`). Extracts tabular data across multi-page tables (using `tabula-py` and `pypdf` fallback), detects and normalizes agency column headers (via `header_mapper`), splits Filipino full names (handling compound surnames like *Dela Cruz*, *San Jose*), and returns standardized JSON preview rows.
- `POST /verify-grantees`: Accepts staged rows, SIS students, and active grantee IDs. Executes 3-Tier matching:
  - **Tier 1 (Exact Student ID Match)**: Resolves SIS match. Checks for duplicate scholarship policy conflicts (`DOUBLE_GRANT`).
  - **Tier 2 (Fuzzy Name Match)**: Uses `SequenceMatcher` with threshold $\ge 0.85$ to detect same-name students across campuses, highlighting multi-matches and low-confidence candidates for Chairman resolution.
  - **Tier 3 (Red Unmatched)**: Categorizes records with no matching student.

### 3. Legacy Verification (v3.0.0 Compatibility)
- `POST /verify-masterlist`: Preserved for existing campus-scoped CSV masterlist workflows.
