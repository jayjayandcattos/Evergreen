document.addEventListener('DOMContentLoaded', () => {
  // Highlight current nav item
  const links = document.querySelectorAll('.nav-links a');
  links.forEach(link => {
    if (link.getAttribute('href') === window.location.pathname.split('/').pop() || 
        link.href === window.location.href) {
      link.classList.add('active');
    }
  });

  // Update time
  function updateTime() {
    const now = new Date();
    const options = { timeZone: 'Asia/Manila', hour12: true };
    const dateEl = document.getElementById('currentDate');
    const timeEl = document.getElementById('currentTime');
    if (dateEl) dateEl.textContent = "Today is " + now.toLocaleDateString('en-PH', { timeZone: 'Asia/Manila' });
    if (timeEl) timeEl.textContent = now.toLocaleTimeString('en-PH', options);
  }
  setInterval(updateTime, 1000);
  updateTime();

  // --- Modal Functionality ---
  const statusModal = document.getElementById('statusModal');
  const remarksModal = document.getElementById('remarksModal');

  const openModal = (modal) => {
    if (!modal) return;
    modal.classList.remove('hide');
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('show'), 10);
  };

  const closeModal = (modal) => {
    if (!modal) return;
    modal.classList.remove('show');
    modal.classList.add('hide');
    setTimeout(() => {
      modal.style.display = 'none';
    }, 300);
  };

  // Close status modal elements
  const closeStatusElems = document.querySelectorAll('.close-status');
  const backStatus = document.querySelector('.back-status');
  
  if (closeStatusElems.length) {
    closeStatusElems.forEach(el => el.addEventListener('click', () => closeModal(statusModal)));
  }
  if (backStatus) backStatus.onclick = () => closeModal(statusModal);

  // Remarks modal
  const remarksBtn = document.querySelector('.remarks-btn');
  if (remarksBtn) {
    remarksBtn.addEventListener('click', () => {
      closeModal(statusModal);
      setTimeout(() => openModal(remarksModal), 300);
    });
  }

  const closeRemarks = document.querySelector('.close-btn');
  const backRemarks = document.querySelector('.back-remarks');
  const submitBtn = document.querySelector('.submit-btn');

  if (closeRemarks) {
    closeRemarks.onclick = () => {
      closeModal(remarksModal);
      setTimeout(() => openModal(statusModal), 300);
    };
  }

  if (backRemarks) {
    backRemarks.onclick = () => {
      closeModal(remarksModal);
      setTimeout(() => openModal(statusModal), 300);
    };
  }

  if (submitBtn) {
    submitBtn.onclick = () => {
      alert('Remarks submitted!');
      closeModal(remarksModal);
      closeModal(statusModal);
    };
  }

  // Click outside to close
  window.addEventListener('click', (e) => {
    if (e.target === statusModal) closeModal(statusModal);
    if (e.target === remarksModal) {
      closeModal(remarksModal);
      setTimeout(() => openModal(statusModal), 300);
    }
  });
});

// ✅ VIEW LOAN FUNCTION - Populate modal with loan data
function viewLoan(loanId) {
  const statusModal = document.getElementById('statusModal');
  
  if (!statusModal) {
    alert('Modal not found');
    return;
  }

  fetch(`view_loan.php?id=${loanId}`)
    .then(res => {
      if (!res.ok) throw new Error('Network error');
      return res.json();
    })
    .then(data => {
      if (data.error) {
        alert(data.error);
        return;
      }

      // Populate Account Information fields
      const fullNameInput = document.getElementById('modal-full-name');
      const accountNumberInput = document.getElementById('modal-account-number');
      const contactNumberInput = document.getElementById('modal-contact-number');
      const emailInput = document.getElementById('modal-email');

      if (fullNameInput) fullNameInput.value = data.full_name || '';
      if (accountNumberInput) accountNumberInput.value = data.account_number || '';
      if (contactNumberInput) contactNumberInput.value = data.contact_number || '';
      if (emailInput) emailInput.value = data.email || '';

      // Populate Loan Details fields
      const loanTypeInput = document.getElementById('modal-loan-type');
      const loanTermInput = document.getElementById('modal-loan-term');

      if (loanTypeInput) loanTypeInput.value = data.loan_type || '';
      if (loanTermInput) loanTermInput.value = data.loan_terms || '';

      // Open the modal
      statusModal.classList.remove('hide');
      statusModal.style.display = 'flex';
      setTimeout(() => statusModal.classList.add('show'), 10);
    })
    .catch(err => {
      console.error('Error:', err);
      alert('Failed to load loan details.');
    });
}