// ============================================
// HR ATTENDANCE – FIXED SEND STATUS MESSAGE
// ============================================
console.log('✅ attendance.js loaded');

var currentWeekStart = '';
var currentWeekEnd = '';
var currentWeekNumber = 1;
var currentMonthYear = '';
var attendanceEmployees = [];
var attendanceChipsInitialized = false;
var attendanceChipsApi = null;
var weekDays = [];
var weekStatus = 'draft';
var isFetchingStatus = false;
var currentDtrUserId = null;
var currentDtrWeekStart = null;

// ===== UTILITY =====
function formatTime(t){ if(!t)return '-'; let p=t.split(':'); return p[0]+':'+p[1]; }
function formatDate(d){ if(!d)return '-'; let dt=new Date(d); return dt.toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}); }
function getRoleDisplayName(r){ let m={owner:'Owner',hr_head:'HR Head',hr_staff:'HR Staff',cashier:'Cashier',finance_head:'Finance Head',finance_staff:'Finance Staff',trainee:'Trainee'}; return m[r]||r; }
function getStatusClass(s,rd){ if(rd)return 'status-rest-day'; let m={present:'status-present',late:'status-late',absent:'status-absent',leave_paid:'status-leave',leave_unpaid:'status-leave',holiday_no_work:'status-holiday',holiday_work:'status-present'}; return m[s]||'status-absent'; }
function getStatusIcon(s,rd,re){ if(rd)return '⛔'; if(!re)return '⏳'; let m={present:'✅',late:'⚠️',absent:'❌',leave_paid:'📋',leave_unpaid:'📋',holiday_no_work:'🎉',holiday_work:'🎉'}; return m[s]||'❓'; }
function getStatusLabel(s,rd,re){ if(rd)return 'Rest Day'; if(!re)return 'No Record'; let m={present:'Present',late:'Late',absent:'Absent',leave_paid:'Leave (Paid)',leave_unpaid:'Leave (Unpaid)',holiday_no_work:'Holiday (No Work)',holiday_work:'Holiday (Work)'}; return m[s]||'Unknown'; }
function escapeHtml(t){ if(!t)return ''; let d=document.createElement('div'); d.textContent=t; return d.innerHTML; }

function isComplete(days){
    if(!days || Object.keys(days).length === 0) return false;
    for(let dt in days){
        let day = days[dt];
        if(day.is_rest_day) continue;
        if(!day.record_exists || day.status === null) return false;
    }
    return true;
}

// ===== LOAD WEEKS =====
function loadWeeksForMonth(year, month){
    let weekSelect=document.getElementById('weekSelect'); if(!weekSelect)return;
    weekSelect.innerHTML='<option value="">Loading weeks...</option>';
    fetch(`?page=api_get_weeks_of_month&year=${year}&month=${month}`)
    .then(r=>r.json())
    .then(data=>{
        if(data.success && data.data.weeks.length>0){
            weekSelect.innerHTML='';
            data.data.weeks.forEach((week,index)=>{
                let opt=document.createElement('option');
                opt.value=week.start_date;
                opt.dataset.endDate=week.end_date;
                opt.dataset.weekNumber=week.week_number;
                opt.dataset.status = 'draft';
                let start=new Date(week.start_date), end=new Date(week.end_date);
                let label=`Week ${week.week_number} (${start.toLocaleDateString('en-US',{month:'short',day:'numeric'})} - ${end.toLocaleDateString('en-US',{month:'short',day:'numeric'})})`;
                opt.textContent=label;
                if(index===0)opt.selected=true;
                weekSelect.appendChild(opt);
            });
            let first=weekSelect.options[0];
            currentWeekStart=first.value; currentWeekEnd=first.dataset.endDate; currentWeekNumber=parseInt(first.dataset.weekNumber); currentMonthYear=`${year}-${month}`;

            // First time weeks ever load for this page view, the selects
            // are showing today's month/year/week -- that is the "default"
            // baseline the chips compare against, so it has to be captured
            // here (after the async week fetch settles), not at
            // DOMContentLoaded, or the Week chip would have nothing real
            // to compare its own default to.
            if (!attendanceChipsInitialized && window.ShelfSenseFilterChips) {
                attendanceChipsInitialized = true;
                attendanceChipsApi = window.ShelfSenseFilterChips.init('activeFilterChips', [
                    { key: 'month', type: 'select', elementId: 'monthSelect', defaultValue: document.getElementById('monthSelect').value },
                    { key: 'year', type: 'select', elementId: 'yearSelect', defaultValue: document.getElementById('yearSelect').value },
                    { key: 'week', type: 'select', elementId: 'weekSelect', defaultValue: weekSelect.value },
                    { key: 'department', type: 'select', elementId: 'filterDepartment', defaultValue: 'all' },
                    { key: 'role', type: 'select', elementId: 'attendanceRoleFilter', defaultValue: 'all' },
                    { key: 'search', type: 'search', elementId: 'attendanceSearch' },
                ]);
            } else if (attendanceChipsApi) {
                // The week <select> gets rebuilt via innerHTML/option.selected
                // on every month/year change, which never fires a native
                // 'change' event -- so the chips need an explicit nudge or
                // the Week chip is left showing a stale "Loading weeks..."
                // label from the transient placeholder option.
                attendanceChipsApi.render();
            }

            fetchWeekStatus(currentMonthYear, currentWeekNumber, function(){ loadAttendance(); });
        } else { weekSelect.innerHTML='<option value="">No weeks found</option>'; }
    })
    .catch(e=>{ console.error(e); weekSelect.innerHTML='<option value="">Error loading weeks</option>'; });
}

