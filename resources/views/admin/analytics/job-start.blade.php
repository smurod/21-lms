<!doctype html>
<html lang="ru">
<head><meta charset="utf-8"></head>
<body>
<script>
    window.parent.postMessage({
        type: 'analytics-job-start',
        jobId: @json($jobId),
        streamUrl: @json($streamUrl),
        completeUrl: @json($completeUrl)
    }, window.location.origin);
</script>
</body>
</html>
