Chart.register(ChartDataLabels);

/* =========================
   HELPERS
========================= */
function formatMonthLabel(label) {
    const [year, month] = label.split("-");
    const date = new Date(year, month - 1);
    return date.toLocaleString("en-US", { month: "short", year: "numeric" });
}

const formattedMonthLabels = monthLabels.map(formatMonthLabel);

/* =========================
   CATEGORY CHART
========================= */
const categoryCtx = document.getElementById("categoryChart").getContext("2d");

new Chart(categoryCtx, {
    type: "doughnut",
    data: {
        labels: categoryLabels,
        datasets: [{
            data: categoryData,
            backgroundColor: [
                "#4e79a7",
                "#f28e2b",
                "#59a14f",
                "#e15759",
                "#76b7b2",
                "#edc948",
                "#b07aa1",
                "#ff9da7"
            ],
            borderColor: "#ffffff",
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: "55%",
        plugins: {
            legend: {
                position: "top",
                labels: {
                    boxWidth: 14,
                    padding: 16,
                    font: {
                        size: 13
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const value = context.raw;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                        return `${context.label}: ${value} orders (${percentage}%)`;
                    }
                }
            },
            datalabels: {
                color: "#fff",
                font: {
                    weight: "bold",
                    size: 13
                },
                formatter: function(value, context) {
                    const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                    if (!value || total === 0) return "";
                    const percentage = ((value / total) * 100).toFixed(1);
                    return `${value}\n${percentage}%`;
                }
            }
        }
    }
});

/* =========================
   MONTHLY ORDERS + REVENUE CHART
========================= */
const monthCtx = document.getElementById("monthChart").getContext("2d");

new Chart(monthCtx, {
    type: "bar",
    data: {
        labels: formattedMonthLabels,
        datasets: [
            {
                type: "bar",
                label: "Orders",
                data: monthData,
                backgroundColor: "rgba(102, 178, 255, 0.75)",
                borderColor: "rgba(102, 178, 255, 1)",
                borderWidth: 1,
                borderRadius: 8,
                yAxisID: "y"
            },
            {
                type: "line",
                label: "Revenue (RM)",
                data: monthRevenue,
                borderColor: "#ff7f50",
                backgroundColor: "#ff7f50",
                tension: 0.35,
                fill: false,
                pointRadius: 4,
                pointHoverRadius: 6,
                yAxisID: "y1"
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: "index",
            intersect: false
        },
        scales: {
            y: {
                beginAtZero: true,
                position: "left",
                title: {
                    display: true,
                    text: "Orders"
                },
                ticks: {
                    precision: 0
                }
            },
            y1: {
                beginAtZero: true,
                position: "right",
                grid: {
                    drawOnChartArea: false
                },
                title: {
                    display: true,
                    text: "Revenue (RM)"
                },
                ticks: {
                    callback: function(value) {
                        return "RM " + value;
                    }
                }
            }
        },
        plugins: {
            legend: {
                position: "top"
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        if (context.dataset.label === "Revenue (RM)") {
                            return `${context.dataset.label}: RM ${Number(context.raw).toFixed(2)}`;
                        }
                        return `${context.dataset.label}: ${context.raw}`;
                    }
                }
            },
            datalabels: {
                display: function(context) {
                    return context.dataset.type === "bar";
                },
                anchor: "end",
                align: "end",
                color: "#333",
                font: {
                    weight: "bold",
                    size: 12
                },
                formatter: function(value) {
                    return value;
                }
            }
        }
    }
});