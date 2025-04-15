
// 🎨 Balanced dark theme chart styling
const chartColors = {
  allocation: ['#a9d9ae', '#b6dfba', '#c2e4c5'], // soft greens
  spending: ['#b6dfba', '#a9d9ae', '#c2e4c5', '#cee9d0'], // consistent pastel range
  income: '#90ce98',   // primary green for income
  expense: '#dc3545'   // red for expenses (keeps contrast)
};



// 🌐 Global chart options
const globalChartOptions = {
  chart: {
    height: 300,
    fontFamily: 'Inter, sans-serif',
    toolbar: { show: false },
    zoom: { enabled: false },
    dropShadow: {
      enabled: true,
      top: 2,
      left: 2,
      blur: 4,
      color: '#000',
      opacity: 0.15
    }
  },
  legend: {
    labels: { colors: '#8b8b8b' },
    fontSize: '14px'
  },
  dataLabels: { enabled: false },
  tooltip: {
    theme: 'dark',
    style: {
      fontSize: '14px'
    }
  }
};

// 📊 Cash Flow Chart
if (document.querySelector("#flowChart")) {
  if (window.flowChartInstance) window.flowChartInstance.destroy();

  window.flowChartInstance = new ApexCharts(document.querySelector("#flowChart"), {
    ...globalChartOptions,
    chart: { ...globalChartOptions.chart, type: 'bar' },
    series: [
      { name: 'Nakazila', data: chartData.flowChart.nakazila },
      { name: 'Dvigi', data: chartData.flowChart.dvigi }
    ],
    xaxis: {
      categories: chartData.flowChart.months,
      labels: { style: { colors: '#8b8b8b' } }
    },
    yaxis: {
      labels: { style: { colors: '#8b8b8b' } }
    },
    grid: {
      borderColor: '#3f3f3f',
      strokeDashArray: 4
    },
    colors: [chartColors.income, chartColors.expense]
  });

  window.flowChartInstance.render();
}

// 💰 Wallet vs. Savings (Donut)
if (document.querySelector("#walletVsSavingsChart")) {
  new ApexCharts(document.querySelector("#walletVsSavingsChart"), {
    chart: {
      type: 'donut',
      height: 320,
      fontFamily: 'Inter, sans-serif',
      animations: {
        enabled: true,
        easing: 'easeinout',
        speed: 800
      }
    },
    labels: chartData.walletVsSavings.labels,
    series: chartData.walletVsSavings.values, // this now holds raw values (wallet + savings)
 // use raw values here
    colors: ['#34D399', '#0EA5E9'],
    legend: {
      show: false
    },
    dataLabels: {
      enabled: true,
      formatter: function (val) {
        return val.toFixed(1) + "%"; // label shows %
      },
      style: {
        fontSize: '14px'
      }
    },
    tooltip: {
      theme: 'dark',
      y: {
        formatter: function (val) {
          return val.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
          }) + "€";
        }
      }
    }
  }).render();
}







// 🎯 Savings Breakdown (Donut)
if (document.querySelector("#savingsBreakdownChart")) {
  new ApexCharts(document.querySelector("#savingsBreakdownChart"), {
    chart: {
      type: 'donut',
      height: 320,
      fontFamily: 'Inter, sans-serif',
      animations: {
        enabled: true,
        easing: 'easeinout',
        speed: 800
      }
    },
    labels: chartData.savingsBreakdown.labels,
    series: chartData.savingsBreakdown.values,
    colors: ['#4ADE80', '#22D3EE', '#FBBF24', '#F87171', '#A78BFA', '#34D399', '#FB7185', '#60A5FA', '#F472B6'],
    legend: {
      show: false
    },
    dataLabels: {
      enabled: true,
      formatter: (val) => val.toFixed(1) + "%",
      style: {
        fontSize: '14px'
      }
    },
    tooltip: {
      theme: 'dark',
      y: {
        formatter: function (val) {
          return  val.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
          }) + "€";
        }
      }
    }
  }).render();
}


// 💸 Spending Analysis (Treemap)
if (
  document.querySelector("#spendingChart") &&
  chartData.spending.categories.length > 0 &&
  chartData.spending.amounts.length > 0
) {
  if (window.spendingChartInstance) window.spendingChartInstance.destroy();

  const treemapData = chartData.spending.categories.map((category, index) => ({
    x: category,
    y: chartData.spending.amounts[index]
  }));

  window.spendingChartInstance = new ApexCharts(document.querySelector("#spendingChart"), {
    chart: {
      type: 'treemap',
      width: 700,
      height: 400,
      offsetX: -100,
      fontFamily: 'Inter, sans-serif',
      toolbar: { show: false },
      colors: ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0', '#3F51B5', '#546E7A', '#D4526E', '#8D5B4C', '#F86624'],

    },
    plotOptions: {
      treemap: {
        distributed: true,
        enableShades: false
      }
    },
    
    series: [{ data: treemapData }],
    colors: [
      '#4ADE80', '#22D3EE', '#FBBF24', '#F87171',
      '#A78BFA', '#34D399', '#FB7185', '#60A5FA', '#F472B6'
    ],
    legend: { show: false },
    dataLabels: {
      enabled: true,
      style: { fontSize: '14px', colors: ['#ffffff'] },
      formatter: function (text, op) {
        return `${text}\n${op.value }€`;
      }
    },
    tooltip: {
      theme: 'dark',
      y: {
        formatter: (value) => `${value.toFixed(2) + "€"}`
      }
    }
  });

  window.spendingChartInstance.render();
}
