class ReportModule {
    constructor() {
        this.startInput = document.querySelector('[data-report-start]');
        this.endInput = document.querySelector('[data-report-end]');
        this.button = document.querySelector('[data-generate-report]');
        this.tbody = document.querySelector('[data-report-table-body]');
        this.emptyState = document.querySelector('[data-report-empty]');

        this.init();
    }

    init() {
        if (!this.button || !this.startInput || !this.endInput || !this.tbody) {
            return;
        }

        // Значения по умолчанию: последние 7 дней
        const today = new Date();
        const weekAgo = new Date();
        weekAgo.setDate(today.getDate() - 7);

        this.endInput.value = today.toISOString().slice(0, 10);
        this.startInput.value = weekAgo.toISOString().slice(0, 10);

        this.button.addEventListener('click', () => this.generate());
    }

    async generate() {
        const startDate = this.startInput.value;
        const endDate = this.endInput.value;

        if (!startDate || !endDate) {
            alert('Пожалуйста, выберите даты начала и окончания периода.');
            return;
        }

        if (startDate > endDate) {
            alert('Дата начала не может быть позже даты окончания.');
            return;
        }

        this.button.disabled = true;
        this.button.textContent = 'Формируем...';

        try {
            const params = new URLSearchParams({ startDate, endDate }).toString();
            const res = await fetch(`/api?action=task.executorStats&${params}`);

            if (!res.ok) {
                throw new Error(`HTTP ${res.status}`);
            }

            const result = await res.json();
            if (!result.success) {
                throw new Error(result.error || 'Ошибка получения отчёта');
            }

            this.renderTable(result.data || []);
        } catch (error) {
            console.error('Ошибка формирования отчёта:', error);
            alert('Не удалось сформировать отчёт: ' + error.message);
        } finally {
            this.button.disabled = false;
            this.button.textContent = 'Сформировать отчёт';
        }
    }

    renderTable(rows) {
        this.tbody.innerHTML = '';

        if (!rows.length) {
            if (this.emptyState) {
                this.emptyState.classList.remove('hidden');
            }
            return;
        }

        if (this.emptyState) {
            this.emptyState.classList.add('hidden');
        }

        rows.forEach(row => {
            const tr = document.createElement('tr');

            const nameTd = document.createElement('td');
            nameTd.textContent = row.executor_fullname || '—';

            const inProgressTd = document.createElement('td');
            inProgressTd.textContent = row.in_progress_count ?? 0;

            const completedTd = document.createElement('td');
            completedTd.textContent = row.completed_count ?? 0;

            tr.appendChild(nameTd);
            tr.appendChild(inProgressTd);
            tr.appendChild(completedTd);

            this.tbody.appendChild(tr);
        });
    }
}

export default ReportModule;


