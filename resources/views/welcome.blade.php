<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Rakime') }}</title>
        <style>
            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

            body {
                font-family: ui-sans-serif, system-ui, sans-serif;
                background-color: #0f0f0f;
                color: #f5f5f5;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .container {
                text-align: center;
                max-width: 600px;
                padding: 2rem;
            }

            .badge {
                display: inline-block;
                background-color: #ff6a001a;
                color: #ff6a00;
                border: 1px solid #ff6a0044;
                border-radius: 999px;
                font-size: 0.75rem;
                font-weight: 600;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                padding: 0.3rem 0.9rem;
                margin-bottom: 1.5rem;
            }

            h1 {
                font-size: 2.75rem;
                font-weight: 700;
                line-height: 1.2;
                margin-bottom: 1.25rem;
                letter-spacing: -0.02em;
            }

            h1 span {
                color: #ff6a00;
            }

            p {
                font-size: 1.0625rem;
                color: #a0a0a0;
                line-height: 1.7;
                margin-bottom: 2.5rem;
            }

            .btn {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                background-color: #ff6a00;
                color: #fff;
                font-size: 0.9375rem;
                font-weight: 600;
                padding: 0.75rem 1.75rem;
                border-radius: 8px;
                text-decoration: none;
                transition: background-color 0.15s ease, transform 0.15s ease;
            }

            .btn:hover {
                background-color: #e05e00;
                transform: translateY(-1px);
            }

            .btn svg {
                flex-shrink: 0;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="badge">REST API</div>

            <h1><span>Rakime</span></h1>

            <p>
                A comprehensive backend API for managing sales, installment contracts,
                inventory, purchases, and branch operations &mdash; built with Laravel.
            </p>

            <a href="/docs" class="btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10 9 9 9 8 9"/>
                </svg>
                View API Docs
            </a>
        </div>
    </body>
</html>
