/**
 * charts.js — Graphiques Budget Mariage PJPM v2.0
 * Utilise Chart.js chargé depuis CDN
 */

'use strict';

const Charts = (() => {
    let _pieChart     = null;
    let _barChart     = null;
    let _trendChart   = null;

    /* ── Palette couleurs harmonieuse ─────────────────────────── */
    const PALETTE = [
        '#8b4f8d','#b87bb8','#d4af37','#e67e22','#e74c3c',
        '#3498db','#2ecc71','#1abc9c','#9b59b6','#f39c12',
        '#16a085','#c0392b','#2980b9','#8e44ad','#27ae60',
    ];

    /* ── Helpers ────────────────────────────────────────────────── */
    function rgba(hex, alpha = 1) {
        const r = parseInt(hex.slice(1,3),16);
        const g = parseInt(hex.slice(3,5),16);
        const b = parseInt(hex.slice(5,7),16);
        return `rgba(${r},${g},${b},${alpha})`;
    }

    function destroyChart(chart) {
        if (chart) { try { chart.destroy(); } catch(e){} }
        return null;
    }

    /* ── Options globales Chart.js ──────────────────────────────── */
    function globalDefaults() {
        if (!window.Chart) return;
        Chart.defaults.font.family = "'Lato','Segoe UI',sans-serif";
        Chart.defaults.font.size   = 12;
        Chart.defaults.color       = '#555';
        Chart.defaults.plugins.tooltip.backgroundColor = '#2d2d2d';
        Chart.defaults.plugins.tooltip.titleColor      = '#fff';
        Chart.defaults.plugins.tooltip.bodyColor       = '#ddd';
        Chart.defaults.plugins.tooltip.padding         = 10;
        Chart.defaults.plugins.tooltip.cornerRadius    = 6;
    }

    /* ─────────────────────────────────────────────────────────────
       1. Graphique Camembert — Répartition par catégorie
    ───────────────────────────────────────────────────────────── */
    function renderPieChart(categories) {
        const canvas = document.getElementById('pie-chart');
        if (!canvas || !window.Chart) return;

        // Filtrer les catégories sans dépenses
        const data = categories.filter(c => parseFloat(c.total) > 0);
        if (data.length === 0) {
            canvas.parentElement.innerHTML = '<p class="chart-empty">Aucune donnée disponible.</p>';
            return;
        }

        _pieChart = destroyChart(_pieChart);

        _pieChart = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: data.map(c => c.name),
                datasets: [{
                    data:            data.map(c => parseFloat(c.total)),
                    backgroundColor: data.map((_, i) => rgba(PALETTE[i % PALETTE.length], 0.85)),
                    borderColor:     data.map((_, i) => PALETTE[i % PALETTE.length]),
                    borderWidth: 2,
                    hoverOffset: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding:    16,
                            boxWidth:   14,
                            font:       { size: 11 },
                            generateLabels(chart) {
                                const ds = chart.data.datasets[0];
                                const total = ds.data.reduce((a,b) => a+b, 0);
                                return chart.data.labels.map((label, i) => ({
                                    text: `${label} (${((ds.data[i]/total)*100).toFixed(1)}%)`,
                                    fillStyle: ds.backgroundColor[i],
                                    strokeStyle: ds.borderColor[i],
                                    lineWidth: 1,
                                    index: i,
                                }));
                            },
                        },
                    },
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                const total = ctx.dataset.data.reduce((a,b)=>a+b,0);
                                const pct   = ((ctx.parsed / total) * 100).toFixed(1);
                                return ` ${ctx.label}: ${fc(ctx.parsed)} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    /* ─────────────────────────────────────────────────────────────
       2. Graphique Barres — Payé vs Reste par catégorie
    ───────────────────────────────────────────────────────────── */
    function renderBarChart(categories) {
        const canvas = document.getElementById('bar-chart');
        if (!canvas || !window.Chart) return;

        const data = categories.filter(c => parseFloat(c.total) > 0);
        if (data.length === 0) return;

        _barChart = destroyChart(_barChart);

        _barChart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: data.map(c => c.name.length > 18 ? c.name.substring(0,16)+'…' : c.name),
                datasets: [
                    {
                        label:           'Payé',
                        data:            data.map(c => parseFloat(c.paid)),
                        backgroundColor: rgba('#2ecc71', 0.8),
                        borderColor:     '#27ae60',
                        borderWidth:     1,
                        borderRadius:    4,
                    },
                    {
                        label:           'Reste à payer',
                        data:            data.map(c => parseFloat(c.remaining)),
                        backgroundColor: rgba('#e74c3c', 0.7),
                        borderColor:     '#c0392b',
                        borderWidth:     1,
                        borderRadius:    4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    x: {
                        stacked: false,
                        grid:    { display: false },
                        ticks:   { font: { size: 10 }, maxRotation: 30 }
                    },
                    y: {
                        beginAtZero: true,
                        grid:    { color: '#f0edf4' },
                        ticks:   {
                            font: { size: 10 },
                            callback: v => v >= 1_000_000
                                ? (v/1_000_000).toFixed(1)+'M'
                                : v >= 1000
                                ? (v/1000).toFixed(0)+'k'
                                : v
                        }
                    }
                },
                plugins: {
                    legend: { position: 'top', labels: { font: { size: 11 }, padding: 14 } },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.dataset.label}: ${fc(ctx.parsed.y)}`
                        }
                    }
                }
            }
        });
    }

    /* ─────────────────────────────────────────────────────────────
       3. Graphique Jauges circulaires par catégorie
    ───────────────────────────────────────────────────────────── */
    function renderGauges(categories) {
        const container = document.getElementById('gauges-container');
        if (!container) return;

        const data = categories.filter(c => parseFloat(c.total) > 0);
        container.innerHTML = data.map((c, i) => {
            const pct   = Math.min(100, parseFloat(c.percentage || 0));
            const color = PALETTE[i % PALETTE.length];
            const dash  = 2 * Math.PI * 45; // circumference ≈ 282.7
            const filled = (pct / 100) * dash;
            return `
            <div class="gauge-item">
                <svg viewBox="0 0 100 100" class="gauge-svg">
                    <circle cx="50" cy="50" r="45" fill="none" stroke="#e8e3dd" stroke-width="8"/>
                    <circle cx="50" cy="50" r="45" fill="none"
                        stroke="${color}" stroke-width="8"
                        stroke-dasharray="${filled.toFixed(2)} ${(dash - filled).toFixed(2)}"
                        stroke-dashoffset="${dash * 0.25}"
                        stroke-linecap="round"/>
                    <text x="50" y="46" text-anchor="middle" font-size="16" font-weight="700"
                          fill="${color}">${pct.toFixed(0)}%</text>
                    <text x="50" y="60" text-anchor="middle" font-size="8" fill="#888">payé</text>
                </svg>
                <div class="gauge-label">${c.name}</div>
                <div class="gauge-amount">${fc(parseFloat(c.paid))} / ${fc(parseFloat(c.total))}</div>
            </div>`;
        }).join('');
    }

    /* ─────────────────────────────────────────────────────────────
       API publique
    ───────────────────────────────────────────────────────────── */
    function initAll(categories) {
        globalDefaults();
        renderPieChart(categories);
        renderBarChart(categories);
        renderGauges(categories);
    }

    function updateAll(categories) {
        initAll(categories);
    }

    return { initAll, updateAll, renderPieChart, renderBarChart, renderGauges };
})();