function fetchWeekStatus(monthYear, weekNum, callback){
    if (isFetchingStatus) return;
    isFetchingStatus = true;
    fetch(`?page=api_get_month_attendance&month_year=${monthYear}`)
    .then(r=>r.json())
    .then(data=>{
        isFetchingStatus = false;
        if(data.success){
            let weeks = data.data.weeks || {};
            let status = (weeks[weekNum] && weeks[weekNum].status) ? weeks[weekNum].status : 'draft';
            weekStatus = status;
            let badge = document.getElementById('weekStatusBadge');
            if(badge){
                if(status === 'locked' || status === 'approved') badge.textContent = '🔒 Locked';
                else if(status === 'sent') badge.textContent = '📨 Sent';
                else badge.textContent = '📝 Draft';
            }
            if(attendanceEmployees.length > 0){
                renderAttendanceGrid(getFilteredEmployees());
                checkSendToHeadHR(attendanceEmployees);
            }
            if(callback) callback();
        } else {
            if(callback) callback();
        }
    })
    .catch(()=>{
        isFetchingStatus = false;
        if(callback) callback();
    });
}

function loadAttendance(){
    let weekSelect=document.getElementById('weekSelect');
    if(weekSelect && weekSelect.value){
        let sel=weekSelect.options[weekSelect.selectedIndex];
        currentWeekStart=sel.value; currentWeekEnd=sel.dataset.endDate||getEndOfWeek(currentWeekStart); currentWeekNumber=parseInt(sel.dataset.weekNumber)||1;
    } else { let today=new Date(); currentWeekStart=today.toISOString().split('T')[0]; currentWeekEnd=getEndOfWeek(currentWeekStart); }
    let department=document.getElementById('filterDepartment')?.value||'all';
    let tbody=document.getElementById('attendanceGridBody'); if(!tbody)return;
    tbody.innerHTML=`<tr><td colspan="10" class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Loading attendance...</p></td></tr>`;
    fetchWeekStatus(currentMonthYear, currentWeekNumber, function(){
        fetch(`?page=api_get_week_attendance&week_start=${currentWeekStart}&week_end=${currentWeekEnd}&department=${department}`)
        .then(r=>r.json())
        .then(data=>{
            if(data.success){
                attendanceEmployees=data.data.employees||[];
                buildWeekDays();
                renderAttendanceGrid(getFilteredEmployees());
                renderStats(attendanceEmployees);
                updateProgress(attendanceEmployees);
                checkSendToHeadHR(attendanceEmployees);
                document.getElementById('weekRangeDisplay').textContent=`${formatDate(currentWeekStart)} - ${formatDate(currentWeekEnd)}`;
                fetchWeekStatus(currentMonthYear, currentWeekNumber);
            } else { tbody.innerHTML=`<tr><td colspan="10" class="text-center text-danger py-4">${data.message||'Failed to load'}</td></tr>`; }
        })
        .catch(e=>{ console.error(e); tbody.innerHTML=`<tr><td colspan="10" class="text-center text-danger py-4">An error occurred.</td></tr>`; });
    });
}

function buildWeekDays(){
    weekDays=[];
    let start=new Date(currentWeekStart), end=new Date(currentWeekEnd);
    let cur=new Date(start);
    while(cur<=end){
        weekDays.push({date:cur.toISOString().split('T')[0], day_name:cur.toLocaleDateString('en-US',{weekday:'short'}), day_number:cur.getDate()});
        cur.setDate(cur.getDate()+1);
    }
}

