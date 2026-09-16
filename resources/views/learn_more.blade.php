@extends('layouts.application-layout')

@section('title', 'Optimal Water Quality Standards for Cultured Fish - STREAM')

@push('head')
    <meta name="description" content="Learn about optimal water quality standards for various fish species and monitoring sensors.">
    @vite('resources/css/learn_more.css')
@endpush

@section('content')
    <div class="py-1">
        {{-- Fish Species Section --}}
        <section id="fishes-section" class="section-anchor">
            <h2 class="mb-4 text-center learnmore-3d-title">🐟 Fish Species & Water Quality Requirements</h2>
            <div class="row g-4">
                {{-- Tilapia Card --}}
                <div class="col-md-4 d-flex">
                <div class="card shadow-sm mat-card h-100 w-100 d-flex flex-column">
                    <img src="{{ asset('images/tilapia.jpg') }}" class="mat-img" alt="Tilapia fish">
                <div class="card-body d-flex flex-column flex-grow-1">
                    <h4 class="card-title">Tilapia</h4>
                    <ul class="list-unstyled mb-2">
                        <li><span class="content-label">Temperature:</span> 27–30°C</li>
                        <li><span class="content-label">pH:</span> 6.5–8.5</li>
                        <li><span class="content-label">Dissolved Oxygen:</span> &gt;3 mg/L</li>
                    </ul>
                    <p class="text-muted small">Tilapia are hardy but optimal growth is achieved within these ranges. (BFAR PH)</p>
                </div>
            </div>
        </div>

        {{-- Milkfish Card --}}
        <div class="col-md-4 d-flex">
            <div class="card shadow-sm mat-card h-100 w-100 d-flex flex-column">
                <img src="{{ asset('images/milkfish.jpg') }}" class="mat-img" alt="Milkfish (Bangus)">
                <div class="card-body d-flex flex-column flex-grow-1">
                    <h4 class="card-title">Milkfish (Bangus)</h4>
                    <ul class="list-unstyled mb-2">
                        <li><span class="content-label">Temperature:</span> 26–32°C</li>
                        <li><span class="content-label">pH:</span> 7.5–8.5</li>
                        <li><span class="content-label">Dissolved Oxygen:</span> &gt;3 mg/L</li>
                    </ul>
                    <p class="text-muted small">Milkfish thrive in brackish water and are sensitive to low oxygen and sudden pH changes. (BFAR PH)</p>
                </div>
            </div>
        </div>

        {{-- Catfish Card --}}
        <div class="col-md-4 d-flex">
            <div class="card shadow-sm mat-card h-100 w-100 d-flex flex-column">
                <img src="{{ asset('images/catfish.jpg') }}" class="mat-img" alt="Catfish">
                <div class="card-body d-flex flex-column flex-grow-1">
                    <h4 class="card-title">Catfish (Hito)</h4>
                    <ul class="list-unstyled mb-2">
                        <li><span class="content-label">Temperature:</span> 27–30°C</li>
                        <li><span class="content-label">pH:</span> 6.5–8.5</li>
                        <li><span class="content-label">Dissolved Oxygen:</span> &gt;3 mg/L</li>
                    </ul>
                    <p class="text-muted small">Catfish are tolerant but best growth is in optimal water quality. (BFAR PH)</p>
                </div>
            </div>
        </div>

        {{-- Shrimp Card --}}
        <div class="col-md-4 d-flex">
            <div class="card shadow-sm mat-card h-100 w-100 d-flex flex-column">
                <img src="{{ asset('images/shrimp.jpg') }}" class="mat-img" alt="Shrimp">
                <div class="card-body d-flex flex-column flex-grow-1">
                    <h4 class="card-title">Shrimp (Sugpo)</h4>
                    <ul class="list-unstyled mb-2">
                        <li><span class="content-label">Temperature:</span> 28–32°C</li>
                        <li><span class="content-label">pH:</span> 7.5–8.5</li>
                        <li><span class="content-label">Dissolved Oxygen:</span> &gt;4 mg/L</li>
                    </ul>
                    <p class="text-muted small mb-3">Shrimp are sensitive to water quality changes and require stable, clean water. (BFAR PH)</p>
                    <a href="https://www.bfar.da.gov.ph/" target="_blank" rel="noopener" class="learn-more-link mt-auto">See BFAR shrimp culture tips →</a>
                </div>
            </div>
        </div>

        {{-- BFAR Resources Card --}}
        <div class="col-md-8 col-lg-8 d-flex pe-md-0">
            <div class="card shadow-sm mat-card article-card bfar-article-card h-100 w-100 d-flex flex-column">
                <div class="card-body d-flex flex-column flex-grow-1">
                    <h4 class="card-title mb-3">Dive deeper with BFAR resources</h4>
                    <h6 class="section-heading">Spotlight Articles</h6>
                    <ul class="text-muted small article-list mb-3">
                        <li><span class="content-label">Managing pond water quality</span> – learn how BFAR recommends balancing aeration, water exchange, and probiotic use.</li>
                        <li><span class="content-label">Stocking density guides</span> – plan tilapia, milkfish, and catfish grow-outs with BFAR’s stocking calculators.</li>
                        <li><span class="content-label">Shrimp health advisories</span> – stay ahead of EMS/WSSV alerts with BFAR’s biosecurity bulletins.</li>
                    </ul>
                    <blockquote>“Sustainable aquaculture begins with consistent monitoring and the right intervention at the right time.”<br><span class="small">— BFAR Technical Bulletin</span></blockquote>
                    <p class="text-muted small mb-3">Browse BFAR’s aquaculture manuals, training materials, and localized advisories to support healthy fisheries in your province.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="https://www.bfar.da.gov.ph/" target="_blank" rel="noopener" class="learn-more-link">Explore BFAR’s knowledge hub →</a>
                        <a href="https://www.bfar.da.gov.ph/programs-projects/" target="_blank" rel="noopener" class="learn-more-link">See national programs →</a>
                    </div>
                </div>
            </div>
        </div>
            </div>
        </section>

        <div class="mt-4 text-center text-muted small">
            <em>Note: Values are based on BFAR Philippines guidelines. Always monitor your pond and consult local experts for best results.</em>
        </div>

        {{-- Monitoring Sensors Section --}}
        <section id="materials-section" class="mt-5 section-anchor">
            <h2 class="mb-4 text-center learnmore-3d-title">🔧 Monitoring Sensors</h2>
            
            <div class="row g-4">
                {{-- pH Sensor Card --}}
        <div class="col-md-4 d-flex">
            <div class="card shadow-sm mat-card h-100 w-100 d-flex flex-column">
                <img src="{{ asset('images/ph_sensor.jpg') }}" class="mat-img" alt="pH Sensor">
                <div class="card-body d-flex flex-column flex-grow-1">
                    <h4 class="card-title">pH Sensor</h4>
                    <p class="mb-2"><span class="content-label">What it does:</span> Measures the acidity or alkalinity of water. Essential for monitoring water quality for fish health.</p>
                </div>
            </div>
        </div>

        {{-- DO Sensor Card --}}
        <div class="col-md-4 d-flex">
            <div class="card shadow-sm mat-card h-100 w-100 d-flex flex-column">
                <img src="{{ asset('images/do_sensor.jpg') }}" class="mat-img" alt="DO Sensor">
                <div class="card-body d-flex flex-column flex-grow-1">
                    <h4 class="card-title">Dissolved Oxygen (DO) Sensor</h4>
                    <p class="mb-2"><span class="content-label">What it does:</span> Measures the amount of oxygen dissolved in water (mg/L). Critical for fish respiration and pond health.</p>
                </div>
            </div>
        </div>

        {{-- Temperature Sensor Card --}}
        <div class="col-md-4 d-flex">
            <div class="card shadow-sm mat-card h-100 w-100 d-flex flex-column">
                <img src="{{ asset('images/temp_sensor.jpg') }}" class="mat-img" alt="Temperature Sensor">
                <div class="card-body d-flex flex-column flex-grow-1">
                    <h4 class="card-title">Temperature Sensor</h4>
                    <p class="mb-2"><span class="content-label">What it does:</span> Monitors the water temperature to ensure it stays within the optimal range for fish growth.</p>
                </div>
            </div>
        </div>
    </div>
            <div class="mt-4 text-center text-muted small">
                <em>These components work together to provide real-time water quality monitoring for aquaculture.</em>
            </div>
        </section>
    </div>

    </div>
@endsection