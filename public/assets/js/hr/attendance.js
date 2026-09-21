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
var selectedEmployeeUserId = null;
var atmDraggedUserId = null;
var atmDraggedEl = null;
var atmPlaceholderEl = null;

// ===== UTILITY =====
function formatTime(t){ if(!t)return '-'; let p=t.split(':'); return p[0]+':'+p[1]; }
function formatDate(d){ if(!d)return '-'; let dt=new Date(d); return dt.toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}); }
function getRoleDisplayName(r){ let m={owner:'Owner',hr_head:'HR Head',hr_staff:'HR Staff',cashier:'Cashier',finance_head:'Finance Head',finance_staff:'Finance Staff',trainee:'Trainee'}; return m[r]||r; }
function getStatusClass(s,rd){ if(rd||s==='rest_day')return 'status-rest-day'; let m={present:'status-present',late:'status-late',absent:'status-absent',leave_paid:'status-leave',leave_unpaid:'status-leave',holiday_no_work:'status-holiday',holiday_work:'status-present'}; return m[s]||'status-absent'; }
function getStatusIconClass(s,rd,re){ if(rd||s==='rest_day')return 'bi-moon-stars-fill'; if(!re)return 'bi-hourglass-split'; let m={present:'bi-check-circle-fill',late:'bi-exclamation-triangle-fill',absent:'bi-x-circle-fill',leave_paid:'bi-clipboard-check-fill',leave_unpaid:'bi-clipboard-fill',holiday_no_work:'bi-stars',holiday_work:'bi-stars'}; return m[s]||'bi-question-circle'; }
function getStatusLabel(s,rd,re){ if(rd||s==='rest_day')return 'Rest Day'; if(!re)return 'No Record'; let m={present:'Present',late:'Late',absent:'Absent',leave_paid:'Leave (Paid)',leave_unpaid:'Leave (Unpaid)',holiday_no_work:'Holiday (No Work)',holiday_work:'Holiday (Work)'}; return m[s]||'Unknown'; }
function escapeHtml(t){ if(!t)return ''; let d=document.createElement('div'); d.textContent=t; return d.innerHTML; }
function ordinalSuffix(n){ let s=['th','st','nd','rd'], v=n%100; return n+(s[(v-20)%10]||s[v]||s[0]); }
function formatOrdinalDate(dateStr){ let d=new Date(dateStr+'T00:00:00'); return `${ordinalSuffix(d.getDate())} ${d.toLocaleDateString('en-US',{month:'short'})} ${d.getFullYear()}`; }
function getDayAbbr(dateStr){ let d=new Date(dateStr+'T00:00:00'); return d.toLocaleDateString('en-US',{weekday:'short'}); }
function hoursBetween(t1,t2){ if(!t1||!t2)return 0; let [h1,m1]=t1.split(':').map(Number), [h2,m2]=t2.split(':').map(Number); let start=h1*60+m1, end=h2*60+m2; if(end<start)end+=24*60; return Math.max(0,(end-start)/60); }

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
function updateWeekNavLabel(){
    let labelText=document.getElementById('weekNavLabelText');
    if(!labelText || !currentWeekStart || !currentWeekEnd) return;
    labelText.textContent=`${formatDate(currentWeekStart)} - ${formatDate(currentWeekEnd)}`;
}

