<?php
$title = 'Recruitment Calendar - ShelfSense HR';
$pageTitle = 'Recruitment Calendar';
$activePage = 'recruitment_calendar';

$content = <<<'EOT'
<style>
.rc-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-bottom: 1rem;
}
.rc-nav {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.rc-nav h5 {
    margin: 0;
    min-width: 170px;
    text-align: center;
}
.rc-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    font-size: 0.8rem;
}
.rc-legend span {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    color: var(--text-muted, #6c757d);
}
.rc-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}
.rc-dot-initial { background: #198754; }
.rc-dot-final { background: #d97706; }
.rc-dot-opened { background: #0d6efd; }
.rc-dot-closes { background: #dc3545; }

.rc-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 6px;
}
.rc-weekday {
    text-align: center;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.03em;
    color: var(--text-muted, #6c757d);
    padding-bottom: 4px;
    text-transform: uppercase;
}
.rc-cell {
    min-height: 92px;
    border-radius: 8px;
    padding: 6px 8px;
    background: var(--card-bg, #fff);
    border: 1px solid var(--border-color, #e9ecef);
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.rc-cell.rc-empty {
    background: transparent;
    border: none;
}
.rc-cell.rc-today {
    border: 2px solid #198754;
}
.rc-daynum {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--text-muted, #6c757d);
}
.rc-cell.rc-today .rc-daynum {
    color: #198754;
}
.rc-event {
    font-size: 0.7rem;
    line-height: 1.2;
    border-radius: 4px;
    padding: 2px 5px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.rc-event-initial { background: rgba(25,135,84,0.12); color: #146c43; }
.rc-event-final { background: rgba(217,119,6,0.14); color: #b45309; }
.rc-event-opened { background: rgba(13,110,253,0.12); color: #0a58ca; }
.rc-event-closes { background: rgba(220,53,69,0.12); color: #b02a37; }
.rc-event small {
    display: block;
    opacity: 0.75;
    font-size: 0.62rem;
}
</style>

<div class="modern-card p-3">
    <div class="rc-toolbar">
        <div class="rc-nav">
            <button class="btn btn-sm btn-outline-secondary" id="rcPrevMonth"><i class="bi bi-chevron-left"></i></button>
            <h5 id="rcMonthLabel">-</h5>
            <button class="btn btn-sm btn-outline-secondary" id="rcNextMonth"><i class="bi bi-chevron-right"></i></button>
            <button class="btn btn-sm btn-yellow-outline ms-2" id="rcTodayBtn">This month</button>
        </div>
        <div class="rc-legend">
            <span><span class="rc-dot rc-dot-initial"></span> Initial interview</span>
            <span><span class="rc-dot rc-dot-final"></span> Final interview</span>
            <span><span class="rc-dot rc-dot-opened"></span> Posting opened</span>
            <span><span class="rc-dot rc-dot-closes"></span> Applications close</span>
        </div>
    </div>

    <div class="rc-grid" id="rcWeekdays">
        <div class="rc-weekday">Mon</div>
        <div class="rc-weekday">Tue</div>
        <div class="rc-weekday">Wed</div>
        <div class="rc-weekday">Thu</div>
        <div class="rc-weekday">Fri</div>
        <div class="rc-weekday">Sat</div>
        <div class="rc-weekday">Sun</div>
    </div>
    <div class="rc-grid mt-2" id="rcGrid">
        <div class="text-center text-muted py-5" style="grid-column: 1 / -1;">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
    </div>
</div>

<script src="/ShelfSense/public/assets/js/hr/recruitment_calendar.js?v=20260908180000"></script>
EOT;

require_once __DIR__ . '/../../layouts/hr.php';
