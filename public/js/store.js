const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
}[character]));

const formatCurrency = (value) => `$${Number(value).toFixed(2)}`;

const showAlert = (container, message, type = 'danger') => {
    container.replaceChildren();
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.setAttribute('role', 'alert');
    alert.textContent = message;
    container.append(alert);
};

const errorMessage = (payload, fallback) => {
    const messages = Object.values(payload.errors ?? {}).flat().map((error) => {
        if (typeof error === 'string') return error;
        if (error && typeof error === 'object' && error.product_id) {
            return `Product ${error.product_id}: ${error.available} in stock, ${error.requested} requested.`;
        }
        return '';
    }).filter(Boolean);

    return [payload.message ?? fallback, ...messages].join(' ');
};

const initializeCustomerAutofill = () => {
    document.querySelectorAll('[data-customer-autofill]').forEach((container) => {
        const nameInput = container.querySelector('input[data-customer-name]');
        const emailInput = container.querySelector('input[data-customer-email]');
        if (!nameInput || !emailInput) return;

        const findOption = (input) => {
            const list = document.getElementById(input.getAttribute('list'));
            const value = input.value.trim().toLocaleLowerCase();
            return Array.from(list?.options ?? []).find((option) =>
                option.value.trim().toLocaleLowerCase() === value
            );
        };

        nameInput.addEventListener('input', () => {
            const option = findOption(nameInput);
            if (option) {
                emailInput.value = option.dataset.customerEmail;
                emailInput.dataset.autofilled = 'true';
            } else if (emailInput.dataset.autofilled === 'true') {
                emailInput.value = '';
                delete emailInput.dataset.autofilled;
            }
        });

        emailInput.addEventListener('input', () => {
            const option = findOption(emailInput);
            if (option) {
                nameInput.value = option.dataset.customerName;
                nameInput.dataset.autofilled = 'true';
            } else if (nameInput.dataset.autofilled === 'true') {
                nameInput.value = '';
                delete nameInput.dataset.autofilled;
            }
        });
    });
};

