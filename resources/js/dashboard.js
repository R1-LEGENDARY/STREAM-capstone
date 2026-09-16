const dashboardConfig = JSON.parse(document.getElementById('dashboard-config')?.textContent || '{}');

let currentFilter = dashboardConfig.filter || 'today';
let filterFrom = dashboardConfig.from || '';
let filterTo = dashboardConfig.to || '';
const initialReadings = Array.isArray(dashboardConfig.initialReadings) ? dashboardConfig.initialReadings : [];
let fromDateInput = dashboardConfig.fromDate || '';
let toDateInput = dashboardConfig.toDate || '';
let fromTimeInput = dashboardConfig.fromTime || '';
let toTimeInput = dashboardConfig.toTime || '';
const rangeLabelEl = document.getElementById('date-range-label');

function buildDateTimeString(dateStr, timeStr, isEndOfDay = false) {
    if (!dateStr) {
        return '';
    }

    if (timeStr && timeStr.trim()) {
        return `${dateStr} ${timeStr.trim()}`;
    }

    return `${dateStr} ${isEndOfDay ? '23:59:59' : '00:00:00'}`;
}

function formatTimeDisplay(timeStr) {
    if (!timeStr) {
        return '';
    }

    const parts = timeStr.split(':');
    if (parts.length < 2) {
        return timeStr;
    }

    const date = new Date();
    date.setHours(parseInt(parts[0], 10));
    date.setMinutes(parseInt(parts[1], 10));
    date.setSeconds(parts[2] ? parseInt(parts[2], 10) : 0);

    return date.toLocaleTimeString(undefined, {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}

function formatRangeDisplay(fromDate, toDate, fromTime, toTime) {
    if (!fromDate || !toDate) {
        return 'Custom Range';
    }

    const dateOptions = { year: 'numeric', month: 'short', day: 'numeric' };
    const fromDateLabel = new Date(`${fromDate}T00:00:00`).toLocaleDateString(undefined, dateOptions);
    const toDateLabel = new Date(`${toDate}T00:00:00`).toLocaleDateString(undefined, dateOptions);
    const fromLabel = fromTime ? `${fromDateLabel} ${formatTimeDisplay(fromTime)}` : fromDateLabel;
    const toLabel = toTime ? `${toDateLabel} ${formatTimeDisplay(toTime)}` : toDateLabel;

    return `${fromLabel} → ${toLabel}`;
}

function formatRangeLabelFallback(fromValue, toValue) {
    if (!fromValue || !toValue) {
        return 'Custom Range';
    }

    const parse = (value) => {
        let dt = new Date(value);
        if (Number.isNaN(dt.getTime()) && value.includes(' ')) {
            dt = new Date(value.replace(' ', 'T'));
        }
        return Number.isNaN(dt.getTime()) ? null : dt;
    };

    const fromObj = parse(fromValue);
    const toObj = parse(toValue);

    if (fromObj && toObj) {
        const options = {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        };

        return `${fromObj.toLocaleString(undefined, options)} → ${toObj.toLocaleString(undefined, options)}`;
    }

    return `${fromValue} → ${toValue}`;
}

if (fromDateInput && !filterFrom) {
    filterFrom = buildDateTimeString(fromDateInput, fromTimeInput, false);
}

if (toDateInput && !filterTo) {
    filterTo = buildDateTimeString(toDateInput, toTimeInput, true);
}

if (rangeLabelEl) {
    if (fromDateInput && toDateInput) {
        rangeLabelEl.textContent = formatRangeDisplay(fromDateInput, toDateInput, fromTimeInput, toTimeInput);
    } else if (filterFrom && filterTo) {
        rangeLabelEl.textContent = formatRangeLabelFallback(filterFrom, filterTo);
    }
}

const animalOptions = dashboardConfig.animalOptions || {
    tilapia: {
        label: 'Tilapia',
        image: '/images/tilapia.jpg'
    },
    milkfish: {
        label: 'Milkfish',
        image: '/images/milkfish.jpg'
    },
    catfish: {
        label: 'Catfish',
        image: '/images/catfish.jpg'
    },
    shrimp: {
        label: 'Shrimp',
        image: '/images/shrimp.jpg'
    }
};

async function fetchReadingsForCurrentView() {
    const params = [];

    if (filterFrom) {
        params.push(`from=${encodeURIComponent(filterFrom)}`);
    }

    if (filterTo) {
        params.push(`to=${encodeURIComponent(filterTo)}`);
    }

    if (!params.length) {
        params.push(`filter=${encodeURIComponent(currentFilter)}`);
    }

    const response = await fetch(`/api/readings?${params.join('&')}`);
    if (!response.ok) {
        throw new Error('Failed to fetch readings');
    }
    return response.json();
}

function playAlertAudio() {
    const alertAudio = document.getElementById('alert-audio');
    if (!alertAudio) {
        return;
    }

    alertAudio.currentTime = 0;
    alertAudio.play().catch(() => {});
}

function formatDate(dt) {
    if (!dt) {
        return '';
    }

    const date = new Date(dt);
    const options = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    };

    return date.toLocaleString(undefined, options);
}

function formatDateOnly(dt) {
    if (!dt) {
        return '';
    }

    const date = new Date(dt);
    const options = {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };

    return date.toLocaleDateString(undefined, options);
}

function formatTimeOnly(dt) {
    if (!dt) {
        return '';
    }

    const date = new Date(dt);
    const options = {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    };

    return date.toLocaleTimeString(undefined, options);
}

function formatHourMinute(hour, minute) {
    if (!Number.isFinite(hour)) {
        return '--';
    }

    const normalizedHour = ((hour % 24) + 24) % 24;
    const hour12 = ((normalizedHour + 11) % 12) + 1;
    const minutePadded = String(Number.isFinite(minute) ? minute : 0).padStart(2, '0');
    const suffix = normalizedHour >= 12 ? 'PM' : 'AM';
    return `${hour12}:${minutePadded} ${suffix}`;
}

function getIntervalRangeLabel(interval) {
    if (!interval) {
        return '';
    }

    const start = formatHourMinute(interval.startHour, 0);
    const end = formatHourMinute(interval.endHour, 59);
    return `${start} – ${end}`;
}

function dateKeyFromDate(dateObj) {
    if (!dateObj) {
        return null;
    }

    const year = dateObj.getFullYear();
    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
    const day = String(dateObj.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function getLabels(readings) {
    return readings.map((reading) => {
        const timestamp = reading.recorded_at ?? reading.created_at;
        return formatDate(timestamp);
    });
}

const tempCanvas = document.getElementById('tempChart');
const tempCtx = tempCanvas ? tempCanvas.getContext('2d') : null;
const tempGradient = tempCtx ? tempCtx.createLinearGradient(0, 0, 0, 220) : null;

if (tempGradient) {
    tempGradient.addColorStop(0, 'rgba(255,0,0,0.18)');
    tempGradient.addColorStop(1, 'rgba(255,0,0,0.02)');
}

const tempChart = tempCtx ? new Chart(tempCtx, {
    type: 'line',
    data: {
        labels: getLabels(initialReadings),
        datasets: [{
            label: 'Temperature (°C)',
            data: initialReadings.map((reading) => reading.temperature),
            borderColor: 'red',
            backgroundColor: tempGradient,
            fill: true,
            pointRadius: 3,
            pointHoverRadius: 6,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                ticks: {
                    autoSkip: true,
                    maxTicksLimit: 8,
                    callback(value, index, values) {
                        const label = this.getLabelForValue(value);
                        return label.split(',')[0];
                    }
                }
            },
            y: {
                min: 20,
                max: 45,
                ticks: { stepSize: 2 },
                grid: { color: '#ffe0e0' }
            }
        },
        plugins: {
            legend: {
                display: true,
                onClick: null,
                labels: { boxWidth: 0, boxHeight: 0 }
            },
            tooltip: {
                callbacks: {
                    title(context) {
                        return context[0].label;
                    }
                }
            }
        }
    }
}) : null;

const phCanvas = document.getElementById('phChart');
const phCtx = phCanvas ? phCanvas.getContext('2d') : null;
const phGradient = phCtx ? phCtx.createLinearGradient(0, 0, 0, 220) : null;

if (phGradient) {
    phGradient.addColorStop(0, 'rgba(76,175,80,0.18)');
    phGradient.addColorStop(1, 'rgba(76,175,80,0.02)');
}

const phChart = phCtx ? new Chart(phCtx, {
    type: 'line',
    data: {
        labels: getLabels(initialReadings),
        datasets: [{
            label: 'pH',
            data: initialReadings.map((reading) => reading.ph),
            borderColor: 'green',
            backgroundColor: phGradient,
            fill: true,
            pointRadius: 3,
            pointHoverRadius: 6,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                ticks: {
                    autoSkip: true,
                    maxTicksLimit: 8,
                    callback(value, index, values) {
                        const label = this.getLabelForValue(value);
                        return label.split(',')[0];
                    }
                }
            },
            y: {
                min: 5,
                max: 10,
                ticks: { stepSize: 0.5 },
                grid: { color: '#e0ffe0' }
            }
        },
        plugins: {
            legend: {
                display: true,
                onClick: null,
                labels: { boxWidth: 0, boxHeight: 0 }
            },
            tooltip: {
                callbacks: {
                    title(context) {
                        return context[0].label;
                    }
                }
            }
        }
    }
}) : null;

const doCanvas = document.getElementById('doChart');
const doCtx = doCanvas ? doCanvas.getContext('2d') : null;
const doGradient = doCtx ? doCtx.createLinearGradient(0, 0, 0, 220) : null;

if (doGradient) {
    doGradient.addColorStop(0, 'rgba(33,150,243,0.18)');
    doGradient.addColorStop(1, 'rgba(33,150,243,0.02)');
}

const doChart = doCtx ? new Chart(doCtx, {
    type: 'line',
    data: {
        labels: getLabels(initialReadings),
        datasets: [{
            label: 'Dissolved Oxygen (mg/L)',
            data: initialReadings.map((reading) => reading.dissolved_oxygen),
            borderColor: 'blue',
            backgroundColor: doGradient,
            fill: true,
            pointRadius: 3,
            pointHoverRadius: 6,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                ticks: {
                    autoSkip: true,
                    maxTicksLimit: 8,
                    callback(value, index, values) {
                        const label = this.getLabelForValue(value);
                        return label.split(',')[0];
                    }
                }
            },
            y: {
                min: 0,
                max: 10,
                ticks: { stepSize: 1 },
                grid: { color: '#e0e8ff' }
            }
        },
        plugins: {
            legend: {
                display: true,
                onClick: null,
                labels: { boxWidth: 0, boxHeight: 0 }
            },
            tooltip: {
                callbacks: {
                    title(context) {
                        return context[0].label;
                    }
                }
            }
        }
    }
}) : null;

let latestBarChart = null;
function updateLatestBarChart(latest) {
    const latestCanvas = document.getElementById('latestBarChart');
    if (!latestCanvas) {
        return;
    }

    const ctx = latestCanvas.getContext('2d');
    const data = {
        labels: ['Temp (°C)', 'pH', 'DO (mg/L)'],
        datasets: [{
            label: '',
            data: [
                latest ? latest.temperature : 0,
                latest ? latest.ph : 0,
                latest ? latest.dissolved_oxygen : 0
            ],
            backgroundColor: [
                'rgba(255,87,34,0.7)',
                'rgba(76,175,80,0.7)',
                'rgba(33,150,243,0.7)'
            ],
            borderColor: [
                'rgba(255,87,34,1)',
                'rgba(76,175,80,1)',
                'rgba(33,150,243,1)'
            ],
            borderWidth: 1
        }]
    };

    if (latestBarChart) {
        latestBarChart.data = data;
        latestBarChart.update();
        return;
    }

    latestBarChart = new Chart(ctx, {
        type: 'bar',
        data,
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { font: { size: 13 } }
                },
                x: {
                    ticks: { font: { size: 13 } }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: { enabled: true }
            }
        }
    });
}

function closeAlertBox() {
    const alertBox = document.getElementById('alert-box');
    if (!alertBox) {
        return;
    }

    alertBox.style.display = 'none';
    lastAlertType = null;

    const alertAudio = document.getElementById('alert-audio');
    if (alertAudio) {
        alertAudio.pause();
        alertAudio.currentTime = 0;
    }

    localStorage.setItem('alertBoxClosedAt', Date.now().toString());
}

let lastAlertType = null;

const animalThresholds = {
    tilapia: {
        label: 'Tilapia',
        temp: { min: 27, max: 30, warnLow: 25, warnHigh: 32, critLow: 24, critHigh: 34, tooltip: 'Ideal: 27–30°C' },
        ph: { min: 6.5, max: 8.5, warnLow: 6.2, warnHigh: 8.8, critLow: 6, critHigh: 9, tooltip: 'Ideal: 6.5–8.5' },
        do: { min: 3, warn: 3, crit: 2, tooltip: 'Ideal: >3 mg/L' }
    },
    milkfish: {
        label: 'Milkfish',
        temp: { min: 26, max: 32, warnLow: 25, warnHigh: 33, critLow: 24, critHigh: 34, tooltip: 'Ideal: 26–32°C' },
        ph: { min: 7.5, max: 8.5, warnLow: 7.2, warnHigh: 8.8, critLow: 7, critHigh: 9, tooltip: 'Ideal: 7.5–8.5' },
        do: { min: 3, warn: 3, crit: 2, tooltip: 'Ideal: >3 mg/L' }
    },
    catfish: {
        label: 'Catfish',
        temp: { min: 27, max: 30, warnLow: 25, warnHigh: 32, critLow: 24, critHigh: 34, tooltip: 'Ideal: 27–30°C' },
        ph: { min: 6.5, max: 8.5, warnLow: 6.2, warnHigh: 8.8, critLow: 6, critHigh: 9, tooltip: 'Ideal: 6.5–8.5' },
        do: { min: 3, warn: 3, crit: 2, tooltip: 'Ideal: >3 mg/L' }
    },
    shrimp: {
        label: 'Shrimp',
        temp: { min: 28, max: 32, warnLow: 27, warnHigh: 33, critLow: 26, critHigh: 34, tooltip: 'Ideal: 28–32°C' },
        ph: { min: 7.5, max: 8.5, warnLow: 7.2, warnHigh: 8.8, critLow: 7, critHigh: 9, tooltip: 'Ideal: 7.5–8.5' },
        do: { min: 4, warn: 4, crit: 3, tooltip: 'Ideal: >4 mg/L' }
    }
};

let selectedAnimal = 'tilapia';

function updateAnimalTooltips(updateLabel = true) {
    const animal = animalThresholds[selectedAnimal];
    if (!animal) {
        return;
    }

    const optionMeta = animalOptions[selectedAnimal];
    const labelEl = document.getElementById('selected-animal-label');
    const thumbEl = document.getElementById('selected-animal-thumb');

    const tempTooltip = document.getElementById('temp-ideal-tooltip');
    const phTooltip = document.getElementById('ph-ideal-tooltip');
    const doTooltip = document.getElementById('do-ideal-tooltip');

    if (tempTooltip) {
        tempTooltip.setAttribute('title', animal.temp.tooltip);
    }
    if (phTooltip) {
        phTooltip.setAttribute('title', animal.ph.tooltip);
    }
    if (doTooltip) {
        doTooltip.setAttribute('title', animal.do.tooltip);
    }

    document.querySelectorAll('.ideal-tooltip').forEach((element) => {
        const title = element.getAttribute('title') || '';
        element.setAttribute('data-bs-original-title', title);
    });

    if (typeof bootstrap !== 'undefined') {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
            if (element._tooltip) {
                element._tooltip.dispose();
            }
            element._tooltip = new bootstrap.Tooltip(element);
        });
    }

    if (updateLabel) {
        if (labelEl) {
            labelEl.textContent = optionMeta?.label || animal.label;
        }

        if (thumbEl) {
            if (optionMeta?.image) {
                thumbEl.src = optionMeta.image;
                thumbEl.alt = `${optionMeta.label || animal.label} thumbnail`;
                thumbEl.classList.remove('d-none');
            } else {
                thumbEl.classList.add('d-none');
            }
        }
    } else if (thumbEl) {
        thumbEl.classList.add('d-none');
    }
}

