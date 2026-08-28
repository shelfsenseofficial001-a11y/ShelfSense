// ============================================
// SEARCHABLE SELECT DROPDOWN (Fully Fixed)
// ============================================

class SearchableSelect {
    constructor(selectElement) {
        this.select = selectElement;
        this.wrapper = null;
        this.input = null;
        this.optionsContainer = null;
        this.clearBtn = null;
        this.originalOptions = [];
        this.currentValue = '';
        this.isOpen = false;
        this.selectedIndex = -1;
        this.ignoreBlur = false;
        
        this.init();
    }

    init() {
        // Store original options
        this.originalOptions = Array.from(this.select.options).map(opt => ({
            value: opt.value,
            label: opt.textContent.trim()
        }));

        // Create wrapper
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'searchable-select-wrapper';
        this.select.parentNode.insertBefore(this.wrapper, this.select);
        this.wrapper.appendChild(this.select);

        // Hide the original select
        this.select.style.display = 'none';

        // Create input
        this.input = document.createElement('input');
        this.input.type = 'text';
        this.input.className = 'form-control';
        this.input.placeholder = this.select.getAttribute('data-placeholder') || 'Search...';
        this.input.autocomplete = 'off';
        this.wrapper.appendChild(this.input);

        // Create arrow icon
        const arrow = document.createElement('span');
        arrow.className = 'dropdown-arrow';
        arrow.innerHTML = '<i class="bi bi-chevron-down"></i>';
        this.wrapper.appendChild(arrow);

        // Create clear button (store as property)
        this.clearBtn = document.createElement('button');
        this.clearBtn.type = 'button';
        this.clearBtn.className = 'clear-btn';
        this.clearBtn.innerHTML = '✕';
        this.clearBtn.title = 'Clear selection';
        this.wrapper.appendChild(this.clearBtn);

        // Create options container
        this.optionsContainer = document.createElement('div');
        this.optionsContainer.className = 'dropdown-options';
        this.wrapper.appendChild(this.optionsContainer);

        // Set initial state
        const selectedOption = this.select.options[this.select.selectedIndex];
        if (selectedOption && selectedOption.value !== '' && selectedOption.value !== 'all') {
            this.input.value = selectedOption.textContent.trim();
            this.currentValue = selectedOption.value;
            this.clearBtn.classList.add('show');
        } else {
            this.input.value = '';
            this.currentValue = 'all';
            this.clearBtn.classList.remove('show');
        }

        // ===== EVENT LISTENERS =====
        this.input.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleDropdown();
        });
        
        this.input.addEventListener('input', () => {
            this.onInput();
        });
        
        this.input.addEventListener('keydown', (e) => {
            this.handleKeydown(e);
        });
        
        this.optionsContainer.addEventListener('click', (e) => {
            e.stopPropagation();
            this.ignoreBlur = true;
        });

        this.clearBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.clearSelection();
        });

        document.addEventListener('click', (e) => {
            if (!this.wrapper.contains(e.target)) {
                this.closeDropdown();
            }
        });

        this.select.addEventListener('change', () => {
            this.updateFromSelect();
        });

        this.renderOptions(this.originalOptions);

        // Let callers find this instance again later (see refresh() below).
        this.select.searchableSelectInstance = this;

        console.log('✅ SearchableSelect initialized:', this.select.id);
    }

    // ============================================
    // REFRESH (for options populated asynchronously,
    // e.g. after a fetch() fills in <option> elements
    // that didn't exist yet when this was constructed)
    // ============================================

    refresh() {
        this.originalOptions = Array.from(this.select.options).map(opt => ({
            value: opt.value,
            label: opt.textContent.trim()
        }));
        this.updateFromSelect();
        if (this.isOpen) {
            this.renderOptions(this.originalOptions);
        }
    }

    // ============================================
    // DROPDOWN TOGGLE
    // ============================================

    toggleDropdown() {
        if (this.isOpen) {
            this.closeDropdown();
        } else {
            this.openDropdown();
        }
    }

    openDropdown() {
        this.isOpen = true;
        this.optionsContainer.classList.add('show');
        this.renderOptions(this.originalOptions);
        this.highlightSelectedOption();
        this.selectedIndex = -1;
    }

    closeDropdown() {
        this.isOpen = false;
        this.optionsContainer.classList.remove('show');
        this.selectedIndex = -1;
    }

    // ============================================
    // INPUT FILTERING
    // ============================================

    onInput() {
        const query = this.input.value.trim();
        let filtered;
        if (query === '') {
            filtered = this.originalOptions;
        } else {
            filtered = this.originalOptions.filter(opt => 
                opt.label.toLowerCase().includes(query.toLowerCase())
            );
        }
        this.renderOptions(filtered, query);
        if (!this.isOpen) {
            this.openDropdown();
        }
    }

    // ============================================
    // RENDER OPTIONS
    // ============================================

    renderOptions(options, query = '') {
        const container = this.optionsContainer;
        container.innerHTML = '';

        if (options.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'no-results';
            empty.textContent = 'No options found';
            container.appendChild(empty);
            return;
        }

        options.forEach((opt, index) => {
            const item = document.createElement('div');
            item.className = 'option-item';
            if (this.currentValue === opt.value) {
                item.classList.add('selected');
            }
            
            if (query) {
                const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                item.innerHTML = opt.label.replace(regex, '<strong>$1</strong>');
            } else {
                item.textContent = opt.label;
            }
            
            item.dataset.value = opt.value;
            item.dataset.index = index;
            
            item.addEventListener('click', (e) => {
                e.stopPropagation();
                this.selectOption(opt.value, opt.label);
            });
            
            item.addEventListener('mouseenter', () => {
                container.querySelectorAll('.option-item').forEach((el, i) => {
                    el.classList.toggle('selected', i === index);
                });
                this.selectedIndex = index;
            });
            
            container.appendChild(item);
        });
    }

    highlightSelectedOption() {
        const container = this.optionsContainer;
        container.querySelectorAll('.option-item').forEach((item) => {
            if (item.dataset.value === this.currentValue) {
                item.classList.add('selected');
            } else {
                item.classList.remove('selected');
            }
        });
    }

    // ============================================
    // SELECT OPTION
    // ============================================

    selectOption(value, label) {
        // Set the hidden select value
        this.select.value = value;
        
        // Update input display
        if (value === 'all' || value === '') {
            this.input.value = '';
            this.currentValue = 'all';
            if (this.clearBtn) this.clearBtn.classList.remove('show');
        } else {
            this.input.value = label;
            this.currentValue = value;
            if (this.clearBtn) this.clearBtn.classList.add('show');
        }
        
        // Update global filters
        this.updateFilters(value);
        
        this.closeDropdown();
        
        console.log('✅ Selected:', value, label);
        
        // Trigger change events
        this.triggerChange();
    }

    // ============================================
    // UPDATE FILTERS DIRECTLY
    // ============================================

    updateFilters(value) {
        const mapping = {
            'filterStatus': 'status',
            'filterRole': 'role',
            'filterType': 'type',
            'filterDepartment': 'department'
        };
        const filterKey = mapping[this.select.id];
        if (filterKey && typeof window.currentFilters !== 'undefined') {
            window.currentFilters[filterKey] = value;
            console.log('🔄 Updated currentFilters.' + filterKey + ' =', value);
        }
    }

    // ============================================
    // TRIGGER CHANGE EVENT
    // ============================================

    triggerChange() {
        const event = new Event('change', { bubbles: true });
        this.select.dispatchEvent(event);
        
        if (typeof this.select.onchange === 'function') {
            this.select.onchange(event);
        }
        
        setTimeout(() => {
            if (typeof window.loadApplicants === 'function') {
                window.loadApplicants(1);
            }
            if (typeof window.loadInterviews === 'function') {
                window.loadInterviews(1);
            }
            if (typeof window.loadTrainees === 'function') {
                window.loadTrainees(1);
            }
            if (typeof window.loadContracts === 'function') {
                window.loadContracts(1);
            }
            if (typeof window.loadAttendance === 'function') {
                window.loadAttendance();
            }
        }, 50);
    }

    // ============================================
    // CLEAR SELECTION
    // ============================================

    clearSelection() {
        this.select.value = 'all';
        this.input.value = '';
        this.currentValue = 'all';
        if (this.clearBtn) this.clearBtn.classList.remove('show');
        this.closeDropdown();
        this.updateFilters('all');
        this.triggerChange();
    }

    // ============================================
    // UPDATE FROM SELECT
    // ============================================

    updateFromSelect() {
        const selected = this.select.options[this.select.selectedIndex];
        if (selected && selected.value !== '' && selected.value !== 'all') {
            this.input.value = selected.textContent.trim();
            this.currentValue = selected.value;
            if (this.clearBtn) this.clearBtn.classList.add('show');
        } else {
            this.input.value = '';
            this.currentValue = 'all';
            if (this.clearBtn) this.clearBtn.classList.remove('show');
        }
        this.closeDropdown();
    }

    // ============================================
    // KEYBOARD NAVIGATION - UPDATED
    // ============================================

    handleKeydown(e) {
        const items = this.optionsContainer.querySelectorAll('.option-item');
        const count = items.length;
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (count === 0) return;
            this.selectedIndex = (this.selectedIndex + 1) % count;
            this.highlightItem(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (count === 0) return;
            this.selectedIndex = (this.selectedIndex - 1 + count) % count;
            this.highlightItem(items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            
            // ✅ If dropdown is open and there's a highlighted item, select it
            if (this.isOpen && this.selectedIndex >= 0 && this.selectedIndex < count) {
                const item = items[this.selectedIndex];
                const value = item.dataset.value;
                const label = item.textContent.trim();
                this.selectOption(value, label);
                return;
            }
            
            // ✅ If dropdown is open and there are visible items, select the first one
            if (this.isOpen && count > 0) {
                const firstItem = items[0];
                if (firstItem) {
                    const value = firstItem.dataset.value;
                    const label = firstItem.textContent.trim();
                    this.selectOption(value, label);
                    return;
                }
            }
            
            // ✅ If dropdown is closed and there's text in the input, try to match it
            const query = this.input.value.trim();
            if (query && !this.isOpen) {
                const matches = this.originalOptions.filter(opt => 
                    opt.label.toLowerCase() === query.toLowerCase()
                );
                if (matches.length > 0) {
                    this.selectOption(matches[0].value, matches[0].label);
                } else {
                    // Try partial match
                    const partial = this.originalOptions.filter(opt => 
                        opt.label.toLowerCase().includes(query.toLowerCase())
                    );
                    if (partial.length > 0) {
                        this.selectOption(partial[0].value, partial[0].label);
                    }
                }
            }
        } else if (e.key === 'Escape') {
            this.closeDropdown();
        } else if (e.key === 'Tab') {
            this.closeDropdown();
        }
    }

    highlightItem(items) {
        items.forEach((item, index) => {
            item.classList.toggle('selected', index === this.selectedIndex);
        });
        if (this.selectedIndex >= 0 && this.selectedIndex < items.length) {
            items[this.selectedIndex].scrollIntoView({ block: 'nearest' });
        }
    }
}

// ============================================
// INITIALIZE ALL SEARCHABLE SELECTS
// ============================================

function initSearchableSelects(selector = 'select.searchable-select') {
    document.querySelectorAll(selector).forEach(select => {
        if (!select.dataset.searchableInitialized) {
            console.log('🔍 Initializing:', select.id || select.name);
            new SearchableSelect(select);
            select.dataset.searchableInitialized = 'true';
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initSearchableSelects();
});

window.reinitSearchableSelects = function() {
    setTimeout(() => {
        initSearchableSelects();
    }, 100);
};

// Call this after changing a searchable-select's <option> list via JS
// (e.g. after a fetch() populates it) so the widget picks up the new
// options and re-syncs its displayed value. Safe to call even if the
// element hasn't been initialized yet (e.g. called very early).
window.refreshSearchableSelect = function(selectOrId) {
    const select = typeof selectOrId === 'string' ? document.getElementById(selectOrId) : selectOrId;
    if (!select) return;
    if (select.searchableSelectInstance) {
        select.searchableSelectInstance.refresh();
    } else if (select.classList.contains('searchable-select')) {
        new SearchableSelect(select);
        select.dataset.searchableInitialized = 'true';
    }
};