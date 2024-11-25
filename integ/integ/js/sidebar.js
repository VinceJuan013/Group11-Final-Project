document.addEventListener('DOMContentLoaded', function () {
    // Try to get the hamburger menu and sidebar elements
    const hamburger = document.getElementById('hamburger-menu');
    const sidebar = document.getElementById('sidebar');

    // Debugging: Check if the elements exist
    console.log('Hamburger:', hamburger);
    console.log('Sidebar:', sidebar);

    // If either element is null, log an error
    if (!hamburger || !sidebar) {
        console.error('One or both elements (hamburger or sidebar) are missing.');
        return; // Exit the script if the elements are missing
    }

    // Toggle sidebar visibility on hamburger click
    hamburger.addEventListener('click', () => {
        sidebar.classList.toggle('sidebar-hidden');
    });
});