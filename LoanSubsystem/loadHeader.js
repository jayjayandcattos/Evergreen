function loadHeader() {
    const headerPlaceholder = document.getElementById('header-placeholder');

    if (headerPlaceholder) {

        fetch('header.html')
            .then(response => {
               
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }            
                return response.text();
            })
            .then(html => {
                
                headerPlaceholder.innerHTML = html;
            })
            .catch(error => {
                console.error('Error loading header:', error);
                headerPlaceholder.innerHTML = '<p>Error loading header content.</p>';
            });
    }
}

document.addEventListener('DOMContentLoaded', loadHeader);