/**
 * Chart.js Visualizations Controller
 * Graduate Predicting Analysis System
 */

const AppCharts = {
    /**
     * Doughnut Chart for Overall Readiness Score
     */
    initOverallScoreDoughnut: function (canvasId, score) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        const val = parseFloat(score) || 0;
        const remainder = Math.max(0, 100 - val);

        let color = '#10b981'; // Green (High)
        if (val < 50) {
            color = '#ef4444'; // Red (Needs improvement)
        } else if (val < 75) {
            color = '#f59e0b'; // Amber (Moderate)
        }

        return new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Score Achieved', 'Remaining'],
                datasets: [{
                    data: [val, remainder],
                    backgroundColor: [color, '#e2e8f0'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '76%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.label + ': ' + context.parsed + '%';
                            }
                        }
                    }
                }
            }
        });
    },

    /**
     * Bar Chart for Academic & Performance Breakdown
     */
    initDomainBarChart: function (canvasId, scores) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Academic', 'Technical', 'Career Ready', 'Soft Skills', 'Extracurricular'],
                datasets: [{
                    label: 'Domain Score (%)',
                    data: [
                        scores.academic || 0,
                        scores.technical || 0,
                        scores.career || 0,
                        scores.soft_skills || 0,
                        scores.extracurricular || 0
                    ],
                    backgroundColor: [
                        'rgba(79, 70, 229, 0.85)',
                        'rgba(6, 182, 212, 0.85)',
                        'rgba(16, 185, 129, 0.85)',
                        'rgba(245, 158, 11, 0.85)',
                        'rgba(168, 85, 247, 0.85)'
                    ],
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { stepSize: 20 }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    },

    /**
     * Radar Chart for Technical & Programming Skill Matrix
     */
    initSkillsRadarChart: function (canvasId, labels, values) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        return new Chart(ctx, {
            type: 'radar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Proficiency Rating (1-5)',
                    data: values,
                    backgroundColor: 'rgba(79, 70, 229, 0.2)',
                    borderColor: 'rgba(79, 70, 229, 1)',
                    pointBackgroundColor: 'rgba(79, 70, 229, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(79, 70, 229, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        angleLines: { color: '#e2e8f0' },
                        grid: { color: '#e2e8f0' },
                        suggestedMin: 0,
                        suggestedMax: 5,
                        ticks: { stepSize: 1, display: true, backdropColor: 'transparent' }
                    }
                },
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    },

    /**
     * Line Chart for Semester-Wise GPA Progression
     */
    initSemesterLineChart: function (canvasId, labels, gpas) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        return new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Semester GPA (0-10)',
                    data: gpas,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#4f46e5',
                    pointBorderColor: '#ffffff',
                    borderWidth: 2.5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        min: 0,
                        max: 10,
                        ticks: { stepSize: 2 }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    },

    /**
     * Doughnut / Pie Chart for Readiness Distribution (Faculty/Admin)
     */
    initReadinessPieChart: function (canvasId, counts) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        return new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['High Readiness', 'Moderate Readiness', 'Needs Improvement'],
                datasets: [{
                    data: [
                        counts['High Readiness'] || 0,
                        counts['Moderate Readiness'] || 0,
                        counts['Needs Improvement'] || 0
                    ],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    },

    /**
     * Multi-Student Comparison Radar Chart
     */
    initComparisonRadar: function (canvasId, labels, datasets) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        const colors = [
            { bg: 'rgba(79, 70, 229, 0.2)', border: '#4f46e5' },
            { bg: 'rgba(16, 185, 129, 0.2)', border: '#10b981' },
            { bg: 'rgba(245, 158, 11, 0.2)', border: '#f59e0b' },
            { bg: 'rgba(6, 182, 212, 0.2)',  border: '#06b6d4' },
            { bg: 'rgba(239, 68, 68, 0.2)',  border: '#ef4444' }
        ];

        const chartDatasets = datasets.map((ds, index) => {
            const col = colors[index % colors.length];
            return {
                label: ds.name,
                data: ds.values,
                backgroundColor: col.bg,
                borderColor: col.border,
                pointBackgroundColor: col.border,
                pointBorderColor: '#fff',
                borderWidth: 2
            };
        });

        return new Chart(ctx, {
            type: 'radar',
            data: {
                labels: labels,
                datasets: chartDatasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        suggestedMin: 0,
                        suggestedMax: 100,
                        ticks: { stepSize: 20, backdropColor: 'transparent' }
                    }
                },
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
};