function setActiveAnimalMenuItem(animalKey) {
    document.querySelectorAll('.animal-select').forEach((item) => {
        const isActive = item.dataset.animal === animalKey;
        item.classList.toggle('active', isActive);
    });
}

setActiveAnimalMenuItem(selectedAnimal);
updateAnimalTooltips(false);

function getStatus(value, type) {
    const animal = animalThresholds[selectedAnimal];

    if (type === 'temp') {
        if (value >= animal.temp.min && value <= animal.temp.max) return { text: 'Safe', cls: 'status-safe' };
        if (value < animal.temp.critLow || value > animal.temp.critHigh) return { text: 'Critical', cls: 'status-critical' };
        return { text: 'Warning', cls: 'status-warning' };
    }

    if (type === 'ph') {
        if (value >= animal.ph.min && value <= animal.ph.max) return { text: 'Safe', cls: 'status-safe' };
        if (value < animal.ph.critLow || value > animal.ph.critHigh) return { text: 'Critical', cls: 'status-critical' };
        return { text: 'Warning', cls: 'status-warning' };
    }

    if (type === 'do') {
        if (value >= animal.do.min) return { text: 'Safe', cls: 'status-safe' };
        if (value < animal.do.crit) return { text: 'Critical', cls: 'status-critical' };
        return { text: 'Warning', cls: 'status-warning' };
    }

    return { text: '--', cls: 'status-safe' };
}