/* ── Utilitaire formatage devise ────────────────────────────────── */
function fc(amount) {
    const n = parseFloat(amount);
    if (isNaN(n)) return '0 FCFA';
    return new Intl.NumberFormat('fr-FR', {
        style:              'decimal',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(n) + ' FCFA';
}

/* ── Ajout dans le module Charts : Summary Chart horizontal ── */
;(function() {
    let _summaryChart = null;

    Charts.renderSummaryChart = function(categories) {
        const canvas = document.getElementById('summary-chart');
        if (!canvas || !window.Chart) return;

        const data = categories.filter(c => parseFloat(c.total) > 0);
        if (!data.length) return;

        if (_summaryChart) { try { _summaryChart.destroy(); } catch(e){} }

        const labels   = data.map(c => c.name.length > 16 ? c.name.substring(0,14)+'…' : c.name);
        const totals   = data.map(c => parseFloat(c.total));
        const paids    = data.map(c => parseFloat(c.paid));
        const rests    = data.map(c => parseFloat(c.remaining));

        _summaryChart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label:           'Budget total',
                        data:            totals,
                        backgroundColor: 'rgba(139,79,141,0.15)',
                        borderColor:     '#8b4f8d',
                        borderWidth:     2,
                        borderRadius:    4,
                        borderSkipped:   false,
                        order:           3,
                    },
                    {
                        label:           'Payé',
                        data:            paids,
                        backgroundColor: 'rgba(46,204,113,0.75)',
                        borderColor:     '#27ae60',
                        borderWidth:     1,
                        borderRadius:    4,
                        order:           1,
                    },
                    {
                        label:           'Reste',
                        data:            rests,
                        backgroundColor: 'rgba(231,76,60,0.65)',
                        borderColor:     '#c0392b',
                        borderWidth:     1,
                        borderRadius:    4,
                        order:           2,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', labels: { font: { size: 11 }, padding: 16 } },
                    tooltip: {
                        callbacks: {
                            label: ctx => {
                                const v = ctx.parsed.y;
                                const n = new Intl.NumberFormat('fr-FR').format(v);
                                return ` ${ctx.dataset.label}: ${n} FCFA`;
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 25 } },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f0edf4' },
                        ticks: {
                            font: { size: 10 },
                            callback: v => v >= 1_000_000 ? (v/1_000_000).toFixed(1)+'M'
                                : v >= 1000 ? (v/1000).toFixed(0)+'k' : v
                        }
                    }
                }
            }
        });
    };
})();
