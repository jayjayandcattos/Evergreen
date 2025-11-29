document.addEventListener("DOMContentLoaded", function() {
    // 1. Get the loan type from the URL query parameter
    const urlParams = new URLSearchParams(window.location.search);
    const preselectedLoanType = urlParams.get('loanType');

    if (preselectedLoanType) {
        // 2. Find the loan type dropdown element
        const loanTypeSelect = document.getElementById('loan_type');
        
        if (loanTypeSelect) {
            // 3. Loop through all options and set 'selected' if the value matches
            // We use decodeURIComponent to handle spaces (e.g., "Personal%20Loan" becomes "Personal Loan")
            const decodedType = decodeURIComponent(preselectedLoanType);

            let matchFound = false;
            for (let i = 0; i < loanTypeSelect.options.length; i++) {
                if (loanTypeSelect.options[i].value === decodedType) {
                    loanTypeSelect.selectedIndex = i;
                    matchFound = true;
                    break;
                }
            }
            
            // Optional: If you use the dynamic progress tracking (as in previous responses), 
            // you should call the update function here to reflect the change.
            // if (matchFound && typeof updateProgress === 'function') {
            //     updateProgress();
            // }
        }
    }
});