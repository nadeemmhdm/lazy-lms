<div class="page-header">
    <div>
        <h1 class="page-title"><i class="bx bx-calendar"></i> Academic Calendar</h1>
        <p class="page-subtitle">Schedule of live classes, examinations, assessment deadlines, and campus events.</p>
    </div>
    <div style="display: flex; gap: 0.5rem; align-items: center;">
        <div class="btn-group" id="viewToggleGroup">
            <button type="button" class="btn btn-outline btn-sm active" data-view="month"><i class="bx bx-grid-alt"></i> Month</button>
            <button type="button" class="btn btn-outline btn-sm" data-view="agenda"><i class="bx bx-list-ul"></i> Agenda</button>
        </div>
    </div>
</div>

<!-- Month View Container -->
<div id="monthViewCard" class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 id="calendarMonthTitle" style="margin: 0; font-size: 1.35rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="bx bx-calendar-event text-primary"></i> <span id="monthYearLabel"></span>
        </h2>
        <div style="display: flex; gap: 0.5rem;">
            <button type="button" class="btn btn-outline btn-sm" id="prevMonthBtn"><i class="bx bx-chevron-left"></i> Prev</button>
            <button type="button" class="btn btn-outline btn-sm" id="todayMonthBtn">Today</button>
            <button type="button" class="btn btn-outline btn-sm" id="nextMonthBtn">Next <i class="bx bx-chevron-right"></i></button>
        </div>
    </div>

    <!-- Calendar Grid -->
    <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; background: var(--border-color); border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden;" id="calendarGrid">
        <!-- Day Headers -->
        <div style="background: var(--bg-hover, #f8fafc); padding: 0.75rem 0.5rem; text-align: center; font-weight: 700; font-size: 0.85rem;">Mon</div>
        <div style="background: var(--bg-hover, #f8fafc); padding: 0.75rem 0.5rem; text-align: center; font-weight: 700; font-size: 0.85rem;">Tue</div>
        <div style="background: var(--bg-hover, #f8fafc); padding: 0.75rem 0.5rem; text-align: center; font-weight: 700; font-size: 0.85rem;">Wed</div>
        <div style="background: var(--bg-hover, #f8fafc); padding: 0.75rem 0.5rem; text-align: center; font-weight: 700; font-size: 0.85rem;">Thu</div>
        <div style="background: var(--bg-hover, #f8fafc); padding: 0.75rem 0.5rem; text-align: center; font-weight: 700; font-size: 0.85rem;">Fri</div>
        <div style="background: var(--bg-hover, #f8fafc); padding: 0.75rem 0.5rem; text-align: center; font-weight: 700; font-size: 0.85rem;">Sat</div>
        <div style="background: var(--bg-hover, #f8fafc); padding: 0.75rem 0.5rem; text-align: center; font-weight: 700; font-size: 0.85rem;">Sun</div>
        <!-- Days cells injected via JS -->
    </div>
</div>

<!-- Agenda View Container -->
<div id="agendaViewCard" class="card" style="display: none;">
    <h3 style="margin-top: 0; margin-bottom: 1.25rem; font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem;">
        <i class="bx bx-list-check text-primary"></i> Scheduled Timeline & Deadlines (<?= count($events) ?>)
    </h3>

    <?php if (empty($events)): ?>
        <div style="text-align: center; padding: 2.5rem; color: var(--text-muted);">No upcoming calendar events found.</div>
    <?php else: ?>
        <div style="display: grid; gap: 1rem;">
            <?php foreach ($events as $ev): ?>
                <div style="padding: 1rem; border: 1px solid var(--border-color); border-radius: 8px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; background: var(--bg-card);">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="min-width: 55px; text-align: center; padding: 0.5rem; background: var(--bg-hover, #f8fafc); border-radius: 6px; border: 1px solid var(--border-color);">
                            <span style="display: block; font-size: 0.7rem; text-transform: uppercase; color: var(--text-muted);"><?= date('M', strtotime($ev['date'])) ?></span>
                            <span style="display: block; font-size: 1.25rem; font-weight: 700; line-height: 1;"><?= date('d', strtotime($ev['date'])) ?></span>
                        </div>
                        <div>
                            <div style="font-weight: 600; font-size: 1rem;">
                                <a href="<?= url($ev['url']) ?>" style="text-decoration: none; color: inherit;"><?= e($ev['title']) ?></a>
                            </div>
                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.2rem;">
                                <?= $ev['time'] ?> &bull; <?= e($ev['course']) ?> (<?= e($ev['batch']) ?>)
                                <?= !empty($ev['teacher']) ? ' &bull; ' . e($ev['teacher']) : '' ?>
                            </div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <span class="badge <?= $ev['badge'] ?>"><?= $ev['type_label'] ?></span>
                        <a href="<?= url($ev['url']) ?>" class="btn btn-outline btn-sm">View &rarr;</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Day Click Modal (Section 27) -->
