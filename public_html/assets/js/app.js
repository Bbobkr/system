// دوال مشتركة للواجهة - بدون أي اعتماديات خارجية

document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.matches('[data-confirm]')) {
        if (!window.confirm(form.getAttribute('data-confirm'))) {
            e.preventDefault();
        }
    }
});

/**
 * يدير جدول أصناف الفاتورة (بيع/شراء): بحث/مسح باركود، إضافة صف، حذف صف، حساب الإجماليات.
 * options: {
 *   products: [{id,name,barcode,sku,price}],
 *   priceField: 'sale_price'|'cost_price' label only,
 *   searchInputId, tableBodyId, subtotalId, taxPercentId, taxAmountId, discountId, totalId,
 *   rowNamePrefix: 'items' -> يولد أسماء الحقول items[i][product_id] الخ
 *   stockUrl: رابط AJAX لجلب الكمية المتاحة (اختياري، للمبيعات فقط)
 *   branchSelectId: عنصر اختيار الفرع (لجلب المخزون عند البيع)
 * }
 */
function initInvoiceForm(options) {
    var products = options.products || [];
    var searchInput = document.getElementById(options.searchInputId);
    var tbody = document.getElementById(options.tableBodyId);
    var rowIndex = 0;

    function findProduct(term) {
        term = term.trim();
        if (!term) return null;
        var byCode = products.find(function (p) {
            return p.barcode === term || p.sku === term;
        });
        if (byCode) return byCode;
        var lower = term.toLowerCase();
        return products.find(function (p) {
            return p.name.toLowerCase() === lower;
        }) || products.find(function (p) {
            return p.name.toLowerCase().indexOf(lower) !== -1;
        });
    }

    function recalc() {
        var subtotal = 0;
        tbody.querySelectorAll('tr').forEach(function (row) {
            var qty = parseFloat(row.querySelector('.f-qty').value) || 0;
            var price = parseFloat(row.querySelector('.f-price').value) || 0;
            var lineTotal = qty * price;
            row.querySelector('.f-line-total').textContent = lineTotal.toFixed(2);
            subtotal += lineTotal;
        });
        var taxPercent = parseFloat(document.getElementById(options.taxPercentId).value) || 0;
        var discount = parseFloat(document.getElementById(options.discountId).value) || 0;
        var taxAmount = (subtotal - discount) * (taxPercent / 100);
        var total = subtotal - discount + taxAmount;

        document.getElementById(options.subtotalId).textContent = subtotal.toFixed(2);
        document.getElementById(options.taxAmountId).textContent = taxAmount.toFixed(2);
        document.getElementById(options.totalId).textContent = total.toFixed(2);
        document.getElementById(options.totalHiddenId).value = total.toFixed(2);
    }

    function addRow(product) {
        if (tbody.querySelector('tr[data-product-id="' + product.id + '"]')) {
            var existing = tbody.querySelector('tr[data-product-id="' + product.id + '"] .f-qty');
            existing.value = (parseFloat(existing.value) || 0) + 1;
            recalc();
            return;
        }
        var i = rowIndex++;
        var tr = document.createElement('tr');
        tr.setAttribute('data-product-id', product.id);
        tr.innerHTML =
            '<td>' + product.name + '<input type="hidden" name="' + options.rowNamePrefix + '[' + i + '][product_id]" value="' + product.id + '"></td>' +
            '<td><input type="number" min="1" step="1" class="f-qty" name="' + options.rowNamePrefix + '[' + i + '][quantity]" value="1"></td>' +
            '<td><input type="number" min="0" step="0.01" class="f-price" name="' + options.rowNamePrefix + '[' + i + '][unit_price]" value="' + product.price + '"></td>' +
            '<td class="f-line-total text-end">0.00</td>' +
            '<td><button type="button" class="btn btn-sm btn-danger f-remove">×</button></td>';
        tbody.appendChild(tr);

        tr.querySelector('.f-qty').addEventListener('input', recalc);
        tr.querySelector('.f-price').addEventListener('input', recalc);
        tr.querySelector('.f-remove').addEventListener('click', function () {
            tr.remove();
            recalc();
        });

        if (options.stockUrl) {
            var branchSelect = document.getElementById(options.branchSelectId);
            var branchId = branchSelect ? branchSelect.value : '';
            fetch(options.stockUrl + '?product_id=' + product.id + '&branch_id=' + branchId)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var td = tr.children[0];
                    var span = document.createElement('div');
                    span.className = 'text-muted';
                    span.style.fontSize = '11px';
                    span.textContent = (options.stockLabel || 'Stock') + ': ' + (data.quantity ?? 0);
                    td.appendChild(span);
                });
        }

        recalc();
    }

    if (searchInput) {
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var product = findProduct(searchInput.value);
                if (product) {
                    addRow(product);
                    searchInput.value = '';
                } else {
                    searchInput.classList.add('text-danger');
                    setTimeout(function () { searchInput.classList.remove('text-danger'); }, 600);
                }
            }
        });
    }

    document.getElementById(options.taxPercentId).addEventListener('input', recalc);
    document.getElementById(options.discountId).addEventListener('input', recalc);

    var form = tbody.closest('form');
    form.addEventListener('submit', function (e) {
        if (!tbody.querySelector('tr')) {
            e.preventDefault();
            alert(options.emptyItemsMessage || 'Add at least one item');
        }
    });

    return { addRow: addRow, recalc: recalc, findProduct: findProduct };
}