function loadWeeksForMonth(year, month, selectMode){
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
                weekSelect.appendChild(opt);
            });
            let target=null;
            if(selectMode==='today'){
                let todayStr=new Date().toISOString().split('T')[0];
                target=Array.from(weekSelect.options).find(o=>o.value<=todayStr && o.dataset.endDate>=todayStr);
            }
            if(!target){
                target=(selectMode==='last') ? weekSelect.options[weekSelect.options.length-1] : weekSelect.options[0];
            }
            target.selected=true;
            currentWeekStart=target.value; currentWeekEnd=target.dataset.endDate; currentWeekNumber=parseInt(target.dataset.weekNumber); currentMonthYear=`${year}-${month}`;
            updateWeekNavLabel();

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
                if(status === 'locked' || status === 'approved') badge.innerHTML = '<i class="bi bi-lock-fill"></i> Locked';
                else if(status === 'sent') badge.innerHTML = '<i class="bi bi-send-fill"></i> Sent';
                else badge.innerHTML = '<i class="bi bi-pencil-square"></i> Draft';
            }
            if(attendanceEmployees.length > 0){
                renderEmployeeList(getFilteredEmployees());
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
    updateWeekNavLabel();
    let department=document.getElementById('filterDepartment')?.value||'all';
    let listPanel=document.getElementById('employeeListPanel'); if(!listPanel)return;
    listPanel.innerHTML=`<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted small">Loading attendance...</p></div>`;
    fetchWeekStatus(currentMonthYear, currentWeekNumber, function(){
        fetch(`?page=api_get_week_attendance&week_start=${currentWeekStart}&week_end=${currentWeekEnd}&department=${department}`)
        .then(r=>r.json())
        .then(data=>{
            if(data.success){
                attendanceEmployees=data.data.employees||[];
                buildWeekDays();
                renderEmployeeList(getFilteredEmployees());
                renderStats(attendanceEmployees);
                updateProgress(attendanceEmployees);
                checkSendToHeadHR(attendanceEmployees);
                document.getElementById('weekRangeDisplay').textContent=`${formatDate(currentWeekStart)} - ${formatDate(currentWeekEnd)}`;
                fetchWeekStatus(currentMonthYear, currentWeekNumber);
            } else { listPanel.innerHTML=`<div class="text-center text-danger py-4">${data.message||'Failed to load'}</div>`; }
        })
        .catch(e=>{ console.error(e); listPanel.innerHTML=`<div class="text-center text-danger py-4">An error occurred.</div>`; });
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

// ===== SEARCH FILTER (client-side, over the loaded week's employees) =====
function getFilteredEmployees(){
    let term = (document.getElementById('attendanceSearch')?.value || '').trim().toLowerCase();
    return attendanceEmployees.filter(emp => {
        if (!term) return true;
        let name = `${emp.first_name} ${emp.last_name}`.toLowerCase();
        let empNum = (emp.employee_number || '').toLowerCase();
        return name.includes(term) || empNum.includes(term);
    });
}

function applyAttendanceFilters(){
    renderEmployeeList(getFilteredEmployees());
}

// Drag-and-drop reorder of the employee list -- purely a display-order
// preference for this loaded week (not persisted server-side); reordering
// the underlying array is safe since nothing downstream (stats, filters)
// depends on list order. beforeUserId is looked up AFTER the dragged item
// is removed, so inserting before it lands in the right spot regardless of
// whether the card moved up or down the list.
function atmCommitReorder(draggedUserId, beforeUserId){
    let fromIdx=attendanceEmployees.findIndex(e=>String(e.user_id)===String(draggedUserId));
    if(fromIdx===-1) return;
    let [moved]=attendanceEmployees.splice(fromIdx,1);
    let toIdx=beforeUserId ? attendanceEmployees.findIndex(e=>String(e.user_id)===String(beforeUserId)) : -1;
    if(toIdx===-1) toIdx=attendanceEmployees.length;
    attendanceEmployees.splice(toIdx,0,moved);
    renderEmployeeList(getFilteredEmployees());
}

// FLIP animation helper: records each item's position before a DOM change,
// runs the change, then animates every item from its old position to its
// new one -- so dragging one card visibly slides the others out of the way
// instead of them silently jumping to their new spot.
function atmAnimateReorder(container, mutate){
    let items=[...container.querySelectorAll('.atm-emp-item, .atm-emp-placeholder')];
    let firstRects=new Map();
    items.forEach(el=>firstRects.set(el, el.getBoundingClientRect()));
    mutate();
    let after=[...container.querySelectorAll('.atm-emp-item, .atm-emp-placeholder')];
    after.forEach(el=>{
        let first=firstRects.get(el);
        if(!first) return;
        let last=el.getBoundingClientRect();
        let deltaY=first.top-last.top;
        if(Math.abs(deltaY)<1) return;
        el.style.transition='none';
        el.style.transform=`translateY(${deltaY}px)`;
        requestAnimationFrame(()=>{
            el.style.transition='transform 180ms ease';
            el.style.transform='';
        });
    });
}

// Finds the sibling the dragged card should land in front of, based on
// the cursor's Y position relative to each remaining card's midpoint.
function atmFindDropTarget(container, y){
    let items=[...container.querySelectorAll('.atm-emp-item:not(.dragging)')];
    for(const item of items){
        let rect=item.getBoundingClientRect();
        if(y < rect.top + rect.height/2) return item;
    }
    return null;
}

// ===== RENDER EMPLOYEE LIST (left pane) =====
function renderEmployeeList(employees){
    let panel=document.getElementById('employeeListPanel');
    if(!panel)return;
    if(!employees||employees.length===0){
        panel.innerHTML=`<div class="text-center text-muted py-4">No employees found.</div>`;
        renderEmployeeTimecard(null);
        return;
    }
    let html='';
    employees.forEach(emp=>{
        let complete=isComplete(emp.days);
        let initials=((emp.first_name||'')[0]||'')+((emp.last_name||'')[0]||'');
        let isActive=(selectedEmployeeUserId && String(emp.user_id)===selectedEmployeeUserId);
        html+=`
            <div class="atm-emp-item ${isActive?'active':''}" data-user-id="${emp.user_id}" draggable="true">
                <i class="bi bi-grip-vertical atm-emp-grip"></i>
                <div class="atm-emp-avatar">${escapeHtml(initials.toUpperCase())}</div>
                <div>
                    <div class="atm-emp-name">${escapeHtml(emp.first_name)} ${escapeHtml(emp.last_name)}</div>
                    <div class="atm-emp-role">${escapeHtml(emp.employee_number||'')} &middot; ${getRoleDisplayName(emp.role)}</div>
                </div>
                <span class="atm-emp-badge ${complete?'bg-success text-white':'bg-warning'}"><i class="bi ${complete?'bi-check-lg':'bi-hourglass-split'}"></i></span>
            </div>
        `;
    });
    panel.innerHTML=html;
    panel.querySelectorAll('.atm-emp-item').forEach(item=>{
        item.addEventListener('click', function(){ selectEmployee(this.dataset.userId); });
        item.addEventListener('dragstart', function(e){
            atmDraggedUserId=this.dataset.userId;
            atmDraggedEl=this;
            e.dataTransfer.effectAllowed='move';
            try{ e.dataTransfer.setData('text/plain', this.dataset.userId); }catch(err){}
            // Let the browser finish capturing its native drag-ghost image
            // from the un-dimmed card before we visually mark it as dragging.
            setTimeout(()=>{ item.classList.add('dragging'); }, 0);
        });
        item.addEventListener('dragend', function(){
            this.classList.remove('dragging');
            atmPlaceholderEl?.remove();
            atmPlaceholderEl=null;
            atmDraggedUserId=null;
            atmDraggedEl=null;
        });
    });

    // Bound once on the container itself (its node survives re-renders --
    // only its innerHTML is replaced), this tracks the cursor across the
    // whole list rather than per-card, so the insertion point updates
    // continuously as you drag over gaps between cards too.
    if(!panel.dataset.dndBound){
        panel.dataset.dndBound='1';
        panel.addEventListener('dragover', function(e){
            if(!atmDraggedEl) return;
            e.preventDefault();
            e.dataTransfer.dropEffect='move';
            if(!atmPlaceholderEl){
                atmPlaceholderEl=document.createElement('div');
                atmPlaceholderEl.className='atm-emp-placeholder';
                // Mirror the dragged card's own markup so the placeholder
                // reads as "this is what lands here", not just an empty slot.
                atmPlaceholderEl.innerHTML=atmDraggedEl.innerHTML;
            }
            let target=atmFindDropTarget(panel, e.clientY);
            if(target===atmPlaceholderEl) return;
            atmAnimateReorder(panel, function(){
                if(target){
                    panel.insertBefore(atmPlaceholderEl, target);
                } else {
                    panel.appendChild(atmPlaceholderEl);
                }
            });
        });
        panel.addEventListener('drop', function(e){
            e.preventDefault();
            if(!atmDraggedUserId || !atmPlaceholderEl) return;
            let nextEl=atmPlaceholderEl.nextElementSibling;
            let beforeUserId=(nextEl && nextEl.classList.contains('atm-emp-item')) ? nextEl.dataset.userId : null;
            atmPlaceholderEl.remove();
            atmPlaceholderEl=null;
            atmCommitReorder(atmDraggedUserId, beforeUserId);
        });
    }

    // Keep the timecard panel in sync whenever the list is rebuilt (e.g.
    // after a save/upload triggers loadAttendance()), so the same employee
    // stays open instead of resetting to the placeholder.
    if(selectedEmployeeUserId){
        let emp=employees.find(e=>String(e.user_id)===selectedEmployeeUserId);
        renderEmployeeTimecard(emp||null);
    }
}

function selectEmployee(userId){
    selectedEmployeeUserId=String(userId);
    renderEmployeeList(getFilteredEmployees());
}

// ===== RENDER TIMECARD (right pane) =====
function renderEmployeeTimecard(employee){
    let panel=document.getElementById('employeeTimecardPanel');
    if(!panel)return;
    if(!employee){
        panel.innerHTML=`<div class="hr-timecard-placeholder"><i class="bi bi-person-lines-fill"></i>Select an employee to view their timecard.</div>`;
        return;
    }
    let isLocked=(weekStatus==='locked'||weekStatus==='approved');
    let initials=((employee.first_name||'')[0]||'')+((employee.last_name||'')[0]||'');

    let present=0,late=0,absent=0,leave=0,restDay=0,holiday=0,overtimeTotal=0,regularHoursTotal=0;
    weekDays.forEach(day=>{
        let d=employee.days[day.date]||{};
        if(d.is_rest_day||d.status==='rest_day') restDay++;
        else if(!d.record_exists) { /* no record */ }
        else if(d.status==='leave_paid'||d.status==='leave_unpaid') leave++;
        else if(d.status==='late') late++;
        else if(d.status==='present'||d.status==='holiday_work') present++;
        else if(d.status==='holiday_no_work') holiday++;
        else if(d.status==='absent') absent++;
        let ot=Number(d.overtime_hours)||0;
        overtimeTotal+=ot;
        if(d.time_in && d.time_out){
            regularHoursTotal+=Math.max(0, hoursBetween(d.time_in,d.time_out)-ot);
        }
    });
    let totalHoursTracked=regularHoursTotal+overtimeTotal;
    let regularPct=totalHoursTracked>0?(regularHoursTotal/totalHoursTracked*100):0;
    let overtimePct=totalHoursTracked>0?(overtimeTotal/totalHoursTracked*100):0;

    let dtrHtml='';
    if(employee.dtr_image_path){
        dtrHtml=`<button class="btn btn-sm btn-outline-primary dtr-view-btn" data-user-id="${employee.user_id}" data-week-start="${currentWeekStart}" data-image-path="${employee.dtr_image_path}"><i class="bi bi-eye"></i> View DTR</button>`;
    } else if(!isLocked){
        dtrHtml=`
            <input type="file" class="dtr-upload-input" data-user-id="${employee.user_id}" data-week-start="${currentWeekStart}" accept=".jpg,.jpeg,.png,.pdf" style="display:none;">
            <button class="btn btn-sm btn-outline-primary dtr-upload-btn" data-user-id="${employee.user_id}" data-week-start="${currentWeekStart}"><i class="bi bi-upload"></i> Upload DTR</button>
        `;
    } else {
        dtrHtml=`<span class="text-muted small">No DTR</span>`;
    }

    let rowsHtml='';
    weekDays.forEach(day=>{
        let d=employee.days[day.date]||{};
        let recordExists=d.record_exists||false;
        let status=d.status||null;
        let isRestDay=d.is_rest_day||false;
        let cellClass=getStatusClass(status,isRestDay);
        let label=getStatusLabel(status,isRestDay,recordExists);
        let iconClass=getStatusIconClass(status,isRestDay,recordExists);
        let lockIcon=isLocked?'<i class="bi bi-lock-fill me-1"></i>':'';
        let timeRangeHtml=(d.time_in && d.time_out)
            ? `<span class="hr-timecard-time-range"><span>${formatTime(d.time_in)}</span><span class="hr-timecard-time-line"></span><span>${formatTime(d.time_out)}</span></span>`
            : `<span class="hr-timecard-time-range is-empty">-</span>`;
        let hasNote=!!(d.notes && d.notes.trim());
        let noteBtn=`<button class="hr-timecard-note-btn ${hasNote?'has-note':''}" ${hasNote?'':'disabled'} data-note="${escapeHtml(d.notes||'')}" data-date="${day.date}" title="${hasNote?'View note':'No note'}"><i class="bi bi-clipboard${hasNote?'-fill':''}"></i></button>`;
        let workHours=(d.time_in && d.time_out) ? Math.max(0, hoursBetween(d.time_in,d.time_out)-(Number(d.overtime_hours)||0)) : 0;
        rowsHtml+=`
            <tr>
                <td class="hr-timecard-date-cell"><span class="hr-timecard-date-pill"><span class="hr-timecard-day-abbr">${getDayAbbr(day.date)}</span>${formatOrdinalDate(day.date)}</span></td>
                <td>${timeRangeHtml}</td>
                <td>${workHours.toFixed(2)}</td>
                <td>${d.overtime_hours||0}</td>
                <td><span class="hr-timecard-status-pill ${cellClass}">${lockIcon}<i class="bi ${iconClass} me-1"></i>${escapeHtml(label)}</span></td>
                <td>${noteBtn}</td>
                <td><button class="hr-timecard-edit-btn" data-user-id="${employee.user_id}" data-date="${day.date}" data-name="${escapeHtml(employee.first_name)} ${escapeHtml(employee.last_name)}" title="Edit"><i class="bi bi-pencil"></i></button></td>
            </tr>
        `;
    });

    panel.innerHTML=`
        <div class="hr-timecard-profile">
            <div class="hr-timecard-avatar">${escapeHtml(initials.toUpperCase())}</div>
            <div>
                <div class="hr-timecard-name">${escapeHtml(employee.first_name)} ${escapeHtml(employee.last_name)}</div>
                <div class="hr-timecard-meta">${escapeHtml(employee.employee_number||'')} &middot; ${getRoleDisplayName(employee.role)}</div>
            </div>
            <div class="hr-timecard-profile-actions">${dtrHtml}</div>
        </div>
        <div class="hr-timecard-hours-summary">
            <div class="hr-timecard-hours-header">
                <span class="hr-timecard-hours-label">Hour breakdown</span>
                <span class="hr-timecard-hours-total">${totalHoursTracked.toFixed(2)} hrs</span>
                <span class="hr-timecard-hours-legend">
                    <span><span class="legend-dot regular"></span>Regular: ${regularHoursTotal.toFixed(2)} hrs</span>
                    <span><span class="legend-dot overtime"></span>Overtime: ${overtimeTotal.toFixed(2)} hrs</span>
                </span>
            </div>
            <div class="hr-timecard-hours-bar">
                <div class="hr-timecard-hours-segment regular" style="width:${regularPct}%"></div>
                <div class="hr-timecard-hours-segment overtime" style="width:${overtimePct}%"></div>
            </div>
        </div>
        <div class="hr-timecard-stats">
            <div class="hr-timecard-stat"><div class="hr-timecard-stat-value">${present}</div><div class="hr-timecard-stat-label">Present</div></div>
            <div class="hr-timecard-stat"><div class="hr-timecard-stat-value">${late}</div><div class="hr-timecard-stat-label">Late</div></div>
            <div class="hr-timecard-stat"><div class="hr-timecard-stat-value">${absent}</div><div class="hr-timecard-stat-label">Absent</div></div>
            <div class="hr-timecard-stat"><div class="hr-timecard-stat-value">${leave}</div><div class="hr-timecard-stat-label">Leave</div></div>
            <div class="hr-timecard-stat"><div class="hr-timecard-stat-value">${restDay}</div><div class="hr-timecard-stat-label">Rest</div></div>
            <div class="hr-timecard-stat"><div class="hr-timecard-stat-value">${holiday}</div><div class="hr-timecard-stat-label">Holiday</div></div>
            <div class="hr-timecard-stat"><div class="hr-timecard-stat-value">${overtimeTotal}</div><div class="hr-timecard-stat-label">OT hrs</div></div>
        </div>
        <div class="table-scroll-wrapper">
            <table class="hr-timecard-table">
                <thead><tr><th>Date</th><th>Time</th><th>Work Hours</th><th>OT (hrs)</th><th>Status</th><th>Note</th><th></th></tr></thead>
                <tbody>${rowsHtml}</tbody>
            </table>
        </div>
    `;

    panel.querySelectorAll('.hr-timecard-edit-btn').forEach(btn=>{
        btn.addEventListener('click', function(){ openEditModal(this.dataset.userId, this.dataset.date, this.dataset.name); });
    });
    panel.querySelectorAll('.hr-timecard-note-btn.has-note').forEach(btn=>{
        btn.addEventListener('click', function(){ openNoteModal(this.dataset.date, this.dataset.note); });
    });
    let dtrViewBtn=panel.querySelector('.dtr-view-btn');
    if(dtrViewBtn){
        dtrViewBtn.addEventListener('click', function(){
            openDtrModal(this.dataset.userId, this.dataset.weekStart, this.dataset.imagePath);
        });
    }
    let dtrUploadBtn=panel.querySelector('.dtr-upload-btn');
    let dtrUploadInput=panel.querySelector('.dtr-upload-input');
    if(dtrUploadBtn && dtrUploadInput){
        dtrUploadBtn.addEventListener('click', function(){ dtrUploadInput.click(); });
        dtrUploadInput.addEventListener('change', function(){
            const file=this.files[0];
            if(!file)return;
            const userId=this.dataset.userId, weekStart=this.dataset.weekStart;
            const formData=new FormData();
            formData.append('dtr_image', file);
            formData.append('user_id', userId);
            formData.append('week_start', weekStart);
            dtrUploadBtn.disabled=true;
            dtrUploadBtn.innerHTML='<span class="spinner-border spinner-border-sm"></span>';
            fetch('?page=api_upload_dtr_image', { method:'POST', body:formData })
            .then(response=>{
                if(!response.ok) return response.text().then(text=>{ throw new Error(text) });
                return response.json();
            })
            .then(data=>{
                if(data.success){ loadAttendance(); }
                else {
                    dtrUploadBtn.disabled=false; dtrUploadBtn.innerHTML='<i class="bi bi-upload"></i> Upload DTR';
                    Swal.fire({ icon:'error', title:'Upload Failed', text:data.message });
                }
            })
            .catch(error=>{
                dtrUploadBtn.disabled=false; dtrUploadBtn.innerHTML='<i class="bi bi-upload"></i> Upload DTR';
                console.error('Upload error:', error);
                Swal.fire({ icon:'error', title:'Upload Error', text:error.message||'Something went wrong.' });
            });
            this.value='';
        });
    }
}

// ===== NOTE MODAL =====
function openNoteModal(date, note) {
    document.getElementById('hrTimecardNoteMeta').textContent = formatDate(date);
    document.getElementById('hrTimecardNoteText').textContent = note || '-';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('hrTimecardNoteModal')).show();
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

// ===== SEND BUTTON VISIBILITY =====
// No banner is rendered here -- completeness/DTR gaps are visible per
// employee in the left-panel badges and each employee's own timecard, so
// the Send button simply doesn't appear until every employee is ready.
function updateSendStatusMessage(employees) {
    const container = document.getElementById('sendStatusMessage');
    if (container) container.style.display = 'none';

    if (weekStatus !== 'draft') {
        document.getElementById('sendToHeadHrBtn').style.display = 'none';
        return;
    }

    let allComplete = employees.every(emp => isComplete(emp.days));
    let allDtr = employees.every(emp => !!emp.dtr_image_path);

    document.getElementById('sendToHeadHrBtn').style.display =
        (allComplete && allDtr && employees.length > 0) ? 'inline-block' : 'none';
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
            if(day.is_rest_day||day.status==='rest_day') restDay++;
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
                    document.getElementById('weekStatusBadge').innerHTML='<i class="bi bi-send-fill"></i> Sent';
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
            updateWeekNavLabel();
            loadAttendance();
        }
    });

    // Compact week navigator (replaces the inline Month/Year/Week row) --
    // prev/next step through the week <select>'s own options, crossing a
    // month boundary by reloading the adjacent month and landing on its
    // first/last week; the label button opens the Week Picker modal that
    // still holds the real Month/Year/Week selects.
    document.getElementById('weekNavPrev')?.addEventListener('click', function(){ navigateWeek(-1); });
    document.getElementById('weekNavNext')?.addEventListener('click', function(){ navigateWeek(1); });

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

    // Department chip row -- drives the same hidden <select> so
    // loadAttendance() and the active-filter-chips widget (which expects a
    // real <select>) keep working unchanged; a 'change' listener on the
    // select syncs the chips back if something else resets its value
    // (e.g. the "Clear all filters" chip).
    const deptSelect = document.getElementById('filterDepartment');
    const deptChipRow = document.getElementById('filterDepartmentChips');
    deptChipRow?.querySelectorAll('.atm-chip').forEach(chip => {
        chip.addEventListener('click', function(){
            deptSelect.value = this.dataset.value;
            deptSelect.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });
    deptSelect.addEventListener('change', function(){
        deptChipRow?.querySelectorAll('.atm-chip').forEach(chip => {
            chip.classList.toggle('active', chip.dataset.value === deptSelect.value);
        });
    });

    let month=document.getElementById('monthSelect').value, year=document.getElementById('yearSelect').value;
    loadWeeksForMonth(year, month, 'today');

    updateAtmPageSubtitle();
    setInterval(updateAtmPageSubtitle, 60000);
});

function updateAtmPageSubtitle(){
    let el=document.getElementById('atmPageSubtitle');
    if(!el) return;
    let now=new Date();
    let weekday=now.toLocaleDateString('en-US',{weekday:'long'});
    let month=now.toLocaleDateString('en-US',{month:'long'});
    let hh=String(now.getHours()).padStart(2,'0'), mm=String(now.getMinutes()).padStart(2,'0');
    el.textContent=`${weekday}, ${now.getDate()} ${month} · ${hh}:${mm}`;
}

function getEndOfWeek(start){ let d=new Date(start); d.setDate(d.getDate()+6); return d.toISOString().split('T')[0]; }

function navigateWeek(delta){
    let weekSelect=document.getElementById('weekSelect');
    if(!weekSelect) return;
    let newIndex=weekSelect.selectedIndex+delta;
    if(newIndex>=0 && newIndex<weekSelect.options.length){
        weekSelect.selectedIndex=newIndex;
        weekSelect.dispatchEvent(new Event('change',{bubbles:true}));
        return;
    }
    let month=parseInt(document.getElementById('monthSelect').value);
    let year=parseInt(document.getElementById('yearSelect').value);
    if(delta<0){ month-=1; if(month<1){ month=12; year-=1; } }
    else { month+=1; if(month>12){ month=1; year+=1; } }
    let yearSelect=document.getElementById('yearSelect');
    let yearStr=String(year);
    if(!Array.from(yearSelect.options).some(o=>o.value===yearStr)){
        Swal.fire({ icon:'info', title:'Out of Range', text:'That year is outside the selectable range.' });
        return;
    }
    yearSelect.value=yearStr;
    let monthStr=String(month).padStart(2,'0');
    document.getElementById('monthSelect').value=monthStr;
    loadWeeksForMonth(yearStr, monthStr, delta<0 ? 'last' : 'first');
}