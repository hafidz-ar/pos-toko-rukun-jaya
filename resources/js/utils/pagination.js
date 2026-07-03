export const getFilteredLinks = (links) => {
    if (!links || links.length === 0) return [];

    const prevLink = links[0];
    const nextLink = links[links.length - 1];
    const pages = links.slice(1, -1);

    const formatLink = (link, type) => ({
        type,
        label: link.label,
        url: link.url,
        active: link.active || false,
        disabled: !link.url
    });

    const totalPages = pages.length;

    // If total pages <= 5, return all pages directly
    if (totalPages <= 5) {
        return [
            formatLink(prevLink, 'previous'),
            ...pages.map(p => formatLink(p, 'page')),
            formatLink(nextLink, 'next')
        ];
    }

    // N > 5: We must choose exactly 5 page numbers to show
    const activeIndex = pages.findIndex(p => p.active);
    const currentPageNum = activeIndex + 1; // 1-based index
    
    let visiblePages = [];

    if (currentPageNum <= 3) {
        // Near start: [1, 2, 3, 4, N]
        visiblePages = [1, 2, 3, 4, totalPages];
    } else if (currentPageNum >= totalPages - 2) {
        // Near end: [1, N-3, N-2, N-1, N]
        visiblePages = [1, totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
    } else {
        // Middle: [1, current-1, current, current+1, N]
        visiblePages = [1, currentPageNum - 1, currentPageNum, currentPageNum + 1, totalPages];
    }

    const result = [formatLink(prevLink, 'previous')];

    for (let idx = 0; idx < totalPages; idx++) {
        const pageNum = idx + 1;
        if (visiblePages.includes(pageNum)) {
            result.push(formatLink(pages[idx], 'page'));
        } else {
            // Add ellipsis if the last added item is not already an ellipsis
            if (result.length > 1 && result[result.length - 1].type !== 'ellipsis') {
                result.push({
                    type: 'ellipsis',
                    label: '...',
                    url: null,
                    active: false,
                    disabled: true
                });
            }
        }
    }

    result.push(formatLink(nextLink, 'next'));
    return result;
};
