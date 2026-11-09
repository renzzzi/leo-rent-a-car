/**
 * Leo Rent-A-Car – Bookings Operations View Script
 * Handles interactive modal dialogues, live capacity limits, date calculations,
 * quick status transitions, and personalized Customer & Vehicle pickers.
 */

let currentPickerTarget = 'create'; // 'create' or 'edit'
let currentVehicleTypeFilter = 'All';

document.addEventListener('DOMContentLoaded', function () {
    // Escape key listener to close active modals
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });

    // Close modal when clicking on backdrop
    const modalBackdrops = document.querySelectorAll('.modal-backdrop-custom');
    modalBackdrops.forEach(modal => {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeAllModals();
            }
        });
    });

    // Pre-set default dates for creation if fields exist and are empty
    initDefaultBookingDates();
});

function closeAllModals() {
    const modals = [
        'modal-create-booking',
        'modal-quick-customer',
        'modal-view-booking',
        'modal-edit-booking',
        'modal-delete-booking',
        'modal-status-change',
        'modal-customer-picker',
        'modal-vehicle-picker'
    ];
    modals.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.add('hidden');
    });
}

function initDefaultBookingDates() {
    const startInput = document.getElementById('create_start_date');
    const endInput   = document.getElementById('create_end_date');

    if (startInput && !startInput.value) {
        const now = new Date();
        now.setMinutes(0);
        now.setSeconds(0);
        now.setHours(now.getHours() + 1);

        const endDate = new Date(now);
        endDate.setDate(endDate.getDate() + 2);
        endDate.setHours(18, 0, 0, 0);

        startInput.value = formatDateTimeLocal(now);
        endInput.value   = formatDateTimeLocal(endDate);

        computeDurationHint('create_start_date', 'create_end_date', 'create_duration_hint');
    }
}

