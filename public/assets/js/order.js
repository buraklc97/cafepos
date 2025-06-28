let productSectionVisible = false;

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
function attachSectionEvents(container) {
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
            openAddProductSection(this.dataset.category);
        });
    });

    initQuantityButtons(container);

    container.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', handleAddProduct);
    });
}

const tableId = document.getElementById('order-data').dataset.tableId;

async function handleAddProduct(e) {
    e.preventDefault();
    const form = e.currentTarget;
    const formData = new FormData(form);
    formData.append('table_id', tableId);
    try {
        const resp = await fetch('api_add_product.php', {
            method: 'POST',
            body: formData
        });
        if (resp.ok) {
            await reloadCart();
            form.querySelector('.quantity-input').value = 1;
        } else {
            alert('Ürün eklenemedi');
        }
    } catch (err) {
        console.error(err);
        alert('Ürün eklenirken hata oluştu');
    }
}

async function reloadCart() {
    try {
        const resp = await fetch('api_order_cart.php?table=' + tableId, { cache: 'no-store' });
        const html = await resp.text();
        document.getElementById('cartWrapper').innerHTML = html;
    } catch (err) {
        console.error(err);
    }
}

function openAddProductSection(categoryId = 0) {
    fetch('order_add.php?table=' + tableId + '&category=' + categoryId)
        .then(res => res.text())
        .then(html => {
            const section = document.getElementById('addProductSection');
            section.innerHTML = html;
            section.style.display = 'block';
            productSectionVisible = true;
            attachSectionEvents(section);
        })
        .catch(err => {
            console.error('Hata:', err);
            alert('Ürünler yüklenirken bir hata oluştu.');
        });
}

document.getElementById('openAddProduct').addEventListener('click', () => {
    const section = document.getElementById('addProductSection');
    if (productSectionVisible) {
        section.style.display = 'none';
        productSectionVisible = false;
    } else {
        openAddProductSection();
    }
});