// ===== SEARCH / ROLE FILTER (client-side, over the loaded week's employees) =====
function getFilteredEmployees(){
    let term = (document.getElementById('attendanceSearch')?.value || '').trim().toLowerCase();
    let role = document.getElementById('attendanceRoleFilter')?.value || 'all';
    return attendanceEmployees.filter(emp => {
        if (role !== 'all' && emp.role !== role) return false;
        if (!term) return true;
        let name = `${emp.first_name} ${emp.last_name}`.toLowerCase();
        let empNum = (emp.employee_number || '').toLowerCase();
        return name.includes(term) || empNum.includes(term);
    });
}

function applyAttendanceFilters(){
    renderAttendanceGrid(getFilteredEmployees());
}

// ===== RENDER GRID =====
function renderAttendanceGrid(employees){
    let thead=document.getElementById('attendanceGridHead'), tbody=document.getElementById('attendanceGridBody');
    if(!employees||employees.length===0){ tbody.innerHTML=`<tr><td colspan="10" class="text-center text-muted py-4">No employees found.</td></tr>`; return; }
    let isLocked = (weekStatus === 'locked' || weekStatus === 'approved');
    let headerHtml=`<tr><th style="min-width:160px;text-align:left;">Employee</th><th style="min-width:70px;">Role</th><th style="min-width:100px;">DTR</th>`;
    weekDays.forEach(day=>{ let dt=new Date(day.date); headerHtml+=`<th><div class="day-header">${dt.toLocaleDateString('en-US',{weekday:'short'})}<br><span class="day-number">${day.day_number}</span></div></th>`; });
    headerHtml+='</tr>'; thead.innerHTML=headerHtml;
    let bodyHtml='';
    employees.forEach(emp=>{
        let complete=isComplete(emp.days);
        let statusIcon=complete?'✅':'⏳';
        let dtrHtml = '';
        if (emp.dtr_image_path) {
            dtrHtml = `<button class="btn btn-sm btn-outline-primary dtr-view-btn" data-user-id="${emp.user_id}" data-week-start="${currentWeekStart}" data-image-path="${emp.dtr_image_path}"><i class="bi bi-eye"></i> View</button>`;
        } else {
            if (!isLocked) {
                dtrHtml = `
                    <input type="file" class="dtr-upload-input" data-user-id="${emp.user_id}" data-week-start="${currentWeekStart}" accept=".jpg,.jpeg,.png,.pdf" style="display:none;">
                    <button class="btn btn-sm btn-outline-primary dtr-upload-btn" data-user-id="${emp.user_id}" data-week-start="${currentWeekStart}"><i class="bi bi-upload"></i></button>
                `;
            } else {
                dtrHtml = `<span class="text-muted">—</span>`;
            }
        }
        bodyHtml+=`<tr><td class="employee-name-cell"><span class="employee-complete-badge ${complete?'bg-success text-white':'bg-warning'}">${statusIcon}</span> ${escapeHtml(emp.first_name)} ${escapeHtml(emp.last_name)}<br><small class="employee-role-cell">${escapeHtml(emp.employee_number||'')}</small></td>
            <td><span class="badge bg-info">${getRoleDisplayName(emp.role)}</span></td>
            <td>${dtrHtml}</td>`;
        weekDays.forEach(day=>{
            let dayData=emp.days[day.date]||{};
            let recordExists=dayData.record_exists||false;
            let status=dayData.status||null;
            let timeIn=dayData.time_in?formatTime(dayData.time_in):'-';
            let timeOut=dayData.time_out?formatTime(dayData.time_out):'-';
            let overtime=dayData.overtime_hours||0;
            let isRestDay=dayData.is_rest_day||false;
            let displayText='', icon='', cellClass='';
            if(isRestDay){ displayText='⛔ REST'; icon='⛔'; cellClass='status-rest-day'; }
            else if(!recordExists){ displayText='⏳ No Record'; icon='⏳'; cellClass='status-absent'; }
            else {
                switch(status){
                    case 'holiday_no_work': displayText='🎉 Holiday'; icon='🎉'; cellClass='status-holiday'; break;
                    case 'holiday_work': if(timeIn!=='-'&&timeOut!=='-'){ displayText=`${timeIn}-${timeOut}${overtime>0?' +'+overtime+'h':''}`; } else { displayText='🎉 Holiday'; } icon='🎉'; cellClass='status-present'; break;
                    case 'leave_paid': case 'leave_unpaid': displayText='📋 Leave'; icon='📋'; cellClass='status-leave'; break;
                    case 'absent': displayText='❌ Absent'; icon='❌'; cellClass='status-absent'; break;
                    case 'present': displayText=`${timeIn}-${timeOut}${overtime>0?' +'+overtime+'h':''}`; icon='✅'; cellClass='status-present'; break;
                    case 'late': displayText=`${timeIn}-${timeOut}${overtime>0?' +'+overtime+'h':''}`; icon='⚠️'; cellClass='status-late'; break;
                    default: displayText=`${timeIn}-${timeOut}`; icon='✅'; cellClass='status-present'; break;
                }
            }
            let lockIcon = '';
            let extraAttrs = '';
            if (isLocked) {
                lockIcon = '🔒 ';
                extraAttrs = `class="attendance-cell ${cellClass} locked-cell" data-locked="true" style="cursor:not-allowed; opacity:0.8;"`;
            } else {
                extraAttrs = `class="attendance-cell ${cellClass} edit-attendance-cell" data-user-id="${emp.user_id}" data-date="${day.date}" data-name="${escapeHtml(emp.first_name)} ${escapeHtml(emp.last_name)}"`;
            }
            bodyHtml+=`<td><div ${extraAttrs} title="${getStatusLabel(status,isRestDay,recordExists)}: ${displayText}"><div class="status-icon">${lockIcon}${icon}</div><div class="time-display">${displayText}</div></div></td>`;
        });
        bodyHtml+='</tr>';
    });
    tbody.innerHTML=bodyHtml;

    if (!isLocked) {
        document.querySelectorAll('.edit-attendance-cell').forEach(cell=>{
            cell.addEventListener('click', function(){ openEditModal(this.dataset.userId, this.dataset.date, this.dataset.name); });
        });
        document.querySelectorAll('.dtr-upload-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const input = this.parentElement.querySelector('.dtr-upload-input');
                if (input) input.click();
            });
        });
        document.querySelectorAll('.dtr-upload-input').forEach(input => {
            input.addEventListener('change', function() {
                const file = this.files[0];
                if (!file) return;
                const userId = this.dataset.userId;
                const weekStart = this.dataset.weekStart;
                const formData = new FormData();
                formData.append('dtr_image', file);
                formData.append('user_id', userId);
                formData.append('week_start', weekStart);
                const btn = this.parentElement.querySelector('.dtr-upload-btn');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                fetch('?page=api_upload_dtr_image', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => { throw new Error(text) });
                    }
                    return response.json();
                })
                .then(data => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-upload"></i>';
                    if (data.success) {
                        loadAttendance();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Upload Failed', text: data.message });
                    }
                })
                .catch(error => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-upload"></i>';
                    console.error('Upload error:', error);
                    Swal.fire({ icon: 'error', title: 'Upload Error', text: error.message || 'Something went wrong.' });
                });
                this.value = '';
            });
        });
    }
    document.querySelectorAll('.locked-cell').forEach(cell => {
        cell.addEventListener('click', function() {
            Swal.fire({ icon: 'info', title: '🔒 Locked', text: 'This week is already approved or locked. Edits are not allowed.', confirmButtonText: 'OK' });
        });
    });
    document.querySelectorAll('.dtr-view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.dataset.userId;
            const weekStart = this.dataset.weekStart;
            const imagePath = this.dataset.imagePath;
            openDtrModal(userId, weekStart, imagePath);
        });
    });
}

