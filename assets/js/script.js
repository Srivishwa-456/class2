// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Date picker initialization
    var datePickers = document.querySelectorAll('.datepicker');
    if (datePickers.length > 0) {
        datePickers.forEach(function(picker) {
            picker.addEventListener('input', function() {
                validateDateFormat(this);
            });
        });
    }
    
    // Form validation
    var forms = document.querySelectorAll('.needs-validation');
    if (forms.length > 0) {
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }
    
    // Confirm delete actions
    var deleteButtons = document.querySelectorAll('.btn-delete');
    if (deleteButtons.length > 0) {
        deleteButtons.forEach(function(button) {
            button.addEventListener('click', function(event) {
                if (!confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
                    event.preventDefault();
                }
            });
        });
    }
    
    // Print report functionality
    var printReportBtn = document.getElementById('printReport');
    if (printReportBtn) {
        printReportBtn.addEventListener('click', function() {
            window.print();
        });
    }
    
    // Export to CSV functionality
    var exportCSVBtn = document.getElementById('exportCSV');
    if (exportCSVBtn) {
        exportCSVBtn.addEventListener('click', function() {
            var tableId = this.getAttribute('data-table') || 'reportTable';
            var filename = this.getAttribute('data-filename') || 'attendance_report.csv';
            exportTableToCSV(filename, tableId);
        });
    }
    
    // Attendance status change handler
    var statusSelects = document.querySelectorAll('select[name="status[]"]');
    if (statusSelects.length > 0) {
        statusSelects.forEach(function(select) {
            select.addEventListener('change', function() {
                var row = this.closest('tr');
                row.className = '';
                row.classList.add('table-' + getStatusClass(this.value));
            });
            
            // Initialize row colors based on current status
            var row = select.closest('tr');
            if (row) {
                row.classList.add('table-' + getStatusClass(select.value));
            }
        });
    }
    
    // Initialize charts if they exist
    initializeCharts();
    
    // Handle bulk actions for attendance
    var bulkActionForm = document.getElementById('bulkActionForm');
    if (bulkActionForm) {
        var checkAll = document.getElementById('checkAll');
        if (checkAll) {
            checkAll.addEventListener('change', function() {
                var checkboxes = document.querySelectorAll('input[name="student_ids[]"]');
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = checkAll.checked;
                });
            });
        }
        
        bulkActionForm.addEventListener('submit', function(event) {
            var action = document.getElementById('bulkAction').value;
            var checkboxes = document.querySelectorAll('input[name="student_ids[]"]:checked');
            
            if (checkboxes.length === 0) {
                event.preventDefault();
                alert('Please select at least one student.');
                return;
            }
            
            if (action === 'delete' && !confirm('Are you sure you want to delete the selected students? This action cannot be undone.')) {
                event.preventDefault();
            }
        });
    }
});

// Function to validate date format
function validateDateFormat(input) {
    var datePattern = /^\d{4}-\d{2}-\d{2}$/;
    if (!datePattern.test(input.value)) {
        input.setCustomValidity('Please use YYYY-MM-DD format');
    } else {
        input.setCustomValidity('');
    }
}

// Function to export table data to CSV
function exportTableToCSV(filename, tableId) {
    var csv = [];
    var rows = document.querySelectorAll('#' + tableId + ' tr');
    
    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (var j = 0; j < cols.length; j++) {
            // Replace any commas in the cell text to avoid CSV issues
            var text = cols[j].innerText.replace(/,/g, ' ');
            // Remove any new lines
            text = text.replace(/(\r\n|\n|\r)/gm, ' ');
            row.push('"' + text + '"');
        }
        
        csv.push(row.join(','));
    }
    
    // Download CSV file
    downloadCSV(csv.join('\n'), filename);
}

// Function to download CSV
function downloadCSV(csv, filename) {
    var csvFile;
    var downloadLink;
    
    // Create CSV file
    csvFile = new Blob([csv], {type: 'text/csv'});
    
    // Create download link
    downloadLink = document.createElement('a');
    
    // File name
    downloadLink.download = filename;
    
    // Create a link to the file
    downloadLink.href = window.URL.createObjectURL(csvFile);
    
    // Hide download link
    downloadLink.style.display = 'none';
    
    // Add the link to DOM
    document.body.appendChild(downloadLink);
    
    // Click download link
    downloadLink.click();
    
    // Remove link from DOM
    document.body.removeChild(downloadLink);
}

// Function to get Bootstrap status class
function getStatusClass(status) {
    switch(status) {
        case 'present':
            return 'success';
        case 'absent':
            return 'danger';
        case 'late':
            return 'warning';
        default:
            return 'light';
    }
}

// Function to initialize charts
function initializeCharts() {
    // Attendance overview chart
    var attendanceChartCanvas = document.getElementById('attendanceChart');
    if (attendanceChartCanvas) {
        var presentCount = parseInt(attendanceChartCanvas.getAttribute('data-present') || 0);
        var absentCount = parseInt(attendanceChartCanvas.getAttribute('data-absent') || 0);
        var lateCount = parseInt(attendanceChartCanvas.getAttribute('data-late') || 0);
        
        var ctx = attendanceChartCanvas.getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Absent', 'Late'],
                datasets: [{
                    data: [presentCount, absentCount, lateCount],
                    backgroundColor: ['#28a745', '#dc3545', '#ffc107'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    position: 'bottom'
                }
            }
        });
    }
    
    // Monthly attendance trend chart
    var trendChartCanvas = document.getElementById('attendanceTrendChart');
    if (trendChartCanvas) {
        var labels = JSON.parse(trendChartCanvas.getAttribute('data-labels') || '[]');
        var values = JSON.parse(trendChartCanvas.getAttribute('data-values') || '[]');
        
        var ctx = trendChartCanvas.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Attendance %',
                    data: values,
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                    borderWidth: 2,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            max: 100
                        }
                    }]
                },
                legend: {
                    display: false
                }
            }
        });
    }
}

// Function to filter table rows
function filterTable(inputId, tableId) {
    var input = document.getElementById(inputId);
    var filter = input.value.toUpperCase();
    var table = document.getElementById(tableId);
    var tr = table.getElementsByTagName('tr');
    
    for (var i = 0; i < tr.length; i++) {
        var found = false;
        var td = tr[i].getElementsByTagName('td');
        
        // Skip header row
        if (td.length === 0) continue;
        
        for (var j = 0; j < td.length; j++) {
            if (td[j]) {
                var txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        if (found) {
            tr[i].style.display = '';
        } else {
            tr[i].style.display = 'none';
        }
    }
}

// Function to sort table
function sortTable(tableId, columnIndex) {
    var table = document.getElementById(tableId);
    var switching = true;
    var dir = 'asc';
    var switchcount = 0;
    
    while (switching) {
        switching = false;
        var rows = table.rows;
        
        for (var i = 1; i < (rows.length - 1); i++) {
            var shouldSwitch = false;
            var x = rows[i].getElementsByTagName('td')[columnIndex];
            var y = rows[i + 1].getElementsByTagName('td')[columnIndex];
            
            if (dir === 'asc') {
                if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                    shouldSwitch = true;
                    break;
                }
            } else if (dir === 'desc') {
                if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                    shouldSwitch = true;
                    break;
                }
            }
        }
        
        if (shouldSwitch) {
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
            switchcount++;
        } else {
            if (switchcount === 0 && dir === 'asc') {
                dir = 'desc';
                switching = true;
            }
        }
    }
} 