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

Laravel expects the service at `http://127.0.0.1:8001` unless `MASTERLIST_VERIFIER_URL` is changed.

## Verification contract

`POST /verify-masterlist` accepts uploaded names and Registrar records:

```json
{
  "records": [{"row_id": 1, "student_name": "Ana Cruz"}],
  "registrar_students": [{
    "id": 10,
    "student_name": "Cruz, Ana",
    "campus_id": 2,
    "enrollment_status": "enrolled",
    "cor_printed": false
  }]
}
```

Every record returns one status: `enrolled`, `no_cor_printed`, or `unenrolled`. Names are matched without regard to capitalization, punctuation, excess spaces, or token order. Ambiguous names are left unmatched for manual checking.
