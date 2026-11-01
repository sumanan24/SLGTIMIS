        </div>
    </div>
</div>
<script>
document.querySelectorAll('form.lc-filters').forEach(function (form) {
    var dataNode = form.querySelector('.lc-filter-data');
    var data = dataNode ? JSON.parse(dataNode.textContent || '{}') : null;
    var department = form.querySelector('[data-lc="department"]');
    var course = form.querySelector('[data-lc="course"]');
    var nvq = form.querySelector('[data-lc="nvq"]');
    var year = form.querySelector('[data-lc="year"]');
    var batch = form.querySelector('[data-lc="batch"]');
    var trade = form.querySelector('[data-lc="trade"]');

    function refill(select, items, selected) {
        if (!select) {
            return;
        }
        var current = selected === undefined ? select.value : selected;
        var placeholder = select.getAttribute('data-placeholder') || 'All';
        select.innerHTML = '';
        var first = document.createElement('option');
        first.value = '';
        first.textContent = placeholder;
        select.appendChild(first);
        var seen = {};
        items.forEach(function (item) {
            if (!item.value || seen[item.value]) {
                return;
            }
            seen[item.value] = true;
            var option = document.createElement('option');
            option.value = item.value;
            option.textContent = item.label || item.value;
            select.appendChild(option);
        });
        select.value = seen[current] ? current : '';
    }

    function applyCascade() {
        if (!data || !department) {
            return;
        }
        var departmentId = department.value;
        var courseId = course ? course.value : '';
        var nvqId = nvq ? nvq.value : '';
        var yearId = year ? year.value : '';
        var inDepartment = (data.courses || []).filter(function (item) {
            return !departmentId || item.department === departmentId;
        });
        if (courseId && !inDepartment.some(function (item) { return item.value === courseId; })) {
            courseId = '';
        }
        var levels = {};
        inDepartment.forEach(function (item) {
            if (courseId && item.value !== courseId) {
                return;
            }
            if (item.nvq) {
                levels[item.nvq] = { value: item.nvq, label: item.nvq };
            }
        });
        if (nvqId && !levels[nvqId]) {
            nvqId = '';
        }
        var courses = inDepartment.filter(function (item) {
            return !nvqId || item.nvq === nvqId;
        });
        if (courseId && !courses.some(function (item) { return item.value === courseId; })) {
            courseId = '';
        }
        refill(course, courses, courseId);
        refill(nvq, Object.keys(levels).map(function (key) { return levels[key]; }), nvqId);
        var years = [];
        (data.years || []).forEach(function (item) {
            if ((departmentId && item.department !== departmentId) || (courseId && item.course !== courseId) || (nvqId && item.nvq !== nvqId)) {
                return;
            }
            years.push({ value: item.value, label: item.value });
        });
        years.sort(function (a, b) { return a.value < b.value ? 1 : -1; });
        if (yearId && !years.some(function (item) { return item.value === yearId; })) {
            yearId = '';
        }
        refill(year, years, yearId);
        refill(batch, (data.batches || []).filter(function (item) {
            return (!departmentId || item.department === departmentId)
                && (!courseId || item.course === courseId)
                && (!yearId || item.year === yearId)
                && (!nvqId || item.nvq === nvqId);
        }));
        var trades = [];
        (data.trades || []).forEach(function (item) {
            if ((departmentId && item.department !== departmentId) || (courseId && item.course !== courseId) || (nvqId && item.nvq !== nvqId)) {
                return;
            }
            trades.push({ value: item.value, label: item.value });
        });
        trades.sort(function (a, b) { return a.label > b.label ? 1 : -1; });
        refill(trade, trades);
    }

    if (data && department) {
        [department, course, nvq, year].forEach(function (select) {
            if (select) {
                select.addEventListener('change', applyCascade);
            }
        });
    }
    form.addEventListener('submit', function () {
        form.querySelectorAll('input, select').forEach(function (field) {
            if (field.name && field.value === '') {
                field.disabled = true;
            }
        });
    });
});
</script>
