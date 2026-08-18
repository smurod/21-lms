<!doctype html>
<html lang="ru">
<head><meta charset="utf-8"></head>
<body>
<script>
    window.parent.postMessage({
        type: 'analytics-chat-response',
        userMessage: @json($userMessage),
        assistantMessage: @json($assistantMessage),
        initialConversation: @json($initialConversation ?? false),
        completeUrl: @json($completeUrl ?? null)
    }, window.location.origin);
</script>
</body>
</html>