async function refreshCharts() {
    try {
        const readings = await fetchReadingsForCurrentView();
        const labels = getLabels(readings);
        const temps = readings.map((reading) => reading.temperature);
        const phs = readings.map((reading) => reading.ph);
        const dos = readings.map((reading) => reading.dissolved_oxygen);

        if (tempChart) {
            tempChart.data.labels = labels;
            tempChart.data.datasets[0].data = temps;
            tempChart.update();
        }

        if (phChart) {
            phChart.data.labels = labels;
            phChart.data.datasets[0].data = phs;
            phChart.update();
        }

        if (doChart) {
            doChart.data.labels = labels;
            doChart.data.datasets[0].data = dos;
            doChart.update();
        }

        updateLatestBarChart(readings.length ? readings[readings.length - 1] : null);

        let showAlert = false;
        const alertMsg = [];
        let alertType = null;

        if (readings.length) {
            const latest = readings[readings.length - 1];

            const tempValueEl = document.getElementById('temp-value');
            const phValueEl = document.getElementById('ph-value');
            const doValueEl = document.getElementById('do-value');
            const tempDateEl = document.getElementById('temp-date');
            const phDateEl = document.getElementById('ph-date');
            const doDateEl = document.getElementById('do-date');

            if (tempValueEl) tempValueEl.textContent = `${latest.temperature} °C`;
            if (phValueEl) phValueEl.textContent = latest.ph;
            if (doValueEl) doValueEl.textContent = `${latest.dissolved_oxygen} mg/L`;
            if (tempDateEl) tempDateEl.textContent = formatDate(latest.recorded_at ?? latest.created_at);
            if (phDateEl) phDateEl.textContent = formatDate(latest.recorded_at ?? latest.created_at);
            if (doDateEl) doDateEl.textContent = formatDate(latest.recorded_at ?? latest.created_at);

            const tempStat = getStatus(latest.temperature, 'temp');
            const phStat = getStatus(latest.ph, 'ph');
            const doStat = getStatus(latest.dissolved_oxygen, 'do');

            const tempStatusEl = document.getElementById('temp-status');
            if (tempStatusEl) {
                tempStatusEl.textContent = tempStat.text;
                tempStatusEl.className = `status-badge ${tempStat.cls}`;
                tempStatusEl.classList.remove('d-none');
            }

            const phStatusEl = document.getElementById('ph-status');
            if (phStatusEl) {
                phStatusEl.textContent = phStat.text;
                phStatusEl.className = `status-badge ${phStat.cls}`;
                phStatusEl.classList.remove('d-none');
            }

            const doStatusEl = document.getElementById('do-status');
            if (doStatusEl) {
                doStatusEl.textContent = doStat.text;
                doStatusEl.className = `status-badge ${doStat.cls}`;
                doStatusEl.classList.remove('d-none');
            }

            if (tempStat.text === 'Critical') {
                showAlert = true;
                alertMsg.push('Temperature: Critical');
                alertType = 'critical';
            } else if (tempStat.text === 'Warning' && !alertType) {
                showAlert = true;
                alertMsg.push('Temperature: Warning');
                alertType = 'warning';
            }

            if (phStat.text === 'Critical') {
                showAlert = true;
                alertMsg.push('pH: Critical');
                alertType = 'critical';
            } else if (phStat.text === 'Warning' && !alertType) {
                showAlert = true;
                alertMsg.push('pH: Warning');
                alertType = 'warning';
            }

            if (doStat.text === 'Critical') {
                showAlert = true;
                alertMsg.push('Dissolved Oxygen: Critical');
                alertType = 'critical';
            } else if (doStat.text === 'Warning' && !alertType) {
                showAlert = true;
                alertMsg.push('Dissolved Oxygen: Warning');
                alertType = 'warning';
            }
        }

        const alertBox = document.getElementById('alert-box');
        const alertMsgEl = document.getElementById('alert-message');
        let closedAt = localStorage.getItem('alertBoxClosedAt');
        let canShow = true;

        if (closedAt) {
            const diff = Date.now() - parseInt(closedAt, 10);
            if (diff < 5 * 60 * 1000) {
                canShow = false;
            } else {
                localStorage.removeItem('alertBoxClosedAt');
            }
        }

        if (showAlert && canShow && alertBox && alertMsgEl) {
            alertBox.classList.remove('d-none');
            alertBox.style.display = 'block';
            alertMsgEl.textContent = alertMsg.join(' | ');
            if (alertType === 'critical') {
                alertBox.className = 'alert alert-danger d-block position-fixed w-100 shadow';
            } else {
                alertBox.className = 'alert alert-warning d-block position-fixed w-100 shadow';
            }
            if (lastAlertType !== alertType) {
                playAlertAudio();
                lastAlertType = alertType;
            }
        } else if (alertBox) {
            alertBox.classList.add('d-none');
            alertBox.style.display = 'none';
        }
    } catch (error) {
        console.error(error);
    }
}

