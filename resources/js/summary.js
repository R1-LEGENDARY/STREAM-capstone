const summaryConfig = JSON.parse(document.getElementById('summary-config')?.textContent || '{}');

const getCheckboxes = () => Array.from(document.querySelectorAll('.summary-checkbox'));

function updateDeleteButtonState() {
    const deleteButton = document.getElementById('delete-selected-btn');
    const selectAllCheckbox = document.getElementById('select-all-summaries');
    const hasCheckedItems = getCheckboxes().some((checkbox) => checkbox.checked);

    if (deleteButton) {
        deleteButton.disabled = !hasCheckedItems;
    }

    if (selectAllCheckbox) {
        const totalCheckboxes = getCheckboxes().length;
        const checkedCheckboxes = getCheckboxes().filter((checkbox) => checkbox.checked).length;
        selectAllCheckbox.checked = totalCheckboxes > 0 && checkedCheckboxes === totalCheckboxes;
    }
}

function getStatsForDate(date) {
    return fetch('/api/readings?filter=30days')
        .then((response) => response.json())
        .then((readings) => {
            const filteredReadings = readings.filter((reading) => reading.recorded_at && reading.recorded_at.startsWith(date));

            const buildStats = (items, key) => {
                const values = items
                    .map((entry) => Number(entry[key]))
                    .filter((value) => Number.isFinite(value));

                if (!values.length) {
                    return { min: '--', max: '--', avg: '--' };
                }

                return {
                    min: Math.min(...values),
                    max: Math.max(...values),
                    avg: (values.reduce((sum, value) => sum + value, 0) / values.length).toFixed(2)
                };
            };

            return {
                temperature: buildStats(filteredReadings, 'temperature'),
                ph: buildStats(filteredReadings, 'ph'),
                do: buildStats(filteredReadings, 'dissolved_oxygen')
            };
        })
        .catch(() => ({
            temperature: { min: '--', max: '--', avg: '--' },
            ph: { min: '--', max: '--', avg: '--' },
            do: { min: '--', max: '--', avg: '--' }
        }));
}

function attachCheckboxHandlers() {
    const selectAllCheckbox = document.getElementById('select-all-summaries');

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', (event) => {
            getCheckboxes().forEach((checkbox) => {
                checkbox.checked = event.target.checked;
            });
            updateDeleteButtonState();
        });
    }

    getCheckboxes().forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            updateDeleteButtonState();
        });
    });
}

function setBadgeContent(element, content) {
    if (!element) return;
    element.innerHTML = content;
}

function createChart(canvas, stats, colors, labels) {
    if (!canvas) return null;

    const existingChart = canvas.__chartInstance;
    if (existingChart) {
        existingChart.destroy();
    }

    const values = [stats.min, stats.avg, stats.max].map((value) => (Number.isNaN(Number(value)) ? 0 : Number(value)));
    const total = values.reduce((sum, value) => sum + value, 0);
    const data = total > 0 ? values : [1, 1, 1];

    const chart = new Chart(canvas.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data,
                backgroundColor: colors,
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            cutout: '70%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.label}: ${context.parsed}`
                    }
                }
            }
        }
    });

    canvas.__chartInstance = chart;
    return chart;
}

async function showSummaryModal(summary) {
    const stats = await getStatsForDate(summary.date);

    const detailInfo = document.getElementById('summary-detail-info');
    if (detailInfo) {
        detailInfo.innerHTML = `
            <div>
                <span class="content-label">Time:</span> ${summary.time}<br>
                <span class="content-label">Temperature:</span> <span class="text-primary">${summary.temp}</span> °C<br>
                <span class="content-label">pH:</span> <span class="text-success">${summary.ph}</span><br>
                <span class="content-label">Dissolved Oxygen:</span> <span class="text-info">${summary.do}</span> mg/L
            </div>
            <div class="mt-2 small text-muted">Stats below are for all readings on <span class="content-label">${summary.dateDisplay || summary.date}</span></div>
        `;
    }

    const tempCanvas = document.getElementById('modalTempChart');
    const phCanvas = document.getElementById('modalPhChart');
    const doCanvas = document.getElementById('modalDoChart');

    createChart(tempCanvas, stats.temperature, ['#ffb74d', '#1976d2', '#d32f2f'], ['Min', 'Avg', 'Max']);
    createChart(phCanvas, stats.ph, ['#81c784', '#43a047', '#fbc02d'], ['Min', 'Avg', 'Max']);
    createChart(doCanvas, stats.do, ['#4fc3f7', '#0288d1', '#8e24aa'], ['Min', 'Avg', 'Max']);

    const tempStats = document.getElementById('modalTempStats');
    if (tempStats) {
        setBadgeContent(tempStats, `
            <span class="badge" style="background:#90caf9;color:#1565c0;">Min: ${stats.temperature.min}</span>
            <span class="badge" style="background:#1976d2;color:#fff;">Avg: ${stats.temperature.avg}</span>
            <span class="badge" style="background:#1565c0;color:#fff;">Max: ${stats.temperature.max}</span>
        `);
    }

    const phStats = document.getElementById('modalPhStats');
    if (phStats) {
        setBadgeContent(phStats, `
            <span class="badge" style="background:#a5d6a7;color:#2e7d32;">Min: ${stats.ph.min}</span>
            <span class="badge" style="background:#43a047;color:#fff;">Avg: ${stats.ph.avg}</span>
            <span class="badge" style="background:#2e7d32;color:#fff;">Max: ${stats.ph.max}</span>
        `);
    }

    const doStats = document.getElementById('modalDoStats');
    if (doStats) {
        setBadgeContent(doStats, `
            <span class="badge" style="background:#b3e5fc;color:#01579b;">Min: ${stats.do.min}</span>
            <span class="badge" style="background:#0288d1;color:#fff;">Avg: ${stats.do.avg}</span>
            <span class="badge" style="background:#01579b;color:#fff;">Max: ${stats.do.max}</span>
        `);
    }

    const modalElement = document.getElementById('summaryDetailModal');
    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }
}

