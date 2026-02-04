/**
 * Parish FAQ Accordion with Pagination
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        initFAQAccordions();
    });

    function initFAQAccordions() {
        var containers = document.querySelectorAll('.parish-faq-container');

        containers.forEach(function(container) {
            initAccordion(container);
            initPagination(container);
        });
    }

    function initAccordion(container) {
        var questions = container.querySelectorAll('.parish-faq-question');

        questions.forEach(function(question) {
            question.addEventListener('click', function(e) {
                e.preventDefault();
                toggleAnswer(question);
            });

            // Keyboard support
            question.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggleAnswer(question);
                }
            });
        });
    }

    function toggleAnswer(question) {
        var answer = question.nextElementSibling;
        var isExpanded = question.getAttribute('aria-expanded') === 'true';

        if (isExpanded) {
            question.setAttribute('aria-expanded', 'false');
            answer.setAttribute('hidden', '');
        } else {
            question.setAttribute('aria-expanded', 'true');
            answer.removeAttribute('hidden');
        }
    }

    function initPagination(container) {
        var perPage = parseInt(container.dataset.perPage) || 10;
        var total = parseInt(container.dataset.total) || 0;
        var currentPage = 1;

        var items = container.querySelectorAll('.parish-faq-item');
        var perPageSelect = container.querySelector('.parish-faq-per-page-select');
        var prevBtn = container.querySelector('.parish-faq-prev');
        var nextBtn = container.querySelector('.parish-faq-next');
        var pageNumbers = container.querySelector('.parish-faq-page-numbers');

        if (!items.length || total <= perPage) {
            return;
        }

        function showPage(page) {
            currentPage = page;
            var start = (page - 1) * perPage;
            var end = perPage === 0 ? total : Math.min(start + perPage, total);

            items.forEach(function(item, index) {
                if (perPage === 0 || (index >= start && index < end)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                    // Collapse hidden items
                    var question = item.querySelector('.parish-faq-question');
                    var answer = item.querySelector('.parish-faq-answer');
                    if (question && answer) {
                        question.setAttribute('aria-expanded', 'false');
                        answer.setAttribute('hidden', '');
                    }
                }
            });

            updateControls();
        }

        function updateControls() {
            var totalPages = perPage === 0 ? 1 : Math.ceil(total / perPage);
            var start = (currentPage - 1) * perPage + 1;
            var end = perPage === 0 ? total : Math.min(currentPage * perPage, total);

            // Update info text
            var startEl = container.querySelector('.parish-faq-showing-start');
            var endEl = container.querySelector('.parish-faq-showing-end');
            if (startEl) startEl.textContent = start;
            if (endEl) endEl.textContent = end;

            // Update prev/next buttons
            if (prevBtn) {
                prevBtn.disabled = currentPage <= 1;
            }
            if (nextBtn) {
                nextBtn.disabled = currentPage >= totalPages || perPage === 0;
            }

            // Update page numbers
            if (pageNumbers) {
                pageNumbers.innerHTML = '';
                if (perPage > 0 && totalPages > 1) {
                    for (var i = 1; i <= totalPages; i++) {
                        var btn = document.createElement('button');
                        btn.className = 'parish-faq-page-num' + (i === currentPage ? ' active' : '');
                        btn.textContent = i;
                        btn.dataset.page = i;
                        btn.addEventListener('click', function() {
                            showPage(parseInt(this.dataset.page));
                        });
                        pageNumbers.appendChild(btn);
                    }
                }
            }
        }

        // Per-page select handler
        if (perPageSelect) {
            perPageSelect.addEventListener('change', function() {
                var value = this.value;
                perPage = value === 'all' ? 0 : parseInt(value);
                currentPage = 1;
                showPage(1);
            });
        }

        // Prev/Next button handlers
        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                if (currentPage > 1) {
                    showPage(currentPage - 1);
                }
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                var totalPages = perPage === 0 ? 1 : Math.ceil(total / perPage);
                if (currentPage < totalPages) {
                    showPage(currentPage + 1);
                }
            });
        }

        // Initial display
        showPage(1);
    }
})();