const initializeOrderForm = () => {
    const form = document.querySelector('#create-order-form');
    if (!form) return;

    const rows = document.querySelector('#order-items');
    const template = document.querySelector('#order-item-template');
    const status = document.querySelector('#order-status');
    const submitButton = document.querySelector('#submit-order');

    const updateTotals = () => {
        let subtotalCents = 0;
        let taxCents = 0;

        rows.querySelectorAll('.order-line').forEach((row) => {
            const select = row.querySelector('.product-select');
            const quantityInput = row.querySelector('.quantity-input');
            const selectedOption = select.selectedOptions[0];
            const quantity = Math.max(0, Number.parseInt(quantityInput.value, 10) || 0);
            const hasProduct = Boolean(select.value);
            const unitPriceCents = hasProduct ? Math.round(Number(selectedOption.dataset.price) * 100) : 0;
            const lineSubtotalCents = unitPriceCents * quantity;
            const taxPercentage = hasProduct ? Number(selectedOption.dataset.tax) : 0;
            const lineTaxCents = Math.round(lineSubtotalCents * taxPercentage / 100);

            subtotalCents += lineSubtotalCents;
            taxCents += lineTaxCents;
            row.querySelector('.line-unit-price').textContent = hasProduct ? formatCurrency(unitPriceCents / 100) : '—';
            row.querySelector('.line-tax-rate').textContent = hasProduct ? `${taxPercentage.toFixed(2)}%` : '—';
            row.querySelector('.line-subtotal').textContent = formatCurrency(lineSubtotalCents / 100);
        });

        document.querySelector('#order-subtotal').textContent = formatCurrency(subtotalCents / 100);
        document.querySelector('#order-tax').textContent = formatCurrency(taxCents / 100);
        document.querySelector('#order-grand-total').textContent = formatCurrency((subtotalCents + taxCents) / 100);
    };

    const updateRemoveButtons = () => {
        const buttons = rows.querySelectorAll('.remove-order-item');
        buttons.forEach((button) => {
            button.disabled = buttons.length === 1;
        });
    };

    const addRow = () => {
        rows.append(template.content.cloneNode(true));
        updateRemoveButtons();
        updateTotals();
    };

    document.querySelector('#add-order-item').addEventListener('click', addRow);
    rows.addEventListener('click', (event) => {
        const button = event.target.closest('.remove-order-item');
        if (!button) return;
        button.closest('.order-line').remove();
        updateRemoveButtons();
        updateTotals();
    });
    rows.addEventListener('input', (event) => {
        if (event.target.matches('.quantity-input')) updateTotals();
    });
    rows.addEventListener('change', (event) => {
        if (event.target.matches('.product-select')) updateTotals();
    });

    addRow();

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        form.classList.add('was-validated');
        const itemRows = Array.from(rows.querySelectorAll('.order-line'));
        if (itemRows.length === 0) {
            showAlert(status, 'Add at least one product line before submitting the order.');
            return;
        }
        if (!form.checkValidity()) return;

        const items = itemRows.map((row) => ({
            product_id: Number(row.querySelector('.product-select').value),
            quantity: Number(row.querySelector('.quantity-input').value),
        }));
        if (new Set(items.map((item) => item.product_id)).size !== items.length) {
            showAlert(status, 'Each product can only appear once. Remove or change the duplicate product line.');
            return;
        }

        const payload = {
            customer_name: form.elements.customer_name.value,
            customer_email: form.elements.customer_email.value,
            items,
        };

        submitButton.disabled = true;
        submitButton.textContent = 'Submitting...';
        status.replaceChildren();

        try {
            const response = await fetch('/api/orders', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify(payload),
            });
            const result = await response.json();

            if (!response.ok) {
                showAlert(status, errorMessage(result, 'The order could not be created.'));
                return;
            }

            const success = document.createElement('div');
            success.className = 'alert alert-success';
            success.setAttribute('role', 'status');
            const heading = document.createElement('h2');
            heading.className = 'alert-heading h6';
            heading.textContent = `Order #${result.data.id} created successfully`;
            const summary = document.createElement('p');
            summary.className = 'mb-0';
            summary.textContent = `Subtotal ${formatCurrency(result.data.subtotal)} · Tax ${formatCurrency(result.data.tax)} · Grand total ${formatCurrency(result.data.grand_total)}`;
            success.append(heading, summary);
            status.replaceChildren(success);
            form.reset();
            form.classList.remove('was-validated');
            rows.replaceChildren();
            addRow();
        } catch {
            showAlert(status, 'The server could not be reached. Please try again.');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Submit order';
        }
    });
};

