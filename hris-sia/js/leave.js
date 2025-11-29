
function openModal() {
    document.getElementById('leaveModal').classList.add('active');
    document.body.style.overflow = 'hidden'; 
}


function closeModal() {
    document.getElementById('leaveModal').classList.remove('active');
    document.body.style.overflow = 'auto'; 
    if (document.getElementById('leaveForm')) {
        document.getElementById('leaveForm').reset();
    }
}

function clearFilters() {
    window.location.href = 'leave.php';
}

function filterByCard(filterType) {
    // Get current URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    
    // Set the filter parameter
    urlParams.set('filter', filterType);
    
    // Remove status filter if it exists (card filters take precedence)
    urlParams.delete('status');
    
    // Redirect to the filtered page
    window.location.href = 'leave.php?' + urlParams.toString();
}


document.getElementById('leaveModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});


document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});


function showConfirmApprove(leaveRequestId) {
    showConfirmModal(
        'Are you sure you want to approve this leave request?',
        function() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="leave_request_id" value="${leaveRequestId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    );
}

function showConfirmReject(leaveRequestId) {
    showConfirmModal(
        'Are you sure you want to reject this leave request?',
        function() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="leave_request_id" value="${leaveRequestId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    );
}

function approveLeave(id) {
    showConfirmApprove(id);
}

function rejectLeave(id) {
    showConfirmReject(id);
}

function viewLeave(id) {
    showAlertModal('View details for leave request #' + id, 'info');
}


function calculateDays() {
    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;
    
    if (startDate && endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        
        
        console.log('Number of days:', diffDays);
    }
}


document.querySelector('input[name="start_date"]')?.addEventListener('change', calculateDays);
document.querySelector('input[name="end_date"]')?.addEventListener('change', calculateDays);

