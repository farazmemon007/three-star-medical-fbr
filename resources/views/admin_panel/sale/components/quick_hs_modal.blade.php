<style>
#quickHsCodeModal {
    z-index: 1065 !important;
}
#quickHsCodeModal .modal-dialog {
    z-index: 1066 !important;
}
</style>

<!-- Quick Update HS Code Modal -->
<div class="modal fade" id="quickHsCodeModal" tabindex="-1" role="dialog" aria-labelledby="quickHsCodeModalLabel" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 480px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <!-- Header -->
            <div class="modal-header text-white px-4 py-3 align-items-center" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-white bg-opacity-25 rounded-circle text-white d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-upc-scan fs-5"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold mb-0 text-white" id="quickHsCodeModalLabel">Update Product HS Code</h6>
                        <small class="text-white-50" style="font-size: 11px;">Update master product database & sale row</small>
                    </div>
                </div>
                <button type="button" class="close text-white ms-auto" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="opacity: 0.9; text-shadow: none; font-size: 1.6rem; background: transparent; border: none; outline: none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Body -->
            <div class="modal-body p-4 bg-white">
                <input type="hidden" id="quickHsProductId">

                <!-- Product Details Box -->
                <div class="p-3 mb-3 rounded-3 border" style="background-color: #f8fafc; border-color: #e2e8f0 !important;">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <span class="text-muted text-uppercase fw-semibold" style="font-size: 10px; letter-spacing: 0.5px;">Product Name</span>
                        <span class="badge badge-secondary bg-secondary" id="quickHsItemCode" style="font-size: 11px;">CODE</span>
                    </div>
                    <h6 class="fw-bold text-dark mb-2" id="quickHsProductName" style="font-size: 14px;">-</h6>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted" style="font-size: 11px;">Current HS Code:</span>
                        <span class="badge badge-light bg-light text-dark border px-2 py-1" id="quickHsCurrentBadge" style="font-size: 11px; font-weight: 600;">None</span>
                    </div>
                </div>

                <!-- HS Code Input -->
                <div class="mb-3">
                    <label for="quickHsInput" class="form-label fw-bold text-dark mb-1" style="font-size: 13px;">
                        New HS Code <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            <i class="bi bi-hash"></i>
                        </span>
                        <input type="text" id="quickHsInput" class="form-control form-control-lg border-start-0 fw-bold" placeholder="e.g. 9018.9090" autocomplete="off" style="font-size: 15px; letter-spacing: 0.5px;">
                    </div>
                    <div class="form-text text-muted" style="font-size: 11px;">
                        Enter the valid Pakistan Customs / FBR Harmonized System Tariff Code.
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer bg-light border-top px-4 py-3 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary px-3" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="btnSaveQuickHs" class="btn btn-primary px-4 fw-semibold shadow-sm">
                    <i class="bi bi-check2-circle me-1"></i> Update & Apply
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    var activeQuickHsRow = null;

    function _showModal() {
        var el = document.getElementById('quickHsCodeModal');
        if (el && el.parentNode !== document.body) {
            document.body.appendChild(el);
        }

        try {
            if (typeof $ !== 'undefined' && $.fn && typeof $.fn.modal === 'function') {
                $('#quickHsCodeModal').modal('show');
                _afterModalShown();
                return;
            }
            if (typeof jQuery !== 'undefined' && jQuery.fn && typeof jQuery.fn.modal === 'function') {
                jQuery('#quickHsCodeModal').modal('show');
                _afterModalShown();
                return;
            }
        } catch (e) {
            console.warn('jQuery modal error:', e);
        }

        var el = document.getElementById('quickHsCodeModal');
        if (el) {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                try {
                    if (typeof bootstrap.Modal.getOrCreateInstance === 'function') {
                        bootstrap.Modal.getOrCreateInstance(el).show();
                        _afterModalShown();
                        return;
                    }
                    var inst = new bootstrap.Modal(el);
                    inst.show();
                    _afterModalShown();
                    return;
                } catch (err) {
                    console.warn('Bootstrap modal error:', err);
                }
            }
            el.style.display = 'block';
            el.classList.add('show');
            document.body.classList.add('modal-open');
            _afterModalShown();
        }
    }

    function _afterModalShown() {
        setTimeout(function() {
            var input = document.getElementById('quickHsInput');
            if (input) {
                input.focus();
                input.select();
            }
        }, 150);
    }

    function _hideModal() {
        try {
            if (typeof $ !== 'undefined' && $.fn && typeof $.fn.modal === 'function') {
                $('#quickHsCodeModal').modal('hide');
                return;
            }
            if (typeof jQuery !== 'undefined' && jQuery.fn && typeof jQuery.fn.modal === 'function') {
                jQuery('#quickHsCodeModal').modal('hide');
                return;
            }
        } catch (e) {
            console.warn('jQuery modal hide error:', e);
        }

        var el = document.getElementById('quickHsCodeModal');
        if (el) {
            el.style.display = 'none';
            el.classList.remove('show');
            document.body.classList.remove('modal-open');
            var backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(function(b) { b.remove(); });
        }
    }

    window.openQuickHsModalForBtn = function(btn) {
        try {
            var row = btn ? btn.closest('tr') : null;
            if (!row) {
                console.warn('Quick HS: row not found');
                return;
            }

            var productIdInput = row.querySelector('.item-id');
            var productId = productIdInput ? productIdInput.value : '';

            var itemCodeInput = row.querySelector('.item-code');
            var itemCode = itemCodeInput ? itemCodeInput.value : '-';

            var itemNameInput = row.querySelector('.item-name');
            var productBtn = row.querySelector('.product-select-btn');
            var itemName = (itemNameInput && itemNameInput.value) ? itemNameInput.value : (productBtn ? productBtn.textContent.trim() : 'Selected Product');

            var hsInput = row.querySelector('.hs-code, .item-hs-code');
            var currentHs = hsInput ? hsInput.value.trim() : '';

            if (!productId) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Product Not Selected',
                        text: 'Please select a product for this row first before setting its HS Code.',
                        confirmButtonColor: '#1e3a8a'
                    });
                } else {
                    alert('Please select a product for this row first before setting its HS Code.');
                }
                return;
            }

            activeQuickHsRow = row;

            var pIdEl = document.getElementById('quickHsProductId');
            if (pIdEl) pIdEl.value = productId;

            var codeEl = document.getElementById('quickHsItemCode');
            if (codeEl) codeEl.textContent = itemCode || '-';

            var nameEl = document.getElementById('quickHsProductName');
            if (nameEl) nameEl.textContent = itemName || 'Selected Product';

            var badgeEl = document.getElementById('quickHsCurrentBadge');
            if (badgeEl) badgeEl.textContent = currentHs || 'None';

            var inputEl = document.getElementById('quickHsInput');
            if (inputEl) inputEl.value = currentHs;

            _showModal();
        } catch (err) {
            console.error('Error opening Quick HS modal:', err);
            alert('Error opening modal: ' + err.message);
        }
    };

    // Native event listener on document
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-quick-hs');
        if (btn) {
            e.preventDefault();
            window.openQuickHsModalForBtn(btn);
            return;
        }

        var saveBtn = e.target.closest('#btnSaveQuickHs');
        if (saveBtn) {
            e.preventDefault();
            _submitHsUpdate();
            return;
        }
    });

    // Enter key submit on input
    document.addEventListener('keydown', function(e) {
        if (e.target && e.target.id === 'quickHsInput' && e.key === 'Enter') {
            e.preventDefault();
            _submitHsUpdate();
        }
    });

    function _submitHsUpdate() {
        var productId = document.getElementById('quickHsProductId').value;
        var hsCode = document.getElementById('quickHsInput').value.trim();
        var saveBtn = document.getElementById('btnSaveQuickHs');

        if (!productId) return;

        var origHtml = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Updating...';

        fetch("{{ route('product.quick_update_hs_code') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}",
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                product_id: productId,
                hs_code: hsCode
            })
        })
        .then(function(res) {
            return res.json().then(function(data) {
                return { ok: res.ok, status: res.status, data: data };
            });
        })
        .then(function(result) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = origHtml;

            if (result.ok && result.data.status === 'success') {
                var updatedCode = result.data.hs_code || '';

                // 1. Update the row clicked
                if (activeQuickHsRow) {
                    var targetInput = activeQuickHsRow.querySelector('.hs-code, .item-hs-code');
                    if (targetInput) {
                        targetInput.value = updatedCode;
                    }
                }

                // 2. Update all other rows with the same product_id on the page
                document.querySelectorAll('input.item-id').forEach(function(inp) {
                    if (inp.value == productId) {
                        var tr = inp.closest('tr');
                        if (tr) {
                            var trHs = tr.querySelector('.hs-code, .item-hs-code');
                            if (trHs) trHs.value = updatedCode;
                        }
                    }
                });

                // 3. Hide modal
                _hideModal();

                // 4. Toast notification
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: result.data.message || 'HS Code updated successfully!',
                        showConfirmButton: false,
                        timer: 2500
                    });
                }
            } else {
                var errMsg = (result.data && result.data.message) ? result.data.message : 'Failed to update HS Code';
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', errMsg, 'error');
                } else {
                    alert(errMsg);
                }
            }
        })
        .catch(function(err) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = origHtml;
            console.error('HS Code Update Error:', err);
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'Network or server error while updating HS Code.', 'error');
            } else {
                alert('Network or server error while updating HS Code.');
            }
        });
    }

    // Modal close buttons (cancel / cross) handling
    document.addEventListener('click', function(e) {
        if (e.target.closest('[data-bs-dismiss="modal"]') || e.target.closest('[data-dismiss="modal"]')) {
            var modal = e.target.closest('#quickHsCodeModal');
            if (modal) {
                _hideModal();
            }
        }
    });

})();
</script>