// ===== DTR MODAL =====
function openDtrModal(userId, weekStart, imagePath) {
    currentDtrUserId = userId;
    currentDtrWeekStart = weekStart;
    const img = document.getElementById('dtrViewImage');
    const placeholder = document.getElementById('dtrViewPlaceholder');
    const deleteBtn = document.getElementById('dtrDeleteBtn');
    if (imagePath) {
        img.src = '/ShelfSense/public/' + imagePath;
        img.style.display = 'block';
        placeholder.style.display = 'none';
        deleteBtn.style.display = 'inline-block';
        deleteBtn.dataset.userId = userId;
        deleteBtn.dataset.weekStart = weekStart;
    } else {
        img.style.display = 'none';
        placeholder.style.display = 'block';
        deleteBtn.style.display = 'none';
    }
    bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('dtrViewModal')).show();
}

document.getElementById('dtrDeleteBtn').addEventListener('click', function() {
    const userId = this.dataset.userId;
    const weekStart = this.dataset.weekStart;
    if (!userId || !weekStart) return;
    Swal.fire({
        title: 'Delete DTR Image?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (result.isConfirmed) {
            fetch('?page=api_delete_dtr_image', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, week_start: weekStart })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Deleted', timer: 1500, showConfirmButton: false });
                    bootstrap.Offcanvas.getInstance(document.getElementById('dtrViewModal')).hide();
                    loadAttendance();
                } else {
                    Swal.fire({ icon: 'error', title: 'Delete Failed', text: data.message });
                }
            })
            .catch(() => {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.' });
            });
        }
    });
});