refreshCharts();
setInterval(refreshCharts, 2000);

const filterSelect = document.getElementById('filter-select');
if (filterSelect) {
    filterSelect.addEventListener('change', function () {
        currentFilter = this.value;
        filterFrom = '';
        filterTo = '';
        fromDateInput = '';
        toDateInput = '';
        fromTimeInput = '';
        toTimeInput = '';

        if (rangeLabelEl) rangeLabelEl.textContent = 'Custom Range';
        refreshCharts();
    });
}

const dateRangeButton = document.getElementById('date-range-btn');
if (dateRangeButton) {
    dateRangeButton.addEventListener('click', function () {
        const modal = new bootstrap.Modal(document.getElementById('dateRangeModal'));
        const modalDateFrom = document.getElementById('modal-date-from');
        const modalDateTo = document.getElementById('modal-date-to');
        const modalTimeFrom = document.getElementById('modal-time-from');
        const modalTimeTo = document.getElementById('modal-time-to');

        if (modalDateFrom) modalDateFrom.value = fromDateInput;
        if (modalDateTo) modalDateTo.value = toDateInput;
        if (modalTimeFrom) modalTimeFrom.value = fromTimeInput;
        if (modalTimeTo) modalTimeTo.value = toTimeInput;

        modal.show();
    });
}

