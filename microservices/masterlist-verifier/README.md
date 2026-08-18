# ScholarSync Masterlist Verifier

Python FastAPI microservice for comparing uploaded scholar names with Registrar enrollment records.

## Run

```bash
cd microservices/masterlist-verifier
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
uvicorn main:app --reload --host 127.0.0.1 --port 8001
```

## Endpoints

- `GET /health-check`
- `POST /verify-masterlist`

Laravel expects the service at `http://127.0.0.1:8001` unless `MASTERLIST_VERIFIER_URL` is changed. A request accepts at most 500 records from one campus.

## Verification contract

`POST /verify-masterlist` accepts uploaded names and Registrar records:

```json
{
  "records": [{"row_id": 1, "student_id_number": "2024-001", "student_name": "Ana Cruz", "campus_id": 2}],
  "registrar_students": [{
    "id": 10,
    "student_id_number": "2024-001",
    "student_name": "Cruz, Ana",
    "campus_id": 2,
    "enrollment_status": "enrolled",
    "cor_printed": false
  }]
}
```

Every record returns separate `enrollment_status`, `cor_status`, and `qualification_status` values plus its match status and reason. Exact student ID is preferred, followed by a unique normalized name within the submitted campus. Missing, ambiguous, and cross-campus candidates are always `needs_review`; they are never automatically classified as not enrolled. Phase 2 qualification means only enrolled with a printed COR.
