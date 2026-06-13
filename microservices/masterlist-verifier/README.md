# ScholarSync Masterlist Verifier

Python FastAPI microservice for validating scholarship agency masterlists.

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