// ===== SEND STATUS MESSAGE (FIXED) =====
function updateSendStatusMessage(employees) {
    const container = document.getElementById('sendStatusMessage');
    if (!container) return;

    if (weekStatus !== 'draft') {
        container.style.display = 'none';
        document.getElementById('sendToHeadHrBtn').style.display = 'none';
        return;
    }

    let allComplete = true;
    let allDtr = true;
    let incompleteList = [];
    let missingDtrList = [];

    employees.forEach(emp => {
        const complete = isComplete(emp.days);
        if (!complete) {
            allComplete = false;
            incompleteList.push(emp.first_name + ' ' + emp.last_name);
        }
        if (!emp.dtr_image_path) {
            allDtr = false;
            missingDtrList.push(emp.first_name + ' ' + emp.last_name);
        }
    });

    // Only hide if everything is perfect
    if (allComplete && allDtr && employees.length > 0) {
        container.style.display = 'none';
        document.getElementById('sendToHeadHrBtn').style.display = 'inline-block';
        return;
    }

    // Otherwise, show a detailed message
    let messages = [];
    if (!allComplete && incompleteList.length > 0) {
        messages.push(`⚠️ Incomplete attendance for: <strong>${incompleteList.join(', ')}</strong>`);
    }
    if (!allDtr && missingDtrList.length > 0) {
        messages.push(`📎 Missing DTR image for: <strong>${missingDtrList.join(', ')}</strong>`);
    }
    if (employees.length === 0) {
        messages.push('No employees found for this week.');
    }

    // Ensure container is visible and has the message
    container.style.display = 'block';
    container.innerHTML = `
        <div class="alert alert-warning alert-sm mb-0">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Cannot send to Head HR yet:</strong>
            <ul class="mb-0 mt-1" style="padding-left:18px;">
                ${messages.map(m => `<li>${m}</li>`).join('')}
            </ul>
            <small class="text-muted">Please fix these issues before sending.</small>
        </div>
    `;
    document.getElementById('sendToHeadHrBtn').style.display = 'none';
}

function checkSendToHeadHR(employees) {
    const btn = document.getElementById('sendToHeadHrBtn');
    if (!btn) return;
    if (weekStatus !== 'draft') {
        btn.style.display = 'none';
        const container = document.getElementById('sendStatusMessage');
        if (container) container.style.display = 'none';
        return;
    }
    updateSendStatusMessage(employees);
}

// ===== STATS & PROGRESS =====
function renderStats(employees){
    let total=employees.length, present=0,late=0,absent=0,leave=0,restDay=0,holiday=0;
    employees.forEach(emp=>{
        Object.values(emp.days).forEach(day=>{
            if(!day)return;
            if(day.is_rest_day) restDay++;
            else if(!day.record_exists) { /* noRecord */ }
            else if(day.status==='leave_paid'||day.status==='leave_unpaid') leave++;
            else if(day.status==='late') late++;
            else if(day.status==='present'||day.status==='holiday_work') present++;
            else if(day.status==='holiday_no_work') holiday++;
            else if(day.status==='absent') absent++;
        });
    });
    document.getElementById('statTotal').textContent=total;
    document.getElementById('statPresent').textContent=present;
    document.getElementById('statLate').textContent=late;
    document.getElementById('statAbsent').textContent=absent;
    document.getElementById('statLeave').textContent=leave;
    document.getElementById('statRestDay').textContent=restDay;
}

function updateProgress(employees){
    let complete=0; employees.forEach(emp=>{ if(isComplete(emp.days)) complete++; });
    let total=employees.length, pct=total>0?Math.round((complete/total)*100):0;
    document.getElementById('progressText').textContent=`Attendance complete: ${complete} of ${total} employees`;
    document.getElementById('progressFill').style.width=pct+'%';
}