function formatDateTimeLocal(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

// =========================================================================
// 1. Create Booking Modal
// =========================================================================
function openCreateBookingModal() {
    const modal = document.getElementById('modal-create-booking');
    if (modal) {
        modal.classList.remove('hidden');
        initDefaultBookingDates();
        // Default initial status radio to Confirmed if none selected
        selectStatusRadio('create', 'Confirmed');
    }
}

function closeCreateBookingModal() {
    const modal = document.getElementById('modal-create-booking');
    if (modal) modal.classList.add('hidden');
}

// =========================================================================
// 2. Quick Customer Modal
// =========================================================================
function openQuickCustomerModal() {
    const modal = document.getElementById('modal-quick-customer');
    if (modal) modal.classList.remove('hidden');
}

function closeQuickCustomerModal() {
    const modal = document.getElementById('modal-quick-customer');
    if (modal) modal.classList.add('hidden');
}

// =========================================================================
// 3. View Booking Details Modal
// =========================================================================
function viewBookingDetails(b) {
    if (!b) return;

    document.getElementById('view-ref').textContent = b.booking_reference || '-';
    document.getElementById('view-customer').textContent = b.customer_name || '-';
    document.getElementById('view-customer-phone').textContent = b.customer_phone ? ('Phone: ' + b.customer_phone) : '';
    document.getElementById('view-customer-email').textContent = b.customer_email || 'No email registered';

    document.getElementById('view-vehicle').textContent = b.vehicle_name || '-';
    document.getElementById('view-vehicle-plate').textContent = 'Plate: ' + (b.vehicle_plate || '-');
    document.getElementById('view-vehicle-meta').textContent = (b.vehicle_type || '') + ' • Capacity: ' + (b.vehicle_seating_capacity || '-') + ' pax';

    document.getElementById('view-destination').textContent = b.destination || '-';
    document.getElementById('view-start').textContent = formatPrettyDate(b.start_date);
    document.getElementById('view-end').textContent = formatPrettyDate(b.end_date);
    document.getElementById('view-duration').textContent = b.duration_text || '-';
    document.getElementById('view-passengers').textContent = (b.number_of_passengers || 1) + ' Passengers';
    document.getElementById('view-driver').textContent = b.driver_name || 'Self-Drive';
    document.getElementById('view-amount').textContent = b.formatted_amount || '₱ 0.00';
    document.getElementById('view-creator').textContent = b.created_by_username || 'Staff';
    document.getElementById('view-created-at').textContent = 'Logged: ' + formatPrettyDate(b.created_at);

    // Render Status Badge
    const badgeContainer = document.getElementById('view-status-badge');
    if (badgeContainer && b.status_badge) {
        badgeContainer.innerHTML = `
            <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold border rounded-[3px] ${b.status_badge.bg} ${b.status_badge.text} ${b.status_badge.border}">
                <span class="w-2 h-2 rounded-full ${b.status_badge.dot}"></span>
                <span>${b.booking_status}</span>
            </span>
        `;
    }

    const modal = document.getElementById('modal-view-booking');
    if (modal) modal.classList.remove('hidden');
}

function closeViewBookingModal() {
    const modal = document.getElementById('modal-view-booking');
    if (modal) modal.classList.add('hidden');
}

// =========================================================================
// 4. Edit Booking Modal
// =========================================================================
function openEditBookingModal(b) {
    if (!b) return;

    document.getElementById('edit_booking_id').value = b.booking_id;
    document.getElementById('edit-ref-display').textContent = '(' + b.booking_reference + ')';

    // Populate Personalized Customer
    const initials = (b.customer_name || 'CL')
        .split(' ')
        .map(w => w[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();

    applyCustomerSelection('edit', {
        id: b.customer_id,
        name: b.customer_name,
        phone: b.customer_phone || '',
        email: b.customer_email || '',
        vip: !!b.customer_is_vip,
        initials: initials
    });

    // Populate Personalized Vehicle
    applyVehicleSelection('edit', {
        id: b.vehicle_id,
        name: b.vehicle_name,
        plate: b.vehicle_plate || '',
        type: b.vehicle_type || 'Car',
        capacity: b.vehicle_seating_capacity || 4,
        status: 'Assigned'
    });

    setSelectValue('edit_driver_id', b.driver_id || '');

    document.getElementById('edit_destination').value = b.destination || '';
    document.getElementById('edit_passengers').value  = b.number_of_passengers || 1;

    if (b.start_date) {
        document.getElementById('edit_start_date').value = b.start_date.replace(' ', 'T').substring(0, 16);
    }
    if (b.end_date) {
        document.getElementById('edit_end_date').value = b.end_date.replace(' ', 'T').substring(0, 16);
    }

    document.getElementById('edit_amount').value = b.booking_amount || 0;

    // Select Status Radio
    selectStatusRadio('edit', b.booking_status || 'Pending');

    const modal = document.getElementById('modal-edit-booking');
    if (modal) modal.classList.remove('hidden');
}

function closeEditBookingModal() {
    const modal = document.getElementById('modal-edit-booking');
    if (modal) modal.classList.add('hidden');
}

function setSelectValue(selectId, val) {
    const el = document.getElementById(selectId);
    if (el) {
        el.value = val;
    }
}

// =========================================================================
// 5. Delete Booking Modal
// =========================================================================
function openDeleteBookingModal(bookingId, reference) {
    document.getElementById('delete_booking_id').value = bookingId;
    document.getElementById('delete-ref-display').textContent = reference;

    const modal = document.getElementById('modal-delete-booking');
    if (modal) modal.classList.remove('hidden');
}

function closeDeleteBookingModal() {
    const modal = document.getElementById('modal-delete-booking');
    if (modal) modal.classList.add('hidden');
}

// =========================================================================
// 6. Quick Status Changer Modal
// =========================================================================
function promptStatusChange(bookingId, currentStatus, reference) {
    document.getElementById('status_booking_id').value = bookingId;
    document.getElementById('status_ref_display').textContent = reference;
    setSelectValue('status_select_value', currentStatus);

    const modal = document.getElementById('modal-status-change');
    if (modal) modal.classList.remove('hidden');
}

function closeStatusModal() {
    const modal = document.getElementById('modal-status-change');
    if (modal) modal.classList.add('hidden');
}

// =========================================================================
// 7. Personalized Customer Picker Operations
// =========================================================================
function openCustomerPickerModal(targetMode) {
    currentPickerTarget = targetMode || 'create';

    const searchInput = document.getElementById('customer_picker_search');
    if (searchInput) searchInput.value = '';

    filterCustomerPicker();

    const modal = document.getElementById('modal-customer-picker');
    if (modal) {
        modal.classList.remove('hidden');
        if (searchInput) setTimeout(() => searchInput.focus(), 50);
    }
}

function closeCustomerPickerModal() {
    const modal = document.getElementById('modal-customer-picker');
    if (modal) modal.classList.add('hidden');
}

function filterCustomerPicker() {
    const input = document.getElementById('customer_picker_search');
    const query = input ? input.value.trim().toLowerCase() : '';
    const items = document.querySelectorAll('.customer-card-item');
    const noResults = document.getElementById('customer_picker_no_results');
    const countEl = document.getElementById('customer_picker_count');

    let visibleCount = 0;

    items.forEach(item => {
        const searchText = (item.getAttribute('data-search') || '').toLowerCase();
        const matches = !query || searchText.includes(query);

        if (matches) {
            item.classList.remove('hidden');
            visibleCount++;
        } else {
            item.classList.add('hidden');
        }
    });

    if (noResults) {
        if (visibleCount === 0) {
            noResults.classList.remove('hidden');
        } else {
            noResults.classList.add('hidden');
        }
    }

    if (countEl) {
        countEl.textContent = `${visibleCount} customer${visibleCount === 1 ? '' : 's'}`;
    }
}

function selectCustomerFromCard(cardEl) {
    if (!cardEl) return;

    const data = {
        id: cardEl.getAttribute('data-id'),
        name: cardEl.getAttribute('data-name'),
        phone: cardEl.getAttribute('data-phone'),
        email: cardEl.getAttribute('data-email'),
        vip: cardEl.getAttribute('data-vip') === '1',
        initials: cardEl.getAttribute('data-initials') || '--'
    };

    applyCustomerSelection(currentPickerTarget, data);
    closeCustomerPickerModal();
}

function applyCustomerSelection(mode, cust) {
    const idInput = document.getElementById(`${mode}_customer_id`);
    if (idInput) idInput.value = cust.id;

    const avatarEl = document.getElementById(`${mode}_cust_avatar`);
    if (avatarEl) avatarEl.textContent = cust.initials || '--';

    const nameEl = document.getElementById(`${mode}_cust_name`);
    if (nameEl) nameEl.textContent = cust.name;

    const phoneEl = document.getElementById(`${mode}_cust_phone`);
    if (phoneEl) phoneEl.textContent = cust.phone || 'No phone';

    const emailEl = document.getElementById(`${mode}_cust_email`);
    if (emailEl) emailEl.textContent = cust.email || 'No email';

    const vipBadge = document.getElementById(`${mode}_cust_vip_badge`);
    if (vipBadge) {
        if (cust.vip) {
            vipBadge.innerHTML = '<span class="px-1.5 py-0.2 text-[9px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-200 rounded-[2px]">VIP</span>';
        } else {
            vipBadge.innerHTML = '';
        }
    }

    const emptyState = document.getElementById(`${mode}_customer_empty_state`);
    const cardState  = document.getElementById(`${mode}_customer_selected_card`);

    if (emptyState) emptyState.classList.add('hidden');
    if (cardState) cardState.classList.remove('hidden');
}

function clearCustomerSelection(mode) {
    const idInput = document.getElementById(`${mode}_customer_id`);
    if (idInput) idInput.value = '';

    const emptyState = document.getElementById(`${mode}_customer_empty_state`);
    const cardState  = document.getElementById(`${mode}_customer_selected_card`);

    if (emptyState) emptyState.classList.remove('hidden');
    if (cardState) cardState.classList.add('hidden');
}

// =========================================================================
// 8. Personalized Vehicle Picker Operations
// =========================================================================
function openVehiclePickerModal(targetMode) {
    currentPickerTarget = targetMode || 'create';

    const searchInput = document.getElementById('vehicle_picker_search');
    if (searchInput) searchInput.value = '';

    // Reset category filter chip to 'All'
    currentVehicleTypeFilter = 'All';
    document.querySelectorAll('.vehicle-filter-chip').forEach(btn => {
        if (btn.textContent.trim() === 'All') {
            btn.className = 'vehicle-filter-chip px-2.5 py-1 text-[11px] font-bold rounded-[3px] bg-red-600 text-white border border-red-600 transition-colors';
        } else {
            btn.className = 'vehicle-filter-chip px-2.5 py-1 text-[11px] font-bold rounded-[3px] bg-white text-slate-700 border border-slate-300 hover:bg-slate-100 transition-colors';
        }
    });

    filterVehiclePicker();

    const modal = document.getElementById('modal-vehicle-picker');
    if (modal) {
        modal.classList.remove('hidden');
        if (searchInput) setTimeout(() => searchInput.focus(), 50);
    }
}

function closeVehiclePickerModal() {
    const modal = document.getElementById('modal-vehicle-picker');
    if (modal) modal.classList.add('hidden');
}

function filterVehiclePickerByType(type, btnEl) {
    currentVehicleTypeFilter = type;

    document.querySelectorAll('.vehicle-filter-chip').forEach(btn => {
        btn.className = 'vehicle-filter-chip px-2.5 py-1 text-[11px] font-bold rounded-[3px] bg-white text-slate-700 border border-slate-300 hover:bg-slate-100 transition-colors';
    });

    if (btnEl) {
        btnEl.className = 'vehicle-filter-chip px-2.5 py-1 text-[11px] font-bold rounded-[3px] bg-red-600 text-white border border-red-600 transition-colors';
    }

    filterVehiclePicker();
}

function filterVehiclePicker() {
    const input = document.getElementById('vehicle_picker_search');
    const query = input ? input.value.trim().toLowerCase() : '';
    const items = document.querySelectorAll('.vehicle-card-item');
    const noResults = document.getElementById('vehicle_picker_no_results');
    const countEl = document.getElementById('vehicle_picker_count');

    let visibleCount = 0;

    items.forEach(item => {
        const itemType   = (item.getAttribute('data-type') || '').trim();
        const searchText = (item.getAttribute('data-search') || '').toLowerCase();

        const typeMatches = (currentVehicleTypeFilter === 'All') || (itemType.toLowerCase() === currentVehicleTypeFilter.toLowerCase());
        const queryMatches = !query || searchText.includes(query);

        if (typeMatches && queryMatches) {
            item.classList.remove('hidden');
            visibleCount++;
        } else {
            item.classList.add('hidden');
        }
    });

    if (noResults) {
        if (visibleCount === 0) {
            noResults.classList.remove('hidden');
        } else {
            noResults.classList.add('hidden');
        }
    }

    if (countEl) {
        countEl.textContent = `${visibleCount} vehicle${visibleCount === 1 ? '' : 's'}`;
    }
}

function selectVehicleFromCard(cardEl) {
    if (!cardEl) return;

    const data = {
        id: cardEl.getAttribute('data-id'),
        name: cardEl.getAttribute('data-name'),
        plate: cardEl.getAttribute('data-plate'),
        type: cardEl.getAttribute('data-veh-type'),
        capacity: cardEl.getAttribute('data-capacity'),
        status: cardEl.getAttribute('data-status')
    };

    applyVehicleSelection(currentPickerTarget, data);
    closeVehiclePickerModal();
}

function applyVehicleSelection(mode, veh) {
    const idInput = document.getElementById(`${mode}_vehicle_id`);
    if (idInput) idInput.value = veh.id;

    const nameEl = document.getElementById(`${mode}_veh_name`);
    if (nameEl) nameEl.textContent = veh.name;

    const plateEl = document.getElementById(`${mode}_veh_plate`);
    if (plateEl) plateEl.textContent = veh.plate;

    const typeEl = document.getElementById(`${mode}_veh_type`);
    if (typeEl) typeEl.textContent = veh.type;

    const capacityEl = document.getElementById(`${mode}_veh_capacity`);
    if (capacityEl) capacityEl.textContent = veh.capacity;

    const iconEl = document.getElementById(`${mode}_veh_icon`);
    if (iconEl) iconEl.textContent = (veh.type || 'CAR').toUpperCase();

    const statusEl = document.getElementById(`${mode}_veh_status`);
    if (statusEl) statusEl.textContent = veh.status || 'Available';

    // Update capacity constraints on passenger field
    const paxInput = document.getElementById(`${mode}_passengers`);
    const hintEl   = document.getElementById(mode === 'create' ? 'vehicle_capacity_hint' : 'edit_vehicle_capacity_hint');

    if (veh.capacity) {
        if (hintEl) {
            hintEl.textContent = `Vehicle maximum seating capacity: ${veh.capacity} passengers`;
            hintEl.classList.remove('text-red-500');
            hintEl.classList.add('text-slate-500');
        }
        if (paxInput) {
            paxInput.max = veh.capacity;
            if (parseInt(paxInput.value, 10) > parseInt(veh.capacity, 10)) {
                paxInput.value = veh.capacity;
            }
        }
    }

    const emptyState = document.getElementById(`${mode}_vehicle_empty_state`);
    const cardState  = document.getElementById(`${mode}_vehicle_selected_card`);

    if (emptyState) emptyState.classList.add('hidden');
    if (cardState) cardState.classList.remove('hidden');
}

function clearVehicleSelection(mode) {
    const idInput = document.getElementById(`${mode}_vehicle_id`);
    if (idInput) idInput.value = '';

    const emptyState = document.getElementById(`${mode}_vehicle_empty_state`);
    const cardState  = document.getElementById(`${mode}_vehicle_selected_card`);

    if (emptyState) emptyState.classList.remove('hidden');
    if (cardState) cardState.classList.add('hidden');

    const hintEl = document.getElementById(mode === 'create' ? 'vehicle_capacity_hint' : 'edit_vehicle_capacity_hint');
    if (hintEl) hintEl.textContent = '';
}

// =========================================================================
// 9. Status Radio Card Selection
// =========================================================================
function selectStatusRadio(mode, status) {
    const formId = `form-${mode}-booking`;
    const form = document.getElementById(formId);
    if (!form) return;

    const radios = form.querySelectorAll('input[type="radio"][name="booking_status"]');

    radios.forEach(radio => {
        const isMatch = (radio.value === status);
        radio.checked = isMatch;

        const label = radio.closest('label');
        if (!label) return;

        if (isMatch) {
            // Apply distinctive border & background per status
            switch (status) {
                case 'Confirmed':
                    label.className = 'flex flex-col p-2.5 border-2 border-blue-500 bg-blue-50/50 rounded-[4px] cursor-pointer transition-all shadow-xs status-radio-card';
                    break;
                case 'Pending':
                    label.className = 'flex flex-col p-2.5 border-2 border-amber-500 bg-amber-50/50 rounded-[4px] cursor-pointer transition-all shadow-xs status-radio-card';
                    break;
                case 'Ongoing':
                    label.className = 'flex flex-col p-2.5 border-2 border-emerald-500 bg-emerald-50/50 rounded-[4px] cursor-pointer transition-all shadow-xs status-radio-card';
                    break;
                case 'Completed':
                    label.className = 'flex flex-col p-2.5 border-2 border-slate-600 bg-slate-100 rounded-[4px] cursor-pointer transition-all shadow-xs status-radio-card';
                    break;
                case 'Cancelled':
                    label.className = 'flex flex-col p-2.5 border-2 border-red-500 bg-red-50/50 rounded-[4px] cursor-pointer transition-all shadow-xs status-radio-card';
                    break;
                default:
                    label.className = 'flex flex-col p-2.5 border-2 border-slate-800 bg-slate-50 rounded-[4px] cursor-pointer transition-all shadow-xs status-radio-card';
            }
        } else {
            label.className = 'flex flex-col p-2.5 border border-slate-300 bg-white rounded-[4px] cursor-pointer transition-all hover:shadow-xs status-radio-card';
        }
    });
}

// =========================================================================
// 10. Dynamic Helper Functions
// =========================================================================
function computeDurationHint(startId, endId, hintId) {
    const startVal = document.getElementById(startId)?.value;
    const endVal   = document.getElementById(endId)?.value;
    const hintEl   = document.getElementById(hintId);

    if (!startVal || !endVal || !hintEl) return;

    const d1 = new Date(startVal);
    const d2 = new Date(endVal);

    if (d2 <= d1) {
        hintEl.innerHTML = '<span class="text-red-600 font-bold">&bull; Warning: End date & time must be strictly after the start date & time.</span>';
        return;
    }

    const diffMs = d2 - d1;
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
    const days = Math.floor(diffHours / 24);
    const remainingHours = diffHours % 24;

    let text = 'Estimated rental duration: ';
    if (days > 0) {
        text += `${days} day${days > 1 ? 's' : ''}`;
    }
    if (remainingHours > 0) {
        text += `${days > 0 ? ', ' : ''}${remainingHours} hour${remainingHours > 1 ? 's' : ''}`;
    }

    hintEl.innerHTML = `<span class="text-slate-600 font-bold">&bull; ${text}</span>`;
}

function formatPrettyDate(dateStr) {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr.replace(' ', 'T'));
        return d.toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    } catch (e) {
        return dateStr;
    }
}
