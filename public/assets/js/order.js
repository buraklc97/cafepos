let productModal;
let toast;

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

    container.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', handleAddProduct);
    });
}

const tableId = document.getElementById('order-data').dataset.tableId;

async function handleAddProduct(e) {
    e.preventDefault();
    const form = e.currentTarget;
    const data = {
        table_id: tableId,
        product_id: form.querySelector('input[name="product_id"]').value,
        quantity: form.querySelector('input[name="quantity"]').value
    };
    try {
        const resp = await fetch('api_add_product.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const resData = await resp.json();
        if (resp.ok && resData.success) {
            updateCart(resData.cart);
            form.querySelector('.quantity-input').value = 1;
            showToast('Ürün sepete eklendi');
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
        const resp = await fetch('api_order_cart.php?table=' + tableId + '&format=json', { cache: 'no-store' });
        const data = await resp.json();
        if (data.cart) {
            updateCart(data.cart);
        }
    } catch (err) {
        console.error(err);
    }
}

function updateCart(cart) {
    const wrapper = document.getElementById('cartWrapper');
    let html = `<div class="cart-header"><span class="material-icons">shopping_cart</span> Sipariş Sepeti</div>`;
    if (!cart.items || cart.items.length === 0) {
        html += `<div class="cart-empty"><div class="material-icons">shopping_cart</div><p>Henüz ürün eklenmedi</p><small>Yukarıdaki butondan ürün eklemeye başlayın</small></div>`;
    } else {
        html += '<table class="cart-table"><thead><tr><th>Ürün</th><th>Adet</th><th>Birim Fiyat</th><th>Tutar</th><th>İşlem</th></tr></thead><tbody>';
        cart.items.forEach(it => {
            const subtotal = it.quantity * it.unit_price;
            html += `<tr>`+
                `<td>${escapeHtml(it.name)}</td>`+
                `<td class="qty-cell"><span class="badge bg-primary rounded-pill">${it.quantity}</span>`+
                `<a href="?table=${tableId}&increase_item=${it.id}" class="qty-btn plus">+</a></td>`+
                `<td>${Number(it.unit_price).toFixed(2)} ₺</td>`+
                `<td><strong>${subtotal.toFixed(2)} ₺</strong></td>`+
                `<td><a href="?table=${tableId}&delete_item=${it.id}" class="delete-link" onclick="return confirm('Bu ürünü silmek istediğinize emin misiniz?')"><span class="material-icons">delete</span></a></td>`+
                `</tr>`;
        });
        html += `</tbody><tfoot><tr style="border-top: 3px solid var(--border-color);"><td colspan="3"><strong>TOPLAM</strong></td><td><strong style="font-size: 1.2rem; color: var(--btn-bg);">${Number(cart.total).toFixed(2)} ₺</strong></td><td></td></tr></tfoot></table>`;
    }
    wrapper.innerHTML = html;
}

function escapeHtml(str) {
    return str.replace(/[&<>"']/g, s => ({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[s]));
}

function showToast(msg) {
    if (!toast) {
        const el = document.getElementById('addToast');
        toast = new bootstrap.Toast(el);
    }
    document.querySelector('#addToast .toast-body').textContent = msg;
    toast.show();
}

function openAddProductModal(categoryId = 0) {
    fetch('order_add.php?table=' + tableId + '&category=' + categoryId)
        .then(res => res.text())
        .then(html => {
            const modalBody = document.getElementById('modal-body-content');
            modalBody.innerHTML = html;

            const modalTitle = document.querySelector('.modal-title');
            modalTitle.innerHTML = '<span class="material-icons me-2">restaurant_menu</span>\u00dcr\u00fcn Seçin';

            if (!productModal) {
                productModal = new bootstrap.Modal(document.getElementById('addProductModal'), {keyboard:false});
            }
            productModal.show();

            attachModalEvents(modalBody);
        })
        .catch(err => {
            console.error('Hata:', err);
            alert('\u00dcr\u00fcnler y\u00fcklenirken bir hata olu\u015ftu.');
        });
}

document.getElementById('openAddProduct').addEventListener('click', () => openAddProductModal());