const applyDateRangeButton = document.getElementById('apply-date-range-btn');
if (applyDateRangeButton) {
    applyDateRangeButton.addEventListener('click', function () {
        fromDateInput = document.getElementById('modal-date-from').value;
        toDateInput = document.getElementById('modal-date-to').value;
        const fromTimeRaw = document.getElementById('modal-time-from').value.trim();
        const toTimeRaw = document.getElementById('modal-time-to').value.trim();

        fromTimeInput = fromTimeRaw;
        toTimeInput = toTimeRaw;

        filterFrom = buildDateTimeString(fromDateInput, fromTimeRaw, false);
        filterTo = buildDateTimeString(toDateInput, toTimeRaw, true);

        if (filterFrom && filterTo) {
            currentFilter = '';
            if (rangeLabelEl) rangeLabelEl.textContent = formatRangeDisplay(fromDateInput, toDateInput, fromTimeInput, toTimeInput);
            refreshCharts();
            const rangeModal = bootstrap.Modal.getInstance(document.getElementById('dateRangeModal'));
            if (rangeModal) rangeModal.hide();
        }
    });
}

const clearDateRangeButton = document.getElementById('clear-date-range-btn');
if (clearDateRangeButton) {
    clearDateRangeButton.addEventListener('click', function () {
        filterFrom = '';
        filterTo = '';
        currentFilter = 'today';
        fromDateInput = '';
        toDateInput = '';
        fromTimeInput = '';
        toTimeInput = '';

        const modalDateFrom = document.getElementById('modal-date-from');
        const modalDateTo = document.getElementById('modal-date-to');
        const modalTimeFrom = document.getElementById('modal-time-from');
        const modalTimeTo = document.getElementById('modal-time-to');

        if (modalDateFrom) modalDateFrom.value = '';
        if (modalDateTo) modalDateTo.value = '';
        if (modalTimeFrom) modalTimeFrom.value = '';
        if (modalTimeTo) modalTimeTo.value = '';

        if (rangeLabelEl) rangeLabelEl.textContent = 'Custom Range';
        if (filterSelect) filterSelect.value = 'today';
        refreshCharts();

        const rangeModal = bootstrap.Modal.getInstance(document.getElementById('dateRangeModal'));
        if (rangeModal) rangeModal.hide();
    });
}

