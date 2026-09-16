{{-- resources/views/dashboard.blade.php --}}
@extends('layouts.application-layout')

@section('title', 'STREAM: SMART TECHNOLOGY FOR REAL-TIME ENVIRONMENTAL AQUATIC MONITORING')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @vite('resources/css/dashboard.css')
@endpush

@section('content')

<div id="alert-box" class="alert alert-danger d-none position-fixed w-100 shadow" style="top:0;left:0;z-index:1050;display:none;">
    <button type="button" class="btn-close position-absolute top-0 end-0 m-2 close-alert-btn" aria-label="Close"></button>
    ⚠️ <span id="alert-message">Warning: Water quality is outside safe range!</span>
    <br>
    <span style="font-size:0.95rem;">Please check aeration, feeding, or water exchange.</span>
</div>

<audio id="alert-audio" src="{{ asset('sounds/critical.mp3') }}" loop></audio>


<section id="dashboard" class="container py-5">
    <div class="mb-4">
        
        <div id="weather-forecast" class="weather-forecast-row">
            <div class="text-muted" style="font-size:1rem;">Loading 7-day weather forecast...</div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-12 col-md-6">
            <div class="text-end text-md-start mb-2 mb-md-0">
                <select id="filter-select" class="form-select w-auto d-inline-block">
                    <option value="today" {{ ($filter ?? 'today') === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="7days" {{ ($filter ?? '') === '7days' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="30days" {{ ($filter ?? '') === '30days' ? 'selected' : '' }}>Last 30 Days</option>
                </select>
                
                <span class="ms-2">
                    <button class="btn btn-outline-secondary btn-sm" id="date-range-btn" type="button">
                        <span id="date-range-label">
                            {{ $rangeLabel ?? 'Custom Range' }}
                        </span>
                    </button>
                </span>
            </div>
        </div>
        <div class="col-12 col-md-6 text-end d-flex justify-content-end align-items-center" style="gap:0.5rem;">
            
            <div class="dropdown me-2">
                <button class="btn btn-outline-info dropdown-toggle" type="button" id="animalDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="d-inline-flex align-items-center gap-2">
                        <img id="selected-animal-thumb" class="animal-thumb d-none" alt="Selected aquatic species thumbnail">
                        <span id="selected-animal-label">Select to Monitor</span>
                    </span>
                </button>
                <ul class="dropdown-menu" aria-labelledby="animalDropdownBtn">
                    <li>
                        <a class="dropdown-item animal-dropdown-item animal-select" data-animal="tilapia" data-animal-image="{{ asset('images/tilapia.jpg') }}" href="#">
                            <img src="{{ asset('images/tilapia.jpg') }}" class="animal-thumb" alt="Tilapia thumbnail">
                            <span>Tilapia</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item animal-dropdown-item animal-select" data-animal="milkfish" data-animal-image="{{ asset('images/milkfish.jpg') }}" href="#">
                            <img src="{{ asset('images/milkfish.jpg') }}" class="animal-thumb" alt="Milkfish thumbnail">
                            <span>Milkfish</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item animal-dropdown-item animal-select" data-animal="catfish" data-animal-image="{{ asset('images/catfish.jpg') }}" href="#">
                            <img src="{{ asset('images/catfish.jpg') }}" class="animal-thumb" alt="Catfish thumbnail">
                            <span>Catfish</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item animal-dropdown-item animal-select" data-animal="shrimp" data-animal-image="{{ asset('images/shrimp.jpg') }}" href="#">
                            <img src="{{ asset('images/shrimp.jpg') }}" class="animal-thumb" alt="Shrimp thumbnail">
                            <span>Shrimp</span>
                        </a>
                    </li>
                </ul>
            </div>
            <button class="btn btn-outline-warning me-2" id="data-overview-btn" type="button" data-bs-toggle="modal" data-bs-target="#dataOverviewModal">
                Data Overview
            </button>
            <button class="btn btn-outline-primary" id="get-summary-btn" data-bs-toggle="modal" data-bs-target="#summaryModal">
                Get Summary
            </button>
        </div>
    </div>
    
    <div class="row text-center mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm p-4">
                <div class="sensor-icon" title="Water Temperature">
                    <span>🌡️</span>
                </div>
                <h5>
                    Temperature
                    <span class="ideal-tooltip" id="temp-ideal-tooltip" data-bs-toggle="tooltip" title="" style="cursor:help;">&#9432;</span>
                </h5>
                <h2 id="temp-value" class="text-primary">
                    {{ $readings->last() ? $readings->last()->temperature . ' °C' : '--' }}
                </h2>
                <span id="temp-status" class="status-badge d-none"></span>
                <div id="temp-date" class="reading-date">
                    {{ $readings->last() ? \Carbon\Carbon::parse($readings->last()->recorded_at ?? $readings->last()->created_at)->format('F j, Y h:i:s A') : '' }}
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm p-4">
                <div class="sensor-icon" title="Water pH">
                    <span>🧪</span>
                </div>
                <h5>
                    pH Level
                    <span class="ms-1 ideal-tooltip" id="ph-ideal-tooltip" data-bs-toggle="tooltip" title="" style="cursor:help;">&#9432;</span>
                </h5>
                <h2 id="ph-value" class="text-success">
                    {{ $readings->last() ? $readings->last()->ph : '--' }}
                </h2>
                <span id="ph-status" class="status-badge d-none"></span>
                <div id="ph-date" class="reading-date">
                    {{ $readings->last() ? \Carbon\Carbon::parse($readings->last()->recorded_at ?? $readings->last()->created_at)->format('F j, Y h:i:s A') : '' }}
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm p-4">
                <div class="sensor-icon" title="Dissolved Oxygen">
                    <span>💧</span>
                </div>
                <h5>
                    Dissolved Oxygen
                    <span class="ms-1 ideal-tooltip" id="do-ideal-tooltip" data-bs-toggle="tooltip" title="" style="cursor:help;">&#9432;</span>
                </h5>
                <h2 id="do-value" class="text-info">
                    {{ $readings->last() ? $readings->last()->dissolved_oxygen . ' mg/L' : '--' }}
                </h2>
                <span id="do-status" class="status-badge d-none"></span>
                <div id="do-date" class="reading-date">
                    {{ $readings->last() ? \Carbon\Carbon::parse($readings->last()->recorded_at ?? $readings->last()->created_at)->format('F j, Y h:i:s A') : '' }}
                </div>
            </div>
        </div>
    </div>

    
    <div class="card shadow-sm p-4 mb-4 chart-container">
        <canvas id="tempChart" height="220"></canvas>
    </div>
    <div class="card shadow-sm p-4 mb-4 chart-container">
        <canvas id="phChart" height="220"></canvas>
    </div>
    <div class="card shadow-sm p-4 mb-4 chart-container">
        <canvas id="doChart" height="220"></canvas>
    </div>

    
    <div class="card shadow-sm p-4 mb-4">
        <h6 class="mb-2 text-center" style="font-size:1.15rem;">
            Sensor Readings
            <span class="ms-1" data-bs-toggle="tooltip" title="Latest sensor readings" style="cursor:help;">&#9432;</span>
        </h6>
        <canvas id="latestBarChart" height="220" style="max-height:220px;"></canvas>
    </div>

</section>


<div class="modal fade" id="summaryModal" tabindex="-1" aria-labelledby="summaryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="summaryModalLabel">Summary of Water Quality Data</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="summary-modal-body">
        
        <div class="text-center text-muted">Loading...</div>
      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="dataOverviewModal" tabindex="-1" aria-labelledby="dataOverviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="dataOverviewModalLabel">Data Overview (6-Hour Intervals)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="data-overview-content">
        <div class="text-center text-muted py-4">Preparing data overview...</div>
      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="intervalLogModal" tabindex="-1" aria-labelledby="intervalLogModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="intervalLogModalLabel">Historical Log</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
          <button type="button" class="btn btn-sm btn-outline-secondary" id="interval-log-prev-btn" disabled>&laquo; Previous Day</button>
          <div class="flex-grow-1 text-center">
            <span class="fw-semibold" id="interval-log-date-label">Select an interval to begin.</span>
          </div>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="interval-log-next-btn" disabled>Next Day &raquo;</button>
        </div>
        <div id="interval-log-description" class="small text-muted mb-3">
          Select an interval within the Data Overview to review its historical readings. Use the day controls to move between available dates.
        </div>
        <div id="interval-log-content" class="border rounded p-3 bg-light">
          <div class="text-muted">No interval selected. Use the "View Log" button inside the Data Overview to begin.</div>
        </div>
      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="logoPreviewModal" tabindex="-1" aria-labelledby="logoPreviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-transparent border-0">
      <div class="modal-body text-center p-0">
        <img src="{{ asset('images/logo.png') }}" alt="Logo Preview" style="max-width:100%;max-height:70vh;border-radius:18px;box-shadow:0 4px 24px rgba(0,0,0,0.18);" id="logo-preview-img">
      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="dateRangeModal" tabindex="-1" aria-labelledby="dateRangeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px;">
      <div class="modal-header">
        <h5 class="modal-title" id="dateRangeModalLabel">Select Date Range</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <div class="row g-3 justify-content-center">
          <div class="col-12 col-sm-6">
            <label class="form-label">From Date</label>
            <input type="date" id="modal-date-from" class="form-control text-center" value="{{ $fromDate ?? '' }}">
          </div>
          <div class="col-12 col-sm-6">
            <label class="form-label">From Time</label>
            <input type="time" id="modal-time-from" class="form-control text-center" value="{{ $fromTime ?? '' }}">
          </div>
          <div class="col-12 col-sm-6">
            <label class="form-label">To Date</label>
            <input type="date" id="modal-date-to" class="form-control text-center" value="{{ $toDate ?? '' }}">
          </div>
          <div class="col-12 col-sm-6">
            <label class="form-label">To Time</label>
            <input type="time" id="modal-time-to" class="form-control text-center" value="{{ $toTime ?? '' }}">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="apply-date-range-btn">Apply</button>
        <button type="button" class="btn btn-outline-secondary" id="clear-date-range-btn">Clear</button>
      </div>
    </div>
  </div>
</div>

<script id="dashboard-config" type="application/json">
    {!! json_encode([
        'filter' => $filter ?? 'today',
        'from' => $from ?? '',
        'to' => $to ?? '',
        'fromDate' => $fromDate ?? '',
        'toDate' => $toDate ?? '',
        'fromTime' => $fromTime ?? '',
        'toTime' => $toTime ?? '',
        'initialReadings' => $readings,
        'animalOptions' => [
            'tilapia' => ['label' => 'Tilapia', 'image' => asset('images/tilapia.jpg')],
            'milkfish' => ['label' => 'Milkfish', 'image' => asset('images/milkfish.jpg')],
            'catfish' => ['label' => 'Catfish', 'image' => asset('images/catfish.jpg')],
            'shrimp' => ['label' => 'Shrimp', 'image' => asset('images/shrimp.jpg')],
        ],
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
@vite('resources/js/dashboard.js')
@endsection