function attachSummaryDetailHandlers() {
    document.querySelectorAll('.see-more-btn').forEach((button) => {
        button.addEventListener('click', () => {
            showSummaryModal({
                id: button.dataset.summaryId,
                date: button.dataset.summaryDate,
                temp: button.dataset.summaryTemp,
                ph: button.dataset.summaryPh,
                do: button.dataset.summaryDo,
                time: button.dataset.summaryTime,
                dateDisplay: button.dataset.summaryDateDisplay
            });
        });
    });
}

function deleteSelectedSummaries() {
    const checkedIds = getCheckboxes()
        .filter((checkbox) => checkbox.checked)
        .map((checkbox) => checkbox.value);

    if (!checkedIds.length) {
        return;
    }

    const confirmButton = document.getElementById('confirm-delete-submit');
    const modalElement = document.getElementById('confirmDeleteModal');
    const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);

    if (confirmButton) {
        confirmButton.disabled = true;
        const previousText = confirmButton.innerHTML;
        confirmButton.innerHTML = 'Deleting...';
        confirmButton.dataset.originalText = previousText;
    }

    fetch(summaryConfig.deleteRoute || '/api/summaries/delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': summaryConfig.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ ids: checkedIds })
    })
        .then((response) => response.json())
        .then((data) => {
            modal.hide();
            if (data.success) {
                window.location.reload();
                return;
            }

            window.alert('Failed to delete summaries.');
            if (confirmButton) {
                confirmButton.disabled = false;
                confirmButton.innerHTML = confirmButton.dataset.originalText || 'Delete';
            }
        })
        .catch(() => {
            modal.hide();
            window.alert('Failed to delete summaries.');
            if (confirmButton) {
                confirmButton.disabled = false;
                confirmButton.innerHTML = confirmButton.dataset.originalText || 'Delete';
            }
        });
}

function showConfirmDeleteModal() {
    const selectedCheckboxes = getCheckboxes().filter((checkbox) => checkbox.checked);

    if (!selectedCheckboxes.length) {
        window.alert('Please select summaries to delete.');
        return;
    }

    const countElement = document.getElementById('confirm-delete-count');
    if (countElement) {
        countElement.textContent = String(selectedCheckboxes.length);
    }

    const modalElement = document.getElementById('confirmDeleteModal');
    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }
}

function initializeSummaryPage() {
    attachCheckboxHandlers();
    attachSummaryDetailHandlers();
    updateDeleteButtonState();

    const deleteButton = document.getElementById('delete-selected-btn');
    if (deleteButton) {
        deleteButton.addEventListener('click', showConfirmDeleteModal);
    }

    const confirmDeleteButton = document.getElementById('confirm-delete-submit');
    if (confirmDeleteButton) {
        confirmDeleteButton.dataset.originalText = confirmDeleteButton.textContent.trim();
        confirmDeleteButton.addEventListener('click', deleteSelectedSummaries);
    }

    const modalElement = document.getElementById('summaryDetailModal');
    if (modalElement) {
        modalElement.addEventListener('hidden.bs.modal', () => {
            ['modalTempChart', 'modalPhChart', 'modalDoChart'].forEach((id) => {
                const canvas = document.getElementById(id);
                if (!canvas) return;
                if (canvas.__chartInstance) {
                    canvas.__chartInstance.destroy();
                    canvas.__chartInstance = null;
                }
            });
        });
    }
}

document.addEventListener('DOMContentLoaded', initializeSummaryPage);