document.querySelectorAll('.animal-select').forEach((item) => {
    item.addEventListener('click', function (event) {
        event.preventDefault();
        const animalKey = this.dataset.animal;
        if (!animalKey || !animalThresholds[animalKey]) {
            return;
        }

        selectedAnimal = animalKey;
        setActiveAnimalMenuItem(animalKey);
        updateAnimalTooltips();
        refreshCharts();
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map((tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl));
});

function computeSummary(readings) {
    if (!readings.length) {
        return null;
    }

    const temps = readings.map((reading) => Number(reading.temperature)).filter(Number.isFinite);
    const phs = readings.map((reading) => Number(reading.ph)).filter(Number.isFinite);
    const dos = readings.map((reading) => Number(reading.dissolved_oxygen)).filter(Number.isFinite);

    function stats(arr) {
        if (!arr.length) {
            return { min: '--', max: '--', avg: '--' };
        }

        const min = Math.min(...arr);
        const max = Math.max(...arr);
        const avg = (arr.reduce((sum, value) => sum + value, 0) / arr.length).toFixed(2);
        return { min, max, avg };
    }

    return {
        temperature: stats(temps),
        ph: stats(phs),
        do: stats(dos),
        count: readings.length
    };
}

function tempEventAdvice(summary) {
    const { min, max, avg } = summary.temperature;
    if (min === '--') return { event: 'No data', effect: '', advice: '' };
    if (max > 34) {
        return {
            event: 'High temperature detected',
            effect: 'Fish may become stressed, eat less, and be prone to disease.',
            advice: 'Increase aeration, provide shade, and avoid feeding during peak heat.'
        };
    }

    if (min < 24) {
        return {
            event: 'Low temperature detected',
            effect: 'Fish metabolism slows, growth decreases, and immune response weakens.',
            advice: 'Reduce feeding, monitor for disease, and avoid sudden water changes.'
        };
    }

    if (avg >= 26 && avg <= 32) {
        return {
            event: 'Temperature is optimal',
            effect: 'Fish are likely healthy and growing well.',
            advice: 'Maintain current management and monitor regularly.'
        };
    }

    return {
        event: 'Temperature slightly outside ideal range',
        effect: 'Fish may experience mild stress or reduced growth.',
        advice: 'Monitor closely and adjust management if extremes persist.'
    };
}

function phEventAdvice(summary) {
    const { min, max, avg } = summary.ph;
    if (min === '--') return { event: 'No data', effect: '', advice: '' };
    if (max > 9 || min < 6) {
        return {
            event: 'Critical pH levels detected',
            effect: 'Fish may suffer from stress, poor growth, or even mortality.',
            advice: 'Check for pollution, reduce feeding, and consider partial water exchange.'
        };
    }

    if (avg >= 6.5 && avg <= 8.5) {
        return {
            event: 'pH is optimal',
            effect: 'Fish health and biological processes are stable.',
            advice: 'Continue regular monitoring and avoid sudden changes.'
        };
    }

    return {
        event: 'pH slightly outside ideal range',
        effect: 'Fish may be mildly stressed, affecting growth and immunity.',
        advice: 'Check for decaying matter, adjust liming if needed, and monitor closely.'
    };
}

function doEventAdvice(summary) {
    const { min, avg } = summary.do;
    if (min === '--') return { event: 'No data', effect: '', advice: '' };
    if (min < 3) {
        return {
            event: 'Low dissolved oxygen detected',
            effect: 'Fish may gasp at the surface, reduce feeding, and risk mortality.',
            advice: 'Increase aeration immediately, reduce feeding, and avoid disturbing the pond bottom.'
        };
    }

    if (avg >= 5) {
        return {
            event: 'Dissolved oxygen is optimal',
            effect: 'Fish are healthy and active.',
            advice: 'Maintain aeration and avoid overstocking.'
        };
    }

    return {
        event: 'Dissolved oxygen slightly low',
        effect: 'Fish may be less active and eat less.',
        advice: 'Increase aeration and avoid feeding during early morning.'
    };
}

function renderSummary(summary) {
    if (!summary) {
        return '<div class="text-danger">No data available.</div>';
    }

    const temp = tempEventAdvice(summary);
    const ph = phEventAdvice(summary);
    const dox = doEventAdvice(summary);

    return `
    <div>
        <div><span class="content-label">Total Records:</span> ${summary.count}</div>
        <hr>
        <div>
            <span class="content-label">Temperature (°C):</span>
            <ul>
                <li>Min: <span class="text-primary">${summary.temperature.min}</span></li>
                <li>Max: <span class="text-primary">${summary.temperature.max}</span></li>
                <li>Average: <span class="text-primary">${summary.temperature.avg}</span></li>
            </ul>
            <div><span class="content-label">Event:</span> ${temp.event}</div>
            <div><span class="content-label">Effect on fish:</span> <span class="text-danger">${temp.effect}</span></div>
            <div><span class="content-label">Advice:</span> <span class="text-success">${temp.advice}</span></div>
        </div>
        <hr>
        <div>
            <span class="content-label">pH:</span>
            <ul>
                <li>Min: <span class="text-success">${summary.ph.min}</span></li>
                <li>Max: <span class="text-success">${summary.ph.max}</span></li>
                <li>Average: <span class="text-success">${summary.ph.avg}</span></li>
            </ul>
            <div><span class="content-label">Event:</span> ${ph.event}</div>
            <div><span class="content-label">Effect on fish:</span> <span class="text-danger">${ph.effect}</span></div>
            <div><span class="content-label">Advice:</span> <span class="text-success">${ph.advice}</span></div>
        </div>
        <hr>
        <div>
            <span class="content-label">Dissolved Oxygen (mg/L):</span>
            <ul>
                <li>Min: <span class="text-info">${summary.do.min}</span></li>
                <li>Max: <span class="text-info">${summary.do.max}</span></li>
                <li>Average: <span class="text-info">${summary.do.avg}</span></li>
            </ul>
            <div><span class="content-label">Event:</span> ${dox.event}</div>
            <div><span class="content-label">Effect on fish:</span> <span class="text-danger">${dox.effect}</span></div>
            <div><span class="content-label">Advice:</span> <span class="text-success">${dox.advice}</span></div>
        </div>
    </div>
    `;
}

const dataOverviewIntervals = [
    { key: 'six_am', label: '6 AM Summary', startHour: 0, endHour: 5 },
    { key: 'noon', label: '12 Noon Summary', startHour: 6, endHour: 11 },
    { key: 'six_pm', label: '6 PM Summary', startHour: 12, endHour: 17 },
    { key: 'midnight', label: '12 Midnight Summary', startHour: 18, endHour: 23 }
];

let overviewReadingsCache = [];
let currentIntervalForLog = null;

const intervalLogModalEl = document.getElementById('intervalLogModal');
const intervalLogContentEl = document.getElementById('interval-log-content');
const intervalLogDescriptionEl = document.getElementById('interval-log-description');
const intervalLogPrevBtn = document.getElementById('interval-log-prev-btn');
const intervalLogNextBtn = document.getElementById('interval-log-next-btn');
const intervalLogDateLabel = document.getElementById('interval-log-date-label');

let currentIntervalEntries = [];
let intervalLogAvailableDates = [];
let currentIntervalDateIndex = -1;

function computeIntervalSummaries(readings) {
    return dataOverviewIntervals.map((interval) => {
        const filtered = readings.filter((reading) => {
            const timestamp = reading.recorded_at ?? reading.created_at;
            if (!timestamp) {
                return false;
            }

            const dt = new Date(timestamp);
            if (Number.isNaN(dt.getTime())) {
                return false;
            }

            const hour = dt.getHours();
            return hour >= interval.startHour && hour <= interval.endHour;
        });

        return {
            ...interval,
            summary: computeSummary(filtered)
        };
    });
}

function renderIntervalTables(intervals) {
    if (!intervals.length) {
        return '<div class="text-center text-muted">No interval data available.</div>';
    }

    return `
        <div class="row g-3">
            ${intervals.map((interval) => {
                if (!interval.summary) {
                    return `
                        <div class="col-12 col-lg-6">
                            <div class="card shadow-sm h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <h6 class="text-primary mb-3">${interval.label}</h6>
                                    <div class="text-muted">No readings available for this interval.</div>
                                    <div class="mt-3">
                                        <button class="btn btn-sm btn-outline-secondary interval-log-btn" data-interval-key="${interval.key}" data-interval-label="${interval.label}">View Log</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }

                const summary = interval.summary;
                return `
                    <div class="col-12 col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
                                    <h6 class="text-primary mb-0">${interval.label}</h6>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-light text-dark">Entries: ${summary.count}</span>
                                        <button class="btn btn-sm btn-outline-secondary interval-log-btn" data-interval-key="${interval.key}" data-interval-label="${interval.label}">View Log</button>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col">Metric</th>
                                                <th scope="col" class="text-center">Min</th>
                                                <th scope="col" class="text-center">Avg</th>
                                                <th scope="col" class="text-center">Max</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Temperature (°C)</td>
                                                <td class="text-center">${summary.temperature.min}</td>
                                                <td class="text-center">${summary.temperature.avg}</td>
                                                <td class="text-center">${summary.temperature.max}</td>
                                            </tr>
                                            <tr>
                                                <td>pH</td>
                                                <td class="text-center">${summary.ph.min}</td>
                                                <td class="text-center">${summary.ph.avg}</td>
                                                <td class="text-center">${summary.ph.max}</td>
                                            </tr>
                                            <tr>
                                                <td>Dissolved Oxygen (mg/L)</td>
                                                <td class="text-center">${summary.do.min}</td>
                                                <td class="text-center">${summary.do.avg}</td>
                                                <td class="text-center">${summary.do.max}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('')}
        </div>
    `;
}

function attachIntervalLogHandlers(intervals) {
    if (!intervalLogModalEl) return;

    const buttons = document.querySelectorAll('.interval-log-btn');
    buttons.forEach((button) => {
        button.addEventListener('click', function () {
            const key = this.dataset.intervalKey;
            const label = this.dataset.intervalLabel || 'Historical Log';
            const interval = intervals.find((item) => item.key === key);
            if (!interval) return;

            currentIntervalForLog = interval;
            if (intervalLogDescriptionEl) {
                intervalLogDescriptionEl.textContent = `Viewing readings captured between ${getIntervalRangeLabel(interval)}. Use the day controls to move between dates.`;
            }

            const modalTitleEl = document.getElementById('intervalLogModalLabel');
            if (modalTitleEl) {
                modalTitleEl.textContent = `${label} - Historical Log`;
            }

            currentIntervalEntries = computeIntervalEntries(interval);
            intervalLogAvailableDates = buildIntervalLogDates(currentIntervalEntries);
            currentIntervalDateIndex = intervalLogAvailableDates.length ? 0 : -1;
            renderIntervalLog();

            const modal = bootstrap.Modal.getOrCreateInstance(intervalLogModalEl);
            modal.show();
        });
    });
}

function computeIntervalEntries(interval) {
    return (overviewReadingsCache || [])
        .map((reading) => ({ reading, date: getReadingDate(reading) }))
        .filter((entry) => {
            const dt = entry.date;
            if (!dt) return false;
            const hour = dt.getHours();
            return hour >= interval.startHour && hour <= interval.endHour;
        })
        .sort((a, b) => b.date - a.date);
}

function buildIntervalLogDates(entries) {
    const seen = new Set();
    const dates = [];

    entries.forEach((entry) => {
        const key = dateKeyFromDate(entry.date);
        if (!key || seen.has(key)) return;
        seen.add(key);
        dates.push({ key, label: formatDateOnly(entry.date) || 'Unknown date' });
    });

    return dates;
}

function updateIntervalLogNavUI(activeDateInfo = null) {
    const hasInterval = Boolean(currentIntervalForLog);
    const hasDates = hasInterval && intervalLogAvailableDates.length > 0 && currentIntervalDateIndex >= 0;
    const resolvedDate = activeDateInfo || (hasDates ? intervalLogAvailableDates[currentIntervalDateIndex] : null);

    if (intervalLogDateLabel) {
        if (!hasInterval) {
            intervalLogDateLabel.textContent = 'Select an interval to begin.';
        } else if (!resolvedDate) {
            intervalLogDateLabel.textContent = `${currentIntervalForLog.label} — No data available`;
        } else {
            intervalLogDateLabel.textContent = `${resolvedDate.label} — ${currentIntervalForLog.label}`;
        }
    }

    if (intervalLogPrevBtn) {
        intervalLogPrevBtn.disabled = !hasDates || currentIntervalDateIndex >= intervalLogAvailableDates.length - 1;
    }

    if (intervalLogNextBtn) {
        intervalLogNextBtn.disabled = !hasDates || currentIntervalDateIndex <= 0;
    }
}

function getReadingDate(reading) {
    if (!reading) return null;
    const timestamp = reading.recorded_at ?? reading.created_at;
    if (!timestamp) return null;
    const dt = new Date(timestamp);
    return Number.isNaN(dt.getTime()) ? null : dt;
}

function renderIntervalLogTable(entries) {
    if (!entries.length) {
        return '<div class="text-muted">No readings found for this interval within the current selection.</div>';
    }

    let lastDateLabel = null;
    const rowsHtml = entries.slice(0, 100).map((entry) => {
        const reading = entry.reading;
        const dt = entry.date;
        const dateLabel = formatDateOnly(dt) || '--';
        const timeLabel = formatTimeOnly(dt) || '--';
        const isSameDate = dateLabel === lastDateLabel;
        const timestampCell = isSameDate
            ? `<div class="ps-4">${timeLabel}</div>`
            : `<div>${dateLabel}</div><div class="small text-muted">${timeLabel}</div>`;

        lastDateLabel = dateLabel;
        return `
            <tr>
                <td>${timestampCell}</td>
                <td class="text-center">${reading.temperature ?? '--'}</td>
                <td class="text-center">${reading.ph ?? '--'}</td>
                <td class="text-center">${reading.dissolved_oxygen ?? '--'}</td>
            </tr>
        `;
    }).join('');

    return `
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Timestamp</th>
                        <th scope="col" class="text-center">Temperature (°C)</th>
                        <th scope="col" class="text-center">pH</th>
                        <th scope="col" class="text-center">Dissolved Oxygen (mg/L)</th>
                    </tr>
                </thead>
                <tbody>
                    ${rowsHtml}
                </tbody>
            </table>
            <div class="small text-muted mt-2">Showing up to 100 most recent readings for this interval.</div>
        </div>
    `;
}

function renderIntervalLog() {
    if (!intervalLogContentEl) return;
    if (!currentIntervalForLog) {
        intervalLogContentEl.innerHTML = '<div class="text-muted">Select an interval from the Data Overview to view its historical log.</div>';
        updateIntervalLogNavUI();
        return;
    }

    const entries = currentIntervalEntries || [];
    if (!entries.length) {
        intervalLogContentEl.innerHTML = '<div class="text-muted">No readings found for this interval within the current selection.</div>';
        updateIntervalLogNavUI();
        return;
    }

    if (!intervalLogAvailableDates.length) {
        intervalLogContentEl.innerHTML = '<div class="text-muted">No readings found for this interval within the current selection.</div>';
        updateIntervalLogNavUI();
        return;
    }

    if (currentIntervalDateIndex < 0 || currentIntervalDateIndex >= intervalLogAvailableDates.length) {
        currentIntervalDateIndex = 0;
    }

    const activeDate = intervalLogAvailableDates[currentIntervalDateIndex];
    const filteredEntries = entries.filter((entry) => dateKeyFromDate(entry.date) === activeDate.key);
    intervalLogContentEl.innerHTML = renderIntervalLogTable(filteredEntries);
    updateIntervalLogNavUI(activeDate);
}

const getSummaryButton = document.getElementById('get-summary-btn');
if (getSummaryButton) {
    getSummaryButton.addEventListener('click', async function () {
        let readings = [];

        try {
            readings = await fetchReadingsForCurrentView();
        } catch (error) {
            readings = initialReadings;
        }

        const summary = computeSummary(readings);
        const summaryModalBody = document.getElementById('summary-modal-body');
        if (summaryModalBody) {
            summaryModalBody.innerHTML = renderSummary(summary);
        }
    });
}

const dataOverviewModalEl = document.getElementById('dataOverviewModal');
if (dataOverviewModalEl) {
    dataOverviewModalEl.addEventListener('show.bs.modal', async function () {
        const contentEl = document.getElementById('data-overview-content');
        if (contentEl) contentEl.innerHTML = '<div class="text-center text-muted py-4">Loading data overview...</div>';

        try {
            let readings = [];
            try {
                readings = await fetchReadingsForCurrentView();
            } catch (error) {
                readings = initialReadings;
            }

            const intervals = computeIntervalSummaries(readings);
            if (contentEl) {
                contentEl.innerHTML = renderIntervalTables(intervals);
            }

            overviewReadingsCache = Array.isArray(readings) ? readings.slice() : [];
            attachIntervalLogHandlers(intervals);
        } catch (error) {
            console.error(error);
            if (contentEl) {
                contentEl.innerHTML = '<div class="text-center text-danger py-4">Unable to load data overview.</div>';
            }
        }
    });
}

if (intervalLogModalEl) {
    intervalLogModalEl.addEventListener('hidden.bs.modal', function () {
        currentIntervalForLog = null;
        currentIntervalEntries = [];
        intervalLogAvailableDates = [];
        currentIntervalDateIndex = -1;

        if (intervalLogContentEl) {
            intervalLogContentEl.innerHTML = '<div class="text-muted">No interval selected. Use the "View Log" button inside the Data Overview to begin.</div>';
        }

        updateIntervalLogNavUI();
    });
}

if (intervalLogPrevBtn) {
    intervalLogPrevBtn.addEventListener('click', function () {
        if (currentIntervalDateIndex < intervalLogAvailableDates.length - 1) {
            currentIntervalDateIndex += 1;
            renderIntervalLog();
        }
    });
}

if (intervalLogNextBtn) {
    intervalLogNextBtn.addEventListener('click', function () {
        if (currentIntervalDateIndex > 0) {
            currentIntervalDateIndex -= 1;
            renderIntervalLog();
        }
    });
}

function getWeatherIcon(code) {
    const map = {
        0: '☀️', 1: '🌤️', 2: '⛅', 3: '☁️',
        45: '🌫️', 48: '🌫️', 51: '🌦️', 53: '🌦️', 55: '🌦️',
        56: '🌧️', 57: '🌧️', 61: '🌧️', 63: '🌧️', 65: '🌧️',
        66: '🌧️', 67: '🌧️', 71: '❄️', 73: '❄️', 75: '❄️',
        77: '❄️', 80: '🌦️', 81: '🌦️', 82: '🌦️',
        85: '🌨️', 86: '🌨️', 95: '⛈️', 96: '⛈️', 99: '⛈️'
    };
    return map[code] || '☀️';
}

function getWeatherDesc(code) {
    const desc = {
        0: 'Clear', 1: 'Mainly clear', 2: 'Partly cloudy', 3: 'Cloudy',
        45: 'Fog', 48: 'Fog', 51: 'Drizzle', 53: 'Drizzle', 55: 'Drizzle',
        56: 'Freezing drizzle', 57: 'Freezing drizzle', 61: 'Rain', 63: 'Rain', 65: 'Rain',
        66: 'Freezing rain', 67: 'Freezing rain', 71: 'Snow', 73: 'Snow', 75: 'Snow',
        77: 'Snow grains', 80: 'Showers', 81: 'Showers', 82: 'Showers',
        85: 'Snow showers', 86: 'Snow showers', 95: 'Thunderstorm', 96: 'Thunderstorm', 99: 'Thunderstorm'
    };
    return desc[code] || 'Clear';
}

function getDayLabel(dateStr, index) {
    if (index === 0) return 'Today';
    if (index === 1) return 'Tomorrow';
    return new Date(dateStr).toLocaleDateString(undefined, { weekday: 'short' });
}

async function fetchWeatherForecast() {
    const lat = 16.6167;
    const lon = 120.3170;
    const url = `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&daily=weathercode,temperature_2m_max,temperature_2m_min&timezone=auto`;
    const container = document.getElementById('weather-forecast');

    if (!container) {
        return;
    }

    try {
        const response = await fetch(url);
        const data = await response.json();
        if (!data.daily) {
            throw new Error('No daily data');
        }

        let html = '';
        for (let index = 0; index < 7; index += 1) {
            html += `
            <div class="weather-card">
                <div class="weather-day">${getDayLabel(data.daily.time[index], index)}</div>
                <div class="weather-icon" style="font-size:2.1rem;">${getWeatherIcon(data.daily.weathercode[index])}</div>
                <div class="weather-temp">${Math.round(data.daily.temperature_2m_max[index])}°<span style="font-size:1rem;color:#888;">/${Math.round(data.daily.temperature_2m_min[index])}°</span></div>
                <div class="weather-desc">${getWeatherDesc(data.daily.weathercode[index])}</div>
                <div class="weather-minmax">H: ${Math.round(data.daily.temperature_2m_max[index])}° L: ${Math.round(data.daily.temperature_2m_min[index])}°</div>
            </div>
            `;
        }

        container.innerHTML = html;
    } catch (error) {
        container.innerHTML = '<div class="text-danger">Unable to load weather forecast.</div>';
    }
}

fetchWeatherForecast();
setInterval(fetchWeatherForecast, 3600000);

const closeAlertButton = document.querySelector('#alert-box .btn-close');
if (closeAlertButton) {
    closeAlertButton.addEventListener('click', closeAlertBox);
}
