@extends('layouts.application-layout')

@section('title','Summary')

@section('content')
@php

    $temps = $summaries->pluck('temperature')->filter(fn($v) => is_numeric($v));
    $phs = $summaries->pluck('ph')->filter(fn($v) => is_numeric($v));
    $dos = $summaries->pluck('dissolved_oxygen')->filter(fn($v) => is_numeric($v));
    $stats = [
        'temperature' => [
            'min' => $temps->min() ?? '--',
            'max' => $temps->max() ?? '--',
            'avg' => $temps->avg() ? number_format($temps->avg(), 2) : '--',
        ],
        'ph' => [
            'min' => $phs->min() ?? '--',
            'max' => $phs->max() ?? '--',
            'avg' => $phs->avg() ? number_format($phs->avg(), 2) : '--',
        ],
        'do' => [
            'min' => $dos->min() ?? '--',
            'max' => $dos->max() ?? '--',
            'avg' => $dos->avg() ? number_format($dos->avg(), 2) : '--',
        ],
    ];
@endphp
<section class="container py-4" aria-labelledby="summary-page-title">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm p-4 mb-4" style="border-radius:14px;">
                <h2 id="summary-page-title" class="mb-3 text-primary" style="font-family:'Montserrat',sans-serif;font-weight:700;">
                    Summary
                </h2>
                <div class="d-flex align-items-center mb-3">
                    <div class="form-check me-3">
                        <input type="checkbox" class="form-check-input" id="select-all-summaries" />
                        <label for="select-all-summaries" class="form-check-label small">Select All</label>
                    </div>
                    <button id="delete-selected-btn" class="btn btn-danger btn-sm" disabled>Delete Selected</button>
                </div>

                
                <div class="list-group">
                    @forelse($summaries as $s)
                        @php
                            $tempStat = ($s->temperature >= 26 && $s->temperature <= 32) ? 'Safe'
                                : (($s->temperature < 24 || $s->temperature > 34) ? 'Critical' : 'Warning');
                            $phStat = ($s->ph >= 6.5 && $s->ph <= 8.5) ? 'Safe'
                                : (($s->ph < 6 || $s->ph > 9) ? 'Critical' : 'Warning');
                            $doStat = ($s->dissolved_oxygen >= 5) ? 'Safe'
                                : (($s->dissolved_oxygen < 3) ? 'Critical' : 'Warning');
                            $badgeClass = function($stat) {
                                if ($stat === 'Critical') return 'bg-danger text-white';
                                if ($stat === 'Warning') return 'bg-warning text-dark';
                                return 'bg-success text-white';
                            };
                        @endphp

                        <article class="list-group-item list-group-item-action d-flex align-items-start justify-content-between py-3" style="border-radius:12px;margin-bottom:10px;">
                            <div class="d-flex align-items-start" style="gap:12px;">
                                <div style="width:44px;height:44px;border-radius:10px;background:linear-gradient(135deg,#fff,#e3f2fd);display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(25,118,210,0.06);font-weight:700;color:#1976d2;">
                                    
                                    N
                                </div>
                                <div>
                                    <div class="d-flex align-items-center" style="gap:10px;">
                                        <div class="small text-muted">{{ \Carbon\Carbon::parse($s->recorded_at)->format('F j, Y h:i:s A') }}</div>
                                        <div class="ms-2">
                                            <span class="badge {{ $badgeClass($tempStat) }}">Temp: {{ $s->temperature }} °C</span>
                                            <span class="badge {{ $badgeClass($phStat) }}">pH: {{ $s->ph }}</span>
                                            <span class="badge {{ $badgeClass($doStat) }}">DO: {{ $s->dissolved_oxygen }}</span>
                                        </div>
                                    </div>
                                    <div class="mt-2" style="line-height:1.2;">
                                        <span class="content-label text-dark">Summary ID: {{ $s->id }}</span>
                                        <div class="small text-muted">Realtime sensor snapshot for the recorded time.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-column align-items-end" style="gap:10px;">
                                <div>
                                    <input type="checkbox" class="form-check-input summary-checkbox" value="{{ $s->id }}">
                                </div>
                                <div>
                                    <button class="btn btn-outline-secondary btn-sm see-more-btn" title="View details"
                                        data-summary-id="{{ $s->id }}"
                                        data-summary-date="{{ \Carbon\Carbon::parse($s->recorded_at)->format('Y-m-d') }}"
                                        data-summary-temp="{{ $s->temperature }}"
                                        data-summary-ph="{{ $s->ph }}"
                                        data-summary-do="{{ $s->dissolved_oxygen }}"
                                        data-summary-time="{{ \Carbon\Carbon::parse($s->recorded_at)->format('F j, Y h:i:s A') }}"
                                        data-summary-date-display="{{ \Carbon\Carbon::parse($s->recorded_at)->format('F j, Y') }}"
                                        style="padding:6px 10px;"
                                    >
                                        See More
                                    </button>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="text-center text-muted py-4">No summaries.</div>
                    @endforelse
                </div>

            </div>
        </div>
    </div>
</section>


<div class="modal fade" id="summaryDetailModal" tabindex="-1" aria-labelledby="summaryDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border-radius:14px;">
      <div class="modal-header">
        <h5 class="modal-title" id="summaryDetailModalLabel">Summary Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="summary-detail-info" class="mb-3"></div>
        <div class="row text-center">
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm p-3" style="border-radius:14px;">
                    <h6 class="mb-2 text-primary">Temperature (°C)</h6>
                    <canvas id="modalTempChart" width="110" height="110"></canvas>
                    <div class="mt-2" id="modalTempStats"></div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm p-3" style="border-radius:14px;">
                    <h6 class="mb-2 text-success">pH</h6>
                    <canvas id="modalPhChart" width="110" height="110"></canvas>
                    <div class="mt-2" id="modalPhStats"></div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm p-3" style="border-radius:14px;">
                    <h6 class="mb-2 text-info">Dissolved Oxygen (mg/L)</h6>
                    <canvas id="modalDoChart" width="110" height="110"></canvas>
                    <div class="mt-2" id="modalDoStats"></div>
                </div>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:12px;">
      <div class="modal-header">
        <h5 class="modal-title">Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete <strong id="confirm-delete-count">0</strong> selected summary(ies)? This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirm-delete-submit" class="btn btn-danger btn-sm">Delete</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script id="summary-config" type="application/json">
    {!! json_encode([
        'deleteRoute' => route('summary.delete'),
        'csrfToken' => csrf_token(),
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
@vite('resources/js/summary.js')
@endsection

