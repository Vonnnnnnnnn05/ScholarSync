For the **3 terminals**, just run these:

**Terminal 1 — Laravel**

```powershell
php artisan serve
```

**Terminal 2 — FastAPI Microservice**

```powershell
cd microservices\masterlist-verifier
python -m uvicorn main:app --reload --host 127.0.0.1 --port 8001
```

**Terminal 3 — Vite**

```powershell
npm run dev
```
