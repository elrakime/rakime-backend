@extends('layouts.app')

@section('title', 'Import — ' . config('app.name', 'Rakime'))

@section('content')
    <div class="mx-auto max-w-5xl px-6 py-12">
        <header class="mb-10">
            <h1 class="text-3xl font-bold tracking-tight">Account Import</h1>
            <p class="mt-2 text-zinc-400">Upload a bank return <code class="text-zinc-200">.txt</code> file and choose a draw day to decode the results.</p>
        </header>

        <form method="POST" action="{{ url('/import') }}" enctype="multipart/form-data" class="mb-10 rounded-2xl border border-zinc-800 bg-zinc-900 p-6">
            @csrf

            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-800 bg-red-950/50 p-4 text-red-300">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (isset($error))
                <div class="mb-6 rounded-lg border border-red-800 bg-red-950/50 p-4 text-red-300">{{ $error }}</div>
            @endif

            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="file" class="mb-2 block text-sm font-medium text-zinc-300">File (.txt)</label>
                    <input id="file" name="file" type="file" accept=".txt,text/plain" required
                           class="block w-full rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-2 text-sm text-zinc-100 file:mr-4 file:rounded-md file:border-0 file:bg-orange-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-orange-500">
                </div>

                <div>
                    <label for="draw_day" class="mb-2 block text-sm font-medium text-zinc-300">Draw day</label>
                    <select id="draw_day" name="draw_day" required
                            class="block w-full rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-2 text-sm text-zinc-100">
                        <option value="" disabled selected>Select draw day…</option>
                        @for ($day = 1; $day <= 30; $day++)
                            <option value="{{ $day }}" {{ old('draw_day') == $day ? 'selected' : '' }}>{{ $day }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            <button type="submit"
                    class="mt-6 inline-flex items-center gap-2 rounded-lg bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-orange-500">
                Decode
            </button>
        </form>

        @if (isset($items))
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-xl font-semibold">Results</h2>
                <span class="rounded-full border border-zinc-800 bg-zinc-900 px-3 py-1 text-sm text-zinc-400">{{ $count }} item{{ $count === 1 ? '' : 's' }}</span>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($items as $item)
                    @php
                        $statusColors = [
                            'paid_on_time' => 'bg-emerald-950 text-emerald-300 border-emerald-800',
                            'late_payment' => 'bg-amber-950 text-amber-300 border-amber-800',
                            'postponed'    => 'bg-blue-950 text-blue-300 border-blue-800',
                            'failed'       => 'bg-red-950 text-red-300 border-red-800',
                        ];
                        $badgeClass = $statusColors[$item['status']] ?? 'bg-zinc-800 text-zinc-300 border-zinc-700';
                    @endphp

                    <article class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5">
                        <div class="mb-3 flex items-start justify-between gap-3">
                            <h3 class="font-semibold leading-tight">{{ $item['client_fullname'] }}</h3>
                            <span class="shrink-0 rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $badgeClass }}">{{ $item['status'] }}</span>
                        </div>

                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">Client CCP</dt>
                                <dd class="font-mono text-zinc-200">{{ $item['client_ccp_number'] }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">Account CCP</dt>
                                <dd class="font-mono text-zinc-200">{{ $item['account_ccp_number'] }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">Amount</dt>
                                <dd class="font-mono text-zinc-200">{{ $item['amount'] }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">Date</dt>
                                <dd class="font-mono text-zinc-200">{{ $item['date'] }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">Cycle</dt>
                                <dd class="font-mono text-zinc-200">{{ $item['cycle'] ?: '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">Tax</dt>
                                <dd class="font-mono text-zinc-200">{{ $item['tax'] }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">Offset</dt>
                                <dd class="font-mono text-zinc-200">{{ $item['offset'] }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">Reference</dt>
                                <dd class="font-mono text-zinc-200">{{ $item['subscription_reference'] }}</dd>
                            </div>
                        </dl>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
@endsection
