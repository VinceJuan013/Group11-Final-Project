document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    const filterSelect = document.getElementById('filter');
    const tableBody = document.querySelector('#documentsTable tbody');
    const noResults = document.getElementById('noResults');

    // Function to create action buttons based on document source
    function createActionButtons(doc) {
        return `
            <td class="actions">
                ${doc.source === 'xray' 
                    ? `
                        <button class="btn btn-outline" onclick="viewDocument('${doc.id}', '${doc.source}')">
                            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z"/>
                            </svg>
                            View
                        </button>
                        <button class="btn btn-outline" onclick="downloadDocument('${doc.id}', '${doc.source}')">
                            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <path d="M7 10l5 5 5-5"/>
                                <path d="M12 15V3"/>
                            </svg>
                            Download
                        </button>`
                    : `
                        <button class="btn btn-outline" disabled>
                            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z"/>
                            </svg>
                            View
                        </button>
                        <button class="btn btn-outline" disabled>
                            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <path d="M7 10l5 5 5-5"/>
                                <path d="M12 15V3"/>
                            </svg>
                            Download
                        </button>`
                }
            </td>
        `;
    }

    // Function to render the table with documents
    function renderTable(docs) {
        tableBody.innerHTML = '';
        if (docs.length === 0) {
            noResults.style.display = 'block';
            tableBody.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            tableBody.style.display = '';
            docs.forEach(doc => {
                const row = `
                    <tr>
                        <td>${doc.name}</td>
                        <td>${doc.type}</td>
                        <td>${doc.date}</td>
                        ${createActionButtons(doc)}
                    </tr>
                `;
                tableBody.innerHTML += row;
            });
        }
    }

    // Filter and render documents based on search and filter selection
    function filterDocuments() {
        const searchTerm = searchInput.value.toLowerCase();
        const filterType = filterSelect.value;

        const filteredDocs = documents.filter(doc => 
            (filterType === 'all' || doc.type === filterType) &&
            (doc.name.toLowerCase().includes(searchTerm) || doc.date.includes(searchTerm))
        );

        renderTable(filteredDocs);
    }

    searchInput.addEventListener('input', filterDocuments);
    filterSelect.addEventListener('change', filterDocuments);

    // Initial render
    renderTable(documents);
});

// View and download document functions
function viewDocument(id, source) {
    if (source === 'xray') {
        window.open(`../patients/docs/view_xray.php?id=${id}`, '_blank');
    } else {
        console.log(`Viewing document: ${id} from ${source}`);
    }
}

function downloadDocument(id, source) {
    if (source === 'xray') {
        window.location.href = `../patients/docs/download_xray.php?id=${id}`;
    } else {
        console.log(`Downloading document: ${id} from ${source}`);
    }
}