// ===== SEND WEEK =====
document.getElementById('sendToHeadHrBtn')?.addEventListener('click', function(){
    let weekSelect=document.getElementById('weekSelect');
    if(!weekSelect||!weekSelect.value){ Swal.fire({icon:'warning',title:'No Week Selected',text:'Please select a week first.'}); return; }
    let selectedOption=weekSelect.options[weekSelect.selectedIndex];
    let weekNumber=selectedOption.dataset.weekNumber;
    let monthYear=document.getElementById('yearSelect').value+'-'+document.getElementById('monthSelect').value;
    if(!weekNumber){ Swal.fire({icon:'error',title:'Error',text:'Week number not found.'}); return; }
    Swal.fire({
        title:'Send Week '+weekNumber+' for Approval?',
        html:`<p>This will send <strong>Week ${weekNumber}</strong> (${selectedOption.textContent}) to Head HR for review.</p><p class="text-muted small">All employees must have DTR images uploaded.</p>`,
        icon:'question', showCancelButton:true, confirmButtonColor:'#198754', confirmButtonText:'Yes, Send', cancelButtonText:'Cancel'
    }).then(result=>{
        if(result.isConfirmed){
            let btn=document.getElementById('sendToHeadHrBtn');
            btn.disabled=true; btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span> Sending...';
            fetch('?page=api_send_week_to_head_hr',{
                method:'POST', headers:{'Content-Type':'application/json'},
                body:JSON.stringify({ month_year:monthYear, week_number:parseInt(weekNumber) })
            })
            .then(r=>r.json())
            .then(data=>{
                btn.disabled=false; btn.innerHTML='<i class="bi bi-send"></i> Send to Head HR';
                if(data.success){
                    Swal.fire({icon:'success',title:'Sent!',text:`Week ${weekNumber} sent.`,timer:2000,showConfirmButton:false});
                    btn.style.display='none';
                    document.getElementById('weekStatusBadge').textContent='Sent 📨';
                    loadAttendance();
                } else {
                    Swal.fire({icon:'error',title:'Failed',text:data.message||'Try again.'});
                }
            })
            .catch(e=>{ btn.disabled=false; btn.innerHTML='<i class="bi bi-send"></i> Send to Head HR'; Swal.fire({icon:'error',title:'Error',text:'Something went wrong.'}); });
        }
    });
});

// ===== EDIT MODAL (keep existing) =====
function openEditModal(userId, date, employeeName){
    if (weekStatus === 'locked' || weekStatus === 'approved') {
        Swal.fire({ icon: 'info', title: '🔒 Locked', text: 'This week is already approved or locked. Edits are not allowed.', confirmButtonText: 'OK' });
        return;
    }
    let employee = attendanceEmployees.find(e => e.user_id == userId);
    if(!employee){ Swal.fire({icon:'error',title:'Error',text:'Employee not found'}); return; }
    let dayData = employee.days[date] || {};
    let scheduledIn = dayData.scheduled_in || '';
    let scheduledOut = dayData.scheduled_out || '';
    let isRestDay = dayData.is_rest_day || false;
    let status = dayData.status || null;
    document.getElementById('editUserId').value = userId;
    document.getElementById('editDate').value = date;
    document.getElementById('editEmployeeName').textContent = employeeName;
    document.getElementById('editDateDisplay').textContent = formatDate(date);
    document.getElementById('editScheduledIn').value = scheduledIn;
    document.getElementById('editScheduledOut').value = scheduledOut;
    document.getElementById('editScheduledShift').textContent = `${formatTime(scheduledIn)||'-'} - ${formatTime(scheduledOut)||'-'}`;
    document.getElementById('editTimeIn').value = dayData.time_in || '';
    document.getElementById('editTimeOut').value = dayData.time_out || '';
    document.getElementById('editOvertime').value = dayData.overtime_hours || 0;
    document.getElementById('editNotes').value = dayData.notes || '';
    document.getElementById('editOnLeave').checked = (status==='leave_paid' || status==='leave_unpaid');
    document.getElementById('editRestDay').checked = isRestDay;
    document.getElementById('editHoliday').checked = (status==='holiday_no_work' || status==='holiday_work');
    document.getElementById('editAbsent').checked = (status==='absent');
    applyToggleStates();
    updateStatusDisplay();
    let timeIn=document.getElementById('editTimeIn'), timeOut=document.getElementById('editTimeOut');
    let onLeave=document.getElementById('editOnLeave'), restDay=document.getElementById('editRestDay'), holiday=document.getElementById('editHoliday'), absent=document.getElementById('editAbsent');
    let newTimeIn=timeIn.cloneNode(true); timeIn.parentNode.replaceChild(newTimeIn,timeIn);
    newTimeIn.addEventListener('input', function(){ updateStatusDisplay(); autoCalculateOvertime(); });
    let newTimeOut=timeOut.cloneNode(true); timeOut.parentNode.replaceChild(newTimeOut,timeOut);
    newTimeOut.addEventListener('input', function(){ updateStatusDisplay(); autoCalculateOvertime(); });
    let newOnLeave=onLeave.cloneNode(true); onLeave.parentNode.replaceChild(newOnLeave,onLeave);
    newOnLeave.addEventListener('change', function(){ enforceSingleToggle(this); applyToggleStates(); updateStatusDisplay(); });
    let newRestDay=restDay.cloneNode(true); restDay.parentNode.replaceChild(newRestDay,restDay);
    newRestDay.addEventListener('change', function(){ enforceSingleToggle(this); applyToggleStates(); updateStatusDisplay(); });
    let newHoliday=holiday.cloneNode(true); holiday.parentNode.replaceChild(newHoliday,holiday);
    newHoliday.addEventListener('change', function(){ enforceSingleToggle(this); applyToggleStates(); updateStatusDisplay(); });
    let newAbsent=absent.cloneNode(true); absent.parentNode.replaceChild(newAbsent,absent);
    newAbsent.addEventListener('change', function(){ enforceSingleToggle(this); applyToggleStates(); updateStatusDisplay(); });
    new bootstrap.Modal(document.getElementById('editAttendanceModal')).show();
}

