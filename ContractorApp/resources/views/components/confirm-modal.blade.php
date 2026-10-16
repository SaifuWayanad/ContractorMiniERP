{{--
    One shared confirmation dialog. Any form with data-confirm="message" opens it before submitting.
    Optional: data-confirm-title, data-confirm-button (button text), data-confirm-variant (danger|warning|primary).
--}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center px-4 pt-4 pb-2">
                <div class="confirm-modal-icon mb-3" data-confirm-icon>
                    <i class="bi bi-trash3"></i>
                </div>
                <h2 class="fs-6 fw-bold mb-1" id="confirmModalTitle">Are you sure?</h2>
                <p class="text-muted small mb-0" data-confirm-message>This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0 justify-content-center gap-2 px-4 pb-4 pt-2">
                <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm px-4" data-confirm-accept>Delete</button>
            </div>
        </div>
    </div>
</div>

<style>
.confirm-modal-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: rgba(220, 53, 69, .1);
    color: #dc3545;
    font-size: 1.4rem;
}
.confirm-modal-icon.variant-warning {
    background: rgba(255, 193, 7, .12);
    color: #b45309;
}
.confirm-modal-icon.variant-success {
    background: rgba(34, 197, 94, .12);
    color: #16a34a;
}
.confirm-modal-icon.variant-primary {
    background: rgba(59, 130, 246, .12);
    color: #2563eb;
}
</style>
