@extends('layouts.admin')

@section('title', 'Admin Dashboard')

<!-- Inject the Admin Sidebar -->
@section('sidebar')
    @include('layouts.sidebars.admin-side')
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@section('content')
    <!-- Dashboard statistics, calendar, and activity widgets go here -->
    <div class="dashboard-grid">
        <div class="card">
            <span class="card-title">Total Properties</span>
            <span class="card-value">{{ $stats['properties'] ?? '–' }}</span>
            <span class="card-meta">All properties</span>
            <a href="#" class="card-link">View all properties →</a>
        </div>
        <div class="card">
            <span class="card-title">Total Brokers</span>
            <span class="card-value">{{ $stats['users'] ?? '–' }}</span>
            <span class="card-meta">Active brokers</span>
            <a href="#" class="card-link">View all brokers →</a>
        </div>
        <div class="card">
            <span class="card-title">Total Agents</span>
            <span class="card-value">{{ $stats['appointments'] ?? '–' }}</span>
            <span class="card-meta">Active agents</span>
            <a href="#" class="card-link">View all agents →</a>
        </div>
    </div>

    <div class="dashboard-main">
        <div class="dashboard-calendar card">
            <div class="calendar-top">
                <div>
                    <span class="card-title">Appointments Calendar</span>
                    <p class="calendar-description">Shows every appointment for all brokers and agents.</p>
                </div>
                <div class="calendar-nav">
                    <button class="calendar-nav-btn" id="prevMonthBtn" type="button">‹</button>
                    <span class="calendar-month" id="calendarMonthLabel"></span>
                    <button class="calendar-nav-btn" id="nextMonthBtn" type="button">›</button>
                </div>
            </div>

            <div class="calendar-weekdays">
                <span>Sun</span>
                <span>Mon</span>
                <span>Tue</span>
                <span>Wed</span>
                <span>Thu</span>
                <span>Fri</span>
                <span>Sat</span>
            </div>
            <div class="calendar-grid" id="calendarDays"></div>
        </div>

        <div class="dashboard-side-panel">
            <div class="property-status-card card">
                <div class="status-header">
                    <div>
                        <span class="card-title">Property Status Overview</span>
                        <p class="status-description">Breakdown of all property statuses in the system.</p>
                    </div>
                    <span class="status-total">Total Properties: {{ $stats['properties'] ?? '–' }}</span>
                </div>
                <div class="status-body">
                    <div class="status-chart"
                        style="background: conic-gradient(
                            {{ $stats['propertyStatus'][0]['color'] }} 0% {{ max($stats['propertyStatus'][0]['percent'], 1) }}%,
                            {{ $stats['propertyStatus'][1]['color'] }} {{ max($stats['propertyStatus'][0]['percent'], 1) }}% {{ max($stats['propertyStatus'][0]['percent'] + $stats['propertyStatus'][1]['percent'], 2) }}%,
                            {{ $stats['propertyStatus'][2]['color'] }} {{ max($stats['propertyStatus'][0]['percent'] + $stats['propertyStatus'][1]['percent'], 2) }}% {{ max($stats['propertyStatus'][0]['percent'] + $stats['propertyStatus'][1]['percent'] + $stats['propertyStatus'][2]['percent'], 3) }}%,
                            {{ $stats['propertyStatus'][3]['color'] }} {{ max($stats['propertyStatus'][0]['percent'] + $stats['propertyStatus'][1]['percent'] + $stats['propertyStatus'][2]['percent'], 3) }}% 100%
                        );">
                        <div class="status-chart-center"></div>
                    </div>
                    <div class="status-legend">
                        @foreach ($stats['propertyStatus'] as $status)
                            <div class="status-legend-item">
                                <span class="status-dot" style="background: {{ $status['color'] }}"></span>
                                <div>
                                    <div class="status-label">{{ $status['label'] }}</div>
                                    <div class="status-meta">{{ $status['count'] }} ({{ $status['percent'] }}%)</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="activity-card card">
                <div class="status-header">
                    <div>
                        <span class="card-title">Recent System Activities</span>
                        <p class="status-description">A quick summary of the latest events in the system.</p>
                    </div>
                    <a href="#" class="card-link">View all →</a>
                </div>
                <div class="activity-list">
                    @foreach ($activities as $activity)
                        <div class="activity-item">
                            <div class="activity-icon">{{ $activity['icon'] }}</div>
                            <div class="activity-content">
                                <div class="activity-title">{{ $activity['title'] }}</div>
                                <div class="activity-description">{{ $activity['description'] }}</div>
                            </div>
                            <div class="activity-time">{{ $activity['time'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/dashboard.js') }}"></script>
@endpush