function enforceSingleToggle(changedEl){
    if(!changedEl.checked) return;
    ['editOnLeave','editRestDay','editHoliday','editAbsent'].forEach(function(id){
        let el=document.getElementById(id);
        if(el && el !== changedEl) el.checked=false;
    });
}

function applyToggleStates(){
    let onLeave=document.getElementById('editOnLeave').checked;
    let isRestDay=document.getElementById('editRestDay').checked;
    let isHoliday=document.getElementById('editHoliday').checked;
    let isAbsent=document.getElementById('editAbsent').checked;
    let timeIn=document.getElementById('editTimeIn'), timeOut=document.getElementById('editTimeOut'), overtime=document.getElementById('editOvertime');
    if(onLeave || isRestDay || isAbsent){
        timeIn.disabled=true; timeOut.disabled=true; timeIn.value=''; timeOut.value=''; overtime.value=0; overtime.disabled=true;
    } else {
        timeIn.disabled=false; timeOut.disabled=false; overtime.disabled=false;
        if(timeOut.value) autoCalculateOvertime();
    }
}

function updateStatusDisplay(){
    let timeIn=document.getElementById('editTimeIn')?.value||'';
    let timeOut=document.getElementById('editTimeOut')?.value||'';
    let onLeave=document.getElementById('editOnLeave')?.checked||false;
    let isRestDay=document.getElementById('editRestDay')?.checked||false;
    let isHoliday=document.getElementById('editHoliday')?.checked||false;
    let isAbsent=document.getElementById('editAbsent')?.checked||false;
    let scheduledIn=document.getElementById('editScheduledIn')?.value||'';
    let display=document.getElementById('editStatusDisplay');
    if(!display)return;
    let status='', badgeClass='';
    if(isAbsent){ status='Absent'; badgeClass='danger'; }
    else if(onLeave){ status='On Leave (Paid)'; badgeClass='info'; }
    else if(isRestDay){ status='Rest Day'; badgeClass='secondary'; }
    else if(isHoliday){
        if(timeIn && timeOut){ status='Holiday (Work)'; badgeClass='success'; }
        else { status='Holiday (No Work)'; badgeClass='purple'; }
    }
    else if(!timeIn && !timeOut){ status='No Record'; badgeClass='secondary'; }
    else if(timeIn && !timeOut){ status='Incomplete (No Time Out)'; badgeClass='warning'; }
    else if(!timeIn && timeOut){ status='Incomplete (No Time In)'; badgeClass='warning'; }
    else {
        if(scheduledIn && timeIn > scheduledIn){ status='Late'; badgeClass='warning'; }
        else { status='Present'; badgeClass='success'; }
    }
    display.innerHTML=`<span class="badge bg-${badgeClass}">${status}</span>`;
}

function autoCalculateOvertime(){
    let timeOut=document.getElementById('editTimeOut')?.value||'';
    let scheduledOut=document.getElementById('editScheduledOut')?.value||'';
    let overtimeInput=document.getElementById('editOvertime');
    if(timeOut && scheduledOut && overtimeInput){
        let outParts=timeOut.split(':'), schParts=scheduledOut.split(':');
        let outMin=parseInt(outParts[0])*60+parseInt(outParts[1]);
        let schMin=parseInt(schParts[0])*60+parseInt(schParts[1]);
        let diff=Math.max(0, outMin - schMin);
        let hours=Math.round((diff/60)*2)/2;
        overtimeInput.value=hours>0?hours:0;
    } else if(overtimeInput){ overtimeInput.value=0; }
}

