// chart 1

var ctx = document.getElementById("chart-bars").getContext("2d");

new Chart(ctx, {
  type: "bar",
  data: {
    labels: ["Block B", "Block C", "Block D"],
    datasets: [
      {
        label: "Occupied",
        tension: 0.4,
        borderWidth: 0,
        borderRadius: 0,
        borderSkipped: false,
        backgroundColor: "#ddd",
        data: [1, 0, 0],
        maxBarThickness: 36,
      },{
        label: "Remains",
        tension: 0.4,
        borderWidth: 0,
        borderRadius: 0,
        borderSkipped: false,
        backgroundColor: "#fff",
        data: [5, 6, 9],
        maxBarThickness: 36,
      },
    ],
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: false,
      },
    },
    interaction: {
      intersect: false,
      mode: "index",
    },
    scales: {
      y: {
        stacked: true,
        grid: {
          drawBorder: false,
          display: true,
          drawOnChartArea: false,
          drawTicks: false,
        },
        ticks: {
          suggestedMin: 0,
          stepSize: 1,
          beginAtZero: true,
          padding: 15,
          font: {
            size: 14,
            family: "Open Sans",
            style: "normal",
            lineHeight: 2,
          },
          color: "#fff",
        },
      },
      x: {
        stacked: true,
        grid: {
          drawBorder: false,
          display: false,
          drawOnChartArea: false,
          drawTicks: false,
        },
        ticks: {
          display: true,
          color: "#fff",
        },
      },
    },
  },
});

// end chart 1
