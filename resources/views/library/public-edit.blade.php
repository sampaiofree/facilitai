<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Edição pública • {{ $entry->title }}</title>
    <style>
        :root { 
            color-scheme: light;
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --primary-light: #dbeafe;
            --success: #10b981;
            --success-light: #d1fae5;
            --error: #ef4444;
            --error-light: #fee2e2;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-900: #111827;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
        }
        
        * { box-sizing: border-box; }
        
        body { 
            margin: 0; 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: var(--gray-900);
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .container { 
            max-width: 900px; 
            margin: 0 auto; 
            padding: 0 20px;
        }
        
        .card { 
            background: #fff; 
            border-radius: 16px; 
            padding: 40px;
            box-shadow: var(--shadow-xl);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: fadeIn 0.4s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .card-center { 
            max-width: 500px; 
            margin: 60px auto;
        }
        
        .header { 
            display: flex; 
            align-items: flex-start; 
            justify-content: space-between; 
            gap: 20px;
            padding-bottom: 24px;
            border-bottom: 2px solid var(--gray-100);
            margin-bottom: 28px;
        }
        
        h1 { 
            margin: 0 0 8px; 
            font-size: 32px;
            font-weight: 700;
            color: var(--gray-900);
            line-height: 1.2;
        }
        
        .muted { 
            color: var(--gray-600); 
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .muted::before {
            content: "🔗";
            font-size: 16px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        label { 
            display: block; 
            font-weight: 600; 
            font-size: 14px; 
            margin-bottom: 8px; 
            color: var(--gray-700);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        input, textarea { 
            width: 100%; 
            border: 2px solid var(--gray-200); 
            border-radius: 12px; 
            padding: 12px 16px; 
            font-size: 15px; 
            font-family: inherit;
            transition: all 0.2s ease;
            background: var(--gray-50);
        }
        
        input:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 4px var(--primary-light);
        }
        
        textarea { 
            min-height: 320px; 
            resize: vertical;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            line-height: 1.6;
        }
        
        .actions { 
            display: flex; 
            gap: 12px; 
            align-items: center;
            margin-top: 28px;
        }
        
        .btn { 
            background: var(--primary);
            color: #fff; 
            border: none; 
            padding: 12px 24px; 
            border-radius: 10px; 
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-md);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn.secondary { 
            background: var(--gray-200);
            color: var(--gray-700);
            box-shadow: var(--shadow-sm);
        }
        
        .btn.secondary:hover {
            background: var(--gray-300);
        }
        
        .btn:disabled { 
            opacity: 0.5; 
            cursor: not-allowed;
            transform: none !important;
        }
        
        .alert { 
            padding: 14px 18px; 
            border-radius: 12px; 
            margin-bottom: 20px; 
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert::before {
            font-size: 20px;
        }
        
        .alert.error { 
            background: var(--error-light);
            color: #991b1b; 
            border: 2px solid var(--error);
        }
        
        .alert.error::before {
            content: "⚠️";
        }
        
        .alert.success { 
            background: var(--success-light);
            color: #065f46; 
            border: 2px solid var(--success);
        }
        
        .alert.success::before {
            content: "✓";
        }
        
        .lock-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            border-radius: 50%;
            margin: 0 auto 24px;
            box-shadow: var(--shadow-lg);
        }
        
        .lock-icon::before {
            content: "🔒";
            font-size: 36px;
        }
        
        .login-card {
            text-align: center;
        }
        
        .login-card .form-group {
            text-align: left;
        }
        
        @media (max-width: 768px) {
            .card { padding: 28px 20px; }
            h1 { font-size: 24px; }
            .header { flex-direction: column; }
            .btn { width: 100%; }
            textarea { min-height: 240px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card {{ $canEdit ? '' : 'card-center login-card' }}">
            @if (!$canEdit)
                <div class="lock-icon"></div>
            @endif
            
            <div class="header">
                <div>
                    <h1>{{ $entry->title }}</h1>
                    <div class="muted">Atualizado em {{ $entry->updated_at->format('d/m/Y H:i') }}</div>
                </div>
                @if ($canEdit)
                    <form method="POST" action="{{ route('library.public.logout', $entry->public_edit_token) }}">
                        @csrf
                        <button type="submit" class="btn secondary" title="Sair desta sessão">Sair</button>
                    </form>
                @endif
            </div>

            @if ($errorMessage)
                <div class="alert error">{{ $errorMessage }}</div>
            @endif
            @if ($successMessage)
                <div class="alert success">{{ $successMessage }}</div>
            @endif

            @if (!$canEdit)
                <form method="POST" action="{{ route('library.public.auth', $entry->public_edit_token) }}">
                    @csrf
                    <div class="form-group">
                        <label for="password">Senha para editar</label>
                        <input id="password" name="password" type="password" autocomplete="off" placeholder="Digite a senha" required>
                        @error('password')
                            <div class="alert error" style="margin-top:12px;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="actions">
                        <button type="submit" class="btn">Entrar</button>
                    </div>
                </form>
            @else
                <form method="POST" action="{{ route('library.public.update', $entry->public_edit_token) }}">
                    @csrf
                    <div class="form-group">
                        <label for="content">Conteúdo (Markdown)</label>
                        <textarea id="content" name="content" maxlength="20000" placeholder="Digite seu conteúdo em Markdown..." required>{{ $content }}</textarea>
                        @error('content')
                            <div class="alert error" style="margin-top:12px;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="actions">
                        <button type="submit" class="btn">Salvar conteúdo</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</body>
</html>