let productModal;

function initQuantityButtons(container) {
    container.querySelectorAll('.quantity-box').forEach(box => {
        const input = box.querySelector('.quantity-input');
        box.querySelector('.minus').addEventListener('click', () => {
            const val = parseInt(input.value) || 1;
            input.value = Math.max(1, val - 1);
        });
        box.querySelector('.plus').addEventListener('click', () => {
            const val = parseInt(input.value) || 1;
            input.value = val + 1;
        });
    });
}
function attachModalEvents(container) {
    const searchInput = container.querySelector('#productSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            container.querySelectorAll('#productGrid .product-item').forEach(item => {
                const name = item.dataset.name.toLowerCase();
                item.style.display = name.includes(term) ? '' : 'none';
            });
    });
    }

    container.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            openAddProductModal(this.dataset.category);
        });
    });

    initQuantityButtons(container);
}

const tableId = document.getElementById('order-data').dataset.tableId;

function openAddProductModal(categoryId = 0) {
    const modalBody = document.getElementById('modal-body-content');

    // Show loading spinner while fetching modal content
    modalBody.innerHTML =
        '<div class="d-flex justify-content-center align-items-center p-5">' +
        '<div class="spinner-border text-primary" role="status" ' +
        'style="width: 3rem; height: 3rem;">' +
        '<span class="visually-hidden">Y\u00fckleniyor...</span>' +
        '</div></div>';

    const modalTitle = document.querySelector('.modal-title');
    modalTitle.innerHTML = '<span class="material-icons me-2">restaurant_menu</span>\u00dcr\u00fcn Seçin';

    // Load modal stylesheet on first use to avoid layout shift
    if (!document.getElementById('order-add-style')) {
        const link = document.createElement('link');
        link.id = 'order-add-style';
        link.rel = 'stylesheet';
        link.href = '/assets/css/order_add.css';
        document.head.appendChild(link);
    }

    if (!productModal) {
        productModal = new bootstrap.Modal(document.getElementById('addProductModal'), { keyboard: false });
    }
    productModal.show();

    const start = Date.now();
    fetch('order_add.php?table=' + tableId + '&category=' + categoryId)
        .then(res => res.text())
        .then(html => {
            const elapsed = Date.now() - start;
            const showContent = () => {
                modalBody.innerHTML = html;
                attachModalEvents(modalBody);
            };
            if (elapsed < 3000) {
                setTimeout(showContent, 3000 - elapsed);
            } else {
                showContent();
            }
        })
        .catch(err => {
            console.error('Hata:', err);
            alert('\u00dcr\u00fcnler y\u00fcklenirken bir hata olu\u015ftu.');
        });
}

document.getElementById('openAddProduct').addEventListener('click', () => openAddProductModal());
