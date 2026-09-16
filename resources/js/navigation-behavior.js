async function refreshSummaryNavbarBadge() {
    try {
        const response = await fetch('/api/summaries');
        const summaries = await response.json();
        const unreadCount = summaries.filter(summary => !summary.seen).length;
        const badge = document.getElementById('summary-navbar-badge');

        if (!badge) return;

        if (unreadCount > 0) {
            badge.style.display = '';
            badge.innerHTML = `<svg width="14" height="14" fill="currentColor" style="vertical-align:middle;margin-right:2px;" viewBox="0 0 16 16"><circle cx="8" cy="8" r="7" fill="#fbc02d"/><text x="8" y="11" text-anchor="middle" font-size="9" fill="#333">${unreadCount}</text></svg> ${unreadCount}`;
        } else {
            badge.style.display = 'none';
            badge.innerHTML = '';
        }
    } catch (error) {
        console.error('Unable to refresh summary badge:', error);
    }
}

function initializeLogoPreview() {
    const logoTrigger = document.getElementById('logo-preview-trigger');
    const modalElement = document.getElementById('logoPreviewModal');

    if (!logoTrigger || !modalElement) return;

    logoTrigger.addEventListener('click', () => {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    });
}

function initializePageTransitions() {
    const main = document.querySelector('.page-transition');

    if (main) {
        setTimeout(() => {
            main.classList.add('page-transition-in');
        }, 10);
    }

    document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
        link.addEventListener('click', event => {
            if (link.target === '_blank' || !link.href || !link.href.startsWith(location.origin)) {
                return;
            }

            event.preventDefault();
            if (!main) {
                window.location.href = link.href;
                return;
            }

            main.classList.remove('page-transition-in');
            setTimeout(() => {
                window.location.href = link.href;
            }, 350);
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initializeLogoPreview();
    initializePageTransitions();
    refreshSummaryNavbarBadge();
    setInterval(refreshSummaryNavbarBadge, 1000);
});
