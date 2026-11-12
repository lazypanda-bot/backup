<aside class="admin-sidebar" id="adminSidebar" aria-label="Admin sidebar">
    <div class="sidebar-inner">
        <div class="sidebar-top-image">
            <div class="sidebar-logo-card">
                <img src="{{ asset('images/ljs-logo.jpg') }}" alt="LJS Realty Logo" onerror="this.onerror=null; this.src='{{ asset('images/ljs-logo.jpg') }}'">
            </div>
        </div>

        <div class="sidebar-header">
            <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Toggle sidebar">
                <span class="sidebar-toggle-icon"></span>
            </button>
        </div>

    <nav class="sidebar-nav" aria-label="Admin navigation">
        <ul class="sidebar-nav-list">
            <li class="sidebar-nav-item">
                <a class="sidebar-link active" href="#" data-module="dashboard">
                    <span class="sidebar-link-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="3" width="7" height="7" rx="1.5" />
                            <rect x="14" y="3" width="7" height="7" rx="1.5" />
                            <rect x="14" y="14" width="7" height="7" rx="1.5" />
                            <rect x="3" y="14" width="7" height="7" rx="1.5" />
                        </svg>
                    </span>
                    <span class="sidebar-link-text">Dashboard</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a class="sidebar-link" href="#" data-module="users">
                    <span class="sidebar-link-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                    </span>
                    <span class="sidebar-link-text">Users</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a class="sidebar-link" href="#" data-module="messages">
                    <span class="sidebar-link-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                        </svg>
                    </span>
                    <span class="sidebar-link-text">Messages</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a class="sidebar-link" href="#" data-module="properties">
                    <span class="sidebar-link-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 9.5L12 3l9 6.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V9.5z" />
                        </svg>
                    </span>
                    <span class="sidebar-link-text">Property</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a class="sidebar-link" href="#" data-module="appointments">
                    <span class="sidebar-link-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="16" rx="2" />
                            <path d="M16 3v4" />
                            <path d="M8 3v4" />
                            <path d="M3 11h18" />
                        </svg>
                    </span>
                    <span class="sidebar-link-text">Appointment</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a class="sidebar-link" href="#" data-module="reviews">
                    <span class="sidebar-link-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polygon points="12 2 15 9 22 9 17 14 19 21 12 17 5 21 7 14 2 9 9 9 12 2" />
                        </svg>
                    </span>
                    <span class="sidebar-link-text">Review</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a class="sidebar-link" href="#" data-module="reports">
                    <span class="sidebar-link-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 4h16v16H4z" />
                            <path d="M8 12h2" />
                            <path d="M12 16h2" />
                            <path d="M12 8h4" />
                        </svg>
                    </span>
                    <span class="sidebar-link-text">Report</span>
                </a>
            </li>
        </ul>
    </nav>

    @php $user = auth()->user(); @endphp
    <div class="sidebar-footer {{ $user ? 'signed' : 'guest' }}">
        @if ($user)
            <div class="sidebar-profile">
                @if (!empty($user->avatar))
                    <img src="{{ asset($user->avatar) }}" alt="{{ $user->name }}" class="sidebar-profile-avatar">
                @else
                    <span class="sidebar-profile-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                @endif
                <div class="sidebar-profile-details">
                    <span class="sidebar-profile-name">{{ $user->name }}</span>
                </div>
            </div>
        @else
            <div class="sidebar-profile sidebar-profile-guest">
                <span class="sidebar-profile-avatar">G</span>
                <a href="/login" class="sidebar-signin">Sign in</a>
            </div>
        @endif
    </div>
</div>
</aside>