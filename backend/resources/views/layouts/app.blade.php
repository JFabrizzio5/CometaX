<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
<title>@yield('title', 'Portal') · {{ config('app.name') }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet" />
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        fontFamily: {
          sans: ['Inter', 'ui-sans-serif', 'sans-serif'],
          mono: ['JetBrains Mono', 'ui-monospace', 'monospace'],
        },
        borderRadius: { card: '24px', control: '23px' },
      },
    },
  };
</script>
<style>
  body { background: #050505; }
  .bg-grid {
    background-image: linear-gradient(to right, rgba(255,255,255,0.045) 1px, transparent 1px),
      linear-gradient(to bottom, rgba(255,255,255,0.045) 1px, transparent 1px);
    background-size: 44px 44px;
  }
  .glow { filter: blur(110px); }
</style>
</head>
<body class="font-sans text-white antialiased min-h-screen relative">

  <div class="fixed inset-0 bg-grid opacity-40 [mask-image:radial-gradient(ellipse_80%_60%_at_50%_0%,black,transparent)] pointer-events-none"></div>
  <div class="glow fixed -top-40 -left-40 h-96 w-96 rounded-full bg-white/10 pointer-events-none"></div>

  @if (session('impersonator_consultant_id'))
    <div class="relative z-30 flex flex-wrap items-center justify-center gap-x-4 gap-y-2 bg-amber-500 px-4 py-2 text-center text-sm font-medium text-black">
      <span>Estás viendo el portal como cliente{{ auth('web')->user()?->client ? ' — '.auth('web')->user()->client->name : '' }}.</span>
      <form method="POST" action="{{ route('impersonation.stop') }}">
        @csrf
        <button class="rounded-full bg-black/85 px-3 py-1 font-mono text-[11px] uppercase tracking-widest text-white transition hover:bg-black">
          Salir de la vista · Volver al panel
        </button>
      </form>
    </div>
  @endif

  <div class="relative">
    @yield('content')
  </div>

</body>
</html>
