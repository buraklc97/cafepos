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

    container.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', handleAddProduct);
    });
}

const tableId = document.getElementById('order-data').dataset.tableId;

async function handleAddProduct(e) {
    e.preventDefault();
    const form = e.currentTarget;
    const button = form.querySelector('.add-button');
    const buttonText = button.querySelector('.btn-text');
    const buttonIcon = button.querySelector('.material-icons');
    
    // Orijinal buton içeriğini sakla
    const originalText = buttonText.textContent;
    const originalIcon = buttonIcon.textContent;
    
    // Yükleme animasyonu başlat
    button.disabled = true;
    button.classList.add('loading');
    buttonIcon.textContent = 'hourglass_empty';
    buttonIcon.classList.add('spinning');
    buttonText.textContent = 'Ekleniyor...';
    
    const formData = new FormData(form);
    formData.append('table_id', tableId);
    
    try {
        const resp = await fetch('api_add_product.php', {
            method: 'POST',
            body: formData
        });
        
        if (resp.ok) {
            // Başarı animasyonu
            button.classList.remove('loading');
            button.classList.add('success');
            buttonIcon.classList.remove('spinning');
            buttonIcon.textContent = 'check_circle';
            buttonText.textContent = 'Eklendi!';
            
            // Sepeti güncelle
            updateOrderCart();
            form.querySelector('.quantity-input').value = 1;
            
            // 1.5 saniye sonra butonu eski haline getir
            setTimeout(() => {
                button.disabled = false;
                button.classList.remove('success');
                buttonIcon.textContent = originalIcon;
                buttonText.textContent = originalText;
            }, 1500);
        } else {
            // Hata durumu
            button.classList.remove('loading');
            button.classList.add('error');
            buttonIcon.classList.remove('spinning');
            buttonIcon.textContent = 'error';
            buttonText.textContent = 'Hata!';
            
            setTimeout(() => {
                button.disabled = false;
                button.classList.remove('error');
                buttonIcon.textContent = originalIcon;
                buttonText.textContent = originalText;
            }, 2000);
            
            alert('Ürün eklenemedi');
        }
    } catch (err) {
        console.error(err);
        
        // Hata durumu
        button.classList.remove('loading');
        button.classList.add('error');
        buttonIcon.classList.remove('spinning');
        buttonIcon.textContent = 'error';
        buttonText.textContent = 'Hata!';
        
        setTimeout(() => {
            button.disabled = false;
            button.classList.remove('error');
            buttonIcon.textContent = originalIcon;
            buttonText.textContent = originalText;
        }, 2000);
        
        alert('Ürün eklenirken hata oluştu');
    }
}

async function updateOrderCart() {
    try {
        const resp = await fetch('api_order_cart.php?table=' + tableId, { cache: 'no-store' });
        const html = await resp.text();
        document.getElementById('cartWrapper').innerHTML = html;
        
        // Ödeme butonunun görünürlüğünü kontrol et
        updatePaymentButtonVisibility();
    } catch (err) {
        console.error(err);
    }
}

function updatePaymentButtonVisibility() {
    const paymentButtonWrapper = document.getElementById('paymentButtonWrapper');
    const cartTable = document.querySelector('#cartWrapper .cart-table');
    
    if (paymentButtonWrapper) {
        if (cartTable) {
            // Sepette ürün varsa butonu göster
            paymentButtonWrapper.style.display = 'block';
        } else {
            // Sepet boşsa butonu gizle
            paymentButtonWrapper.style.display = 'none';
        }
    }
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
