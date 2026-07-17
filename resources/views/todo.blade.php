<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Task Commander — Stesen Kawalan</title>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Zen+Kaku+Gothic+New:wght@500;700;900&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
<style>
  :root{
    --void:#0A0D1C;
    --void-2:#0F1330;
    --panel:rgba(19,24,52,0.62);
    --panel-solid:#131834;
    --line:#262c52;
    --sakura:#FF7AA8;
    --aqua:#5EE6D9;
    --amber:#FFC65C;
    --text-hi:#F1F0FF;
    --text-lo:#8B93B8;
    --font-display:'Zen Kaku Gothic New', sans-serif;
    --font-body:'Inter', sans-serif;
    --font-mono:'JetBrains Mono', monospace;
  }
  html,body{background:var(--void); font-family:var(--font-body); color:var(--text-hi);}
  .font-display{font-family:var(--font-display);}
  .font-mono{font-family:var(--font-mono);}
  ::selection{background:var(--sakura); color:var(--void);}

  a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible{
    outline:2px solid var(--aqua); outline-offset:2px; border-radius:6px;
  }

  .starfield{position:fixed; inset:0; z-index:0; pointer-events:none; overflow:hidden;
    background:
      radial-gradient(ellipse 120% 60% at 50% -10%, #241a4a 0%, transparent 60%),
      radial-gradient(ellipse 80% 50% at 90% 10%, #1a2246 0%, transparent 55%),
      var(--void);
  }
  .star{position:absolute; width:2px; height:2px; background:#cfd6ff; border-radius:50%; opacity:.5; animation:twinkle 4s ease-in-out infinite;}
  @keyframes twinkle{0%,100%{opacity:.15;} 50%{opacity:.9;}}

  .hero-scene{position:relative; overflow:hidden; border-radius:24px; border:1px solid var(--line);
    background:linear-gradient(180deg,#171b3d 0%, #12163a 45%, #0d1030 100%);
  }
  .moon{position:absolute; top:-40px; right:40px; width:180px; height:180px; border-radius:50%;
    background:radial-gradient(circle at 35% 35%, #fff8e8 0%, #ffe9c2 30%, #ffd9a0 55%, transparent 72%);
    filter:blur(2px); opacity:.9;
  }
  .moon-halo{position:absolute; top:-90px; right:-10px; width:320px; height:320px; border-radius:50%;
    background:radial-gradient(circle, rgba(255,233,194,0.16) 0%, transparent 65%);
  }
  .skyline{position:absolute; left:0; right:0; bottom:0; height:46%;}
  .petal{position:absolute; top:-24px; width:10px; height:10px; background:var(--sakura); opacity:.75;
    border-radius:70% 20% 70% 20%; animation:fall linear infinite;
  }
  @keyframes fall{
    0%{transform:translate(0,0) rotate(0deg); opacity:0;}
    8%{opacity:.8;}
    100%{transform:translate(var(--drift,40px), 340px) rotate(280deg); opacity:0;}
  }

  .gauge-ring{transform:rotate(-90deg); transform-origin:center;}
  .gauge-ring circle{transition:stroke-dashoffset .6s ease, stroke .3s ease;}
  .ribbon{width:4px; border-radius:4px; align-self:stretch;}
  .glass{background:var(--panel); backdrop-filter:blur(10px); border:1px solid var(--line);}
  .fade-in{animation:fadeIn .35s ease both;}
  @keyframes fadeIn{from{opacity:0; transform:translateY(4px);} to{opacity:1; transform:translateY(0);}}
</style>
</head>
<body class="min-h-screen antialiased">

@auth
<audio id="commander-jukebox" preload="auto"></audio>
@endauth

<div class="starfield" id="starfield"></div>

<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 relative z-10">

  <!-- ===================== HERO ===================== -->
  <div class="hero-scene px-6 sm:px-10 py-10 mb-8">
    <div class="moon-halo"></div>
    <div class="moon"></div>
    <svg class="skyline" viewBox="0 0 800 200" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
      <polygon points="0,200 0,120 40,120 40,90 70,90 70,130 110,130 110,70 150,70 150,140 190,140 190,100 230,100 230,150 280,150 280,80 320,80 320,145 360,145 360,110 410,110 410,160 460,160 460,95 500,95 500,150 540,150 540,120 590,120 590,160 640,160 640,90 690,90 690,155 730,155 730,120 800,120 800,200" fill="#0a0d24"/>
    </svg>
    <div id="petals"></div>

    <div class="relative flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div>
        <span class="inline-block font-mono text-[10px] tracking-[0.2em] uppercase text-[color:var(--aqua)] border border-[color:var(--line)] rounded-full px-3 py-1 mb-4">Sistem Bersepadu · v2</span>
        <h1 class="font-display font-black text-4xl sm:text-5xl leading-none text-white">Task Commander</h1>
        <p class="text-sm sm:text-base text-[color:var(--text-lo)] mt-3 max-w-md">Susun misi harian anda — berserta deskripsi dan garis masa dalam satu pangkalan data.</p>
      </div>

      @auth
      <div id="user-chip" class="glass rounded-2xl px-5 py-3 text-right shrink-0 flex flex-col items-end min-w-[180px]">
        <p class="font-mono text-[10px] uppercase tracking-widest text-[color:var(--text-lo)]">Pengguna</p>
        <p class="font-display font-bold text-white">{{ Auth::user()->name }}</p>
        
        <div class="mt-2 flex items-center gap-2 bg-[#0c0f26]/60 border border-[color:var(--line)] px-2 py-1 rounded-lg w-full justify-between">
            <span id="current-track-label" class="font-mono text-[9px] text-[color:var(--aqua)] truncate max-w-[90px]">Track 1</span>
            <div class="flex items-center gap-1.5">
                <button onclick="playPrevTrack()" class="text-slate-400 hover:text-white font-bold text-xs cursor-pointer">⏮</button>
                <button id="jukebox-toggle" onclick="toggleJukebox()" class="text-[color:var(--sakura)] font-bold text-xs cursor-pointer">▶</button>
                <button onclick="playNextTrack()" class="text-slate-400 hover:text-white font-bold text-xs cursor-pointer">⏭</button>
            </div>
        </div>
        <form method="POST" action="/inline-logout" class="inline mt-2">
            @csrf
            <button type="submit" class="font-mono text-[10px] font-bold text-[color:var(--sakura)] hover:brightness-125 transition cursor-pointer">[ Log Keluar → ]</button>
        </form>
      </div>
      @endauth
    </div>
  </div>

  <!-- ===================== PAPARAN DASHBOARD ===================== -->
  @auth
  <div id="dashboard-view">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <div class="glass rounded-2xl p-5">
        <p class="font-mono text-[10px] uppercase tracking-widest text-[color:var(--text-lo)]">Jumlah Misi</p>
        <p id="stat-total" class="font-display font-black text-3xl text-white mt-2">{{ $stats['total'] }}</p>
      </div>
      <div class="glass rounded-2xl p-5">
        <p class="font-mono text-[10px] uppercase tracking-widest text-[color:var(--aqua)]">Selesai</p>
        <p id="stat-completed" class="font-display font-black text-3xl mt-2" style="color:var(--aqua)">{{ $stats['completed'] }}</p>
      </div>
      <div class="glass rounded-2xl p-5">
        <p class="font-mono text-[10px] uppercase tracking-widest text-[color:var(--amber)]">Dalam Tindakan</p>
        <p id="stat-pending" class="font-display font-black text-3xl mt-2" style="color:var(--amber)">{{ $stats['pending'] }}</p>
      </div>
      <div class="glass rounded-2xl p-5 flex items-center gap-4 col-span-2 lg:col-span-1">
        <div class="relative w-[74px] h-[74px] shrink-0">
          <svg class="gauge-ring" width="74" height="74" viewBox="0 0 74 74">
            <circle cx="37" cy="37" r="31" fill="none" stroke="var(--line)" stroke-width="7"/>
            <circle id="gauge-progress" cx="37" cy="37" r="31" fill="none" stroke="var(--sakura)" stroke-width="7" stroke-linecap="round" stroke-dasharray="194.8" stroke-dashoffset="194.8"/>
          </svg>
          <div class="absolute inset-0 flex items-center justify-center">
            <span id="rank-letter" class="font-display font-black text-xl text-[color:var(--sakura)]">–</span>
          </div>
        </div>
        <div>
          <p class="font-mono text-[10px] uppercase tracking-widest text-[color:var(--text-lo)]">Gred Produktiviti</p>
          <p id="rank-label" class="font-display font-bold text-sm text-white mt-1">Mengira...</p>
          <p id="stat-efficiency" class="font-mono text-xs text-[color:var(--text-lo)] mt-0.5">{{ $stats['efficiency'] }}%</p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      
      <!-- BORANG INPUT (NAIK TARAF: DESKRIPSI & DATELINE) -->
      <div class="glass rounded-2xl p-6 h-fit">
        <h3 class="font-display font-bold text-white mb-4 flex items-center gap-2">
          <span class="w-2 h-4 rounded-sm" style="background:var(--sakura)"></span> Log Objektif Baharu
        </h3>
        <form id="form-add-task" class="space-y-4">
          <div>
            <label class="block font-mono text-[10px] uppercase tracking-widest text-[color:var(--text-lo)] mb-2">Tajuk Misi</label>
            <input type="text" id="input-title" placeholder="cth. Sediakan laporan kewangan" required
              class="w-full bg-[#0c0f26] border border-[color:var(--line)] rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-[color:var(--aqua)] transition">
          </div>
          
          <div>
            <label class="block font-mono text-[10px] uppercase tracking-widest text-[color:var(--text-lo)] mb-2">Butiran / Deskripsi Misi</label>
            <textarea id="input-description" rows="3" placeholder="Tulis huraian atau nota tugasan di sini..."
              class="w-full bg-[#0c0f26] border border-[color:var(--line)] rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-[color:var(--aqua)] transition resize-none"></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block font-mono text-[10px] uppercase tracking-widest text-[color:var(--text-lo)] mb-2">Kategori</label>
              <select id="input-category" class="w-full bg-[#0c0f26] border border-[color:var(--line)] rounded-xl px-3 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-[color:var(--aqua)]">
                <option value="Kerja">💼 Kerja</option>
                <option value="Peribadi">🏠 Peribadi</option>
                <option value="Belajar">📚 Belajar</option>
                <option value="Umum">🌍 Umum</option>
              </select>
            </div>
            <div>
              <label class="block font-mono text-[10px] uppercase tracking-widest text-[color:var(--text-lo)] mb-2">Keutamaan</label>
              <select id="input-priority" class="w-full bg-[#0c0f26] border border-[color:var(--line)] rounded-xl px-3 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-[color:var(--aqua)]">
                <option value="Tinggi">Tinggi</option>
                <option value="Medium">Medium</option>
                <option value="Rendah">Rendah</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-mono text-[10px] uppercase tracking-widest text-[color:var(--text-lo)] mb-2">Tarikh Akhir (Dateline)</label>
            <input type="date" id="input-duedate" class="w-full bg-[#0c0f26] border border-[color:var(--line)] rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-[color:var(--aqua)]">
          </div>

          <button type="submit" class="w-full text-[#0A0D1C] font-display font-bold text-sm py-3 rounded-xl transition active:scale-[0.98] cursor-pointer" style="background:var(--sakura)">Tambah ke Senarai</button>
        </form>

        <div class="mt-6 pt-5 border-t border-[color:var(--line)] relative overflow-hidden rounded-xl bg-slate-900/40 p-4 border border-indigo-500/10">
            <span class="bg-purple-500/20 text-[color:var(--sakura)] text-[9px] font-black tracking-wider uppercase px-2 py-0.5 rounded">Quotes API</span>
            <p class="text-xs text-slate-300 mt-2 font-medium italic leading-relaxed">{{ $animeQuote }}</p>
            <p class="text-[10px] text-[color:var(--aqua)] mt-1.5 font-mono">{{ $animeCharacter }}</p>
        </div>
      </div>

      <!-- KANAN: PAPARAN LIST & VIEW DESCRIPTION -->
      <div class="lg:col-span-2 space-y-4">
        <form action="/" method="GET" class="glass rounded-2xl p-4 flex flex-col sm:flex-row gap-3 items-center justify-between">
          <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari misi..." class="w-full sm:w-64 bg-[#0c0f26] border border-[color:var(--line)] rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-[color:var(--aqua)] transition">
          <div class="flex gap-3 w-full sm:w-auto justify-end">
              <select name="status" class="w-full sm:w-auto bg-[#0c0f26] border border-[color:var(--line)] rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-[color:var(--aqua)]">
                <option value="">Semua Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Belum Selesai</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai</option>
              </select>
              <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-white text-sm font-bold px-4 py-2.5 rounded-xl transition cursor-pointer">Tapis</button>
          </div>
        </form>

        <div id="task-list" class="space-y-3">
            @forelse($tasks as $task)
              <div id="task-item-{{ $task->id }}" class="glass rounded-2xl p-4 flex flex-col gap-2 group fade-in">
                
                <!-- Bahagian Atas Kad -->
                <div class="flex items-center gap-4">
                    <div class="ribbon" style="background:{{ $task->priority == 'Tinggi' ? 'var(--sakura)' : ($task->priority == 'Medium' ? 'var(--amber)' : 'var(--aqua)') }}"></div>
                    <button onclick="toggleTaskStatus({{ $task->id }}); event.stopPropagation();" class="w-6 h-6 rounded-lg border flex items-center justify-center transition shrink-0 cursor-pointer"
                      style="border-color:{{ $task->is_completed ? 'transparent' : 'var(--line)' }}; background:{{ $task->is_completed ? 'var(--aqua)' : '#0c0f26' }}; color:#0A0D1C; font-weight:900;">
                      <span id="check-icon-{{ $task->id }}">{!! $task->is_completed ? '✓' : '' !!}</span>
                    </button>
                    
                    <!-- Tajuk Tugasan (Boleh Klik Untuk Papar Description) -->
                    <div class="flex-1 min-w-0 cursor-pointer" onclick="toggleDescriptionPanel({{ $task->id }})">
                      <p id="task-title-{{ $task->id }}" class="font-display font-bold text-sm md:text-base transition {{ $task->is_completed ? 'line-through text-[#4b5280]' : 'text-slate-100' }}">
                        {{ $task->title }}
                      </p>
                      <div class="flex items-center flex-wrap gap-2 mt-1.5">
                        <span class="font-mono text-[9px] font-bold px-1.5 py-0.5 rounded border" style="border-color:var(--line); color:var(--text-lo)">
                            {{ $task->category == 'Kerja' ? '💼 Kerja' : ($task->category == 'Peribadi' ? '🏠 Peribadi' : ($task->category == 'Belajar' ? '📚 Belajar' : '🌍 Umum')) }}
                        </span>
                        @if($task->due_date)
                        <span class="font-mono text-[9px] text-[color:var(--amber)] bg-[color:var(--amber)]/5 px-1.5 py-0.5 rounded border border-[color:var(--amber)]/20">
                            📅 {{ \Carbon\Carbon::parse($task->due_date)->format('d M Y') }}
                        </span>
                        @endif
                      </div>
                    </div>
                    <button onclick="deleteTask({{ $task->id }}); event.stopPropagation();" class="font-mono text-[11px] font-bold text-[#4b5280] hover:text-[color:var(--sakura)] transition px-2 py-2 shrink-0 cursor-pointer md:opacity-0 group-hover:opacity-100">Padam</button>
                </div>

                <!-- BAHAGIAN DESKRIPSI (View & Expandable Detail) -->
                @if($task->description)
                <div id="desc-panel-{{ $task->id }}" class="hidden pl-10 pr-4 py-2 mt-1 border-t border-[color:var(--line)]/40 transition-all duration-300">
                    <p class="text-xs text-[color:var(--text-lo)] leading-relaxed bg-[#0c0f26]/40 p-3 rounded-xl border border-[color:var(--line)]/30">
                        {{ $task->description }}
                    </p>
                </div>
                @endif

              </div>
            @empty
              <div id="no-task-alert" class="glass rounded-2xl p-12 text-center border-dashed" style="border-style:dashed; color:var(--text-lo)">
                <p class="font-display font-bold">Tiada misi sepadan aktif.</p>
              </div>
            @endforelse
        </div>
      </div>
    </div>
  </div>
  @endauth

  <!-- ===================== AUTH GATE VIEW ===================== -->
  @guest
  <div id="auth-view" class="max-w-md mx-auto">
    <div class="glass rounded-2xl p-6">
      @if($errors->any())
        <div class="bg-red-500/10 border border-red-500/20 text-red-400 text-xs rounded-xl p-3 mb-4 font-medium font-mono">
            {{ $errors->first() }}
        </div>
      @endif

      <div id="login-section">
        <p class="font-mono text-[10px] uppercase tracking-widest text-[color:var(--aqua)] mb-1">Stesen Kawalan</p>
        <h2 class="font-display font-black text-2xl text-white mb-5">Sahkan identiti anda</h2>
        <form action="/inline-login" method="POST" class="space-y-4">
          @csrf
          <div>
            <label class="block font-mono text-[10px] uppercase tracking-widest text-[color:var(--text-lo)] mb-2">E-mel</label>
            <input type="email" name="email" required class="w-full bg-[#0c0f26] border border-[color:var(--line)] rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-[color:var(--aqua)]">
          </div>
          <div>
            <label class="block font-mono text-[10px] uppercase tracking-widest text-[color:var(--text-lo)] mb-2">Kata Laluan</label>
            <input type="password" name="password" required class="w-full bg-[#0c0f26] border border-[color:var(--line)] rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-[color:var(--aqua)]">
          </div>
          <button type="submit" class="w-full text-[#0A0D1C] font-display font-bold text-sm py-2.5 rounded-xl transition cursor-pointer" style="background:var(--sakura)">Masuk Log</button>
        </form>
        <p class="text-xs text-center text-[color:var(--text-lo)] mt-4">Belum ada akaun? <button onclick="switchAuthMode('register')" class="font-bold hover:underline cursor-pointer" style="color:var(--aqua)">Daftar Akaun</button></p>
      </div>

      <div id="register-section" class="hidden">
        <p class="font-mono text-[10px] uppercase tracking-widest text-[color:var(--sakura)] mb-1">Pendaftaran Sarang</p>
        <h2 class="font-display font-black text-2xl text-white mb-5">Daftar Kod Akses Baru</h2>
        <form action="/inline-register" method="POST" class="space-y-4">
          @csrf
          <div>
            <label class="block font-mono text-[10px] uppercase tracking-widest text-[color:var(--text-lo)] mb-2">Nama</label>
            <input type="text" name="name" required class="w-full bg-[#0c0f26] border border-[color:var(--line)] rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-[color:var(--aqua)]">
          </div>
          <div>
            <label class="block font-mono text-[10px] uppercase tracking-widest text-[color:var(--text-lo)] mb-2">E-mel</label>
            <input type="email" name="email" required class="w-full bg-[#0c0f26] border border-[color:var(--line)] rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-[color:var(--aqua)]">
          </div>
          <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-mono text-[10px] uppercase tracking-widest text-[color:var(--text-lo)] mb-2">Kata Laluan</label>
                <input type="password" name="password" required class="w-full bg-[#0c0f26] border border-[color:var(--line)] rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-[color:var(--aqua)]">
              </div>
              <div>
                <label class="block font-mono text-[10px] uppercase tracking-widest text-[color:var(--text-lo)] mb-2">Sahkan</label>
                <input type="password" name="password_confirmation" required class="w-full bg-[#0c0f26] border border-[color:var(--line)] rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-[color:var(--aqua)]">
              </div>
          </div>
          <button type="submit" class="w-full text-[#0A0D1C] font-display font-bold text-sm py-2.5 rounded-xl transition cursor-pointer" style="background:var(--aqua)">Bina Akaun</button>
        </form>
        <p class="text-xs text-center text-[color:var(--text-lo)] mt-4">Sudah berdaftar? <button onclick="switchAuthMode('login')" class="font-bold hover:underline cursor-pointer" style="color:var(--sakura)">Log Masuk</button></p>
      </div>
    </div>
  </div>
  @endguest

</div>

<script>
  // ---------- ambient starfield ----------
  const starfield = document.getElementById('starfield');
  for(let i=0;i<50;i++){
    const s = document.createElement('div');
    s.className='star';
    s.style.left = Math.random()*100+'%';
    s.style.top = Math.random()*100+'%';
    s.style.animationDelay = (Math.random()*4)+'s';
    starfield.appendChild(s);
  }
  const petalHost = document.getElementById('petals');
  for(let i=0;i<9;i++){
    const p = document.createElement('div');
    p.className='petal';
    p.style.left = (5 + Math.random()*90)+'%';
    p.style.setProperty('--drift', (Math.random()*80-40)+'px');
    p.style.animationDuration = (6 + Math.random()*5)+'s';
    p.style.animationDelay = (Math.random()*6)+'s';
    petalHost.appendChild(p);
  }

  function switchAuthMode(mode){
    document.getElementById('login-section').classList.toggle('hidden', mode==='register');
    document.getElementById('register-section').classList.toggle('hidden', mode==='login');
  }

  // Fungsi JavaScript untuk Buka/Tutup Butiran Deskripsi (Expand Task Description)
  function toggleDescriptionPanel(id) {
      const panel = document.getElementById(`desc-panel-${id}`);
      if(panel) {
          panel.classList.toggle('hidden');
      }
  }

  // ---------- ENJIN LIVE AJAX & JUKEBOX WORKSPACE ----------
  @auth
  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const CIRC = 2 * Math.PI * 31;
  const rankTiers = [
    {min:90, letter:'S', label:'Prestasi Legenda', color:'#FF7AA8'},
    {min:70, letter:'A', label:'Momentum Kukuh', color:'#5EE6D9'},
    {min:45, letter:'B', label:'Dalam Landasan', color:'#FFC65C'},
    {min:20, letter:'C', label:'Perlu Tumpuan', color:'#9aa3c9'},
    {min:0,  letter:'D', label:'Mula Bergerak', color:'#6b7299'},
  ];
  
  function rankFor(eff){ return rankTiers.find(t => eff >= t.min); }

  function updateDashboardStats(stats) {
      document.getElementById('stat-total').innerText = stats.total;
      document.getElementById('stat-completed').innerText = stats.completed;
      document.getElementById('stat-pending').innerText = stats.pending;
      document.getElementById('stat-efficiency').innerText = stats.efficiency + '%';
      
      const rank = rankFor(stats.efficiency);
      const ring = document.getElementById('gauge-progress');
      ring.setAttribute('stroke-dashoffset', CIRC - (CIRC * stats.efficiency/100));
      ring.setAttribute('stroke', rank.color);
      document.getElementById('rank-letter').innerText = stats.total ? rank.letter : '–';
      document.getElementById('rank-letter').style.color = rank.color;
      document.getElementById('rank-label').innerText = stats.total ? rank.label : 'Belum bermula';
  }

  updateDashboardStats({
      total: parseInt(document.getElementById('stat-total').innerText),
      completed: parseInt(document.getElementById('stat-completed').innerText),
      pending: parseInt(document.getElementById('stat-pending').innerText),
      efficiency: parseInt(document.getElementById('stat-efficiency').innerText)
  });

  // AJAX Tambah Misi (Dinaik Taraf dengan Deskripsi & Dateline)
  document.getElementById('form-add-task').addEventListener('submit', function(e) {
      e.preventDefault();
      const title = document.getElementById('input-title').value;
      const description = document.getElementById('input-description').value;
      const category = document.getElementById('input-category').value;
      const priority = document.getElementById('input-priority').value;
      const due_date = document.getElementById('input-duedate').value;

      fetch('/task', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
          body: JSON.stringify({ title, description, category, priority, due_date })
      })
      .then(res => res.json())
      .then(data => {
          if(data.success) {
              const noTaskAlert = document.getElementById('no-task-alert');
              if (noTaskAlert) noTaskAlert.remove();
              
              const pColor = data.task.priority === 'Tinggi' ? 'var(--sakura)' : (data.task.priority === 'Medium' ? 'var(--amber)' : 'var(--aqua)');
              const catIcon = data.task.category === 'Kerja' ? '💼 Kerja' : (data.task.category === 'Peribadi' ? '🏠 Peribadi' : (data.task.category === 'Belajar' ? '📚 Belajar' : '🌍 Umum'));
              
              let dateBadgeHtml = '';
              if(data.task.due_date) {
                  dateBadgeHtml = `<span class="font-mono text-[9px] text-[color:var(--amber)] bg-[color:var(--amber)]/5 px-1.5 py-0.5 rounded border border-[color:var(--amber)]/20">📅 ${data.task.due_date}</span>`;
              }

              let descPanelHtml = '';
              if(data.task.description) {
                  descPanelHtml = `
                    <div id="desc-panel-${data.task.id}" class="hidden pl-10 pr-4 py-2 mt-1 border-t border-[color:var(--line)]/40 transition-all duration-300">
                        <p class="text-xs text-[color:var(--text-lo)] leading-relaxed bg-[#0c0f26]/40 p-3 rounded-xl border border-[color:var(--line)]/30">${data.task.description}</p>
                    </div>`;
              }

              const newTaskHtml = `
                  <div id="task-item-${data.task.id}" class="glass rounded-2xl p-4 flex flex-col gap-2 group fade-in">
                      <div class="flex items-center gap-4">
                          <div class="ribbon" style="background:${pColor}"></div>
                          <button onclick="toggleTaskStatus(${data.task.id}); event.stopPropagation();" class="w-6 h-6 rounded-lg border flex items-center justify-center transition shrink-0 cursor-pointer" style="border-color:var(--line); background:#0c0f26; color:#0A0D1C; font-weight:900;">
                              <span id="check-icon-${data.task.id}"></span>
                          </button>
                          <div class="flex-1 min-w-0 cursor-pointer" onclick="toggleDescriptionPanel(${data.task.id})">
                              <p id="task-title-${data.task.id}" class="font-display font-bold text-sm md:text-base text-slate-100">${data.task.title}</p>
                              <div class="flex items-center flex-wrap gap-2 mt-1.5">
                                  <span class="font-mono text-[9px] font-bold px-1.5 py-0.5 rounded border" style="border-color:var(--line); color:var(--text-lo)">${catIcon}</span>
                                  ${dateBadgeHtml}
                              </div>
                          </div>
                          <button onclick="deleteTask(${data.task.id}); event.stopPropagation();" class="font-mono text-[11px] font-bold text-[#4b5280] hover:text-[color:var(--sakura)] transition px-2 py-2 shrink-0 cursor-pointer md:opacity-0 group-hover:opacity-100">Padam</button>
                      </div>
                      ${descPanelHtml}
                  </div>
              `;
              document.getElementById('task-list').insertAdjacentHTML('afterbegin', newTaskHtml);
              
              // Reset borang
              document.getElementById('input-title').value = '';
              document.getElementById('input-description').value = '';
              document.getElementById('input-duedate').value = '';
              updateDashboardStats(data.stats);
          }
      });
  });

  // AJAX Tukar Status Misi
  function toggleTaskStatus(id) {
      fetch(`/task/${id}`, {
          method: 'PATCH',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
      })
      .then(res => res.json())
      .then(data => {
          if(data.success) {
              const btn = document.querySelector(`#task-item-${id} button`);
              const titleText = document.getElementById(`task-title-${id}`);
              const icon = document.getElementById(`check-icon-${id}`);
              
              if(data.is_completed) {
                  btn.style.backgroundColor = "var(--aqua)";
                  btn.style.borderColor = "transparent";
                  icon.innerText = "✓";
                  titleText.className = "font-display font-bold text-sm md:text-base line-through text-[#4b5280]";
              } else {
                  btn.style.backgroundColor = "#0c0f26";
                  btn.style.borderColor = "var(--line)";
                  icon.innerText = "";
                  titleText.className = "font-display font-bold text-sm md:text-base text-slate-100";
              }
              updateDashboardStats(data.stats);
          }
      });
  }

  // AJAX Padam Misi
  function deleteTask(id) {
      if(!confirm('Adakah anda pasti mahu memadam misi ini?')) return;
      fetch(`/task/${id}`, {
          method: 'DELETE',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
      })
      .then(res => res.json())
      .then(data => {
          if(data.success) {
              const element = document.getElementById(`task-item-${id}`);
              element.style.opacity = '0';
              setTimeout(() => {
                  element.remove();
                  if(document.getElementById('task-list').children.length === 0) {
                      document.getElementById('task-list').innerHTML = `<div id="no-task-alert" class="glass rounded-2xl p-12 text-center border-dashed" style="border-style:dashed; color:var(--text-lo)"><p class="font-display font-bold">Tiada misi sepadan aktif.</p></div>`;
                  }
              }, 200);
              updateDashboardStats(data.stats);
          }
      });
  }

  // JUKEBOX SYSTEM
  const playlist = [
      { name: "Track 1", url: "{{ asset('audio/track1.mp3') }}" },
      { name: "Track 2", url: "{{ asset('audio/track2.mp3') }}" },
     
  ];
  
  let currentTrackIndex = 0;
  const jukebox = document.getElementById('commander-jukebox');
  const toggleBtn = document.getElementById('jukebox-toggle');
  const label = document.getElementById('current-track-label');

  function loadTrack(index) {
      currentTrackIndex = index;
      jukebox.src = playlist[index].url;
      label.innerText = playlist[index].name;
  }

  function toggleJukebox() {
      if (jukebox.paused) {
          jukebox.play().then(() => { toggleBtn.innerText = "⏸"; });
      } else {
          jukebox.pause(); toggleBtn.innerText = "▶";
      }
  }

  function playNextTrack() {
      let nextIndex = (currentTrackIndex + 1) % playlist.length;
      loadTrack(nextIndex); jukebox.play().then(() => { toggleBtn.innerText = "⏸"; });
  }

  function playPrevTrack() {
      let prevIndex = currentTrackIndex - 1;
      if (prevIndex < 0) prevIndex = playlist.length - 1;
      loadTrack(prevIndex); jukebox.play().then(() => { toggleBtn.innerText = "⏸"; });
  }

  jukebox.addEventListener('ended', () => { playNextTrack(); });

  window.addEventListener('load', () => {
      loadTrack(0); jukebox.volume = 0.25;
      if (!sessionStorage.getItem('login_theme_played')) {
          jukebox.play().then(() => {
              toggleBtn.innerText = "⏸";
              sessionStorage.setItem('login_theme_played', 'true');
          }).catch(error => { console.log("Autoplay disekat."); });
      }
  });
  @endauth

  const logoutForm = document.querySelector('form[action="/inline-logout"]');
  if (logoutForm) {
      logoutForm.addEventListener('submit', () => { sessionStorage.removeItem('login_theme_played'); });
  }
</script>
</body>
</html>