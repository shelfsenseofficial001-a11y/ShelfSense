// ============================================
// FINANCE HEAD - BUDGET (FIXED - NO LOOP)
// ============================================

console.log('✅ finance/head/budget.js loaded');

let budgetChart = null;
let dataLoaded = false;

document.addEventListener('DOMContentLoaded', function() {
    // ✅ Load ONCE on page load
    loadBudget();

    // ✅ Set Budget button
    document.getElementById('setBudgetBtn')?.addEventListener('click', function() {
        setBudget();
    });

    // ✅ Manual refresh button (optional - add if needed)
    // Create a refresh button if you want one
});

function loadBudget() {
    // ✅ Prevent multiple loads
    if (dataLoaded) {
        console.log('✅ Data already loaded, skipping');
        return;
    }

    const department = document.getElementById('budgetDepartment').value;
    const month = document.getElementById('budgetMonth').value;

    console.log('📡 Loading budget once for:', department, month);

    fetch(`?page=api_finance_get_budget&department=${department}&month=${month}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                dataLoaded = true;
                updateUI(data.data);
            }
        })
        .catch(error => {
            console.error('❌ Error:', error);
        });
}

function updateUI(data) {
    const budget = data.budget || { allocated_budget: 0, used_budget: 0 };
    const remaining = data.remaining || 0;

    document.getElementById('totalBudget').textContent = '₱' + (parseFloat(budget.allocated_budget) || 0).toFixed(2);
    document.getElementById('usedBudget').textContent = '₱' + (parseFloat(budget.used_budget) || 0).toFixed(2);
    document.getElementById('remainingBudget').textContent = '₱' + (parseFloat(remaining) || 0).toFixed(2);
    document.getElementById('budgetAmount').value = budget.allocated_budget || 0;

    // ✅ Render chart ONCE
    renderChart(data.all_budgets || []);
}

function renderChart(allBudgets) {
    const canvas = document.getElementById('budgetChart');
    if (!canvas) return;

    // ✅ Destroy existing chart if any
    if (budgetChart) {
        budgetChart.destroy();
        budgetChart = null;
    }

    if (!allBudgets || allBudgets.length === 0) {
        return;
    }

    const labels = allBudgets.map(b => (b.department || 'Unknown').toUpperCase());
    const allocated = allBudgets.map(b => parseFloat(b.allocated_budget) || 0);
    const used = allBudgets.map(b => parseFloat(b.used_budget) || 0);

    // ✅ Create chart with responsive options that don't cause loops
    budgetChart = new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Allocated',
                    data: allocated,
                    backgroundColor: 'rgba(250, 204, 21, 0.6)',
                    borderColor: '#facc15',
                    borderWidth: 2
                },
                {
                    label: 'Used',
                    data: used,
                    backgroundColor: 'rgba(220, 38, 38, 0.6)',
                    borderColor: '#dc2626',
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,  // ✅ Prevents height changes
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

function setBudget() {
    const department = document.getElementById('budgetDepartment').value;
    const month = document.getElementById('budgetMonth').value;
    const amount = parseFloat(document.getElementById('budgetAmount').value);

    if (!amount || amount <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid Amount',
            text: 'Please enter a valid budget amount.'
        });
        return;
    }

    const btn = document.getElementById('setBudgetBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

    fetch('?page=api_finance_set_budget', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            department: department,
            month_year: month,
            allocated_budget: amount
        })
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = 'Set Budget';

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Budget Set!',
                timer: 1500,
                showConfirmButton: false
            });
            // ✅ Reload ONCE after saving (reset flag)
            dataLoaded = false;
            loadBudget();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Failed',
                text: data.message || 'Please try again.'
            });
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = 'Set Budget';
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Something went wrong.'
        });
    });
}