<div id="dayModal" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="width: 100%; max-width: 550px; margin: 1rem; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
            <h3 id="dayModalDateTitle" style="margin: 0; font-size: 1.2rem;"></h3>
            <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('dayModal').style.display='none'"><i class="bx bx-x"></i></button>
        </div>
        <div id="dayModalEventsList" style="display: grid; gap: 0.75rem;"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const events = <?= json_encode($events) ?>;
    let currentDate = new Date();

    const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    const grid = document.getElementById('calendarGrid');
    const label = document.getElementById('monthYearLabel');
    const dayModal = document.getElementById('dayModal');
    const modalTitle = document.getElementById('dayModalDateTitle');
    const modalList = document.getElementById('dayModalEventsList');

    function renderMonthCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        label.textContent = `${monthNames[month]} ${year}`;

        // Remove old day cells (keep first 7 headers)
        while (grid.children.length > 7) {
            grid.removeChild(grid.lastChild);
        }

        const firstDay = new Date(year, month, 1);
        let startingDay = firstDay.getDay() - 1; // Mon = 0, Sun = 6
        if (startingDay < 0) startingDay = 6;

        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const prevMonthDays = new Date(year, month, 0).getDate();

        // Previous month padding
        for (let i = startingDay - 1; i >= 0; i--) {
            const cell = document.createElement('div');
            cell.style.cssText = 'background: var(--card-bg, #fff); opacity: 0.4; min-height: 100px; padding: 0.5rem; font-size: 0.85rem;';
            cell.textContent = prevMonthDays - i;
            grid.appendChild(cell);
        }

        const todayStr = new Date().toISOString().slice(0, 10);

        // Days of current month
        for (let day = 1; day <= daysInMonth; day++) {
            const dayPadded = String(day).padStart(2, '0');
            const monthPadded = String(month + 1).padStart(2, '0');
            const dateStr = `${year}-${monthPadded}-${dayPadded}`;

            const dayEvents = events.filter(e => e.date === dateStr);
            const isToday = (dateStr === todayStr);

            const cell = document.createElement('div');
            cell.style.cssText = `background: var(--card-bg, #fff); min-height: 100px; padding: 0.5rem; font-size: 0.85rem; cursor: pointer; transition: background 0.15s ease; ${isToday ? 'border: 2px solid var(--primary);' : ''}`;
            cell.className = 'calendar-day-cell';

            let cellHtml = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                    <span style="font-weight: ${isToday ? '700' : '600'}; ${isToday ? 'color: var(--primary);' : ''}">${day}</span>
                    ${dayEvents.length > 0 ? `<span style="font-size: 0.75rem; background: var(--primary); color: #fff; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">${dayEvents.length}</span>` : ''}
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
            `;

            dayEvents.slice(0, 2).forEach(ev => {
                cellHtml += `
                    <div style="padding: 0.2rem 0.35rem; border-radius: 4px; font-size: 0.75rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; background: var(--bg-hover, #f1f5f9); border-left: 3px solid var(--primary);" title="${ev.title}">
                        ${ev.time ? ev.time + ' ' : ''}${ev.title}
                    </div>
                `;
            });

            if (dayEvents.length > 2) {
                cellHtml += `<div style="font-size: 0.7rem; color: var(--text-muted);">+${dayEvents.length - 2} more</div>`;
            }

            cellHtml += '</div>';
            cell.innerHTML = cellHtml;

            // Day click opens detailed schedule modal
            cell.addEventListener('click', () => {
                openDayModal(dateStr, day, monthNames[month], year, dayEvents);
            });

            grid.appendChild(cell);
        }
    }

    function openDayModal(dateStr, day, monthName, year, dayEvents) {
        modalTitle.textContent = `${monthName} ${day}, ${year}`;
        if (dayEvents.length === 0) {
            modalList.innerHTML = '<p style="color: var(--text-muted); text-align: center; margin: 1.5rem 0;">No events scheduled for this date.</p>';
        } else {
            let html = '';
            dayEvents.forEach(ev => {
                html += `
                    <div style="padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-card);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                            <span class="badge ${ev.badge}">${ev.type_label}</span>
                            <span style="font-size: 0.85rem; font-weight: 700;">${ev.time || 'All Day'}</span>
                        </div>
                        <h4 style="margin: 0.35rem 0 0.2rem 0; font-size: 1rem;">${ev.title}</h4>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">
                            <strong>Course:</strong> ${ev.course} &bull; <strong>Batch:</strong> ${ev.batch}
                            ${ev.teacher ? `&bull; <strong>Teacher:</strong> ${ev.teacher}` : ''}
                        </div>
                        ${ev.description ? `<p style="margin: 0.5rem 0 0 0; font-size: 0.85rem; color: var(--text-main);">${ev.description}</p>` : ''}
                        <div style="margin-top: 0.5rem; text-align: right;">
                            <a href="${ev.url}" class="btn btn-primary btn-sm">Open Link &rarr;</a>
                        </div>
                    </div>
                `;
            });
            modalList.innerHTML = html;
        }
        dayModal.style.display = 'flex';
    }

    document.getElementById('prevMonthBtn').addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderMonthCalendar();
    });

    document.getElementById('nextMonthBtn').addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderMonthCalendar();
    });

    document.getElementById('todayMonthBtn').addEventListener('click', () => {
        currentDate = new Date();
        renderMonthCalendar();
    });

    // View toggles: Month vs Agenda
    const viewBtns = document.querySelectorAll('#viewToggleGroup button');
    const monthCard = document.getElementById('monthViewCard');
    const agendaCard = document.getElementById('agendaViewCard');

    viewBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            viewBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const view = btn.getAttribute('data-view');
            if (view === 'agenda') {
                monthCard.style.display = 'none';
                agendaCard.style.display = 'block';
            } else {
                monthCard.style.display = 'block';
                agendaCard.style.display = 'none';
            }
        });
    });

    renderMonthCalendar();
});
</script>
