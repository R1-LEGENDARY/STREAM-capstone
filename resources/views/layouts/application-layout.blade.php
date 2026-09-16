<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title','STREAM')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    @vite('resources/css/application-shell.css')
    @stack('head')
</head>
<body>
<nav class="navbar navbar-expand-lg glassy-navbar shadow-sm">
  <div class="container-fluid">
    <span class="d-flex align-items-center">
      <span class="rounded-circle bg-white d-inline-block me-2" style="width:40px;height:40px;overflow:hidden;box-shadow:0 1px 4px rgba(33,150,243,0.10);cursor:pointer;" id="logo-preview-trigger">
        <img src="{{ asset('images/logo.png') }}" alt="Logo" style="width:100%;height:100%;object-fit:cover;">
      </span>
      <a href="{{ url('/') }}" class="navbar-brand fw-bold stream-logo" title="Smart Technology for Real-Time Environmental Aquatic Monitoring" style="font-family:'Montserrat',sans-serif;font-weight:900;letter-spacing:2px;font-size:1.7rem;text-decoration:none;">
        S<span style="color:#2196f3;">T</span>R<span style="color:#43a047;">E</span>A<span style="color:#fbc02d;">M</span>
      </a>
    </span>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link {{ request()->is('dashboard')?'active':'' }}" href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="nav-item">
          <a class="nav-link {{ request()->is('summary')?'active':'' }}" href="{{ url('/summary') }}">
            Summary
            <span id="summary-navbar-badge" class="badge bg-warning text-dark align-middle ms-1" style="display:none;">
              <!-- unread count will be injected here -->
            </span>
          </a>
        </li>
        <li class="nav-item"><a class="nav-link {{ request()->is('learn_more')?'active':'' }}" href="{{ url('/learn_more') }}">Learn More</a></li>
      </ul>
    </div>
  </div>
</nav>
<main class="container py-4 page-transition">
    @yield('content')
</main>

<!-- Logo Preview Modal -->
<div class="modal fade" id="logoPreviewModal" tabindex="-1" aria-labelledby="logoPreviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-transparent border-0">
      <div class="modal-body text-center p-0">
        <img src="{{ asset('images/logo.png') }}" alt="Logo Preview" style="max-width:100%;max-height:70vh;border-radius:18px;box-shadow:0 4px 24px rgba(0,0,0,0.18);" id="logo-preview-img">
      </div>
    </div>
  </div>
</div>

<!-- Notification sound element (hidden) -->
<audio id="notif-audio" src="{{ asset('sounds/notification.mp3') }}" preload="auto"></audio>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@vite('resources/js/navigation-behavior.js')
@stack('scripts')
</body>
</html>