// ===== SAVE ATTENDANCE =====
document.addEventListener('DOMContentLoaded', function(){
    document.getElementById('editAttendanceForm').addEventListener('submit', function(e){
        e.preventDefault();
        if (weekStatus === 'locked' || weekStatus === 'approved') {
            Swal.fire({ icon: 'warning', title: 'Locked', text: 'This week is already approved or locked. Changes cannot be saved.', confirmButtonText: 'OK' });
            return;
        }
        let formData=new FormData(this);
        let data=Object.fromEntries(formData);
        let onLeave=data.on_leave==='on';
        let isRestDay=data.is_rest_day==='on';
        let isHoliday=data.is_holiday==='on';
        let isAbsent=data.is_absent==='on';
        let timeIn=data.time_in;
        let timeOut=data.time_out;
        let scheduledIn=data.scheduled_in;
        let status='present';
        if(isAbsent) status='absent';
        else if(onLeave) status='leave_paid';
        else if(isRestDay) status='rest_day';
        else if(isHoliday){
            if(timeIn && timeOut) status='holiday_work';
            else status='holiday_no_work';
        }
        else if(!timeIn && !timeOut) status='absent';
        else if(scheduledIn && timeIn > scheduledIn) status='late';
        else status='present';
        data.status=status;
        delete data.on_leave; delete data.is_rest_day; delete data.is_holiday; delete data.is_absent; delete data.scheduled_in; delete data.scheduled_out;
        if(!data.user_id || !data.date){ Swal.fire({icon:'warning',title:'Missing Data',text:'Please fill required fields.'}); return; }
        let submitBtn=this.querySelector('button[type="submit"]');
        submitBtn.disabled=true; submitBtn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span> Saving...';
        fetch('?page=api_save_attendance',{
            method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data)
        })
        .then(r=>r.json())
        .then(result=>{
            submitBtn.disabled=false; submitBtn.innerHTML='Save';
            if(result.success){
                Swal.fire({icon:'success',title:'Saved!',timer:1000,showConfirmButton:false});
                bootstrap.Modal.getInstance(document.getElementById('editAttendanceModal')).hide();
                loadAttendance();
            } else {
                Swal.fire({icon:'error',title:'Save Failed',text:result.message||'Try again.'});
            }
        })
        .catch(e=>{ submitBtn.disabled=false; submitBtn.innerHTML='Save'; Swal.fire({icon:'error',title:'Error',text:'Something went wrong.'}); });
    });

    document.getElementById('monthSelect').addEventListener('change', function(){
        let month=this.value, year=document.getElementById('yearSelect').value;
        loadWeeksForMonth(year, month);
    });
    document.getElementById('yearSelect').addEventListener('change', function(){
        let year=this.value, month=document.getElementById('monthSelect').value;
        loadWeeksForMonth(year, month);
    });
    document.getElementById('weekSelect').addEventListener('change', function(){
        let sel=this.options[this.selectedIndex];
        if(sel && sel.value){
            currentWeekStart=sel.value; currentWeekEnd=sel.dataset.endDate||getEndOfWeek(currentWeekStart); currentWeekNumber=parseInt(sel.dataset.weekNumber)||1;
            loadAttendance();
        }
    });
    document.getElementById('loadAttendanceBtn').addEventListener('click', function(){
        let weekSelect=document.getElementById('weekSelect');
        if(weekSelect && weekSelect.value){
            let sel=weekSelect.options[weekSelect.selectedIndex];
            currentWeekStart=sel.value; currentWeekEnd=sel.dataset.endDate||getEndOfWeek(currentWeekStart); currentWeekNumber=parseInt(sel.dataset.weekNumber)||1;
            loadAttendance();
        }
    });
    document.getElementById('filterDepartment').addEventListener('change', loadAttendance);
    document.getElementById('attendanceSearch')?.addEventListener('input', applyAttendanceFilters);
    document.getElementById('attendanceRoleFilter')?.addEventListener('change', applyAttendanceFilters);

    let month=document.getElementById('monthSelect').value, year=document.getElementById('yearSelect').value;
    loadWeeksForMonth(year, month);
});

function getEndOfWeek(start){ let d=new Date(start); d.setDate(d.getDate()+6); return d.toISOString().split('T')[0]; }