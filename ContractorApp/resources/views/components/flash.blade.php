@if (session('toast') || session('error'))
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div class="toast show align-items-center border-0 {{ session('error') ? 'text-bg-danger' : 'text-bg-dark' }}" role="status" aria-live="polite" data-autohide="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi {{ session('error') ? 'bi-exclamation-triangle' : 'bi-check-circle' }} me-2"></i>{{ session('error') ?? session('toast') }}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
@endif