const initializeOrderHistory = () => {
    const form = document.querySelector('#order-history-form');
    if (!form) return;

    const emailInput = form.elements.email;
    const nameInput = form.elements.customer_name;
    const status = document.querySelector('#history-status');
    const results = document.querySelector('#order-history-results');
    const submitButton = document.querySelector('#find-orders-button');
    const presetEmail = new URLSearchParams(window.location.search).get('email');
    if (presetEmail) emailInput.value = presetEmail;

    const renderHistory = (data) => {
        const customer = data.customer;
        const customerCard = `
            <section class="card content-card mb-3">
                <div class="card-body d-flex flex-column flex-sm-row justify-content-between gap-1">
                    <div><h2 class="h6 mb-1">${escapeHtml(customer.name)}</h2><div class="text-secondary">${escapeHtml(customer.email)}</div></div>
                    <div class="small text-secondary">${data.orders.length} ${data.orders.length === 1 ? 'order' : 'orders'}</div>
                </div>
            </section>`;

        if (!data.orders.length) {
            results.innerHTML = `${customerCard}<div class="alert alert-info" role="status">No orders found for this customer.</div>`;
            return;
        }

        const orders = data.orders.map((order, index) => {
            const orderDate = new Date(order.order_date);
            const formattedDate = Number.isNaN(orderDate.getTime()) ? '' : orderDate.toLocaleString();
            const itemRows = order.items.map((item) => `
                <tr>
                    <td>${escapeHtml(item.product_name)}</td>
                    <td class="text-end">${escapeHtml(item.quantity)}</td>
                    <td class="text-end">${formatCurrency(item.unit_price)}</td>
                    <td class="text-end">${formatCurrency(item.subtotal)}</td>
                    <td class="text-end">${formatCurrency(item.tax)}</td>
                    <td class="text-end">${formatCurrency(item.total)}</td>
                </tr>`).join('');

            return `
                <section class="card content-card mb-3">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                            <div>
                                <h3 class="h6 mb-1">Order #${escapeHtml(order.id)}</h3>
                                <div class="small text-secondary">${escapeHtml(formattedDate)}</div>
                            </div>
                            <dl class="history-order-totals mb-0">
                                <div><dt>Subtotal</dt><dd>${formatCurrency(order.subtotal)}</dd></div>
                                <div><dt>Tax</dt><dd>${formatCurrency(order.tax)}</dd></div>
                                <div class="fw-bold"><dt>Grand total</dt><dd>${formatCurrency(order.grand_total)}</dd></div>
                            </dl>
                        </div>
                        <details class="mt-3">
                            <summary class="link-success fw-semibold">View ${order.items.length} ${order.items.length === 1 ? 'item' : 'items'}</summary>
                            <div class="table-responsive mt-3">
                                <table class="table table-hover order-item-table align-middle mb-0">
                                    <thead><tr><th>Product</th><th class="text-end">Qty</th><th class="text-end">Unit price</th><th class="text-end">Subtotal</th><th class="text-end">Tax</th><th class="text-end">Total</th></tr></thead>
                                    <tbody>${itemRows}</tbody>
                                </table>
                            </div>
                        </details>
                    </div>
                </section>`;
        }).join('');

        results.innerHTML = customerCard + orders;
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        form.classList.add('was-validated');
        if (!form.checkValidity()) return;
        status.replaceChildren();
        results.replaceChildren();
        submitButton.disabled = true;
        submitButton.textContent = 'Searching...';

        try {
            const response = await fetch(`/api/customers/orders?email=${encodeURIComponent(emailInput.value)}`, {
                headers: { Accept: 'application/json' },
            });
            const result = await response.json();
            if (!response.ok) {
                showAlert(status, errorMessage(result, response.status === 404 ? 'Customer not found.' : 'Could not load order history.'));
                return;
            }
            nameInput.value = result.data.customer.name;
            emailInput.value = result.data.customer.email;
            renderHistory(result.data);
        } catch {
            showAlert(status, 'The server could not be reached. Please try again.');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Find orders';
        }
    });

    if (presetEmail) form.requestSubmit();
};

const initializeLowStock = () => {
    const form = document.querySelector('#low-stock-form');
    if (!form) return;

    const results = document.querySelector('#low-stock-results');
    const status = document.querySelector('#low-stock-status');
    const threshold = form.elements.threshold;
    const submitButton = document.querySelector('#search-low-stock');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        form.classList.add('was-validated');
        if (!form.checkValidity()) return;
        status.replaceChildren();
        results.innerHTML = '<tr><td colspan="5" class="empty-state">Loading products...</td></tr>';
        submitButton.disabled = true;
        submitButton.textContent = 'Searching...';

        try {
            const response = await fetch(`/api/products/low-stock?threshold=${encodeURIComponent(threshold.value)}`, {
                headers: { Accept: 'application/json' },
            });
            const result = await response.json();
            if (!response.ok) {
                results.replaceChildren();
                showAlert(status, errorMessage(result, 'Could not load low-stock products.'));
                return;
            }

            if (!result.data.length) {
                results.innerHTML = '<tr><td colspan="5" class="empty-state">No products below this threshold.</td></tr>';
                return;
            }

            results.innerHTML = result.data.map((product) => `
                <tr>
                    <td class="fw-semibold">${escapeHtml(product.name)}</td>
                    <td><code>${escapeHtml(product.code)}</code></td>
                    <td class="text-end">${formatCurrency(product.price)}</td>
                    <td class="text-end">${escapeHtml(product.tax_percentage)}%</td>
                    <td class="text-end"><span class="badge text-bg-warning">${escapeHtml(product.stock)}</span></td>
                </tr>`).join('');
        } catch {
            results.replaceChildren();
            showAlert(status, 'The server could not be reached. Please try again.');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Search';
        }
    });

    form.requestSubmit();
};

document.addEventListener('DOMContentLoaded', () => {
    initializeCustomerAutofill();
    initializeOrderForm();
    initializeOrderHistory();
    initializeLowStock();
});
