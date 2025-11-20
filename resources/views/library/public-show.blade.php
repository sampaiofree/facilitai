<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $entry->title }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background: #f5f7fb; margin: 0; color: #1f2937; }
        .container { max-width: 760px; margin: 0 auto; padding: 32px 16px 48px; }
        .card { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        h1 { margin: 0 0 12px; font-size: 28px; line-height: 1.2; }
        .muted { color: #6b7280; font-size: 14px; margin-bottom: 20px; }
        .content :where(p, ul, ol, pre, code, blockquote, h2, h3, h4) { margin: 0 0 12px; }
        .content h2 { font-size: 20px; margin-top: 20px; }
        .content pre { background: #f3f4f6; padding: 12px; border-radius: 8px; overflow-x: auto; }
        .content code { background: #f3f4f6; padding: 2px 4px; border-radius: 4px; }
        .content ul, .content ol { padding-left: 20px; }
        .badge { display: inline-flex; align-items: center; gap: 6px; background: #e5e7eb; color: #374151; padding: 6px 10px; border-radius: 999px; font-size: 13px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>{{ $entry->title }}</h1>
            <div class="muted">
                <span class="badge">Texto público</span>
                <span aria-hidden="true">•</span>
                Atualizado em {{ $entry->updated_at->format('d/m/Y H:i') }}
            </div>
            <div class="content">
                {!! $rendered !!}
            </div>
        </div>
    </div>
</body>
</html>
