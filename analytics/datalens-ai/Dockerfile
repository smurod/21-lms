FROM python:3.11-slim

WORKDIR /app

# Install dependencies first (layer-cached until requirements.txt changes)
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy source code
COPY . .

# Remove local dev artifacts that must not end up in the image
RUN rm -f .env jobs.json uvicorn.pid

EXPOSE 8100

HEALTHCHECK --interval=30s --timeout=10s --start-period=15s --retries=3 \
    CMD python3 -c "import urllib.request; urllib.request.urlopen('http://localhost:8100/health', timeout=8)"

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8100", \
     "--workers", "1", "--log-level", "info"]
